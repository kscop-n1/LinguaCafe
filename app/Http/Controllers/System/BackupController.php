<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    public function store()
    {
        Artisan::call('app:create-backup');

        return response()->noContent();
    }
}
