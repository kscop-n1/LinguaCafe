<?php

namespace App\Services;

use App\Models\Setting;
use DateInterval;
use DateTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    public function createBackup(bool $compress = false): void
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
        if ($compress) {
            $fileName = $fileName . '.zip';
        }
        $fullFilePath = $path . $fileName;

        $this->deleteOldBackups($prefix);

        $exitCode = null;

        if ($compress) {
            exec(
                command: 'mysqldump --no-tablespaces' . $host . $port . $username . $password . $database . ' | zip > ' . $fullFilePath,
                result_code: $exitCode
            );
        } else {
            exec(
                command: 'mysqldump --no-tablespaces' . $host . $port . $username . $password . $database . ' > ' . $fullFilePath,
                result_code: $exitCode
            );
        }

        if ($exitCode !== 0) {
            throw new \Exception('Backup process failed.');
        }
    }

    public static function deleteOldBackups(string $prefix): void
    {
        $retentionIntervals = [
            ['max' => (int) Setting::where('name', 'backupRetainYearly')->first()->value, 'interval' => DateInterval::createFromDateString('1 year')],
            ['max' => (int) Setting::where('name', 'backupRetainMonthly')->first()->value, 'interval' => DateInterval::createFromDateString('1 month')],
            ['max' => (int) Setting::where('name', 'backupRetainWeekly')->first()->value, 'interval' => DateInterval::createFromDateString('1 week')],
            ['max' => (int) Setting::where('name', 'backupRetainDaily')->first()->value, 'interval' => DateInterval::createFromDateString('1 day')],
        ];

        $filesToKeep = array_map(function ($path) {
            return [
                'path' => $path,
                'mtime' => Storage::disk('backup')->lastModified($path), // when the file was created/last modified
                'keep' => false, // whether the file should be kept or deleted
            ];
        }, BackupService::getBackupFiles($prefix));

        // Sort files by last modified time, most recent first
        usort($filesToKeep, fn ($fileA, $fileB) => $fileA['mtime'] < $fileB['mtime'] ? 1 : -1);

        // Mark 'X' most recent backups to be kept
        $mostRecentRetentionCount = Setting::where('name', 'backupRetainMostRecent')->first()->value;
        $markKeep = function ($file) {
            $file['keep'] = true;

            return $file;
        };
        $filesToKeep = array_replace($filesToKeep, array_map($markKeep, array_slice($filesToKeep, 0, $mostRecentRetentionCount, preserve_keys: true)));

        // Partition files by interval working backwards from the current time
        foreach ($retentionIntervals as $retentionInterval) {
            $filesPartitionedByInterval = [];
            $partitionStart = new DateTime('now');
            $partitionEnd = clone $partitionStart;
            $keepCount = $retentionInterval['max'];
            for ($i = 0; $i < $keepCount; $i++) {
                $partitionStart->sub($retentionInterval['interval']);
                $filesPartitionedByInterval[] = array_filter($filesToKeep, fn ($file) => $file['mtime'] > $partitionStart->getTimeStamp() && $file['mtime'] < $partitionEnd->getTimeStamp());
                $partitionEnd = clone $partitionStart;
            }

            // Mark the first (most recent backup) of each interval to be kept
            foreach ($filesPartitionedByInterval as $partition) {
                $partitionKeys = array_keys($partition);
                if (array_key_exists(0, $partitionKeys)) {
                    $firstKey = $partitionKeys[0];
                    $filesToKeep[$firstKey]['keep'] = true;
                }
            }
        }

        // Delete backup files that don't match the current retention rules
        foreach ($filesToKeep as $file) {
            if (!$file['keep']) {
                Storage::disk('backup')->delete($file['path']);
            }
        }
    }

    public static function getBackupFiles(string $prefix): array
    {
        $files = Storage::disk('backup')->files();
        $files = Arr::where($files, function ($value) use ($prefix) {
            return strpos($value, $prefix) === 0 && (str_ends_with($value, '.sql') || str_ends_with($value, '.zip'));
        });

        return $files;
    }
}
