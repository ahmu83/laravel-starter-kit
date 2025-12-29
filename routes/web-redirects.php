<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web redirects
|--------------------------------------------------------------------------
|
| Simple permanent redirects handled at the routing layer.
|
*/
Route::middleware(['web'])->group(function () {

    Route::permanentRedirect('/test-redirect', '/welcome');

});
