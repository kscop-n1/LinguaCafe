<?php

use App\Http\Controllers\Manual\ManualController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ManualController::class, 'index']);
Route::get('/{fileName}', [App\Http\Controllers\Manual\ManualController::class, 'show']);
