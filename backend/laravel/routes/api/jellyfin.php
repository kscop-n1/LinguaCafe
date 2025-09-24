<?php

use App\Http\Controllers\Jellyfin\JellyfinController;
use Illuminate\Support\Facades\Route;

Route::get('/subtitles', [JellyfinController::class, 'index']);
