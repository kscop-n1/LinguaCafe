<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\BackupService;
use Database\Seeders\SettingsSeeder;
use DateInterval;
use DateTime;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertFileExists;

class BackupPruning extends TestCase
{
    /**
     * Test backup file detection
     */
    public function test_delete_old_backups(): void
    {
        $this->seed(SettingsSeeder::class);

        $this->assertDatabaseHas('settings', [
            'name' => 'backupRetainMostRecent',
            'name' => 'backupRetainDaily',
            'name' => 'backupRetainWeekly',
            'name' => 'backupRetainMonthly',
            'name' => 'backupRetainYearly',
        ]);

        // Generate test backup files, one per hour for the last 'X' years
        $prefix = 'lc_test_';
        $retentionYears = Setting::select('value')->where('name', 'backupRetainYearly')->value('value');
        $now = new DateTime;
        $startDate = (clone $now)->sub(DateInterval::createFromDateString("{$retentionYears} years"));
        $interval = DateInterval::createFromDateString('1 hour');
        $fileIndex = 1;
        while ($now > $startDate) {
            $filename = "{$prefix}{$fileIndex}.sql.zip";
            Storage::disk('backup')->put($filename, '');
            touch(Storage::disk('backup')->path($filename), $now->getTimestamp());
            $fileIndex += 1;
            $now->sub($interval);
        }

        $this->expectsDatabaseQueryCount(5);
        BackupService::deleteOldBackups($prefix);

        $backupFilesAfterPruning = BackupService::getBackupFiles($prefix);

        // These results assume the follow retention values
        // Latest: 5
        // Daily: 5
        // Weekly: 3
        // Monthly: 6
        // Yearly: 3
        assertCount(18, $backupFilesAfterPruning);
        $filesToKeep = [
            'lc_test_1.sql.zip',
            'lc_test_2.sql.zip',
            'lc_test_3.sql.zip',
            'lc_test_4.sql.zip',
            'lc_test_5.sql.zip',
            'lc_test_25.sql.zip',
            'lc_test_49.sql.zip',
            'lc_test_73.sql.zip',
            'lc_test_97.sql.zip',
            'lc_test_169.sql.zip',
            'lc_test_337.sql.zip',
            'lc_test_745.sql.zip',
            'lc_test_1465.sql.zip',
            'lc_test_2209.sql.zip',
            'lc_test_2929.sql.zip',
            'lc_test_3673.sql.zip',
            'lc_test_8761.sql.zip',
            'lc_test_17545.sql.zip',
        ];

        foreach ($filesToKeep as $file) {
            assertFileExists(Storage::disk('backup')->path($file));
        }
    }
}
