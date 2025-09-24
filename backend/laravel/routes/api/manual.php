<?php

use App\Http\Controllers\Manual\ManualController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ManualController::class, 'index']);
Route::get('/{fileName}', [ManualController::class, 'show']);
