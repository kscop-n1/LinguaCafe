<?php

use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

/*
    This function's authentication is inside the controller, because
    the first user can be created without being logged in.
*/
Route::group(['middleware' => 'web'], function () {
    Route::post('/users/create', [App\Http\Controllers\UserController::class, 'createUser']);
});

// basic user data for the app
Route::get('/user/data', [App\Http\Controllers\UserController::class, 'getInitUserData']);

Route::group(['middleware' => ['auth', 'auth.session', 'web']], function () {

    Route::group(['middleware' => 'admin'], function () {
        // users
        Route::get('/users/get', [App\Http\Controllers\UserController::class, 'getUsers']);
        Route::post('/users/update/{user}', [App\Http\Controllers\UserController::class, 'updateUser']);

        // settings
        Route::post('/settings/global/update', [App\Http\Controllers\SettingsController::class, 'updateGlobalSettings']);
        Route::post('/settings/global/get', [App\Http\Controllers\SettingsController::class, 'getGlobalSettingsByName']);
    });

    // users
    Route::post('/users/update-password', [App\Http\Controllers\UserController::class, 'updatePassword']);
    Route::get('/users/is-password-changed', [App\Http\Controllers\UserController::class, 'isUserPasswordChanged']);
    Route::delete('/users/delete-language-data/{language}', [App\Http\Controllers\UserController::class, 'deleteUserLanguageData']);

    // jellyfin
    Route::get('/jellyfin/subtitles', [App\Http\Controllers\JellyfinController::class, 'getJellyfinCurrentlyPlayedSubtitles']);

    // settings
    Route::post('/settings/user/get', [App\Http\Controllers\SettingsController::class, 'getUserSettingsByName']);
    Route::post('/settings/user/update', [App\Http\Controllers\SettingsController::class, 'updateOrCreateUserSettings']);
    Route::get('/settings/is-jellyfin-enabled', [App\Http\Controllers\SettingsController::class, 'isJellyfinEnabled']);
    Route::get('/settings/get-anki-settings', [App\Http\Controllers\SettingsController::class, 'getAnkiSettings']);

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

    // vue routes
    Route::view('/{any?}', 'home')->where('any', '.*');

});
