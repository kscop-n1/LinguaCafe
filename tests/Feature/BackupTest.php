<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_service_accepts_successful_dump_with_non_empty_file(): void
    {
        $backupPath = $this->makeBackupDirectory();
        $executable = $this->makeDumpExecutable("#!/bin/sh\necho '-- LinguaCafe SQL dump'\nexit 0\n");

        $result = $this->makeBackupService($executable, $backupPath)->createBackup();

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['exitCode']);
        $this->assertFileExists($result['path']);
        $this->assertGreaterThan(0, filesize($result['path']));
        $this->assertStringEndsWith('.sql', $result['filename']);
    }

    public function test_backup_service_rejects_dump_exit_code_two_and_removes_partial_file(): void
    {
        $backupPath = $this->makeBackupDirectory();
        $executable = $this->makeDumpExecutable("#!/bin/sh\necho 'partial dump'\necho 'simulated mysqldump failure with secret-password' >&2\nexit 2\n");

        $result = $this->makeBackupService($executable, $backupPath)->createBackup();

        $this->assertFalse($result['success']);
        $this->assertSame(2, $result['exitCode']);
        $this->assertSame('BACKUP_EXPORT_FAILED', $result['errorCode']);
        $this->assertStringContainsString('simulated mysqldump failure', $result['stderr']);
        $this->assertStringNotContainsString('secret-password', $result['stderr']);
        $this->assertStringContainsString('--ssl=0', $result['command']);
        $this->assertStringNotContainsString('secret-password', $result['command']);
        $this->assertFileDoesNotExist($result['path']);
    }

    public function test_backup_service_fails_when_dump_executable_is_missing(): void
    {
        $backupPath = $this->makeBackupDirectory();

        $result = $this->makeBackupService($backupPath . '/missing-dump', $backupPath)->createBackup();

        $this->assertFalse($result['success']);
        $this->assertSame('BACKUP_EXPORT_FAILED', $result['errorCode']);
        $this->assertStringContainsString('missing or not executable', $result['exception']);
        $this->assertFileDoesNotExist($result['path']);
    }

    public function test_backup_service_reports_invalid_credentials_or_connection_failure(): void
    {
        $backupPath = $this->makeBackupDirectory();
        $executable = $this->makeDumpExecutable("#!/bin/sh\necho 'Access denied for user' >&2\nexit 2\n");

        $result = $this->makeBackupService($executable, $backupPath)->createBackup();

        $this->assertFalse($result['success']);
        $this->assertSame(2, $result['exitCode']);
        $this->assertStringContainsString('Access denied', $result['stderr']);
        $this->assertFileDoesNotExist($result['path']);
    }

    public function test_backup_service_fails_when_destination_directory_is_not_writable_or_creatable(): void
    {
        $basePath = storage_path('framework/testing/backups_' . uniqid());
        file_put_contents($basePath, 'not a directory');
        $executable = $this->makeDumpExecutable("#!/bin/sh\necho '-- dump'\nexit 0\n");

        $result = $this->makeBackupService($executable, $basePath)->createBackup();

        $this->assertFalse($result['success']);
        $this->assertSame('BACKUP_EXPORT_FAILED', $result['errorCode']);
        $this->assertStringContainsString('destination directory', $result['exception']);
    }

    public function test_backup_service_rejects_empty_output_even_with_zero_exit_code(): void
    {
        $backupPath = $this->makeBackupDirectory();
        $executable = $this->makeDumpExecutable("#!/bin/sh\nexit 0\n");

        $result = $this->makeBackupService($executable, $backupPath)->createBackup();

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['exitCode']);
        $this->assertSame('BACKUP_FILE_INVALID', $result['errorCode']);
        $this->assertSame(0, $result['fileSize']);
        $this->assertFileDoesNotExist($result['path']);
    }

    public function test_backup_create_endpoint_returns_success_contract(): void
    {
        $this->app->instance(BackupService::class, new class extends BackupService {
            public function __construct() {}

            public function createBackup(): array
            {
                return [
                    'success' => true,
                    'filename' => 'linguacafe_2026_06_06_12_00_00.sql',
                ];
            }
        });

        $response = $this->actingAs($this->createAdminUser())->getJson('/backups/create');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'filename' => 'linguacafe_2026_06_06_12_00_00.sql',
            ])
            ->assertJsonMissing(['exitCode']);
    }

    public function test_backup_create_endpoint_returns_non_2xx_structured_error_on_export_failure(): void
    {
        $this->app->instance(BackupService::class, new class extends BackupService {
            public function __construct() {}

            public function createBackup(): array
            {
                return [
                    'success' => false,
                    'errorCode' => 'BACKUP_EXPORT_FAILED',
                    'exitCode' => 2,
                ];
            }
        });

        $response = $this->actingAs($this->createAdminUser())->getJson('/backups/create');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'BACKUP_EXPORT_FAILED',
                    'message' => 'Database backup could not be created.',
                ],
            ])
            ->assertJsonMissing(['exitCode']);
    }

    private function makeBackupService(string $executable, string $backupPath): BackupService
    {
        return new BackupService($executable, $backupPath, [
            'host' => 'database.example',
            'port' => 3306,
            'username' => 'linguacafe',
            'password' => 'secret-password',
            'database' => 'linguacafe',
        ]);
    }

    private function makeBackupDirectory(): string
    {
        $path = storage_path('framework/testing/backups_' . uniqid());
        mkdir($path, 0755, true);

        return $path;
    }

    private function makeDumpExecutable(string $contents): string
    {
        $path = storage_path('framework/testing/dump_' . uniqid() . '.sh');
        file_put_contents($path, $contents);
        chmod($path, 0755);

        return $path;
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'is_admin' => true,
        ]);
    }
}
