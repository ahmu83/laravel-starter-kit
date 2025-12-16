<?php

use Illuminate\Support\Facades\Route;

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

Route::middleware(['web', 'sandbox.access', 'basic.auth'])
// Route::middleware(['web', 'can:accessToolbox'])
  ->prefix('sandbox')
  ->name('sandbox.')
  ->group(function () use ($handlers) {

    Route::get('/', $handlers['index'])->name('index');
    Route::get('/ping', $handlers['ping'])->name('ping');

  });


