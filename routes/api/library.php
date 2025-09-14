<?php

use App\Http\Controllers\Import\ImportController;
use App\Http\Controllers\Import\SubtitleController;
use App\Http\Controllers\Import\WebsiteController;
use App\Http\Controllers\Import\YoutubeController;
use Illuminate\Support\Facades\Route;

Route::prefix('/import')->group(function () {

    Route::post('/', ImportController::class, 'import');

    Route::prefix('/subtitles')->group(function () {
        Route::post('/parse', [SubtitleController::class, 'parseSubtitleFile']);
    });

    Route::prefix('/websites')->group(function () {
        Route::post('/text', [WebsiteController::class, 'getWebsiteText']);
    });

    Route::prefix('/youtube')->group(function () {
        Route::post('/subtitles', [YoutubeController::class, 'getYoutubeSubtitles']);
    });

});
