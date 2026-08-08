<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Tenant: get own dashboard data.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (!$tenant) {
            // User exists but no tenant profile — check onboarding application status
            $user = $request->user();
            $application = \App\Models\OnboardingInvitation::where('candidate_mobile', $user->mobile)
                ->where('status', 'submitted')
                ->orderByDesc('created_at')
                ->first();

            return response()->json([
                'pending_approval' => true,
                'application_status' => $application ? $application->status : 'submitted',
                'application_date' => $application?->submitted_at,
                'candidate_name' => $application?->candidate_name ?? $user->name,
            ]);
        }

        $tenant->load([
            'pgLocation',
            'currentBedAllocation.bed.room',
        ]);

        // Current month rent
        $currentRent = $tenant->monthlyRents()
            ->with('paymentSubmissions')
            ->orderByDesc('billing_month')
            ->first();

        // Pending electricity
        $pendingElectricity = $tenant->electricityBillAllocations()
            ->where('status', 'unpaid')
            ->sum('amount');

        // First payment status (payments without monthly_rent_id)
        $firstPayments = $tenant->paymentSubmissions()
            ->whereNull('monthly_rent_id')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'tenant' => $tenant,
            'current_rent' => $currentRent,
            'pending_electricity' => $pendingElectricity,
            'roommates' => $tenant->roommates,
            'first_payments' => $firstPayments,
        ]);
    }

    /**
     * Tenant: get own profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant->load([
            'pgLocation',
            'currentBedAllocation.bed.room',
            'documents',
        ]);

        return response()->json(['profile' => $tenant]);
    }

    /**
     * Tenant: get own electricity bill allocations.
     */
    public function electricity(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $allocations = $tenant->electricityBillAllocations()
            ->with('electricityBill')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['allocations' => $allocations]);
    }

    /**
     * Tenant: change own password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        $request->user()->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }
}
