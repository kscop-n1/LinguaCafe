<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

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
        Route::get('/dev', [App\Http\Controllers\HomeController::class, 'index']);

        // users
        Route::get('/users/get', [App\Http\Controllers\UserController::class, 'getUsers']);
        Route::post('/users/update/{user}', [App\Http\Controllers\UserController::class, 'updateUser']);

        // languages
        Route::post('/languages/install', [App\Http\Controllers\LanguageController::class, 'installLanguage']);
        Route::delete('/languages/installed/delete', [App\Http\Controllers\LanguageController::class, 'deleteInstalledLanguages']);
        Route::get('/languages/get-admin-language-settings-data', [App\Http\Controllers\LanguageController::class, 'getAdminLanguageSettingsData']);

        // vue routes
        Route::get('/admin/{page?}', [App\Http\Controllers\HomeController::class, 'index']);

        // settings
        Route::post('/settings/global/update', [App\Http\Controllers\SettingsController::class, 'updateGlobalSettings']);
        Route::post('/settings/global/get', [App\Http\Controllers\SettingsController::class, 'getGlobalSettingsByName']);
    });

    // languages
    Route::get('/languages/get-language-selection-dialog-data', [App\Http\Controllers\LanguageController::class, 'getLanguageSelectionDialogData']);
    Route::get('/languages/select/{language}', [App\Http\Controllers\LanguageController::class, 'selectLanguage']);

    // users
    Route::post('/users/update-password', [App\Http\Controllers\UserController::class, 'updatePassword']);
    Route::get('/users/is-password-changed', [App\Http\Controllers\UserController::class, 'isUserPasswordChanged']);
    Route::delete('/users/delete-language-data/{language}', [App\Http\Controllers\UserController::class, 'deleteUserLanguageData']);

    // jellyfin
    Route::get('/jellyfin/subtitles', [App\Http\Controllers\JellyfinController::class, 'getJellyfinCurrentlyPlayedSubtitles']);

    // vue routes
    Route::get('/', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/user-settings', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/user-manual/{currentPage?}', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/attributions', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/patch-notes', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/books/{bookId?}', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/book/create', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/chapters/{id}', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/chapters/read/{id}', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/chapters/create/{bookId}', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/chapters/edit/{bookId}/{chapterId}', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/review/{practiceMode?}/{bookId?}/{chapterId?}', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/vocabulary/search', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/vocabulary/search/{text}/{stage}/{book}/{chapter}/{translation}/{phrases}/{orderBy}/{page}', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/kanji/search', [App\Http\Controllers\HomeController::class, 'index']);
    Route::get('/kanji/{character}', [App\Http\Controllers\HomeController::class, 'index']);

    // home
    Route::post('/statistics/get', [App\Http\Controllers\HomeController::class, 'getStatistics']);
    Route::get('/config/languages', [App\Http\Controllers\HomeController::class, 'getLanguageConfig']);

    // settings
    Route::post('/settings/user/get', [App\Http\Controllers\SettingsController::class, 'getUserSettingsByName']);
    Route::post('/settings/user/update', [App\Http\Controllers\SettingsController::class, 'updateOrCreateUserSettings']);
    Route::get('/settings/is-jellyfin-enabled', [App\Http\Controllers\SettingsController::class, 'isJellyfinEnabled']);
    Route::get('/settings/get-anki-settings', [App\Http\Controllers\SettingsController::class, 'getAnkiSettings']);

    // images
    Route::get('/images/book-images/{book}', [App\Http\Controllers\ImageController::class, 'getBookImage']);
    Route::get('/images/kanji/{fileName}', [App\Http\Controllers\ImageController::class, 'getKanjiImage']);

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

    // review
    Route::post('/reviews/update', [App\Http\Controllers\ReviewController::class, 'updateReadWordsGoal']);
    Route::post('/reviews/{book?}/{chapter?}', [App\Http\Controllers\ReviewController::class, 'getReviewItems']);
});
