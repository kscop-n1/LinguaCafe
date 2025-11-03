<?php

use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

/*
    These functions have their own auth.
*/
Route::middleware(['web'])->group(function () {
    Route::get('/data', [UserController::class, 'appUserData']);
    Route::post('/store', [UserController::class, 'store']);
});

Route::middleware(['auth', 'auth.session', 'web'])->group(function () {
    Route::get('/password/changed', [UserController::class, 'passwordChanged']);
    // TODO: /update/password -> /password/update
    Route::post('/update/password', [UserController::class, 'updatePassword']);
});
