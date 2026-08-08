<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\RentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTenantController extends Controller
{
    /**
     * Admin: list tenants (scoped to assigned PGs).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Tenant::with(['user', 'pgLocation', 'currentBedAllocation.bed.room', 'electricityBillAllocations']);

        if ($user->isAdmin()) {
            $assignedPgIds = $user->assignedPgLocations()->pluck('pg_locations.id');
            $query->whereIn('pg_location_id', $assignedPgIds);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('pg_location_id')) {
            $query->where('pg_location_id', $request->pg_location_id);
        }

        $tenants = $query->orderByDesc('created_at')->get();

        // Attach current month payment status for each tenant
        $currentMonth = now()->startOfMonth()->toDateString();
        $tenants->each(function ($tenant) use ($currentMonth) {
            $latestRent = $tenant->monthlyRents()
                ->with('paymentSubmissions')
                ->orderByDesc('billing_month')
                ->first();

            $tenant->setAttribute('latest_rent', $latestRent);
        });

        return response()->json(['tenants' => $tenants]);
    }

    /**
     * Admin: view single tenant details.
     */
    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load([
            'user',
            'pgLocation',
            'currentBedAllocation.bed.room',
            'bedAllocations.bed.room',
            'monthlyRents.paymentSubmissions',
            'paymentSubmissions',
            'electricityBillAllocations.electricityBill',
            'documents',
            'rentHistory',
        ]);

        return response()->json(['tenant' => $tenant]);
    }

    /**
     * Admin: change tenant rent.
     */
    public function changeRent(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'new_rent' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
            'reason' => 'nullable|string|max:255',
        ]);

        $rentService = app(RentService::class);
        $history = $rentService->changeRent(
            $tenant,
            $request->new_rent,
            Carbon::parse($request->effective_date),
            $request->reason
        );

        return response()->json([
            'message' => 'Rent updated.',
            'rent_history' => $history,
        ]);
    }

    /**
     * Admin: change tenant's room/bed assignment.
     */
    public function changeBed(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'bed_id' => 'required|exists:beds,id',
        ]);

        $bed = \App\Models\Bed::findOrFail($request->bed_id);

        if ($bed->status !== 'available') {
            return response()->json(['message' => 'This bed is not available.'], 422);
        }

        $roomAllocationService = app(\App\Services\RoomAllocationService::class);
        $roomAllocationService->allocateBed($tenant, $bed);

        return response()->json([
            'message' => 'Tenant moved to new room/bed successfully.',
            'new_bed' => $bed->load('room'),
        ]);
    }

    /**
     * Admin: reset tenant's password.
     */
    public function resetPassword(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        $tenant->user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => "Password reset successfully for {$tenant->user->name}.",
        ]);
    }

    /**
     * Admin: impersonate a tenant (get a token to view their dashboard).
     */
    public function impersonate(Tenant $tenant): JsonResponse
    {
        $user = $tenant->user;

        if (!$user) {
            return response()->json(['message' => 'Tenant user account not found.'], 404);
        }

        // Create a temporary token for the tenant
        $token = $user->createToken('impersonate', ['*'], now()->addHour())->plainTextToken;

        return response()->json([
            'message' => "Impersonating {$user->name}",
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Admin: offboard a tenant (vacate bed, mark offboarded, keep records).
     */
    public function offboard(Tenant $tenant): JsonResponse
    {
        if ($tenant->status !== 'active') {
            return response()->json(['message' => 'Tenant is not active.'], 422);
        }

        // Vacate bed
        $currentAllocation = $tenant->currentBedAllocation;
        if ($currentAllocation) {
            $currentAllocation->update(['is_current' => false, 'vacated_at' => now()]);
            $currentAllocation->bed->update(['status' => 'available']);
        }

        // Mark offboarded but keep account active for viewing history
        $tenant->update([
            'status' => 'offboarded',
            'offboarded_at' => now(),
        ]);

        // Deactivate login
        $tenant->user->update(['is_active' => false]);

        return response()->json([
            'message' => "{$tenant->user->name} has been offboarded. Bed vacated.",
        ]);
    }

    /**
     * Admin: soft delete a tenant (move to trash).
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        // Deactivate the user account
        $tenant->user->update(['is_active' => false]);

        // Vacate bed if assigned
        $currentAllocation = $tenant->currentBedAllocation;
        if ($currentAllocation) {
            $currentAllocation->update(['is_current' => false, 'vacated_at' => now()]);
            $currentAllocation->bed->update(['status' => 'available']);
        }

        $tenant->update(['status' => 'offboarded']);
        $tenant->delete(); // soft delete

        return response()->json([
            'message' => "Tenant {$tenant->tenant_id} moved to trash.",
        ]);
    }

    /**
     * Admin: list trashed (soft-deleted) tenants.
     */
    public function trash(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Tenant::onlyTrashed()->with(['user', 'pgLocation']);

        if ($user->isAdmin()) {
            $assignedPgIds = $user->assignedPgLocations()->pluck('pg_locations.id');
            $query->whereIn('pg_location_id', $assignedPgIds);
        }

        $tenants = $query->orderByDesc('deleted_at')->get();

        return response()->json(['tenants' => $tenants]);
    }

    /**
     * Admin: restore a soft-deleted tenant from trash.
     */
    public function restore(int $id): JsonResponse
    {
        $tenant = Tenant::onlyTrashed()->findOrFail($id);

        $tenant->restore();
        $tenant->update(['status' => 'active']);
        $tenant->user->update(['is_active' => true]);

        return response()->json([
            'message' => "Tenant {$tenant->tenant_id} restored successfully.",
        ]);
    }

    /**
     * Admin: permanently delete a tenant from trash.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $tenant = Tenant::onlyTrashed()->findOrFail($id);

        // Delete related records
        $tenant->monthlyRents()->delete();
        $tenant->paymentSubmissions()->delete();
        $tenant->electricityBillAllocations()->delete();
        $tenant->bedAllocations()->delete();
        $tenant->documents()->delete();
        $tenant->rentHistory()->delete();

        // Delete user account
        $user = $tenant->user;

        // Force delete tenant
        $tenant->forceDelete();

        // Delete user and their tokens
        if ($user) {
            $user->tokens()->delete();
            $user->delete();
        }

        return response()->json([
            'message' => 'Tenant permanently deleted.',
        ]);
    }
}
