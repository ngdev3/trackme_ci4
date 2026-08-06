<?php

namespace Modules\Api\Controllers;

use App\Models\SettingModel;

/**
 * App metadata for the mobile client — currently the latest-release info used by
 * the in-app "App Update" screen. Public (no token needed) so the check can run
 * before login and on a cold start.
 *
 *   GET /api/v1/app/version[?platform=android&build=<int>&version=<str>]
 *
 * The canonical values live in the global settings store (settings, user_id 0)
 * so they can be bumped on each release without a code deploy; the constants
 * below are the fallback defaults when a key hasn't been set.
 */
class AppApiController extends BaseApiController
{
    /** Fallback defaults (used until overridden in the settings store). */
    private const DEFAULT_LATEST_VERSION = '2.0.10';
    private const DEFAULT_LATEST_BUILD   = 20010;
    // Minimum supported build — clients below this should be force-updated. Kept
    // at 1 for now (force update is a future rollout); bump when it goes live.
    private const DEFAULT_MIN_BUILD    = 1;
    private const DEFAULT_MIN_VERSION  = '1.0.0';
    private const DEFAULT_STORE_URL    = 'https://play.google.com/store/apps/details?id=com.crind.hissabkitaab';

    public function version()
    {
        $settings = new SettingModel();

        $latestVersion = (string) $settings->get('app_latest_version', 0, self::DEFAULT_LATEST_VERSION);
        $latestBuild   = (int) $settings->get('app_latest_build', 0, self::DEFAULT_LATEST_BUILD);
        $minBuild      = (int) $settings->get('app_min_build', 0, self::DEFAULT_MIN_BUILD);
        $minVersion    = (string) $settings->get('app_min_version', 0, self::DEFAULT_MIN_VERSION);
        $storeUrl      = (string) $settings->get('app_store_url', 0, self::DEFAULT_STORE_URL);
        $releaseNotes  = (string) $settings->get('app_release_notes', 0, '');

        // The caller may pass its installed build so the server can tell it
        // directly whether an update is available (and, in future, forced).
        $clientBuild = (int) ($this->request->getGet('build') ?? 0);

        $updateAvailable = $clientBuild > 0 ? $clientBuild < $latestBuild : null;
        $forceUpdate     = $clientBuild > 0 ? $clientBuild < $minBuild : false;

        return $this->respond([
            'status'           => 'ok',
            'platform'         => (string) ($this->request->getGet('platform') ?? 'android'),
            'latest_version'   => $latestVersion,
            'latest_build'     => $latestBuild,
            'min_version'      => $minVersion,
            'min_build'        => $minBuild,
            'store_url'        => $storeUrl,
            'release_notes'    => $releaseNotes,
            'update_available' => $updateAvailable,
            'force_update'     => $forceUpdate,
        ]);
    }
}
