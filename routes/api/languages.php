<?php

use App\Http\Controllers\Languages\LanguageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LanguageController::class, 'index']);
Route::get('/dialog', [LanguageController::class, 'indexForDialog']);
Route::get('/select/{language}', [LanguageController::class, 'select']);
Route::delete('/{language}', [LanguageController::class, 'destroyData']);
