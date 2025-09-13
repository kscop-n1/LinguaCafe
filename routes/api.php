<?php

use Illuminate\Support\Facades\Route;

Route::prefix('/manual')
    ->middleware(['auth', 'auth.session', 'web'])
    ->group(base_path('routes/api/manual.php'));

Route::prefix('/library')
    ->middleware(['auth', 'auth.session', 'web'])
    ->group(base_path('routes/api/library.php'));
