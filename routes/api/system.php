<?php

use App\Http\Controllers\System\BackupController;
use Illuminate\Support\Facades\Route;

Route::get('/backups/create', [BackupController::class, 'store']);
