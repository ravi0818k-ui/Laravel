<?php

namespace App\Services;

use App\Models\Bed;
use App\Models\ElectricityBill;
use App\Models\ElectricityBillAllocation;
use App\Models\MonthlyRent;
use App\Models\Room;
use App\Models\TenantBedAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ElectricityBillingService
{
    /**
     * Create electricity bill for a room and split among active tenants.
     * Also auto-updates MonthlyRent.additional_charge for each tenant.
     */
    public function createBill(
        Room $room,
        Carbon $billingMonth,
        float $totalUnits,
        float $ratePerUnit,
        ?string $notes = null,
        ?string $previousMeterImage = null,
        ?string $currentMeterImage = null,
        ?float $previousReading = null,
        ?float $currentReading = null
    ): ElectricityBill {
        return DB::transaction(function () use ($room, $billingMonth, $totalUnits, $ratePerUnit, $notes, $previousMeterImage, $currentMeterImage, $previousReading, $currentReading) {
            $month = $billingMonth->startOfMonth()->toDateString();
            $totalAmount = $totalUnits * $ratePerUnit;

            // Find active tenants in this room at time of billing
            $activeTenantIds = TenantBedAllocation::where('is_current', true)
                ->whereHas('bed', fn($q) => $q->where('room_id', $room->id))
                ->pluck('tenant_id');

            $activeTenantCount = $activeTenantIds->count();

            if ($activeTenantCount === 0) {
                throw new \Exception('No active tenants in this room to split the bill.');
            }

            $perTenantAmount = round($totalAmount / $activeTenantCount, 2);

            $bill = ElectricityBill::create([
                'room_id' => $room->id,
                'billing_month' => $month,
                'total_units' => $totalUnits,
                'rate_per_unit' => $ratePerUnit,
                'total_amount' => $totalAmount,
                'active_tenants_count' => $activeTenantCount,
                'per_tenant_amount' => $perTenantAmount,
                'entered_by' => Auth::id(),
                'notes' => $notes,
                'previous_meter_image' => $previousMeterImage,
                'current_meter_image' => $currentMeterImage,
                'previous_reading' => $previousReading,
                'current_reading' => $currentReading,
            ]);

            // Create allocations for each active tenant
            foreach ($activeTenantIds as $tenantId) {
                ElectricityBillAllocation::create([
                    'electricity_bill_id' => $bill->id,
                    'tenant_id' => $tenantId,
                    'amount' => $perTenantAmount,
                ]);

                // Auto-attach electricity to monthly rent as additional_charge
                $this->updateMonthlyRentElectricity($tenantId, $month);
            }

            return $bill;
        });
    }

    /**
     * Recalculate and update a tenant's monthly rent additional_charge
     * based on their total unpaid electricity allocations for that month.
     */
    public function updateMonthlyRentElectricity(int $tenantId, string $billingMonth): void
    {
        $monthlyRent = MonthlyRent::where('tenant_id', $tenantId)
            ->where('billing_month', $billingMonth)
            ->first();

        if (!$monthlyRent) return;

        // Sum all electricity allocations for this tenant in this month
        $totalElec = ElectricityBillAllocation::where('tenant_id', $tenantId)
            ->whereHas('electricityBill', function ($q) use ($billingMonth) {
                $q->where('billing_month', $billingMonth);
            })
            ->sum('amount');

        $monthlyRent->update([
            'additional_charge' => $totalElec,
            'total_amount' => $monthlyRent->base_rent - ($monthlyRent->discount ?? 0) + $totalElec,
            'due_amount' => $monthlyRent->base_rent - ($monthlyRent->discount ?? 0) + $totalElec - $monthlyRent->paid_amount,
        ]);
    }
}
