<?php

use App\Models\SettingModel;
use Config\Services;

if (! function_exists('settings')) {
    /**
     * Shared, request-cached settings service (global + current-user merged).
     */
    function settings(): \App\Libraries\Settings
    {
        return Services::settings();
    }
}

if (! function_exists('setting')) {
    /**
     * Read an effective setting: the current user's personal value if present,
     * otherwise the global value, otherwise $default.
     */
    function setting(string $key, $default = null)
    {
        return Services::settings()->get($key, $default);
    }
}

if (! function_exists('global_setting')) {
    /**
     * Read a global (web-app) setting only, ignoring personal overrides.
     */
    function global_setting(string $key, $default = null)
    {
        return (new SettingModel())->get($key, 0, $default);
    }
}
