<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Not used — this is an API-only application.
| All routes are in routes/api.php under /api/v1/ prefix.
*/

Route::get('/', function () {
    return response()->json([
        'app' => 'PG A1 Management System',
        'version' => '1.0.0',
        'api' => '/api/v1/',
        'docs' => 'See INSTALL.md for setup instructions.',
    ]);
});

// Serve onboarding form page for candidates
Route::get('/onboarding/{token}', function () {
    return file_get_contents(public_path('onboarding.html'));
});
