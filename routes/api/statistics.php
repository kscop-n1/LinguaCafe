<?php

use App\Http\Controllers\Statistics\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StatisticsController::class, 'show']);
