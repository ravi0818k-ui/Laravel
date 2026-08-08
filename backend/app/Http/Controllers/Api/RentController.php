<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlyRent;
use App\Services\RentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentController extends Controller
{
    protected RentService $rentService;

    public function __construct(RentService $rentService)
    {
        $this->rentService = $rentService;
    }

    /**
     * Tenant: list own rent history.
     */
    public function tenantIndex(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $rents = $tenant->monthlyRents()
            ->with('paymentSubmissions')
            ->orderByDesc('billing_month')
            ->get();

        return response()->json(['rents' => $rents]);
    }

    /**
     * Admin: generate monthly rents for all active tenants.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'billing_month' => 'required|date_format:Y-m',
        ]);

        $month = Carbon::createFromFormat('Y-m', $request->billing_month);
        $count = $this->rentService->generateMonthlyRents($month, $request->user()->id);

        return response()->json([
            'message' => "Monthly rent generated for {$count} tenants.",
        ]);
    }

    /**
     * Admin: generate rent for a single tenant.
     */
    public function generateIndividual(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'billing_month' => 'required|date_format:Y-m',
        ]);

        $tenant = \App\Models\Tenant::findOrFail($request->tenant_id);
        $month = Carbon::createFromFormat('Y-m', $request->billing_month)->startOfMonth()->toDateString();

        // Check if already exists
        $existing = MonthlyRent::where('tenant_id', $tenant->id)
            ->where('billing_month', $month)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => "Rent for {$tenant->tenant_id} already exists for this month.",
            ], 422);
        }

        MonthlyRent::create([
            'tenant_id' => $tenant->id,
            'billing_month' => $month,
            'base_rent' => $tenant->current_rent,
            'discount' => 0,
            'additional_charge' => 0,
            'total_amount' => $tenant->current_rent,
            'paid_amount' => 0,
            'due_amount' => $tenant->current_rent,
            'status' => 'unpaid',
            'due_date' => Carbon::parse($month)->addDays(10),
            'generated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => "Rent generated for {$tenant->user->name} ({$tenant->tenant_id}) — ₹{$tenant->current_rent}",
        ]);
    }
}
