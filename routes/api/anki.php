<?php

use App\Http\Controllers\Anki\AnkiController;
use Illuminate\Support\Facades\Route;

Route::post('/cards/add', [AnkiController::class, 'addCardToAnki']);
