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
                command: 'mysqldump --skip-ssl --no-tablespaces' . $host . $port . $username . $password . $database . ' | gzip > ' . $fullFilePath,
                result_code: $exitCode
            );
        } else {
            exec(
                command: 'mysqldump --skip-ssl --no-tablespaces' . $host . $port . $username . $password . $database . ' > ' . $fullFilePath,
                result_code: $exitCode
            );
        }

        if ($exitCode !== 0) {
            throw new \Exception('Backup process failed.');
        }
    }

    private function markMostRecentFiles(array &$files, int $n)
    {
        usort($files, fn ($fileA, $fileB) => $fileA['mtime'] < $fileB['mtime'] ? 1 : -1);

        $markKeep = function ($file) {
            $file['keep'] = true;

            return $file;
        };

        $files = array_replace(
            $files,
            array_map(
                $markKeep,
                array_slice($files, 0, $n, preserve_keys: true)
            )
        );
    }

    private function filterFilesByInterval(array $files, int $start, int $end): array
    {
        return array_filter(
            $files,
            fn ($file) => $file['mtime'] > $start && $file['mtime'] < $end
        );
    }

    private function partitionFilesByInterval(array $files, int $maxIntervalCount, DateInterval $interval)
    {
        $filesPartitionedByInterval = [];

        // Partition files by interval
        $partitionStart = new DateTime('now');
        $partitionEnd = clone $partitionStart;
        for ($i = 0; $i < $maxIntervalCount; $i++) {
            $partitionStart->sub($interval);
            $filesPartitionedByInterval[] = $this->filterFilesByInterval($files, $partitionStart->getTimestamp(), $partitionEnd->getTimestamp());
            $partitionEnd = clone $partitionStart;
        }

        return $filesPartitionedByInterval;
    }

    public function deleteOldBackups($prefix)
    {
        $retentionIntervals = [
            ['max' => Setting::where('name', 'backupRetainYearly')->first()->decode(), 'interval' => DateInterval::createFromDateString('1 year')],
            ['max' => Setting::where('name', 'backupRetainMonthly')->first()->decode(), 'interval' => DateInterval::createFromDateString('1 month')],
            ['max' => Setting::where('name', 'backupRetainWeekly')->first()->decode(), 'interval' => DateInterval::createFromDateString('1 week')],
            ['max' => Setting::where('name', 'backupRetainDaily')->first()->decode(), 'interval' => DateInterval::createFromDateString('1 day')],
        ];

        $filesToKeep = array_map(function ($path) {
            return [
                'path' => $path,
                'mtime' => Storage::disk('backup')->lastModified($path), // when the file was created/last modified
                'keep' => false, // whether the file should be kept or deleted
            ];
        }, $this->getBackupFiles($prefix));

        $mostRecentRetentionCount = Setting::where('name', 'backupRetainMostRecent')->first()->value;
        $this->markMostRecentFiles($filesToKeep, $mostRecentRetentionCount);

        foreach ($retentionIntervals as $retentionInterval) {
            $filesPartitionedByInterval = $this->partitionFilesByInterval($filesToKeep, $retentionInterval['max'], $retentionInterval['interval']);

            // Mark the first (most recent backup) of each interval to be kept
            foreach ($filesPartitionedByInterval as $partition) {
                // Note: the first file of each partition can only be safely selected this way because the
                // $filesToKeep are already sorted (by mtime) in place by the call to $this->markMostRecentFiles
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

    public function getBackupFiles(string $prefix): array
    {
        $files = Storage::disk('backup')->files();
        $files = Arr::where($files, function ($value) use ($prefix) {
            return strpos($value, $prefix) === 0 && (str_ends_with($value, '.sql') || str_ends_with($value, '.zip'));
        });

        return $files;
    }
}
