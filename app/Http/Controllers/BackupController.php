<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    public function createBackup() {
        Artisan::call('app:create-backup');

        return response()->noContent();
    }
}