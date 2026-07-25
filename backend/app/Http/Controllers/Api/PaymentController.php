<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentSubmission;
use App\Services\RentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    protected RentService $rentService;

    public function __construct(RentService $rentService)
    {
        $this->rentService = $rentService;
    }

    /**
     * Tenant: submit a payment with screenshot.
     */
    public function tenantSubmit(Request $request): JsonResponse
    {
        $request->validate([
            'monthly_rent_id' => 'nullable|exists:monthly_rents,id',
            'claimed_amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:upi,phonepe,gpay,paytm,bank_transfer,cash,other',
            'transaction_reference' => 'nullable|string|max:255',
            'screenshot' => 'required|image|max:5120', // 5MB
            'payment_date' => 'nullable|date',
        ]);

        $tenant = $request->user()->tenant;

        // Store screenshot in private storage
        $path = $request->file('screenshot')->store(
            "payments/{$tenant->tenant_id}",
            'local'
        );

        $payment = PaymentSubmission::create([
            'monthly_rent_id' => $request->monthly_rent_id ?: null,
            'tenant_id' => $tenant->id,
            'claimed_amount' => $request->claimed_amount,
            'payment_method' => $request->payment_method,
            'transaction_reference' => $request->transaction_reference,
            'screenshot_path' => $path,
            'status' => 'verification_pending',
            'payment_date' => $request->payment_date ?? now()->toDateString(),
        ]);

        return response()->json([
            'message' => 'Payment submitted for verification.',
            'payment' => $payment,
        ], 201);
    }

    /**
     * Admin: list pending payments for verification.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = PaymentSubmission::with(['tenant.user', 'tenant.pgLocation', 'monthlyRent']);

        if ($user->isAdmin()) {
            $assignedPgIds = $user->assignedPgLocations()->pluck('pg_locations.id');
            $query->whereHas('tenant', fn($q) => $q->whereIn('pg_location_id', $assignedPgIds));
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->pendingVerification();
        }

        $payments = $query->orderByDesc('created_at')->get();

        return response()->json(['payments' => $payments]);
    }

    /**
     * Admin: verify a payment submission.
     */
    public function verify(Request $request, PaymentSubmission $payment): JsonResponse
    {
        $request->validate([
            'verified_amount' => 'required|numeric|min:0',
        ]);

        $this->rentService->processVerifiedPayment(
            $payment,
            $request->verified_amount,
            $request->user()->id
        );

        return response()->json([
            'message' => 'Payment verified successfully.',
            'payment' => $payment->fresh()->load('monthlyRent'),
        ]);
    }

    /**
     * Admin: reject a payment submission.
     */
    public function reject(Request $request, PaymentSubmission $payment): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $payment->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payment rejected.',
            'payment' => $payment->fresh(),
        ]);
    }

    /**
     * Admin: view payment screenshot image.
     */
    public function viewScreenshot(Request $request, PaymentSubmission $payment)
    {
        if (!$payment->screenshot_path || !Storage::disk('local')->exists($payment->screenshot_path)) {
            return response()->json(['message' => 'Screenshot not found.'], 404);
        }

        $file = Storage::disk('local')->get($payment->screenshot_path);
        $mimeType = Storage::disk('local')->mimeType($payment->screenshot_path);

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'private, max-age=3600');
    }
}
