<?php

use App\Http\Controllers\Dictionaries\DictionaryController;
use Illuminate\Support\Facades\Route;

Route::post('/dictionaries/store-api', [DictionaryController::class, 'storeApi']);
