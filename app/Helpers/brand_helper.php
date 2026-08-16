<?php

/**
 * Brand accessors — the SINGLE source of truth for the product's display name
 * and tagline on the website/backend. Everything (views, emails, controllers)
 * must read the brand through these functions instead of hardcoding a string,
 * so a rebrand is one change (the `app_name` / `app_tagline` settings, or the
 * defaults below).
 *
 * Backed by the DB-editable settings; the defaults here are the canonical
 * fallback used when a setting isn't present.
 *
 * NOTE: the domain and email intentionally stay `hissabkitaab.com` (no hyphen) —
 * only the human-readable NAME is "Hissab-Kitaab".
 */

if (! function_exists('brand_name')) {
    /** Product display name, e.g. "Hissab-Kitaab". */
    function brand_name(): string
    {
        return (string) setting('app_name', 'Hissab-Kitaab');
    }
}

if (! function_exists('brand_tagline')) {
    /** Product tagline, e.g. "Har Len-Den Ka Sahi Hisaab". */
    function brand_tagline(): string
    {
        return (string) setting('app_tagline', 'Har Len-Den Ka Sahi Hisaab');
    }
}

if (! function_exists('brand_domain')) {
    /** Public domain (no scheme), e.g. "hissabkitaab.com". Not hyphenated. */
    function brand_domain(): string
    {
        return (string) setting('app_url', 'hissabkitaab.com');
    }
}

if (! function_exists('brand_support_email')) {
    /** Support / from email, e.g. "admin@hissabkitaab.com". Not hyphenated. */
    function brand_support_email(): string
    {
        return (string) setting('support_email', 'admin@hissabkitaab.com');
    }
}
