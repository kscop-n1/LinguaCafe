<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Console\Command;

use function PHPUnit\Framework\isNull;

class CreateBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a backup of the database into the storage folder.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $backupCompression = Setting::where('name', 'backupCompression')->first()?->value;
        $backupCompressionEnabled = is_null($backupCompression) ? false : json_decode($backupCompression);
        $exitCode = (new BackupService)->createBackup($backupCompressionEnabled);
        
        return $exitCode;
    }
}
