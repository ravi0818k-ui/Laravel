<?php

namespace App\Services;

use App\Models\PgLocation;
use Illuminate\Support\Facades\DB;

class TenantIdService
{
    /**
     * Generate the next sequential tenant ID for a given PG location.
     * Uses DB-level locking to prevent race conditions.
     *
     * @param PgLocation $pgLocation
     * @return string e.g. "TSN0001"
     */
    public function generateNextId(PgLocation $pgLocation): string
    {
        return DB::transaction(function () use ($pgLocation) {
            // Lock the row for update to prevent concurrent duplicates
            $location = PgLocation::where('id', $pgLocation->id)
                ->lockForUpdate()
                ->first();

            $nextNumber = $location->tenant_id_counter + 1;
            $location->tenant_id_counter = $nextNumber;
            $location->save();

            return $location->tenant_id_prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }
}
