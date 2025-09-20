<?php

use App\Http\Controllers\Images\ImageController;
use App\Http\Controllers\Images\ImageSearchController;
use App\Http\Controllers\Images\PhraseImageController;
use App\Http\Controllers\Images\WordImageController;
use Illuminate\Support\Facades\Route;

Route::get('/search/{searchEngine}/{searchTerm}', ImageSearchController::class);

Route::prefix('/phrase')->group(function () {
    Route::post('/update-from-url/{phrase}', [PhraseImageController::class, 'updateFromUrl']);
    Route::post('/update/{phrase}', [PhraseImageController::class, 'update']);
    Route::get('/{phrase}', [PhraseImageController::class, 'show']);
    Route::delete('/{phrase}', [PhraseImageController::class, 'destroy']);
});

Route::prefix('/word')->group(function () {
    Route::post('/update-from-url/{word}', [WordImageController::class, 'updateFromUrl']);
    Route::post('/update/{word}', [WordImageController::class, 'update']);
    Route::get('/{word}', [WordImageController::class, 'show']);
    Route::delete('/{word}', [WordImageController::class, 'destroy']);
});

Route::get('/books/{book}', [ImageController::class, 'showBook']);
Route::get('/kanji/{fileName}', [ImageController::class, 'showKanji']);
