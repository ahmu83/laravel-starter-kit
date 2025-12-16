<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Toolbox Routes
|--------------------------------------------------------------------------
|
| Here is where you can register toolbox routes for your
| application. i.e, log-viewer, queue viewer etc.
|
*/

Route::get('/', function () {
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
})->name('index');

Route::get('/ping', function () {
  return 'sandbox pong';
})->name('ping');



