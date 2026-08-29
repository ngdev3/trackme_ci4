<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Stores the default settings for the ContentSecurityPolicy, if you
 * choose to use it. The values here will be read in and set as defaults
 * for the site. If needed, they can be overridden on a page-by-page basis.
 *
 * Suggested reference for explanations:
 *
 * @see https://www.html5rocks.com/en/tutorials/security/content-security-policy/
 */
class ContentSecurityPolicy extends BaseConfig
{
    // -------------------------------------------------------------------------
    // Broadbrush CSP management
    // -------------------------------------------------------------------------

    /**
     * Report-only for the initial rollout (F-5): the browser REPORTS violations
     * but does NOT block anything, so enabling this cannot break a page. Watch the
     * console across the app, tighten the host lists / drop 'unsafe-inline' below,
     * and only then flip this to false to enforce.
     */
    public bool $reportOnly = true;

    /**
     * Specifies a URL where a browser will send reports
     * when a content security policy is violated.
     */
    public ?string $reportURI = null;

    /**
     * Specifies a reporting endpoint to which violation reports ought to be sent.
     */
    public ?string $reportTo = null;

    /**
     * Instructs user agents to rewrite URL schemes, changing
     * HTTP to HTTPS. This directive is for websites with
     * large numbers of old URLs that need to be rewritten.
     */
    public bool $upgradeInsecureRequests = false;

    // -------------------------------------------------------------------------
    // CSP DIRECTIVES SETTINGS
    // NOTE: once you set a policy to 'none', it cannot be further restricted
    // -------------------------------------------------------------------------

    /**
     * Will default to `'self'` if not overridden
     *
     * @var list<string>|string|null
     */
    public $defaultSrc;

    /**
     * Lists allowed scripts' URLs. 'unsafe-inline'/'unsafe-eval' keep the app's
     * existing inline scripts working while report-only (tighten later with nonces).
     * External: Cashfree checkout SDK + Google Translate widget.
     *
     * @var list<string>|string
     */
    public $scriptSrc = [
        'self',
        "'unsafe-inline'",
        "'unsafe-eval'",
        'https://sdk.cashfree.com',
        'https://translate.google.com',
        'https://translate.googleapis.com',
        'https://www.google.com',
        'https://www.gstatic.com',
    ];

    /**
     * Valid sources for <script> ELEMENTS. Mirrors scriptSrc (CI4 emits this as a
     * separate directive that would otherwise override script-src at 'self').
     *
     * @var list<string>|string
     */
    public array|string $scriptSrcElem = [
        'self',
        "'unsafe-inline'",
        'https://sdk.cashfree.com',
        'https://translate.google.com',
        'https://translate.googleapis.com',
        'https://www.google.com',
        'https://www.gstatic.com',
    ];

    /**
     * Valid sources for inline event handlers (onclick=…) and javascript: URLs.
     * The app uses inline handlers, so 'unsafe-inline' is required.
     *
     * @var list<string>|string
     */
    public array|string $scriptSrcAttr = ["'unsafe-inline'"];

    /**
     * Lists allowed stylesheets' URLs. Inline styles are used throughout the views;
     * Google Fonts serves the stylesheet from fonts.googleapis.com.
     *
     * @var list<string>|string
     */
    public $styleSrc = [
        'self',
        "'unsafe-inline'",
        'https://fonts.googleapis.com',
        'https://www.gstatic.com',
    ];

    /**
     * Valid sources for <style>/<link> stylesheet ELEMENTS. Mirrors styleSrc.
     *
     * @var list<string>|string
     */
    public array|string $styleSrcElem = [
        'self',
        "'unsafe-inline'",
        'https://fonts.googleapis.com',
        'https://www.gstatic.com',
    ];

    /**
     * Valid sources for inline `style="…"` ATTRIBUTES, used throughout the views.
     *
     * @var list<string>|string
     */
    public array|string $styleSrcAttr = ["'unsafe-inline'"];

    /**
     * Defines the origins from which images can be loaded. data: covers inline
     * avatars/QR previews; api.qrserver.com renders payee QR codes; the Google
     * hosts serve the Translate widget's flag icons.
     *
     * @var list<string>|string
     */
    public $imageSrc = [
        'self',
        'data:',
        'https://api.qrserver.com',
        'https://fonts.gstatic.com',
        'https://www.google.com',
        'https://www.gstatic.com',
        'https://translate.googleapis.com',
    ];

    /**
     * Restricts the URLs that can appear in a page's `<base>` element.
     *
     * Will default to self if not overridden
     *
     * @var list<string>|string|null
     */
    public $baseURI;

    /**
     * Lists the URLs for workers and embedded frame contents
     *
     * @var list<string>|string
     */
    public $childSrc = 'self';

    /**
     * Limits the origins that you can connect to (via XHR, WebSockets,
     * EventSource). The app fetches from: weather (open-meteo), IP geolocation
     * (geojs), IFSC lookup (razorpay), QR (qrserver) and the Cashfree SDK.
     *
     * @var list<string>|string
     */
    public $connectSrc = [
        'self',
        'https://api.open-meteo.com',
        'https://geocoding-api.open-meteo.com',
        'https://get.geojs.io',
        'https://ifsc.razorpay.com',
        'https://api.qrserver.com',
        'https://sdk.cashfree.com',
        'https://translate.googleapis.com',
    ];

    /**
     * Specifies the origins that can serve web fonts (Google Fonts files; data:
     * covers any inline/base64 font).
     *
     * @var list<string>|string
     */
    public $fontSrc = [
        'self',
        'data:',
        'https://fonts.gstatic.com',
    ];

    /**
     * Lists valid endpoints for submission from `<form>` tags.
     *
     * @var list<string>|string
     */
    public $formAction = 'self';

    /**
     * Specifies the sources that can embed the current page.
     * This directive applies to `<frame>`, `<iframe>`, `<embed>`,
     * and `<applet>` tags. This directive can't be used in
     * `<meta>` tags and applies only to non-HTML resources.
     *
     * @var list<string>|string|null
     */
    public $frameAncestors;

    /**
     * The frame-src directive restricts the URLs which may be loaded into nested
     * browsing contexts — the Cashfree checkout iframe and the Google Translate
     * widget.
     *
     * @var list<string>|string|null
     */
    public $frameSrc = [
        'self',
        'https://sdk.cashfree.com',
        'https://translate.google.com',
        'https://www.google.com',
    ];

    /**
     * Restricts the origins allowed to deliver video and audio.
     *
     * @var list<string>|string|null
     */
    public $mediaSrc;

    /**
     * Allows control over Flash and other plugins.
     *
     * @var list<string>|string
     */
    public $objectSrc = 'self';

    /**
     * @var list<string>|string|null
     */
    public $manifestSrc;

    /**
     * @var list<string>|string
     */
    public array|string $workerSrc = [];

    /**
     * Limits the kinds of plugins a page may invoke.
     *
     * @var list<string>|string|null
     */
    public $pluginTypes;

    /**
     * List of actions allowed.
     *
     * @var list<string>|string|null
     */
    public $sandbox;

    /**
     * Nonce placeholder for style tags.
     */
    public string $styleNonceTag = '{csp-style-nonce}';

    /**
     * Nonce placeholder for script tags.
     */
    public string $scriptNonceTag = '{csp-script-nonce}';

    /**
     * Replace nonce tag automatically? Off for the report-only rollout: the policy
     * above allows 'unsafe-inline', so no per-request nonce rewriting of the HTML
     * body is needed yet. Turn this back on when moving to a nonce-based enforced
     * policy (and drop 'unsafe-inline' from script/style at the same time).
     */
    public bool $autoNonce = false;
}
