<?php

use App\Http\Controllers\Toolbox\ToolboxController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
  ->prefix('toolbox')
  ->group(function () {

    // Toolbox pages (keep protected)
    Route::middleware(['toolbox.access:administrator'])->group(function () {
      Route::name('toolbox.')->group(function () {
        Route::get('/', [ToolboxController::class, 'index'])->name('index');
        Route::get('/tools', [ToolboxController::class, 'tools'])->name('tools');
        Route::get('/ping', [ToolboxController::class, 'ping'])->name('ping');
      });
    });

    // Vantage (queues) - protected by feature enable/method middleware
    Route::middleware([
      'vantage.access',
      'toolbox.access:administrator',
    ])
      ->prefix('queues')
      ->group(function () {
        require base_path('routes/vendor/vantage.php');
      });

  });
