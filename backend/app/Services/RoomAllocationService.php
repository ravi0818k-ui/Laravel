<?php

namespace App\Services;

use App\Models\Bed;
use App\Models\Tenant;
use App\Models\TenantBedAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RoomAllocationService
{
    /**
     * Allocate a bed to a tenant (handling previous allocation).
     */
    public function allocateBed(Tenant $tenant, Bed $bed, ?Carbon $allocatedAt = null): TenantBedAllocation
    {
        return DB::transaction(function () use ($tenant, $bed, $allocatedAt) {
            $date = $allocatedAt ?? Carbon::today();

            // Vacate current bed if any
            $current = $tenant->currentBedAllocation;
            if ($current) {
                $current->update([
                    'is_current' => false,
                    'vacated_at' => $date,
                ]);
                $current->bed->update(['status' => 'available']);
            }

            // Create new allocation
            $allocation = TenantBedAllocation::create([
                'tenant_id' => $tenant->id,
                'bed_id' => $bed->id,
                'allocated_at' => $date,
                'is_current' => true,
            ]);

            // Mark bed as occupied
            $bed->update(['status' => 'occupied']);

            return $allocation;
        });
    }

    /**
     * Vacate a bed (during offboarding).
     */
    public function vacateBed(Tenant $tenant, ?Carbon $vacatedAt = null): void
    {
        $date = $vacatedAt ?? Carbon::today();

        $current = $tenant->currentBedAllocation;
        if ($current) {
            $current->update([
                'is_current' => false,
                'vacated_at' => $date,
            ]);
            $current->bed->update(['status' => 'available']);
        }
    }
}
