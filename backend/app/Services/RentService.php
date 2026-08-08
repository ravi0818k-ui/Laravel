<?php

namespace App\Services;

use App\Models\MonthlyRent;
use App\Models\PaymentSubmission;
use App\Models\Tenant;
use App\Models\TenantRentHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class RentService
{
    /**
     * Generate monthly rent entries for all active tenants.
     */
    public function generateMonthlyRents(Carbon $billingMonth, ?int $generatedBy = null): int
    {
        $month = $billingMonth->startOfMonth()->toDateString();
        $count = 0;

        Tenant::active()->chunk(100, function ($tenants) use ($month, $generatedBy, &$count) {
            foreach ($tenants as $tenant) {
                MonthlyRent::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'billing_month' => $month],
                    [
                        'base_rent' => $tenant->current_rent,
                        'discount' => 0,
                        'additional_charge' => 0,
                        'total_amount' => $tenant->current_rent,
                        'paid_amount' => 0,
                        'due_amount' => $tenant->current_rent,
                        'status' => 'unpaid',
                        'due_date' => Carbon::parse($month)->addDays(10),
                        'generated_by' => $generatedBy,
                    ]
                );
                $count++;
            }
        });

        return $count;
    }

    /**
     * Change a tenant's rent with history tracking.
     */
    public function changeRent(Tenant $tenant, float $newRent, Carbon $effectiveDate, ?string $reason = null): TenantRentHistory
    {
        $history = TenantRentHistory::create([
            'tenant_id' => $tenant->id,
            'previous_rent' => $tenant->current_rent,
            'new_rent' => $newRent,
            'effective_date' => $effectiveDate,
            'reason' => $reason,
            'changed_by' => Auth::id(),
        ]);

        $tenant->update(['current_rent' => $newRent]);

        return $history;
    }

    /**
     * Process a verified payment and update the monthly rent ledger.
     */
    public function processVerifiedPayment(PaymentSubmission $payment, float $verifiedAmount, int $verifiedBy): void
    {
        $payment->update([
            'verified_amount' => $verifiedAmount,
            'status' => 'verified',
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
        ]);

        if ($payment->monthlyRent) {
            $payment->monthlyRent->recalculateDue();

            // If the monthly rent is now fully paid, mark all electricity allocations for that month as paid
            $monthlyRent = $payment->monthlyRent->fresh();
            if ($monthlyRent->status === 'paid' && $payment->tenant) {
                $payment->tenant->electricityBillAllocations()
                    ->where('status', 'unpaid')
                    ->whereHas('electricityBill', function ($q) use ($monthlyRent) {
                        $q->where('billing_month', $monthlyRent->billing_month);
                    })
                    ->update(['status' => 'paid', 'paid_at' => now()]);
            }

            // Also handle partial: if verified amount covers base rent + some electricity
            if ($monthlyRent->status !== 'paid' && $payment->tenant) {
                $baseRent = $monthlyRent->base_rent ?? 0;
                $totalVerified = $monthlyRent->paymentSubmissions()->where('status', 'verified')->sum('verified_amount');
                $paidExtra = $totalVerified - $baseRent;

                if ($paidExtra > 0) {
                    $unpaidElec = $payment->tenant->electricityBillAllocations()
                        ->where('status', 'unpaid')
                        ->whereHas('electricityBill', fn($q) => $q->where('billing_month', $monthlyRent->billing_month))
                        ->orderBy('created_at')
                        ->get();

                    foreach ($unpaidElec as $alloc) {
                        if ($paidExtra >= $alloc->amount) {
                            $alloc->update(['status' => 'paid', 'paid_at' => now()]);
                            $paidExtra -= $alloc->amount;
                        }
                        if ($paidExtra <= 0) break;
                    }
                }
            }
        }

        // For first payments (no monthly rent)
        if (!$payment->monthlyRent && $payment->tenant) {
            $tenant = $payment->tenant;
            $rentAmount = $tenant->current_rent ?? 0;
            $paidExtra = $verifiedAmount - $rentAmount;

            if ($paidExtra > 0) {
                $unpaidElec = $tenant->electricityBillAllocations()
                    ->where('status', 'unpaid')
                    ->orderBy('created_at')
                    ->get();

                foreach ($unpaidElec as $alloc) {
                    if ($paidExtra >= $alloc->amount) {
                        $alloc->update(['status' => 'paid', 'paid_at' => now()]);
                        $paidExtra -= $alloc->amount;
                    }
                    if ($paidExtra <= 0) break;
                }
            }
        }
    }
}
