<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;

class SettingsService
{
    public function __construct()
    {
        //
    }

    public function isJellyfinEnabled(): bool
    {
        // TODO: setting user_id should be null instead of -1 for user settings
        $isJellyfinEnabled = Setting::query()
            ->select('value', 'name')
            ->where('user_id', '=', -1)
            ->where('name', 'jellyfinEnabled')
            ->firstOrFail();

        return json_decode($isJellyfinEnabled->value);
    }

    public function getAnkiSettings(): Collection
    {
        $ankiSettingNames = ['ankiAutoAddCards', 'ankiShowNotifications'];

        $ankiSettings = Setting::query()
            ->select('value', 'name')
            ->where('user_id', '=', -1)
            ->whereIn('name', $ankiSettingNames)
            ->get()
            ->keyBy('name')
            ->map(function (Setting $item) {
                return json_decode($item->value);
            });

        return $ankiSettings;
    }

    public function getGlobalSettingsByName($settingNames): Collection
    {
        $settings = Setting::query()
            ->select('value', 'name')
            ->where('user_id', '=', -1)
            ->whereIn('name', $settingNames)
            ->get()
            ->keyBy('name')
            ->map(function ($setting) {
                return json_decode($setting->value);
            });

        return $settings;
    }

    public function updateGlobalSettings(Collection $settings): void
    {
        $settings->each(function (mixed $settingValue, string $settingName) {
            $setting = Setting::query()
                ->where('name', $settingName)
                ->where('user_id', -1)
                ->firstOrFail();

            $setting->value = json_encode($settingValue);
            $setting->save();
        });
    }

    public function getUserSettingsByName(User $user, Collection $settingNames): Collection
    {
        $settings = Setting::query()
            ->select('value', 'name')
            ->where('user_id', '=', $user->id)
            ->whereIn('name', $settingNames)
            ->get()
            ->keyBy('name')
            ->map(function ($setting) {
                return json_decode($setting->value);
            });

        return $settings;
    }

    public function updateOrCreateUserSettings(User $user, Collection $settings): void
    {
        $settings->each(function (mixed $settingValue, string $settingName) use ($user) {
            $setting = Setting::query()
                ->where('name', $settingName)
                ->where('user_id', $user->id)
                ->first();

            if (!$setting) {
                $setting = new Setting;
                $setting->user_id = $user->id;
                $setting->name = $settingName;
            }

            $setting->value = json_encode($settingValue);
            $setting->save();
        });
    }
}
