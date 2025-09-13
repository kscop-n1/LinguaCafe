<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [UserController::class, 'showLoginForm'])->name('login');
Route::post('/login', [UserController::class, 'authenticateUser']);
Route::post('/logout', [UserController::class, 'logoutUser'])
    ->middleware('auth')
    ->name('logout');
