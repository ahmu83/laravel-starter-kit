<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Toolbox routes (/toolbox/*)
|--------------------------------------------------------------------------
|
| Internal tooling routes such as log viewer, queue viewer, etc.
|
*/

$handlers = [];

/**
 * /toolbox route handler
 */
$handlers['index'] = function () {

  $data = [
    'status' => 'ok',
    'message' => 'Toolbox root route is working',
    'true' => true,
    'false' => false,
    'null' => null,
    'user' => auth()->check() ? auth()->user()->email : null,
    'environment' => app()->environment(),
    'routes' => [
      'logs' => url('toolbox/log-viewer'),
      'queue' => url('toolbox/queues'),
      'tinker' => url('toolbox/tinker'),
    ],
  ];

  printr($data, '$data');
  dd($data);

};

/**
 * /toolbox/ping route handler
 */
$handlers['ping'] = function () {
  return 'toolbox pong';
};

/**
 * /toolbox/queues route handler
 */
$handlers['queues'] = function () {
  require base_path('routes/vendor/vantage.php');
};

Route::middleware(['web', 'toolbox.access'])

// Route::middleware(['web', 'can:accessToolbox'])
  ->prefix('toolbox')
  ->group(function () use ($handlers) {

    /*
    |--------------------------------------------------------------------------
    | Toolbox named routes (toolbox.*)
    |--------------------------------------------------------------------------
    */
    Route::name('toolbox.')->group(function () use ($handlers) {

      Route::get('/', $handlers['index'])->name('index');
      Route::get('/ping', $handlers['ping'])->name('ping');

    });

    /*
    |--------------------------------------------------------------------------
    | Vantage (Queue Monitor)
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | Do not put Vantage inside the "toolbox." name group or its internal
    | route('vantage.*') calls will break.
    |
    */
    Route::prefix('queues')->group($handlers['queues']);

  });
