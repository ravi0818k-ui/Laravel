<?php

use App\Http\Controllers\Api\AdminTenantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BedController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PgController;
use App\Http\Controllers\Api\RentController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1/
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ─── Public (No Auth) ───────────────────────────────────────
    Route::get('/public/pg-locations', [PgController::class, 'publicIndex']);

    // Onboarding (public form submission)
    Route::get('/onboarding/{token}/validate', [OnboardingController::class, 'validateToken']);
    Route::post('/onboarding/{token}/submit', [OnboardingController::class, 'submitForm']);

    // Auth
    Route::post('/login', [AuthController::class, 'login']);

    // ─── Authenticated Routes ───────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // ─── Tenant Routes ──────────────────────────────────────
        Route::prefix('tenant')->middleware('role:tenant')->group(function () {
            Route::get('/dashboard', [TenantController::class, 'dashboard']);
            Route::get('/profile', [TenantController::class, 'profile']);
            Route::get('/rents', [RentController::class, 'tenantIndex']);
            Route::get('/electricity', [TenantController::class, 'electricity']);
            Route::get('/electricity/{bill}/meter-image/{type}', [\App\Http\Controllers\Api\ElectricityController::class, 'viewMeterImage']);
            Route::post('/payments', [PaymentController::class, 'tenantSubmit']);
        });

        // ─── Admin Routes ───────────────────────────────────────
        Route::prefix('admin')->middleware('role:admin,super_admin')->group(function () {
            // Tenants
            Route::get('/tenants', [AdminTenantController::class, 'index']);
            Route::get('/tenants/trash', [AdminTenantController::class, 'trash']);
            Route::get('/tenants/{tenant}', [AdminTenantController::class, 'show']);
            Route::delete('/tenants/{tenant}', [AdminTenantController::class, 'destroy']);
            Route::post('/tenants/{id}/restore', [AdminTenantController::class, 'restore']);
            Route::delete('/tenants/{id}/force-delete', [AdminTenantController::class, 'forceDelete']);
            Route::post('/tenants/{tenant}/change-rent', [AdminTenantController::class, 'changeRent']);
            Route::post('/tenants/{tenant}/change-bed', [AdminTenantController::class, 'changeBed']);
            Route::post('/tenants/{tenant}/reset-password', [AdminTenantController::class, 'resetPassword']);
            Route::post('/tenants/{tenant}/impersonate', [AdminTenantController::class, 'impersonate']);

            // Payments
            Route::get('/payments', [PaymentController::class, 'adminIndex']);
            Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify']);
            Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject']);
            Route::get('/payments/{payment}/screenshot', [PaymentController::class, 'viewScreenshot']);

            // Rooms & Beds
            Route::get('/rooms', [RoomController::class, 'index']);
            Route::post('/rooms', [RoomController::class, 'store']);
            Route::get('/rooms/{room}', [RoomController::class, 'show']);
            Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);
            Route::get('/beds', [BedController::class, 'index']);
            Route::post('/beds', [BedController::class, 'store']);
            Route::put('/beds/{bed}', [BedController::class, 'update']);
            Route::delete('/beds/{bed}', [BedController::class, 'destroy']);

            // PG Locations (admin view)
            Route::get('/pg-locations', [PgController::class, 'index']);
            Route::get('/pg-locations/{pgLocation}', [PgController::class, 'show']);

            // Onboarding
            Route::post('/onboarding/invite', [OnboardingController::class, 'createInvitation']);
            Route::get('/onboarding/applications', [OnboardingController::class, 'listApplications']);
            Route::post('/onboarding/{invitation}/approve', [OnboardingController::class, 'approve']);
            Route::post('/onboarding/{invitation}/reject', [OnboardingController::class, 'reject']);

            // Documents
            Route::get('/documents/{document}/view', [OnboardingController::class, 'viewDocument']);
            Route::get('/onboarding/{invitation}/documents', [OnboardingController::class, 'getApplicationDocuments']);

            // Rent generation
            Route::post('/rents/generate', [RentController::class, 'generate']);

            // Notes
            Route::get('/notes', [NoteController::class, 'index']);
            Route::post('/notes', [NoteController::class, 'store']);
            Route::put('/notes/{note}', [NoteController::class, 'update']);
            Route::delete('/notes/{note}', [NoteController::class, 'destroy']);

            // Expenses
            Route::get('/expenses', [ExpenseController::class, 'index']);
            Route::post('/expenses', [ExpenseController::class, 'store']);
            Route::post('/expenses/{expense}', [ExpenseController::class, 'update']);
            Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);
            Route::get('/expenses/{expense}/image', [ExpenseController::class, 'viewImage']);

            // Electricity bills
            Route::post('/electricity-bills', [\App\Http\Controllers\Api\ElectricityController::class, 'store']);
            Route::get('/electricity-bills', [\App\Http\Controllers\Api\ElectricityController::class, 'index']);
            Route::get('/electricity-bills/{bill}/meter-image/{type}', [\App\Http\Controllers\Api\ElectricityController::class, 'viewMeterImage']);
            Route::post('/electricity-allocations/{allocation}/mark-paid', [\App\Http\Controllers\Api\ElectricityController::class, 'markAllocationPaid']);
            Route::post('/tenants/{tenant}/electricity-adjustment', [\App\Http\Controllers\Api\ElectricityController::class, 'adjustForTenant']);
        });

        // ─── Super Admin Routes ─────────────────────────────────
        Route::prefix('super-admin')->middleware('role:super_admin')->group(function () {
            Route::get('/dashboard', [SuperAdminController::class, 'dashboard']);

            // Admins management
            Route::get('/admins', [SuperAdminController::class, 'listAdmins']);
            Route::post('/admins', [SuperAdminController::class, 'createAdmin']);
            Route::post('/admins/{admin}/assign-pg', [SuperAdminController::class, 'assignPgLocations']);
            Route::put('/admins/{admin}/status', [SuperAdminController::class, 'toggleAdminStatus']);

            // PG Location CRUD (create/update)
            Route::post('/pg-locations', [PgController::class, 'store']);
            Route::put('/pg-locations/{pgLocation}', [PgController::class, 'update']);
        });
    });
});
