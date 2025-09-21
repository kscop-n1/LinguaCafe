<?php

use App\Http\Controllers\Users\AuthController;
use Illuminate\Support\Facades\Route;

// should be in auth.php routes
Route::get('/login', [AuthController::class, 'loginForm'])->name('login');

Route::group(['middleware' => ['auth', 'auth.session', 'web']], function () {
    // vocabulary
    Route::get('/vocabulary/words/get/{word}', [App\Http\Controllers\VocabularyController::class, 'getUniqueWord']);
    Route::post('/vocabulary/word/update/{word}', [App\Http\Controllers\VocabularyController::class, 'updateWord']);
    Route::get('/vocabulary/phrases/get/{phrase}', [App\Http\Controllers\VocabularyController::class, 'getPhrase']);
    Route::post('/vocabulary/phrases/create', [App\Http\Controllers\VocabularyController::class, 'createPhrase']);
    Route::post('/vocabulary/phrases/update/{phrase}', [App\Http\Controllers\VocabularyController::class, 'updatePhrase']);
    Route::post('/vocabulary/phrases/delete/{phrase}', [App\Http\Controllers\VocabularyController::class, 'deletePhrase']);
    Route::post('/vocabulary/example-sentence/create-or-update', [App\Http\Controllers\VocabularyController::class, 'createOrUpdateExampleSentence']);
    Route::post('/vocabulary/search', [App\Http\Controllers\VocabularyController::class, 'searchVocabulary']);
    Route::post('/vocabulary/export-to-csv', [App\Http\Controllers\VocabularyController::class, 'exportToCsv']);
    Route::post('/vocabulary/import-from-csv', [App\Http\Controllers\VocabularyController::class, 'importFromCsv']);
    Route::get('/vocabulary/example-sentence/word/{word}', [App\Http\Controllers\VocabularyController::class, 'getWordExampleSentence']);
    Route::get('/vocabulary/example-sentence/phrase/{phrase}', [App\Http\Controllers\VocabularyController::class, 'getPhraseExampleSentence']);
    Route::post('/kanji/search', [App\Http\Controllers\VocabularyController::class, 'searchKanji']);
    Route::post('/kanji/details', [App\Http\Controllers\VocabularyController::class, 'getKanjiDetails']);

    Route::view('/{any?}', 'home')->where('any', '.*');
});
