<?php

use App\Http\Controllers\Fonts\FontTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/{fontType}', [FontTypeController::class, 'show']);
Route::get('/language/{language}', [FontTypeController::class, 'indexForLanguage']);
