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

        // --- TrackmeNew migration filters (replace MY_Controller guards) ---
        'adminAuth'  => \App\Filters\AdminAuthFilter::class,
        'fyContext'  => \App\Filters\FyContextFilter::class,
        'traffic'    => \App\Filters\TrafficFilter::class,
        'rbac'       => \App\Filters\RbacFilter::class,
        'salaryCron' => \App\Filters\SalaryCronFilter::class,
        'apiAuth'    => \App\Filters\ApiAuthFilter::class,
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
            'toolbar',     // Debug Toolbar
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
            // 'honeypot',
            // CSRF only checks unsafe verbs (POST/PUT/PATCH/DELETE); GET pages are
            // untouched. Excepted: the token-authenticated mobile API and the
            // key-guarded public webhooks/downloads, which are not browser-form
            // flows and carry their own auth. Token is injected app-wide via
            // csrf_meta() + assets/js/csrf.js (AJAX header) and csrf_field() (forms).
            'csrf' => ['except' => [
                'api_services/*',
                'farmer_capture/*',
                'app_download/*',
                'web_push/*',
                'letter_verify/*',
            ]],
            // 'invalidchars',
        ],
        'after' => [
            // 'honeypot',
            'secureheaders', // X-Frame-Options, X-Content-Type-Options(nosniff), Referrer-Policy, etc.
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
        // --- TrackmeNew: CI3 MY_Controller guard chain, now ON for admin/* -----
        // (P2). AdminAuthFilter internally excepts admin/auth/* so login stays
        // public. Order matters: auth -> context -> rbac.
        'adminAuth' => ['before' => ['admin/*', 'master/*', 'task', 'task/*']],
        'fyContext' => ['before' => ['admin/*', 'master/*', 'task', 'task/*']],
        'rbac'      => ['before' => ['admin/*', 'master/*', 'task', 'task/*']],
        // traffic + salaryCron are wired but deferred to their phases (P3/P5):
        // 'traffic'    => ['before' => ['admin/*']],
        // 'salaryCron' => ['before' => ['admin/*']],
        // apiAuth enables with the mobile API (P7):
        // 'apiAuth'    => ['before' => ['api_services/*']],
    ];
}
