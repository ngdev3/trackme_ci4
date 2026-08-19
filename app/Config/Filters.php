<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;
use App\Filters\AuthFilter;
use App\Filters\PermissionFilter;
use App\Filters\GuestFilter;
use App\Filters\AutoSetup;
use App\Filters\MustChangePassword;
use App\Filters\FirmPermFilter;
use App\Filters\RequireCompany;
use App\Filters\SuperAdminFilter;
use App\Filters\UserActionLogFilter;
use App\Filters\ProFeature;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>>
     *
     * [filter_name => classname]
     * or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'auth'          => AuthFilter::class,
        'permission'    => PermissionFilter::class,
        'guest'         => GuestFilter::class,
        'autosetup'     => AutoSetup::class,
        'mustchange'    => MustChangePassword::class,
        'requirecompany'=> RequireCompany::class,
        'superadmin'    => SuperAdminFilter::class,
        'firmperm'      => FirmPermFilter::class,
        'useractionlog' => UserActionLogFilter::class,
        'pro'           => ProFeature::class,
        'feature'       => \App\Filters\FeatureGate::class,
        'apifeature'    => \App\Filters\ApiFeature::class,
        'sublifecycle'  => \App\Filters\SubscriptionLifecycle::class,
        'apitoggle'     => \App\Filters\ApiToggle::class,
    ];

    /**
     * List of special required filters.
     *
     * The filters listed here are special. They are applied before and after
     * other kinds of filters, and always applied even if a route does not exist.
     *
     * Filters set by default provide framework functionality. If removed,
     * those functions will no longer work.
     *
     * @see https://codeigniter.com/user_guide/incoming/filters.html#provided-filters
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps', // Force Global Secure Requests
            'pagecache',  // Web Page Caching
        ],
        'after' => [
            'pagecache',   // Web Page Caching
            'performance', // Performance Metrics
            // 'toolbar' (CodeIgniter debug toolbar) removed — hides the CI logo/bar bottom-right.
        ],
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array{
     *     before: array<string, array{except: list<string>|string}>|list<string>,
     *     after: array<string, array{except: list<string>|string}>|list<string>
     * }
     */
    public array $globals = [
        'before' => [
            'autosetup', // keep DB schema in sync on CLI-less deploys (runs pending migrations)
            'csrf' => ['except' => ['api/*', 'subscription/webhook']],
            'invalidchars',
            'mustchange',     // force a pending password change before anything else
            'requirecompany', // social sign-ups must create a company before proceeding
            'sublifecycle' => ['except' => ['api/*', 'subscription/webhook', 'login', 'logout', 'auth/*']],
        ],
        'after' => [
            'secureheaders',
            'useractionlog',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'POST' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        // Package feature gate. Blocks direct-URL access to modules the active
        // package does not include (the FeatureGate filter maps each path to a
        // feature and checks hasFeature()). This is the single backend seam —
        // controllers/menus/views all read the same hasFeature() rule.
        'feature' => ['before' => [
            'calculator', 'calculator/*',   // → calculator        (Standard+)
            'passwords', 'passwords/*',      // → password_manager  (Standard+)
            'reminders', 'reminders/*',      // → reminder/calendar (Plus+)
            'notes', 'notes/*',              // → notes             (Premium)
            'company/trash',                 // → trash             (Plus+)
        ]],

        // API package gate (bearer-token aware). No feature-gated API routes at
        // present (the inventory module was removed).
        'apifeature' => ['before' => [
        ]],

        // Super-Admin "kill switch": if an endpoint has been toggled inactive in
        // the API Monitor, the live app receives a 503 instead of the handler.
        // Fails open (never blocks on a registry error).
        'apitoggle' => ['before' => [
            'api/v1', 'api/v1/*',
        ]],

        // NOTE: the former 'pro' URI gate is intentionally removed. Under the
        // package model, Rokadh Parcha PDF/print, reports, attachments,
        // statement download and opening balance belong to EVERY package (incl.
        // Basic and the expired/Basic floor), so they are no longer gated. The
        // 'pro' alias remains defined for backward compatibility only.
    ];
}
