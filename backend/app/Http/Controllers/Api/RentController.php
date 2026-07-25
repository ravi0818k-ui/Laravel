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
}
