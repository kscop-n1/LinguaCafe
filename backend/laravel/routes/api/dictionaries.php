<?php

use App\Http\Controllers\Dictionaries\DictionaryController;
use App\Http\Controllers\Dictionaries\DictionarySearchController;
use Illuminate\Support\Facades\Route;

Route::get('/api/enabled', [DictionaryController::class, 'isAnyApiDictionaryEnabled']);

Route::prefix('/search')->group(function () {
    Route::post('/', [DictionarySearchController::class, 'search']);
    Route::post('/api', [DictionarySearchController::class, 'searchApi']);
    Route::post('/hover', [DictionarySearchController::class, 'searchHover']);
    Route::post('/inflections', [DictionarySearchController::class, 'searchInflections']);
});
