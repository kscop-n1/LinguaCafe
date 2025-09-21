<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\GetUserSettingsByNameRequest;
use App\Http\Requests\Settings\updateOrCreateUserSettingsRequest;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Auth;

class UserSettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService
    ) {
        //
    }

    public function show(GetUserSettingsByNameRequest $request)
    {
        $user = Auth::user();
        $settingNames = $request->validated('settingNames');
        $settingNames = collect($settingNames);

        $settings = $this->settingsService->getUserSettingsByName($user, $settingNames);

        return response()->json($settings, 200);
    }

    public function updateOrCreate(updateOrCreateUserSettingsRequest $request)
    {
        $user = Auth::user();
        $settings = $request->validated('settings');
        $settings = collect($settings);

        $settings = $this->settingsService->updateOrCreateUserSettings($user, $settings);

        return response()->noContent();
    }
}
