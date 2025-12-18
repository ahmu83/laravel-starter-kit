<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
  return view('welcome');
});

Route::get('/welcome', function () {
  return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Account Index Route
|--------------------------------------------------------------------------
| Redirects to login or dashboard based on auth status
*/
Route::get('/account', function () {
  return auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login');
})->name('account.index');

