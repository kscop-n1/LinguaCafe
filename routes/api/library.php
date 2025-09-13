<?php

use App\Http\Controllers\Subtitle\SubtitleController;
use App\Http\Controllers\Website\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('/import')->group(function () {
    Route::post('/parse-subtitle-file', [SubtitleController::class, 'parseSubtitleFile']);
    Route::post('/get-website-text', [WebsiteController::class, 'getWebsiteText']);
});
