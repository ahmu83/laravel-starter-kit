<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Toolbox\ToolboxController;

/*
|--------------------------------------------------------------------------
| Toolbox Routes (/toolbox/*)
|--------------------------------------------------------------------------
|
| Internal tooling routes for development and debugging.
|
| Access Control Strategy (ALTERNATIVE VERSION):
| - Toolbox pages require WordPress admin role + email allowlist
| - Vantage has INDEPENDENT access control via feature gate
| - Individual features (Pulse, LogViewer) use their own feature gates
|
| Use this version if Vantage needs different access rules than toolbox.
|
*/

Route::middleware(['web'])
    ->prefix('toolbox')
    ->group(function () {

        /*
    |--------------------------------------------------------------------------
    | Toolbox Pages
    |--------------------------------------------------------------------------
    | Main toolbox interface and tools listing
    */
        Route::middleware(['toolbox.access:administrator'])->group(function () {
            Route::name('toolbox.')->group(function () {
                Route::get('/', [ToolboxController::class, 'index'])->name('index');
                Route::get('/tools', [ToolboxController::class, 'tools'])->name('tools');
                Route::get('/ping', [ToolboxController::class, 'ping'])->name('ping');
            });
        });

    });
