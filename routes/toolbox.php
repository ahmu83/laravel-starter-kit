<?php

use App\Http\Controllers\Toolbox\ToolboxController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'toolbox.access:administrator'])
  ->prefix('toolbox')
  ->group(function () {

    Route::name('toolbox.')->group(function () {
      Route::get('/', [ToolboxController::class, 'index'])->name('index');
      Route::get('/tools', [ToolboxController::class, 'tools'])->name('tools');
      Route::get('/ping', [ToolboxController::class, 'ping'])->name('ping');
    });

    Route::prefix('queues')->group(function () {
      require base_path('routes/vendor/vantage.php');
    });

  });
