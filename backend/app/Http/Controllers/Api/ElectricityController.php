<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ElectricityBill;
use App\Models\Room;
use App\Services\ElectricityBillingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ElectricityController extends Controller
{
    protected ElectricityBillingService $billingService;

    public function __construct(ElectricityBillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Admin: create electricity bill for a room.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'billing_month' => 'required|date_format:Y-m',
            'total_units' => 'required|numeric|min:0.1',
            'rate_per_unit' => 'required|numeric|min:0.1',
            'previous_reading' => 'nullable|numeric|min:0',
            'current_reading' => 'nullable|numeric|min:0',
            'previous_meter_image' => 'nullable|image|max:5120',
            'current_meter_image' => 'nullable|image|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        $room = Room::findOrFail($request->room_id);
        $month = Carbon::createFromFormat('Y-m', $request->billing_month);

        // Check if bill already exists for this room and month
        $existingBill = ElectricityBill::where('room_id', $room->id)
            ->whereYear('billing_month', $month->year)
            ->whereMonth('billing_month', $month->month)
            ->where('total_units', '>', 0) // exclude individual adjustments
            ->first();

        if ($existingBill) {
            return response()->json([
                'message' => "An electricity bill for this room already exists for {$request->billing_month}.",
            ], 422);
        }

        try {
            // Store meter images if provided
            $prevImagePath = null;
            $currImagePath = null;

            if ($request->hasFile('previous_meter_image')) {
                $prevImagePath = $request->file('previous_meter_image')->store("electricity/room_{$room->id}", 'local');
            }
            if ($request->hasFile('current_meter_image')) {
                $currImagePath = $request->file('current_meter_image')->store("electricity/room_{$room->id}", 'local');
            }

            $bill = $this->billingService->createBill(
                $room,
                $month,
                $request->total_units,
                $request->rate_per_unit,
                $request->notes,
                $prevImagePath,
                $currImagePath,
                $request->previous_reading,
                $request->current_reading
            );

            return response()->json([
                'message' => "Electricity bill created. ₹{$bill->total_amount} split among {$bill->active_tenants_count} tenants (₹{$bill->per_tenant_amount} each).",
                'bill' => $bill->load('allocations.tenant'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Admin: update an existing electricity bill (edit readings/rate, recalculate split).
     */
    public function update(Request $request, ElectricityBill $bill): JsonResponse
    {
        $request->validate([
            'total_units' => 'required|numeric|min:0.1',
            'rate_per_unit' => 'required|numeric|min:0.1',
            'previous_reading' => 'nullable|numeric|min:0',
            'current_reading' => 'nullable|numeric|min:0',
            'previous_meter_image' => 'nullable|image|max:5120',
            'current_meter_image' => 'nullable|image|max:5120',
        ]);

        $totalAmount = $request->total_units * $request->rate_per_unit;
        $activeTenantCount = $bill->active_tenants_count ?: 1;
        $perTenantAmount = round($totalAmount / $activeTenantCount, 2);

        // Update meter images if provided
        if ($request->hasFile('previous_meter_image')) {
            if ($bill->previous_meter_image) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($bill->previous_meter_image);
            }
            $bill->previous_meter_image = $request->file('previous_meter_image')->store("electricity/room_{$bill->room_id}", 'local');
        }
        if ($request->hasFile('current_meter_image')) {
            if ($bill->current_meter_image) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($bill->current_meter_image);
            }
            $bill->current_meter_image = $request->file('current_meter_image')->store("electricity/room_{$bill->room_id}", 'local');
        }

        $bill->update([
            'total_units' => $request->total_units,
            'rate_per_unit' => $request->rate_per_unit,
            'total_amount' => $totalAmount,
            'per_tenant_amount' => $perTenantAmount,
            'previous_reading' => $request->previous_reading,
            'current_reading' => $request->current_reading,
        ]);

        // Update all allocations with new per-tenant amount
        $bill->allocations()->update(['amount' => $perTenantAmount]);

        return response()->json([
            'message' => "Bill updated. ₹{$totalAmount} split as ₹{$perTenantAmount} per tenant.",
            'bill' => $bill->fresh()->load('allocations.tenant'),
        ]);
    }

    /**
     * Admin: list electricity bills.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ElectricityBill::with(['room.pgLocation', 'allocations.tenant']);

        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->has('pg_location_id')) {
            $query->whereHas('room', fn($q) => $q->where('pg_location_id', $request->pg_location_id));
        }

        $bills = $query->orderByDesc('billing_month')->get();

        return response()->json(['bills' => $bills]);
    }

    /**
     * View meter image (previous or current).
     */
    public function viewMeterImage(ElectricityBill $bill, string $type)
    {
        $path = $type === 'previous' ? $bill->previous_meter_image : $bill->current_meter_image;

        if (!$path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'Meter image not found.'], 404);
        }

        $file = \Illuminate\Support\Facades\Storage::disk('local')->get($path);
        $mimeType = \Illuminate\Support\Facades\Storage::disk('local')->mimeType($path);

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'private, max-age=3600');
    }

    /**
     * Admin: delete an electricity bill and its allocations.
     */
    public function destroy(ElectricityBill $bill): JsonResponse
    {
        $bill->allocations()->delete();
        $bill->delete();

        return response()->json(['message' => 'Electricity bill deleted.']);
    }

    /**
     * Admin: mark a specific tenant's electricity allocation as paid.
     */
    public function markAllocationPaid(\App\Models\ElectricityBillAllocation $allocation): JsonResponse
    {
        if ($allocation->status === 'paid') {
            return response()->json(['message' => 'Already marked as paid.'], 422);
        }

        $allocation->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return response()->json([
            'message' => "Electricity bill marked as paid for tenant.",
            'allocation' => $allocation->fresh()->load(['tenant', 'electricityBill']),
        ]);
    }

    /**
     * Admin: adjust electricity bill for a specific tenant.
     * Creates or updates an individual electricity allocation.
     */
    public function adjustForTenant(Request $request, \App\Models\Tenant $tenant): JsonResponse
    {
        $request->validate([
            'billing_month' => 'required|date_format:Y-m',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $month = Carbon::createFromFormat('Y-m', $request->billing_month);

        // Find existing allocation for this tenant/month or create new one
        $allocation = \App\Models\ElectricityBillAllocation::where('tenant_id', $tenant->id)
            ->whereHas('electricityBill', function ($q) use ($month) {
                $q->whereYear('billing_month', $month->year)
                  ->whereMonth('billing_month', $month->month);
            })
            ->first();

        if ($allocation) {
            // Update existing allocation amount
            $allocation->update(['amount' => $request->amount]);

            return response()->json([
                'message' => "Electricity allocation updated to ₹{$request->amount} for {$tenant->tenant_id}.",
                'allocation' => $allocation->fresh()->load('electricityBill'),
            ]);
        }

        // No existing bill/allocation — find the room's bill or create a standalone allocation
        $currentBed = $tenant->currentBedAllocation?->bed;
        if (!$currentBed) {
            return response()->json(['message' => 'Tenant has no room assignment.'], 422);
        }

        $bill = ElectricityBill::where('room_id', $currentBed->room_id)
            ->whereYear('billing_month', $month->year)
            ->whereMonth('billing_month', $month->month)
            ->first();

        if (!$bill) {
            // Create a minimal bill record for individual adjustment
            $bill = ElectricityBill::create([
                'room_id' => $currentBed->room_id,
                'billing_month' => $month->startOfMonth(),
                'total_units' => 0,
                'rate_per_unit' => 0,
                'total_amount' => $request->amount,
                'active_tenants_count' => 1,
                'per_tenant_amount' => $request->amount,
                'entered_by' => $request->user()->id,
                'notes' => $request->notes ?? 'Individual adjustment',
            ]);
        }

        $allocation = \App\Models\ElectricityBillAllocation::create([
            'electricity_bill_id' => $bill->id,
            'tenant_id' => $tenant->id,
            'amount' => $request->amount,
            'status' => 'unpaid',
        ]);

        return response()->json([
            'message' => "Electricity bill of ₹{$request->amount} assigned to {$tenant->tenant_id}.",
            'allocation' => $allocation->load('electricityBill'),
        ], 201);
    }
}
