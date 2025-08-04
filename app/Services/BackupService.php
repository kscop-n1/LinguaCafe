<?php

namespace App\Services;

use App\Models\Setting;
use Cron\CronExpression;
use DateInterval;
use DateTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

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

    private function deleteOldBackups(string $prefix): void
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
                'mtime' => Storage::disk('backup')->lastModified($path),
                'keep' => false,
            ];
        }, $this->getBackupFiles($prefix));

        // Sort files by last modified time (newest first)
        usort($filesToKeep, fn ($fileA, $fileB) => $fileA['mtime'] < $fileB['mtime'] ? 1 : -1);

        // Mark 'X' most recent backups to be kept
        $mostRecentRetentionCount = Setting::where('name', 'backupRetainMostRecent')->first()->value;
        $markKeep = function ($file) {
            $file['keep'] = true;

            return $file;
        };
        $filesToKeep = array_replace($filesToKeep, array_map($markKeep, array_slice($filesToKeep, 0, $mostRecentRetentionCount, preserve_keys: true)));

        // Mark backups to be kept by intervals
        foreach ($retentionIntervals as $retentionInterval) {
            // Partition files by interval
            $intervalPartitions = [];
            $needleDateTime = new DateTime;
            $nextNeedleDateTime = new DateTime;
            $keepCount = $retentionInterval['max'];
            for ($i = 0; $i < $keepCount; $i++) {
                $nextNeedleDateTime->sub($retentionInterval['interval']);
                $intervalPartitions[] = array_filter($filesToKeep, fn ($file) => $file['mtime'] < $needleDateTime->getTimeStamp() && $file['mtime'] > $nextNeedleDateTime->getTimeStamp());
                $needleDateTime = clone $nextNeedleDateTime;
            }

            foreach ($intervalPartitions as $key => $partition) {
                $partitionKeys = array_keys($partition);
                if (array_key_exists(0, $partitionKeys)) {
                    $firstKey = $partitionKeys[0];
                    $filesToKeep[$firstKey]['keep'] = true;
                }
            }
        }

        // Delete marked backup files
        foreach ($filesToKeep as $file) {
            if ($file['keep'] == false) {
                Storage::disk('backup')->delete($file['path']);
            }
        }
    }

    private function getBackupFiles(string $prefix): array
    {
        $files = Storage::disk('backup')->files();
        $files = Arr::where($files, function ($value) use ($prefix) {
            return strpos($value, $prefix) === 0 && (str_ends_with($value, '.sql') || str_ends_with($value, '.zip'));
        });

        return $files;
    }

    public static function updateBackupSchedule(string $cron)
    {
        if (CronExpression::isValidExpression($cron)) {
            Schedule::command('app:create-backup')->cron($cron)->withoutOverlapping();
        } else {
            throw new InvalidArgumentException("The provided cron expression ($cron) is invalid. Please provide a valid cron expression.");
        }
    }
}
