<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\GetGlobalSettingsByNameRequest;
use App\Http\Requests\Settings\GetUserSettingsByNameRequest;
use App\Http\Requests\Settings\UpdateGlobalSettingsRequest;
use App\Http\Requests\Settings\updateOrCreateUserSettingsRequest;
use App\Services\BackupService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService
    ) {
        //
    }

    public function isJellyfinEnabled()
    {
        $isJellyfinEnabled = $this->settingsService->isJellyfinEnabled();

        return response()->json([
            'data' => $isJellyfinEnabled,
        ]);
    }

    public function getAnkiSettings()
    {
        $ankiSettings = $this->settingsService->getAnkiSettings();

        return response()->json([
            'data' => $ankiSettings,
        ]);
    }

    public function getGlobalSettingsByName(GetGlobalSettingsByNameRequest $request)
    {
        $settingNames = $request->validated('settingNames');

        $settings = $this->settingsService->getGlobalSettingsByName($settingNames);

        return response()->json([
            'data' => $settings,
        ]);
    }

    public function updateGlobalSettings(UpdateGlobalSettingsRequest $request)
    {
        $settings = $request->validated('settings');
        $settings = collect($settings);

        try {
            if ($settings->has('backupInterval')) {
                BackupService::updateBackupSchedule($settings['backupInterval']);
            }

            $settings = $this->settingsService->updateGlobalSettings($settings);

        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }

        return response()->noContent();
    }

    public function getUserSettingsByName(GetUserSettingsByNameRequest $request)
    {
        $user = Auth::user();
        $settingNames = $request->validated('settingNames');
        $settingNames = collect($settingNames);

        $settings = $this->settingsService->getUserSettingsByName($user, $settingNames);

        return response()->json($settings, 200);
    }

    public function updateOrCreateUserSettings(updateOrCreateUserSettingsRequest $request)
    {
        $user = Auth::user();
        $settings = $request->validated('settings');
        $settings = collect($settings);

        $settings = $this->settingsService->updateOrCreateUserSettings($user, $settings);

        return response()->noContent();
    }
}
