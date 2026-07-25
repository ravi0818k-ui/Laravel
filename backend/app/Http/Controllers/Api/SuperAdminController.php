<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminPgAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    /**
     * List all admins with their assigned PGs.
     */
    public function listAdmins(): JsonResponse
    {
        $admins = User::admins()
            ->with('assignedPgLocations')
            ->get();

        return response()->json(['admins' => $admins]);
    }

    /**
     * Create a new admin user.
     */
    public function createAdmin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|digits:10|unique:users',
            'email' => 'nullable|email|unique:users',
            'password' => 'required|string|min:8',
            'pg_location_ids' => 'array',
            'pg_location_ids.*' => 'exists:pg_locations,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
        ]);

        // Assign PG locations
        if (!empty($validated['pg_location_ids'])) {
            foreach ($validated['pg_location_ids'] as $pgId) {
                AdminPgAssignment::create([
                    'user_id' => $user->id,
                    'pg_location_id' => $pgId,
                    'assigned_by' => $request->user()->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Admin created successfully.',
            'admin' => $user->load('assignedPgLocations'),
        ], 201);
    }

    /**
     * Assign PG locations to an admin.
     */
    public function assignPgLocations(Request $request, User $admin): JsonResponse
    {
        if (!$admin->isAdmin()) {
            return response()->json(['message' => 'User is not an admin.'], 422);
        }

        $request->validate([
            'pg_location_ids' => 'required|array',
            'pg_location_ids.*' => 'exists:pg_locations,id',
        ]);

        foreach ($request->pg_location_ids as $pgId) {
            AdminPgAssignment::firstOrCreate(
                ['user_id' => $admin->id, 'pg_location_id' => $pgId],
                ['assigned_by' => $request->user()->id]
            );
        }

        return response()->json([
            'message' => 'PG locations assigned.',
            'admin' => $admin->load('assignedPgLocations'),
        ]);
    }

    /**
     * Toggle admin active/inactive status.
     */
    public function toggleAdminStatus(Request $request, User $admin): JsonResponse
    {
        if (!$admin->isAdmin()) {
            return response()->json(['message' => 'User is not an admin.'], 422);
        }

        $request->validate(['is_active' => 'required|boolean']);

        $admin->update(['is_active' => $request->is_active]);

        return response()->json([
            'message' => $request->is_active ? 'Admin activated.' : 'Admin deactivated.',
            'admin' => $admin,
        ]);
    }

    /**
     * Dashboard stats for Super Admin.
     */
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'total_pg_locations' => \App\Models\PgLocation::count(),
            'total_rooms' => \App\Models\Room::count(),
            'total_beds' => \App\Models\Bed::count(),
            'available_beds' => \App\Models\Bed::available()->count(),
            'total_tenants' => \App\Models\Tenant::active()->count(),
            'total_admins' => User::admins()->active()->count(),
            'pending_payments' => \App\Models\PaymentSubmission::pendingVerification()->count(),
        ]);
    }
}
