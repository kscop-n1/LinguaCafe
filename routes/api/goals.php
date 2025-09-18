<?php

use App\Http\Controllers\Goals\GoalAchievementController;
use App\Http\Controllers\Goals\GoalController;
use Illuminate\Support\Facades\Route;

Route::prefix('/achievements')->group(function () {
    Route::post('/reviews/increment', [GoalAchievementController::class, 'incrementReview']);
    Route::post('/{goalAchievement?}', [GoalAchievementController::class, 'updateOrStore']);
});

Route::get('/calendar', [GoalController::class, 'calendar']);
Route::post('/{goal}', [GoalController::class, 'update']);
Route::get('/', [GoalController::class, 'index']);
