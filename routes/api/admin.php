<?php

use App\Http\Controllers\Dictionaries\DictionaryController;
use App\Http\Controllers\System\BackupController;
use Illuminate\Support\Facades\Route;

Route::post('/dictionaries/store-api', [DictionaryController::class, 'storeApi']);

Route::get('/backups/create', [BackupController::class, 'store']);
