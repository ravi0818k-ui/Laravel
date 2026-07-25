<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'pg.access' => \App\Http\Middleware\EnsureAdminPgAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle database integrity constraint violations (duplicates, etc.)
        $exceptions->renderable(function (\Illuminate\Database\QueryException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $errorCode = $e->errorInfo[1] ?? null;

                // MySQL error 1062: Duplicate entry
                if ($errorCode == 1062) {
                    // Extract a user-friendly message from the error
                    $message = 'A record with this data already exists.';
                    $rawMessage = $e->getMessage();

                    if (str_contains($rawMessage, 'unique_room_per_pg')) {
                        $message = 'This room number already exists in this PG location.';
                    } elseif (str_contains($rawMessage, 'unique_bed_per_room')) {
                        $message = 'This bed number already exists in this room.';
                    } elseif (str_contains($rawMessage, 'unique_bill_per_room_month')) {
                        $message = 'An electricity bill for this room and month already exists.';
                    } elseif (str_contains($rawMessage, 'unique_allocation')) {
                        $message = 'This tenant already has an electricity allocation for this bill.';
                    } elseif (str_contains($rawMessage, 'users_mobile_unique') || str_contains($rawMessage, "'mobile'")) {
                        $message = 'This mobile number is already registered.';
                    } elseif (str_contains($rawMessage, 'users_email_unique') || str_contains($rawMessage, "'email'")) {
                        $message = 'This email address is already registered.';
                    } elseif (str_contains($rawMessage, 'pg_locations_tenant_id_prefix_unique')) {
                        $message = 'This tenant ID prefix is already in use by another PG location.';
                    } elseif (str_contains($rawMessage, 'unique_monthly_rent')) {
                        $message = 'Monthly rent for this tenant and billing month already exists.';
                    }

                    return response()->json([
                        'message' => $message,
                    ], 422);
                }

                // MySQL error 1451/1452: Foreign key constraint
                if ($errorCode == 1451) {
                    return response()->json([
                        'message' => 'Cannot delete this record because it is linked to other data.',
                    ], 422);
                }

                if ($errorCode == 1452) {
                    return response()->json([
                        'message' => 'Referenced record not found. Please check your input.',
                    ], 422);
                }
            }
        });
    })->create();
