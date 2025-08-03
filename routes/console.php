<?php

use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Support\Facades\Log;

try {
    $backupInterval = json_decode(Setting::where('name', 'backupInterval')->first()?->value);
    BackupService::updateBackupSchedule($backupInterval);
} catch (InvalidArgumentException|TypeError $exception) {
    // Initialize default backup interval
    $defaultBackupInterval = '0,30 * * * *';
    Log::info("The backup service could not initialize the backup schedule to the interval ($backupInterval) because it is invalid. The default backup interval ($defaultBackupInterval) will be used instead.");
    BackupService::updateBackupSchedule($defaultBackupInterval);
}
