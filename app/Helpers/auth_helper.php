<?php

use App\Libraries\Acl;
use App\Libraries\Auth;
use Config\Services;

if (! function_exists('auth')) {
    /**
     * Shared Auth library instance.
     */
    function auth(): Auth
    {
        return Services::auth();
    }
}

if (! function_exists('acl')) {
    /**
     * Shared Acl library instance.
     */
    function acl(): Acl
    {
        return Services::acl();
    }
}

if (! function_exists('current_user')) {
    /**
     * The logged-in user record (array) or null.
     */
    function current_user(): ?array
    {
        return Services::auth()->user();
    }
}

if (! function_exists('user_id')) {
    function user_id(): ?int
    {
        return Services::auth()->id();
    }
}

if (! function_exists('can')) {
    /**
     * Permission check: can the current user do $action on $moduleCode?
     */
    function can(string $moduleCode, string $action = 'view'): bool
    {
        return Services::acl()->can($moduleCode, $action);
    }
}

if (! function_exists('activity_log')) {
    /**
     * Record an activity-log entry.
     */
    function activity_log(string $module, string $action, string $description = ''): void
    {
        Services::activityLogger()->log($module, $action, $description);
    }
}
