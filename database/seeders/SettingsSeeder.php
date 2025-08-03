<?php

namespace Database\Seeders;

use App\Models\Setting;
use Cron\CronExpression;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsSeeder extends Seeder
{
    /*
        This seeder adds default settings to the database.
    */
    public function run()
    {
        // deepl api settings
        $setting = Setting::where('name', 'deeplApiKey')->first();
        if (!$setting) {
            DB::table('settings')->insert([
                'name' => 'deeplApiKey',
                'value' => json_encode('00000000-aaaa-aaaa-aaaa-000aaaa000aa:00'),
            ]);
        }

        // deepl host settings
        $setting = Setting::where('name', 'deeplHost')->first();
        if (!$setting) {
            DB::table('settings')->insert([
                'name' => 'deeplHost',
                'value' => json_encode('https://api-free.deepl.com/v2'),
            ]);
        } elseif (json_decode($setting->value) === 'https://api-free.deepl.com/v2/translate') {
            $setting->value = json_encode('https://api-free.deepl.com/v2');
            $setting->save();
        }

        // libretranslate host settings
        $setting = Setting::where('name', 'libreTranslateHost')->first();
        if (!$setting) {
            DB::table('settings')->insert([
                'name' => 'libreTranslateHost',
                'value' => json_encode('http://libretranslate:5000/translate'),
            ]);
        }

        // jellyfin api settings
        $setting = Setting::where('name', 'jellyfinEnabled')->first();
        if (!$setting) {
            DB::table('settings')->insert([
                'name' => 'jellyfinEnabled',
                'value' => json_encode(false),
            ]);
        }

        $setting = Setting::where('name', 'jellyfinApiKey')->first();
        if (!$setting) {
            DB::table('settings')->insert([
                'name' => 'jellyfinApiKey',
                'value' => json_encode('00a0a000aaa00000a00aaaaa00a00a0a'),
            ]);
        }

        $setting = Setting::where('name', 'jellyfinHost')->first();
        if (!$setting) {
            DB::table('settings')->insert([
                'name' => 'jellyfinHost',
                'value' => json_encode('http://jellyfin:8096'),
            ]);
        }

        // anki api settings
        $setting = Setting::where('name', 'ankiConnectHost')->first();
        if (!$setting) {
            DB::table('settings')->insert([
                'name' => 'ankiConnectHost',
                'value' => json_encode('http://host.docker.internal:8765'),
            ]);
        }

        $setting = Setting::where('name', 'ankiAutoAddCards')->first();
        if (!$setting) {
            DB::table('settings')->insert([
                'name' => 'ankiAutoAddCards',
                'value' => json_encode(false),
            ]);
        }

        $setting = Setting::where('name', 'ankiUpdateCards')->first();
        if (!$setting) {
            DB::table('settings')->insert([
                'name' => 'ankiUpdateCards',
                'value' => json_encode(true),
            ]);
        }

        $setting = Setting::where('name', 'ankiShowNotifications')->first();
        if (!$setting) {
            DB::table('settings')->insert([
                'name' => 'ankiShowNotifications',
                'value' => json_encode(true),
            ]);
        }

        // review srs settings
        $setting = Setting::where('name', 'reviewIntervals')->first();
        if (!$setting) {
            DB::table('settings')->insert([
                'name' => 'reviewIntervals',
                'value' => json_encode([
                    '-7' => [0],
                    '-6' => [1],
                    '-5' => [2, 3],
                    '-4' => [6, 7, 8],
                    '-3' => [15, 16, 17, 18],
                    '-2' => [37, 38, 39, 40, 41, 42],
                    '-1' => [94, 95, 96, 97, 98, 99, 100, 101],
                ]),
            ]);
        }

        // db backup compression setting
        $setting = Setting::where('name', 'backupCompression')->first();
        if (!$setting) {
            DB::table('settings')->insert([
                'name' => 'backupCompression',
                'value' => json_encode(true),
            ]);
        }

        // db backup schedule
        $setting = Setting::where('name', 'backupInterval')->first();
        if (!$setting) {
            $cron = env('BACKUP_INTERVAL', '0 * * * *');
            if (CronExpression::isValidExpression($cron)) {
                DB::table('settings')->insert([
                    'name' => 'backupInterval',
                    'value' => json_encode($cron),
                ]);

            } else {
                $defaultBackupInterval = '0,30 * * * *';
                Log::info("A backup interval of ($cron) is invalid. Setting to default ($defaultBackupInterval)");
                DB::table('settings')->insert([
                    'name' => 'backupInterval',
                    'value' => json_encode($defaultBackupInterval),
                ]);
            }
        }
    }
}
