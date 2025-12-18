<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication routes (/account/*)
|--------------------------------------------------------------------------
|
| This file owns the /account prefix and all authentication-related routes.
| The "web" middleware is applied here so bootstrap can just include files.
|
*/

$groups = [];

/*
|--------------------------------------------------------------------------
| Guest routes (login, register, password reset, social auth)
|--------------------------------------------------------------------------
*/
$groups['guest'] = function () {

  // Email/Password Registration
  Route::get('register', [RegisteredUserController::class, 'create'])
    ->name('register');

  Route::post('register', [RegisteredUserController::class, 'store']);

  // Email/Password Login
  Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

  Route::post('login', [AuthenticatedSessionController::class, 'store']);

  // Password Reset
  Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
    ->name('password.request');

  Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
    ->name('password.email');

  Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
    ->name('password.reset');

  Route::post('reset-password', [NewPasswordController::class, 'store'])
    ->name('password.store');

  // Social Authentication (moved from web.php)
  Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->name('social.redirect');

  Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->name('social.callback');

};

/*
|--------------------------------------------------------------------------
| Authenticated routes (dashboard, profile, email verification, logout)
|--------------------------------------------------------------------------
*/
$groups['auth'] = function () {

  // Dashboard
  Route::get('dashboard', function () {
    return view('dashboard');
  })->name('dashboard');

  // Profile Management
  Route::get('profile', [ProfileController::class, 'edit'])
    ->name('profile.edit');

  Route::patch('profile', [ProfileController::class, 'update'])
    ->name('profile.update');

  Route::delete('profile', [ProfileController::class, 'destroy'])
    ->name('profile.destroy');

  // Email Verification
  Route::get('verify-email', EmailVerificationPromptController::class)
    ->name('verification.notice');

  Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

  Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('verification.send');

  // Password Confirmation
  Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
    ->name('password.confirm');

  Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

  // Password Update (from profile)
  Route::put('password', [PasswordController::class, 'update'])
    ->name('password.update');

  // Logout
  Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

};

/*
|--------------------------------------------------------------------------
| Route Registration
|--------------------------------------------------------------------------
| All routes under /account prefix with web middleware
*/
Route::middleware(['web'])
  ->prefix('account')
  ->group(function () use ($groups) {

    Route::middleware(['guest'])->group($groups['guest']);
    Route::middleware(['auth', 'verified'])->group($groups['auth']);

  });

