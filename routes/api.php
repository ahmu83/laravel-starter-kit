<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/

Route::get('/ping', function () {
  return response()->json([
    'status'    => 'ok',
    'message'   => 'pong',
    'timestamp' => now()->toISOString(),
  ]);
});

Route::get('/test', function () {
  return response()->json([
    'status'    => 'success',
    'message'   => 'API is working!',
    'app'       => config('app.name'),
    'env'       => config('app.env'),
    'timestamp' => now()->toISOString(),
  ]);
});

/*
|--------------------------------------------------------------------------
| Authenticated API Routes
|--------------------------------------------------------------------------
| Requires Sanctum authentication
*/

Route::middleware('auth:sanctum')->group(function () {

  Route::get('/user', function (Request $request) {
    return response()->json([
      'status' => 'success',
      'data'   => $request->user(),
    ]);
  });

});

/*
|--------------------------------------------------------------------------
| Protected API Routes (API Key Authentication)
|--------------------------------------------------------------------------
| Requires API key via X-API-KEY header
*/

Route::middleware('api.auth')->group(function () {

  Route::get('/protected', function () {
    return response()->json([
      'status'    => 'success',
      'message'   => 'You have access to this protected endpoint!',
      'timestamp' => now()->toISOString(),
    ]);
  });

});
