<?php

use App\Http\Controllers\Vocabulary\ExampleSentenceController;
use App\Http\Controllers\Vocabulary\VocabularyController;
use App\Http\Controllers\Vocabulary\VocabularyImportExportController;
use App\Http\Controllers\Vocabulary\VocabularyKanjiController;
use App\Http\Controllers\Vocabulary\VocabularyPhraseController;
use App\Http\Controllers\Vocabulary\VocabularyWordController;
use Illuminate\Support\Facades\Route;

Route::post('/search', [VocabularyController::class, 'index']);

Route::prefix('/words')->group(function () {
    Route::get('/{word}', [VocabularyWordController::class, 'show']);
    Route::post('/{word}', [VocabularyWordController::class, 'update']);
});

Route::prefix('/phrases')->group(function () {
    Route::get('/{phrase}', [VocabularyPhraseController::class, 'show']);
    Route::post('/store', [VocabularyPhraseController::class, 'store']);
    Route::post('/{phrase}', [VocabularyPhraseController::class, 'update']);
    Route::delete('/{phrase}', [VocabularyPhraseController::class, 'destroy']);
});

Route::prefix('/example-sentences')->group(function () {
    Route::get('/word/{word}', [ExampleSentenceController::class, 'showForWord']);
    Route::get('/phrase/{phrase}', [ExampleSentenceController::class, 'showForPhrase']);
    Route::post('/', [ExampleSentenceController::class, 'createOrUpdate']);
});

Route::prefix('/kanji')->group(function () {
    Route::post('/search', [VocabularyKanjiController::class, 'index']);
    Route::post('/details', [VocabularyKanjiController::class, 'show']);
});

Route::post('/export', [VocabularyImportExportController::class, 'export']);
Route::post('/import', [VocabularyImportExportController::class, 'import']);
