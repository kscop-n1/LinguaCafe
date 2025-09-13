<?php

use Illuminate\Support\Facades\Route;

Route::prefix('/manual')
    ->middleware(['auth', 'auth.session', 'web'])
    ->group(base_path('routes/api/manual.php'));
