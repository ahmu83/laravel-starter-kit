<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return view('welcome');
});

Route::get('/welcome', function () {
  return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Account routes (application pages)
|--------------------------------------------------------------------------
|
| Auth routes (login, register, etc.) are loaded via bootstrap/app.php.
| This file contains only app pages.
|
*/

Route::prefix('account')->group(function () {

  /**
   * /account
   * - Guest -> /account/login
   * - Auth  -> /account/dashboard
   */
  Route::get('/', function () {
    if (auth()->check()) {
      return redirect()->route('dashboard');
    }

    return redirect()->route('login');
  })->name('account.index');

  Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
      return view('dashboard');
    })->name('dashboard');

    Route::get('profile', [ProfileController::class, 'edit'])
      ->name('profile.edit');

    Route::patch('profile', [ProfileController::class, 'update'])
      ->name('profile.update');

    Route::delete('profile', [ProfileController::class, 'destroy'])
      ->name('profile.destroy');
  });
});


