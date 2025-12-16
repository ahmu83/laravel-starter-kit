<?php

use Illuminate\Support\Facades\Route;
use HoudaSlassi\Vantage\Http\Controllers\QueueMonitorController;

Route::get('/', [QueueMonitorController::class, 'index'])->name('vantage.dashboard');
Route::get('/jobs', [QueueMonitorController::class, 'jobs'])->name('vantage.jobs');
Route::get('/jobs/{id}', [QueueMonitorController::class, 'show'])->name('vantage.jobs.show');
Route::get('/tags', [QueueMonitorController::class, 'tags'])->name('vantage.tags');
Route::get('/failed', [QueueMonitorController::class, 'failed'])->name('vantage.failed');
Route::post('/jobs/{id}/retry', [QueueMonitorController::class, 'retry'])->name('vantage.jobs.retry');
