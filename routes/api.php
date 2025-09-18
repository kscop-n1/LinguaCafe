<?php

use Illuminate\Support\Facades\Route;

Route::prefix('/admin')
    ->middleware(['auth', 'auth.session', 'web', 'admin'])
    ->group(base_path('routes/api/admin.php'));

Route::prefix('/anki')
    ->middleware(['auth', 'auth.session', 'web'])
    ->group(base_path('routes/api/anki.php'));

Route::prefix('/dictionaries')
    ->middleware(['auth', 'auth.session', 'web'])
    ->group(base_path('routes/api/dictionaries.php'));

Route::prefix('/fonts')
    ->middleware(['auth', 'auth.session', 'web'])
    ->group(base_path('routes/api/fonts.php'));

Route::prefix('/goals')
    ->middleware(['auth', 'auth.session', 'web'])
    ->group(base_path('routes/api/goals.php'));

Route::prefix('/images')
    ->middleware(['auth', 'auth.session', 'web'])
    ->group(base_path('routes/api/images.php'));

Route::prefix('/library')
    ->middleware(['auth', 'auth.session', 'web'])
    ->group(base_path('routes/api/library.php'));

Route::prefix('/manual')
    ->middleware(['auth', 'auth.session', 'web'])
    ->group(base_path('routes/api/manual.php'));
