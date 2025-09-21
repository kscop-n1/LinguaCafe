<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\GetGlobalSettingsByNameRequest;
use App\Http\Requests\Settings\UpdateGlobalSettingsRequest;
use App\Services\SettingsService;

class GlobalSettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService
    ) {
        //
    }

    public function show(GetGlobalSettingsByNameRequest $request)
    {
        $settingNames = $request->validated('settingNames');

        $settings = $this->settingsService->getGlobalSettingsByName($settingNames);

        return response()->json([
            'data' => $settings,
        ]);
    }

    public function showJellyfin()
    {
        $isJellyfinEnabled = $this->settingsService->isJellyfinEnabled();

        return response()->json([
            'data' => $isJellyfinEnabled,
        ]);
    }

    public function showAnki()
    {
        $ankiSettings = $this->settingsService->getAnkiSettings();

        return response()->json([
            'data' => $ankiSettings,
        ]);
    }

    public function update(UpdateGlobalSettingsRequest $request)
    {
        // TODO: replace ->post() with ->validated(), and add validation for every setting
        $settings = $request->post('settings');
        $settings = collect($settings);

        $settings = $this->settingsService->updateGlobalSettings($settings);

        return response()->noContent();
    }
}
