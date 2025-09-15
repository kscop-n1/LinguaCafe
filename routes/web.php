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

        // dictionaries
        Route::post('/dictionary/update', [App\Http\Controllers\DictionaryController::class, 'updateDictionary']);

        // vue routes
        Route::get('/admin/{page?}', [App\Http\Controllers\HomeController::class, 'index']);

        // fonts
        Route::get('/fonts/get', [App\Http\Controllers\FontTypeController::class, 'getInstalledFontTypes']);
        Route::post('/fonts/upload', [App\Http\Controllers\FontTypeController::class, 'createFontType']);
        Route::post('/fonts/update/{fontType}', [App\Http\Controllers\FontTypeController::class, 'updateFontType']);
        Route::post('/fonts/delete/{fontType}', [App\Http\Controllers\FontTypeController::class, 'deleteFontType']);

        // settings
        Route::post('/settings/global/update', [App\Http\Controllers\SettingsController::class, 'updateGlobalSettings']);
        Route::post('/settings/global/get', [App\Http\Controllers\SettingsController::class, 'getGlobalSettingsByName']);

        // dictionaries
        Route::post('/dictionaries/get-supported-dictionary-file-information', [App\Http\Controllers\DictionaryController::class, 'getDictionaryFileInformation']);
        Route::post('/dictionaries/import', [App\Http\Controllers\DictionaryController::class, 'importSupportedDictionary']);
        Route::get('/dictionaries/deepl/get-usage', [App\Http\Controllers\DictionaryController::class, 'getDeeplCharacterLimit']);
        Route::get('/dictionaries/get', [App\Http\Controllers\DictionaryController::class, 'getDictionaries']);
        Route::get('/dictionaries/get/{dictionary}', [App\Http\Controllers\DictionaryController::class, 'getDictionary']);
        Route::post('/dictionaries/update/{dictionary}', [App\Http\Controllers\DictionaryController::class, 'updateDictionary']);
        Route::post('/dictionaries/test-csv-file', [App\Http\Controllers\DictionaryController::class, 'testDictionaryCsvFile']);
        Route::post('/dictionaries/import-csv-file', [App\Http\Controllers\DictionaryController::class, 'importDictionaryCsvFile']);
        Route::post('/dictionaries/create-deepl', [App\Http\Controllers\DictionaryController::class, 'createDeeplDictionary']);
        Route::post('/dictionaries/create-my-memory', [App\Http\Controllers\DictionaryController::class, 'createMyMemoryDictionary']);
        Route::post('/dictionaries/create-custom-api', [App\Http\Controllers\DictionaryController::class, 'createCustomApiDictionary']);
        Route::post('/dictionaries/create-libre-translate', [App\Http\Controllers\DictionaryController::class, 'createLibreTranslateDictionary']);
        Route::delete('/dictionaries/delete/{dictionary}', [App\Http\Controllers\DictionaryController::class, 'deleteDictionary']);
        Route::get('/jmdict/xml-to-text', [App\Http\Controllers\DictionaryController::class, 'jmdictXmlToText']);
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

    // goals
    Route::post('/goals/get', [App\Http\Controllers\GoalController::class, 'getGoals']);
    Route::post('/goal/update/{goal}', [App\Http\Controllers\GoalController::class, 'updateGoal']);
    Route::post('/goals/get-calendar-data', [App\Http\Controllers\GoalController::class, 'getCalendarData']);
    Route::post('/goals/achievement/update/{goalAchievement?}', [App\Http\Controllers\GoalController::class, 'updateOrCreateGoalAchievement']);
    Route::post('/goals/achievement/review/update', [App\Http\Controllers\GoalController::class, 'updateReviewGoalAchievement']);

    // fonts
    Route::get('/fonts/get-fonts-for-language/{language}', [App\Http\Controllers\FontTypeController::class, 'getFontTypesForLanguage']);
    Route::get('/fonts/file/{fileName}', [App\Http\Controllers\FontTypeController::class, 'downloadFontTypeFile']);

    // settings
    Route::post('/settings/user/get', [App\Http\Controllers\SettingsController::class, 'getUserSettingsByName']);
    Route::post('/settings/user/update', [App\Http\Controllers\SettingsController::class, 'updateOrCreateUserSettings']);
    Route::get('/settings/is-jellyfin-enabled', [App\Http\Controllers\SettingsController::class, 'isJellyfinEnabled']);
    Route::get('/settings/get-anki-settings', [App\Http\Controllers\SettingsController::class, 'getAnkiSettings']);

    // images
    Route::get('/images/book-images/{book}', [App\Http\Controllers\ImageController::class, 'getBookImage']);
    Route::get('/images/kanji/{fileName}', [App\Http\Controllers\ImageController::class, 'getKanjiImage']);

    // dictionaries
    Route::post('/dictionaries/api/search', [App\Http\Controllers\DictionaryController::class, 'searchApiDictionaries']);
    Route::get('/dictionaries/api/is-enabled', [App\Http\Controllers\DictionaryController::class, 'isAnyApiDictionaryEnabled']);
    Route::post('/dictionaries/search', [App\Http\Controllers\DictionaryController::class, 'searchDefinitions']);
    Route::post('/dictionaries/search-for-hover-vocabulary', [App\Http\Controllers\DictionaryController::class, 'searchDefinitionsForHoverVocabulary']);
    Route::post('/dictionaries/search/inflections', [App\Http\Controllers\DictionaryController::class, 'searchInflections']);

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
