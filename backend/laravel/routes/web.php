<?php

use App\Http\Controllers\Users\AuthController;
use Illuminate\Support\Facades\Route;

// should be in auth.php routes
Route::get('/login', [AuthController::class, 'loginForm'])->name('login');

Route::group(['middleware' => ['auth', 'auth.session', 'web']], function () {
    Route::view('/{any?}', 'home')->where('any', '.*');
});
