<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sandbox Routes
|--------------------------------------------------------------------------
|
| Here is where you can register sandbox routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "sandbox" middleware group. Make something great!
|
*/

foreach ([
    'axios-test',
    'components',
    'tailwind',
    'test',
] as $slug) {
    // Route::view($slug, 'pages.sandbox.' . $slug)->name($slug);
}

Route::get('/', function () {
  return response()->json([
    'status' => 'ok',
    'message' => 'Sandbox root route is working',
    'user' => auth()->check() ? auth()->user()->email : null,
    'environment' => app()->environment(),
  ]);
})->name('index');

Route::get('/ping', function () {
  return 'sandbox pong';
})->name('ping');


