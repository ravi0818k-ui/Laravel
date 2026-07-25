<?php

namespace App\Services;

use App\Models\OffboardingRequest;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class OffboardingService
{
    protected RoomAllocationService $roomAllocationService;

    public function __construct(RoomAllocationService $roomAllocationService)
    {
        $this->roomAllocationService = $roomAllocationService;
    }

    /**
     * Complete offboarding: vacate bed, mark tenant offboarded.
     */
    public function complete(OffboardingRequest $request, int $completedBy): void
    {
        DB::transaction(function () use ($request, $completedBy) {
            $tenant = $request->tenant;

            // Vacate the bed
            $this->roomAllocationService->vacateBed($tenant, $request->actual_leaving_date ?? now());

            // Mark tenant offboarded
            $tenant->update([
                'status' => 'offboarded',
                'offboarded_at' => now(),
            ]);

            // Deactivate user login
            $tenant->user->update(['is_active' => false]);

            // Update offboarding request
            $request->update([
                'status' => 'completed',
                'completed_by' => $completedBy,
                'completed_at' => now(),
            ]);
        });
    }
}
