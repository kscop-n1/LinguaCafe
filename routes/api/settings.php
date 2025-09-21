<?php

use App\Http\Controllers\Settings\GlobalSettingsController;
use App\Http\Controllers\Settings\UserSettingsController;
use Illuminate\Support\Facades\Route;

Route::post('/update', [UserSettingsController::class, 'updateOrCreate']);
Route::get('/anki', [GlobalSettingsController::class, 'showAnki']);
Route::get('/jellyfin', [GlobalSettingsController::class, 'showJellyfin']);
Route::post('/', [UserSettingsController::class, 'show']);
