<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

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
    public function handle(BackupService $backupService)
    {
        $result = $backupService->createBackup();

        if (($result['success'] ?? false) !== true) {
            $this->error('Database backup could not be created.');

            if (array_key_exists('exitCode', $result)) {
                $this->line('Exit code: ' . ($result['exitCode'] ?? 'unknown'));
            }

            return self::FAILURE;
        }

        $this->info('Database backup created: ' . $result['filename']);

        return self::SUCCESS;
    }
}
