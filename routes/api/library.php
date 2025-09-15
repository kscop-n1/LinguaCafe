<?php

use App\Http\Controllers\Import\ImportController;
use App\Http\Controllers\Import\SubtitleController;
use App\Http\Controllers\Import\WebsiteController;
use App\Http\Controllers\Import\YoutubeController;
use App\Http\Controllers\Library\BookController;
use App\Http\Controllers\Library\BookmarkController;
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

Route::prefix('/books')->group(function () {
    Route::get('/', [BookController::class, 'index']);
    Route::get('/{book}', [BookController::class, 'show']);
    Route::get('/word-counts/{book}', [BookController::class, 'wordCounts']);
    Route::post('/', [BookController::class, 'store']);
    Route::post('/{book}', [BookController::class, 'update']);
    Route::delete('/{book}', [BookController::class, 'destroy']);
});

Route::prefix('/bookmarks')->group(function () {
    Route::get('/{type?}', [BookmarkController::class, 'index']);
    Route::delete('/{bookmark}', [BookmarkController::class, 'destroy']);
});
