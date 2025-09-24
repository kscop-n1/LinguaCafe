<?php

use App\Http\Controllers\Reviews\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/{practiceMode}/{book?}/{chapter?}', [ReviewController::class, 'show']);
Route::post('/update', [ReviewController::class, 'updateGoal']);
