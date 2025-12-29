<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Sandbox\SandboxController;

/*
|--------------------------------------------------------------------------
| Sandbox routes (/sandbox/*)
|--------------------------------------------------------------------------
|
| Isolated sandbox endpoints for testing and debugging.
| Protected by basic auth, with selective sandbox access enforcement.
|
*/

Route::middleware(['web', 'basic.auth'])
    ->prefix('sandbox')
    ->name('sandbox.')
    ->group(function () {

        // Public (within sandbox, but no sandbox.access)
        Route::get('/', [SandboxController::class, 'index'])->name('index');
        Route::get('/pages', [SandboxController::class, 'pages'])->name('pages');
        Route::get('/ping', [SandboxController::class, 'ping'])->name('ping');

        Route::middleware(['sandbox.access:administrator'])->group(function () {
            Route::get('/test', [SandboxController::class, 'test'])->name('test');
            // Route::post('/reset', ...);
        });

        Route::get('/test/proxied-url', [SandboxController::class, 'proxiedUrl'])->name('test.proxied-url');

    });
