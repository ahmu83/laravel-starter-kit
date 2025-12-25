<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Sandbox\TestController;

/*
|--------------------------------------------------------------------------
| Sandbox routes (/sandbox/*)
|--------------------------------------------------------------------------
|
| Isolated sandbox endpoints for testing and debugging.
| Protected by sandbox + basic auth middleware.
|
*/

$handlers = [];

$handlers['index'] = function () {

  $data = [
    'status' => 'ok',
    'message' => 'Sandbox root route is working',
    'user' => auth()->check() ? auth()->user()->email : null,
    'environment' => app()->environment(),
    'routes' => [],
  ];

  dd($data);

};

$handlers['ping'] = function () {

  return 'sandbox pong';

};

$handlers['test'] = [TestController::class, 'handler_test'];
$handlers['test-proxied-url'] = [TestController::class, 'handler_proxied_url'];




Route::middleware(['web', 'sandbox.access', 'basic.auth'])
// Route::middleware(['web', 'can:accessToolbox'])
  ->prefix('sandbox')
  ->name('sandbox.')
  ->group(function () use ($handlers) {

    Route::get('/', $handlers['index'])->name('index');
    Route::get('/ping', $handlers['ping'])->name('ping');

    Route::get( '/test', $handlers['test'] )->name('test');
    Route::get( '/test/proxied-url', $handlers['test-proxied-url'] )->name('test.proxied-url');

  });


