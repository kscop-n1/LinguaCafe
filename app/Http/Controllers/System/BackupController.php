<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    public function create()
    {
        Artisan::call('app:create-backup');

        return response()->noContent();
    }
}
