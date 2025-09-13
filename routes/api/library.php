<?php

use Illuminate\Support\Facades\Route;

Route::prefix('/import')->group(function () {
    Route::post('/parse-subtitle-file', [App\Http\Controllers\Subtitle\SubtitleController::class, 'parseSubtitleFile']);
});
