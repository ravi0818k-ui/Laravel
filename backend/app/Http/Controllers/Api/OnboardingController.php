<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnboardingInvitation;
use App\Models\Tenant;
use App\Models\TenantDocument;
use App\Models\User;
use App\Services\RoomAllocationService;
use App\Services\TenantIdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    /**
     * Admin: generate a new onboarding invitation link.
     * One link can be used by multiple candidates (mass onboarding).
     * Each submission creates a separate application from the same link.
     */
    public function createInvitation(Request $request): JsonResponse
    {
        $request->validate([
            'pg_location_id' => 'nullable|exists:pg_locations,id',
            'expires_in_hours' => 'nullable|integer|min:1|max:720',
            'link_type' => 'nullable|in:bulk,single',
        ]);

        $token = Str::random(64);
        $expiresIn = $request->expires_in_hours ?? 72;
        $linkType = $request->link_type ?? 'bulk';

        $invitation = OnboardingInvitation::create([
            'token' => $token,
            'pg_location_id' => $request->pg_location_id,
            'created_by' => $request->user()->id,
            'expires_at' => now()->addHours($expiresIn),
            'link_type' => $linkType,
        ]);

        return response()->json([
            'message' => 'Onboarding link created.',
            'invitation' => $invitation,
            'link' => url("/onboarding/{$token}"),
            'expires_in_hours' => $expiresIn,
            'link_type' => $linkType,
        ], 201);
    }

    /**
     * Public: validate token and return invitation status.
     */
    public function validateToken(string $token): JsonResponse
    {
        $invitation = OnboardingInvitation::where('token', $token)->first();

        if (!$invitation) {
            return response()->json(['message' => 'Invalid invitation link.'], 404);
        }

        if ($invitation->isExpired()) {
            return response()->json(['message' => 'This invitation link has expired.'], 410);
        }

        // Check if single-use link has already been used
        if ($invitation->link_type === 'single' && $invitation->status === 'submitted') {
            return response()->json(['message' => 'This link has already been used. Please contact admin for a new link.'], 410);
        }

        return response()->json([
            'valid' => true,
            'pg_location' => $invitation->pgLocation,
        ]);
    }

    /**
     * Public: candidate submits onboarding form.
     * Creates a new application record linked to the parent invitation token.
     * The same link can be used by multiple candidates (mass onboarding).
     */
    public function submitForm(Request $request, string $token): JsonResponse
    {
        $invitation = OnboardingInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            return response()->json(['message' => 'This link has expired.'], 422);
        }

        // Check if single-use link has already been used
        if ($invitation->link_type === 'single' && $invitation->status === 'submitted') {
            return response()->json(['message' => 'This is a single-use link and has already been used.'], 422);
        }

        $validated = $request->validate([
            'candidate_name' => 'required|string|max:255',
            'candidate_mobile' => 'required|string|digits:10',
            'candidate_dob' => 'required|date',
            'candidate_blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'candidate_company_college' => 'required|string|max:255',
            'candidate_company_college_address' => 'nullable|string',
            'candidate_parent_mobile' => 'required|string|digits:10',
            'candidate_reference_mobile_1' => 'required|string|digits:10',
            'candidate_reference_mobile_2' => 'required|string|digits:10',
            'preferred_pg_location_id' => 'nullable|exists:pg_locations,id',
            'referral_code_used' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:8',
            'selfie' => 'required|image|max:5120',
            'aadhaar' => 'required|image|max:10240',
            'aadhaar_pages' => 'nullable|array',
            'aadhaar_pages.*' => 'image|max:10240',
            'voter_id_front' => 'nullable|image|max:5120',
            'voter_id_back' => 'nullable|image|max:5120',
            'company_college_id' => 'nullable|image|max:5120',
        ]);

        // Voter ID requires image format (not PDF)
        // Aadhaar is always sent as decrypted JPEG from client-side PDF processing

        DB::transaction(function () use ($invitation, $validated, $request) {
            // Create a new application record (child of the original link)
            // This allows the same link to be used by multiple candidates
            $application = OnboardingInvitation::create([
                'token' => Str::random(64), // unique token for this application
                'pg_location_id' => $invitation->pg_location_id,
                'created_by' => $invitation->created_by,
                'expires_at' => $invitation->expires_at,
                'status' => 'submitted',
                'submitted_at' => now(),
                'candidate_name' => $validated['candidate_name'],
                'candidate_mobile' => $validated['candidate_mobile'],
                'candidate_dob' => $validated['candidate_dob'],
                'candidate_blood_group' => $validated['candidate_blood_group'] ?? null,
                'candidate_company_college' => $validated['candidate_company_college'],
                'candidate_company_college_address' => $validated['candidate_company_college_address'] ?? null,
                'candidate_parent_mobile' => $validated['candidate_parent_mobile'],
                'candidate_reference_mobile_1' => $validated['candidate_reference_mobile_1'],
                'candidate_reference_mobile_2' => $validated['candidate_reference_mobile_2'],
                'preferred_pg_location_id' => $validated['preferred_pg_location_id'] ?? null,
                'referral_code_used' => $validated['referral_code_used'] ?? null,
            ]);

            // Create a user account so the candidate can log in and check status
            $passwordHash = Hash::make($validated['candidate_mobile']);
            if (!empty($validated['password'])) {
                $passwordHash = Hash::make($validated['password']);
            }

            // Only create if mobile not already registered
            if (!User::where('mobile', $validated['candidate_mobile'])->exists()) {
                User::create([
                    'name' => $validated['candidate_name'],
                    'mobile' => $validated['candidate_mobile'],
                    'password' => $passwordHash,
                    'role' => 'tenant',
                    'is_active' => true,
                ]);
            }

            // Store the password hash in admin_notes for approval flow
            $application->update([
                'admin_notes' => !empty($validated['password'])
                    ? json_encode(['password_hash' => $passwordHash])
                    : null,
            ]);

            // Store documents
            $documents = [
                'selfie' => 'selfie',
                'aadhaar' => 'aadhaar',
                'voter_id_front' => 'voter_id_front',
                'voter_id_back' => 'voter_id_back',
                'company_college_id' => 'company_college_id',
            ];

            foreach ($documents as $field => $type) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $path = $file->store("onboarding/{$application->id}", 'local');

                    TenantDocument::create([
                        'onboarding_invitation_id' => $application->id,
                        'document_type' => $type,
                        'file_path' => $path,
                        'original_filename' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            // Store additional Aadhaar pages (from multi-page PDF decrypted client-side)
            if ($request->hasFile('aadhaar_pages')) {
                foreach ($request->file('aadhaar_pages') as $pageFile) {
                    $path = $pageFile->store("onboarding/{$application->id}", 'local');

                    TenantDocument::create([
                        'onboarding_invitation_id' => $application->id,
                        'document_type' => 'aadhaar',
                        'file_path' => $path,
                        'original_filename' => $pageFile->getClientOriginalName(),
                        'mime_type' => $pageFile->getMimeType(),
                        'file_size' => $pageFile->getSize(),
                    ]);
                }
            }
        });

        // Mark single-use link as used
        if ($invitation->link_type === 'single') {
            $invitation->update(['status' => 'submitted']);
        }

        return response()->json([
            'message' => 'Application submitted successfully. We will contact you shortly.',
        ]);
    }

    /**
     * Admin: approve an onboarding application.
     * Assigns tenant ID, PG, room/bed, rent, creates user account.
     */
    public function approve(Request $request, OnboardingInvitation $invitation): JsonResponse
    {
        if ($invitation->status !== 'submitted') {
            return response()->json(['message' => 'This application is not in submitted state.'], 422);
        }

        $validated = $request->validate([
            'pg_location_id' => 'required|exists:pg_locations,id',
            'bed_id' => 'required|exists:beds,id',
            'rent' => 'required|numeric|min:0',
            'joining_date' => 'required|date',
            'security_deposit' => 'nullable|numeric|min:0',
        ]);

        $tenantIdService = app(TenantIdService::class);
        $roomAllocationService = app(RoomAllocationService::class);
        $pgLocation = \App\Models\PgLocation::findOrFail($validated['pg_location_id']);
        $bed = \App\Models\Bed::findOrFail($validated['bed_id']);

        // Check if bed is still available
        if ($bed->status !== 'available') {
            return response()->json([
                'message' => 'The selected bed is no longer available. Please choose another bed.',
            ], 422);
        }

        $result = DB::transaction(function () use (
            $request, $invitation, $validated, $tenantIdService, $roomAllocationService, $pgLocation, $bed
        ) {
            // Generate tenant ID
            $tenantIdStr = $tenantIdService->generateNextId($pgLocation);

            // Find existing user (created during onboarding submission) or create new
            $existingUser = User::where('mobile', $invitation->candidate_mobile)->first();

            if ($existingUser) {
                // User already exists from onboarding submission — use it
                // Check if already has a tenant profile (shouldn't happen but safety check)
                if ($existingUser->tenant) {
                    throw new \Exception("This user already has an active tenant profile.");
                }
                $user = $existingUser;
                $user->update(['name' => $invitation->candidate_name, 'is_active' => true]);
            } else {
                // Create new user account
                $passwordHash = Hash::make($invitation->candidate_mobile);
                if ($invitation->admin_notes) {
                    $notes = json_decode($invitation->admin_notes, true);
                    if (!empty($notes['password_hash'])) {
                        $passwordHash = $notes['password_hash'];
                    }
                }

                $user = User::create([
                    'name' => $invitation->candidate_name,
                    'mobile' => $invitation->candidate_mobile,
                    'password' => $passwordHash,
                    'role' => 'tenant',
                ]);
            }

            // Create tenant profile
            $tenant = Tenant::create([
                'user_id' => $user->id,
                'tenant_id' => $tenantIdStr,
                'pg_location_id' => $pgLocation->id,
                'date_of_birth' => $invitation->candidate_dob,
                'blood_group' => $invitation->candidate_blood_group,
                'company_or_college' => $invitation->candidate_company_college,
                'company_college_address' => $invitation->candidate_company_college_address,
                'parent_mobile' => $invitation->candidate_parent_mobile,
                'reference_mobile_1' => $invitation->candidate_reference_mobile_1,
                'reference_mobile_2' => $invitation->candidate_reference_mobile_2,
                'referral_code' => $tenantIdStr . '-REF',
                'referred_by_code' => $invitation->referral_code_used,
                'joining_date' => $validated['joining_date'],
                'current_rent' => $validated['rent'],
                'security_deposit' => $validated['security_deposit'] ?? 0,
            ]);

            // Allocate bed
            $roomAllocationService->allocateBed($tenant, $bed, \Carbon\Carbon::parse($validated['joining_date']));

            // Transfer documents from invitation to tenant and mark as verified
            TenantDocument::where('onboarding_invitation_id', $invitation->id)
                ->update([
                    'tenant_id' => $tenant->id,
                    'verification_status' => 'verified',
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                ]);

            // Update invitation status
            $invitation->update(['status' => 'approved']);

            // Handle referral if code was provided
            if ($invitation->referral_code_used) {
                $referrer = Tenant::where('referral_code', $invitation->referral_code_used)->first();
                if ($referrer) {
                    \App\Models\Referral::create([
                        'referrer_tenant_id' => $referrer->id,
                        'referred_tenant_id' => $tenant->id,
                        'onboarding_invitation_id' => $invitation->id,
                        'referral_code_used' => $invitation->referral_code_used,
                        'status' => 'converted',
                    ]);
                }
            }

            return [
                'tenant_id' => $tenantIdStr,
                'user' => $user,
                'tenant' => $tenant,
            ];
        });

        return response()->json([
            'message' => 'Tenant approved and created successfully.',
            'tenant_id' => $result['tenant_id'],
            'credentials' => [
                'mobile' => $result['user']->mobile,
                'password' => '(same as mobile number — advise tenant to change on first login)',
            ],
        ]);
    }

    /**
     * Admin: list onboarding applications.
     */
    public function listApplications(Request $request): JsonResponse
    {
        $query = OnboardingInvitation::with(['pgLocation', 'createdByUser', 'documents']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->orderByDesc('created_at')->get();

        return response()->json(['applications' => $applications]);
    }

    /**
     * Admin: reject an onboarding application.
     */
    public function reject(Request $request, OnboardingInvitation $invitation): JsonResponse
    {
        if ($invitation->status !== 'submitted') {
            return response()->json(['message' => 'This application is not in submitted state.'], 422);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $invitation->update([
            'status' => 'rejected',
            'admin_notes' => json_encode([
                'rejection_reason' => $request->reason ?? 'Application rejected by admin.',
                'rejected_by' => $request->user()->id,
                'rejected_at' => now()->toDateTimeString(),
            ]),
        ]);

        // Deactivate the user account if one was created during submission
        $user = \App\Models\User::where('mobile', $invitation->candidate_mobile)->first();
        if ($user && !$user->tenant) {
            $user->update(['is_active' => false]);
        }

        return response()->json([
            'message' => "Application for {$invitation->candidate_name} has been rejected.",
        ]);
    }

    /**
     * Admin: get documents for an onboarding application.
     */
    public function getApplicationDocuments(OnboardingInvitation $invitation): JsonResponse
    {
        $documents = TenantDocument::where('onboarding_invitation_id', $invitation->id)->get();

        return response()->json(['documents' => $documents]);
    }

    /**
     * Admin: securely view/download a document file.
     * Only accessible to authenticated admins/super admins.
     */
    public function viewDocument(TenantDocument $document)
    {
        $path = $document->file_path;

        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return response()->file(
            Storage::disk('local')->path($path),
            ['Content-Type' => $document->mime_type]
        );
    }
}
