<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login via mobile + password. Returns Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'mobile' => 'required|string|digits:10',
            'password' => 'required|string',
        ]);

        $user = User::where('mobile', $request->mobile)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'mobile' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Contact admin.',
            ], 403);
        }

        // Revoke old tokens (optional: limit concurrent sessions)
        // $user->tokens()->delete();

        $token = $user->createToken('api-token', [$user->role])->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'mobile' => $user->mobile,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Logout — revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'mobile' => $user->mobile,
            'email' => $user->email,
            'role' => $user->role,
        ];

        if ($user->isTenant() && $user->tenant) {
            $data['tenant'] = $user->tenant->load('currentBedAllocation.bed.room', 'pgLocation');
        }

        if ($user->isAdmin()) {
            $data['assigned_pg_locations'] = $user->assignedPgLocations;
        }

        return response()->json(['user' => $data]);
    }
}
