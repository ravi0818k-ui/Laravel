<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPgAccess
{
    /**
     * Ensure admin has access to the PG location referenced in the request.
     * Super Admins bypass this check.
     * Expects 'pg_location_id' in route parameter or request body.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admin has full access
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Determine the PG location ID from route or request
        $pgLocationId = $request->route('pg_location_id')
            ?? $request->route('pg_location')
            ?? $request->input('pg_location_id');

        if ($pgLocationId && $user->isAdmin()) {
            $hasAccess = $user->assignedPgLocations()
                ->where('pg_locations.id', $pgLocationId)
                ->exists();

            if (!$hasAccess) {
                return response()->json([
                    'message' => 'You do not have access to this PG location.',
                ], 403);
            }
        }

        return $next($request);
    }
}
