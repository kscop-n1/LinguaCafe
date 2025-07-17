<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class BackupService {
    public function createBackup(): void
    {
        $host = ' -h ' . env('DB_HOST');
        $port = ' -P ' . env('DB_PORT');
        $username = ' -u ' . env('DB_USERNAME');
        $password = ' -p' . env('DB_PASSWORD');
        $database = ' ' . env('DB_DATABASE');
        $timestamp = Carbon::now()->format('Y_m_d_H_i_s');
        
        $path = '/var/www/html/storage/backup/';
        $prefix = 'linguacafe_';
        $fileName = $prefix . $timestamp . '.sql';
        $fullFilePath = $path . $fileName;

        $this->deleteOldBackups($prefix);

        $exitCode = null;
        exec(
            command: 'mysqldump --no-tablespaces' . $host . $port . $username . $password . $database . ' > ' . $fullFilePath, 
            result_code: $exitCode
        );

        if ($exitCode !== 0)  {
            throw new \Exception('Backup process failed.');
        }
    }

    private function deleteOldBackups(string $prefix): void
    {
        $maxBackups = env('MAX_SAVED_BACKUPS');
        $files = $this->getBackupFiles($prefix);
        while (count($files) >= $maxBackups) {
            $fileToDelete = array_shift($files);
            Storage::disk('backup')->delete($fileToDelete);
        }
    }

    private function getBackupFiles(string $prefix): array 
    {
        $files = Storage::disk('backup')->files();
        $files = Arr::where($files, function ($value) use($prefix) {
            return strpos($value, $prefix) === 0 && str_contains($value, '.sql');
        });

        return $files;
    }
}