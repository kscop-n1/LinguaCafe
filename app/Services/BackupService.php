<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupService {
    private ?string $dumpExecutable;
    private string $backupPath;
    private array $databaseConfig;

    public function __construct(?string $dumpExecutable = null, ?string $backupPath = null, ?array $databaseConfig = null)
    {
        $this->dumpExecutable = $dumpExecutable;
        $this->backupPath = $backupPath ?: storage_path('backup');
        $this->databaseConfig = $databaseConfig ?: [
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT', 3306),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'database' => env('DB_DATABASE'),
        ];
    }

    public function createBackup(): array
    {
        $timestamp = Carbon::now()->format('Y_m_d_H_i_s');
        $prefix = 'linguacafe_';
        $fileName = $prefix . $timestamp . '.sql';
        $fullFilePath = rtrim($this->backupPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        try {
            $executable = $this->resolveDumpExecutable();
            $this->assertBackupDirectoryWritable();

            $command = $this->buildDumpCommand($executable);
            $processResult = $this->runDumpProcess($command, $fullFilePath);
            $fileExists = is_file($fullFilePath);
            $fileSize = $fileExists ? filesize($fullFilePath) : 0;

            if ($processResult['exitCode'] !== 0 || !$fileExists || $fileSize === 0) {
                $this->removeInvalidBackupFile($fullFilePath);

                $result = [
                    'success' => false,
                    'filename' => $fileName,
                    'path' => $fullFilePath,
                    'exitCode' => $processResult['exitCode'],
                    'stderr' => $this->sanitizeDiagnosticText($processResult['stderr']),
                    'command' => $this->sanitizeCommandForLog($command),
                    'errorCode' => $processResult['exitCode'] === 0 ? 'BACKUP_FILE_INVALID' : 'BACKUP_EXPORT_FAILED',
                    'fileExists' => $fileExists,
                    'fileSize' => $fileSize,
                ];
                $this->logFailure($result);

                return $result;
            }

            $this->deleteOldBackups($prefix, $fileName);

            return [
                'success' => true,
                'filename' => $fileName,
                'path' => $fullFilePath,
                'exitCode' => 0,
                'fileSize' => $fileSize,
            ];
        } catch (\Throwable $exception) {
            $this->removeInvalidBackupFile($fullFilePath);

            $result = [
                'success' => false,
                'filename' => $fileName,
                'path' => $fullFilePath,
                'exitCode' => null,
                'stderr' => '',
                'command' => $this->dumpExecutable ?: 'mysqldump|mariadb-dump',
                'errorCode' => 'BACKUP_EXPORT_FAILED',
                'exception' => get_class($exception) . ': ' . $this->sanitizeDiagnosticText($exception->getMessage()),
                'fileExists' => is_file($fullFilePath),
                'fileSize' => is_file($fullFilePath) ? filesize($fullFilePath) : 0,
            ];
            $this->logFailure($result);

            return $result;
        }
    }

    private function resolveDumpExecutable(): string
    {
        if ($this->dumpExecutable !== null && $this->dumpExecutable !== '') {
            return $this->resolveExecutable($this->dumpExecutable);
        }

        foreach (['mysqldump', 'mariadb-dump'] as $candidate) {
            $resolved = $this->resolveExecutable($candidate, false);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        throw new \RuntimeException('No database dump executable was found. Expected mysqldump or mariadb-dump.');
    }

    private function resolveExecutable(string $executable, bool $throw = true): ?string
    {
        if (str_contains($executable, DIRECTORY_SEPARATOR)) {
            if (is_file($executable) && is_executable($executable)) {
                return $executable;
            }

            if ($throw) {
                throw new \RuntimeException('Configured database dump executable is missing or not executable.');
            }

            return null;
        }

        foreach (explode(PATH_SEPARATOR, getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin') as $path) {
            $candidate = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $executable;
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        if ($throw) {
            throw new \RuntimeException('Configured database dump executable is missing or not executable.');
        }

        return null;
    }

    private function assertBackupDirectoryWritable(): void
    {
        if (!is_dir($this->backupPath) && !@mkdir($this->backupPath, 0755, true) && !is_dir($this->backupPath)) {
            throw new \RuntimeException('Backup destination directory could not be created.');
        }

        if (!is_writable($this->backupPath)) {
            throw new \RuntimeException('Backup destination directory is not writable.');
        }
    }

    private function buildDumpCommand(string $executable): array
    {
        return [
            $executable,
            '--no-tablespaces',
            '--ssl=0',
            '-h',
            (string) $this->databaseConfig['host'],
            '-P',
            (string) $this->databaseConfig['port'],
            '-u',
            (string) $this->databaseConfig['username'],
            (string) $this->databaseConfig['database'],
        ];
    }

    private function runDumpProcess(array $command, string $fullFilePath): array
    {
        $descriptorSpec = [
            1 => ['file', $fullFilePath, 'w'],
            2 => ['pipe', 'w'],
        ];

        $environment = $_ENV;
        $password = $this->databaseConfig['password'] ?? null;
        if ($password !== null && $password !== '') {
            $environment['MYSQL_PWD'] = (string) $password;
        }

        $process = proc_open($command, $descriptorSpec, $pipes, base_path(), $environment);
        if (!is_resource($process)) {
            throw new \RuntimeException('Database dump process could not be started.');
        }

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return [
            'exitCode' => proc_close($process),
            'stderr' => $stderr === false ? '' : $stderr,
        ];
    }

    private function removeInvalidBackupFile(string $fullFilePath): void
    {
        if (is_file($fullFilePath)) {
            @unlink($fullFilePath);
        }
    }

    private function logFailure(array $result): void
    {
        Log::error('Database backup export failed.', [
            'errorCode' => $result['errorCode'] ?? 'BACKUP_EXPORT_FAILED',
            'command' => $result['command'] ?? null,
            'exitCode' => $result['exitCode'] ?? null,
            'stderr' => $result['stderr'] ?? null,
            'outputPath' => $result['path'] ?? null,
            'fileExists' => $result['fileExists'] ?? null,
            'fileSize' => $result['fileSize'] ?? null,
            'exception' => $result['exception'] ?? null,
        ]);
    }

    private function sanitizeCommandForLog(array $command): string
    {
        return implode(' ', array_map('escapeshellarg', $command));
    }

    private function sanitizeDiagnosticText(string $text): string
    {
        $password = $this->databaseConfig['password'] ?? '';
        if ($password !== '') {
            $text = str_replace((string) $password, '[redacted]', $text);
        }

        return trim($text);
    }

    private function deleteOldBackups($prefix, ?string $currentFileName = null): void
    {
        $maxBackups = intval(env('MAX_SAVED_BACKUPS', 8));
        if ($maxBackups < 1) {
            return;
        }

        $files = $this->getBackupFiles($prefix);
        while (count($files) > $maxBackups) {
            $fileToDelete = array_shift($files);
            if ($fileToDelete !== $currentFileName) {
                Storage::disk('backup')->delete($fileToDelete);
            }
        }
    }

    private function getBackupFiles($prefix): array 
    {
        $files = Storage::disk('backup')->files();
        $files = Arr::where($files, function ($value) use($prefix) {
            return strpos($value, $prefix) === 0 && str_contains($value, '.sql');
        });
        sort($files);

        return $files;
    }
}
