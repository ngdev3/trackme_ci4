<?php

/**
 * SEO helper — CI4 port of application/helpers/seo_helper.php.
 * Faithful port; only the framework-specific bits changed:
 *   - get_instance()->uri  -> service('uri')
 *   - CI3 uri->segment/uri_string -> CI4 getSegment()/uri_string()
 *   - constants fall back to sane defaults if not defined
 * Settings live in app/Config/seo_settings.json (no DB table).
 */

if (! function_exists('seo_settings_path')) {
    function seo_settings_path()
    {
        return APPPATH . 'Config/seo_settings.json';
    }
}

if (! function_exists('seo_brand')) {
    function seo_brand()
    {
        return defined('WEBSITE_NAME') && WEBSITE_NAME ? WEBSITE_NAME : 'C R Industries';
    }
}

if (! function_exists('seo_defaults')) {
    function seo_defaults()
    {
        $brand = seo_brand();
        return [
            'site_name'           => $brand,
            'default_title'       => $brand . ' — Premium Quality Rice Mill',
            'title_suffix'        => ' | ' . $brand,
            'default_description' => $brand . ' is a modern, GST & FSSAI certified rice mill offering trusted bulk supply of premium basmati & non-basmati rice.',
            'default_keywords'    => 'rice mill, basmati rice, non-basmati rice, wholesale rice, bulk rice supplier, ' . $brand,
            'og_image'            => 'assets/ricemill/hero-poster.jpg',
            'twitter_card'        => 'summary_large_image',
            'twitter_handle'      => '',
            'indexable'           => true,
            'robots_extra'        => 'max-image-preview:large, max-snippet:-1, max-video-preview:-1',
            'business'            => [
                'name' => $brand, 'category' => 'Rice Mill', 'phone' => '', 'email' => '',
                'street' => '', 'locality' => 'Shahabad, Hardoi', 'region' => 'Uttar Pradesh',
                'postal' => '', 'country' => 'IN', 'latitude' => '', 'longitude' => '',
                'map_url' => '', 'opening_hours' => 'Mo-Sa 09:00-18:00',
                'logo' => 'assets/images/logo.png', 'price_range' => '',
            ],
            'verification' => ['google' => '', 'bing' => '', 'yandex' => '', 'pinterest' => ''],
            'analytics'    => ['ga4' => '', 'gtm' => '', 'clarity' => '', 'fb_pixel' => ''],
            'schema'       => ['organization' => 1, 'localbusiness' => 1, 'website' => 1, 'breadcrumb' => 1, 'faq' => 1],
        ];
    }
}

if (! function_exists('seo_array_merge_deep')) {
    function seo_array_merge_deep($base, $over)
    {
        foreach ($over as $k => $v) {
            if (is_array($v) && isset($base[$k]) && is_array($base[$k])) {
                $base[$k] = seo_array_merge_deep($base[$k], $v);
            } else {
                $base[$k] = $v;
            }
        }
        return $base;
    }
}

if (! function_exists('seo_settings')) {
    function seo_settings()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $settings = seo_defaults();
        $file     = seo_settings_path();
        if (is_file($file)) {
            $saved = json_decode((string) file_get_contents($file), true);
            if (is_array($saved)) {
                $settings = seo_array_merge_deep($settings, $saved);
            }
        }
        return $cache = $settings;
    }
}

if (! function_exists('seo_e')) {
    function seo_e($v)
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('seo_abs_url')) {
    function seo_abs_url($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return base_url(ltrim($path, '/'));
    }
}

if (! function_exists('seo_sitemap_urls')) {
    /** Public URLs for the sitemap. Add public routes here as they grow. */
    function seo_sitemap_urls()
    {
        $today = date('Y-m-d');
        return [
            ['loc' => base_url(),           'changefreq' => 'weekly', 'priority' => '1.0', 'lastmod' => $today],
            ['loc' => base_url('ricemill'), 'changefreq' => 'weekly', 'priority' => '0.9', 'lastmod' => $today],
        ];
    }
}

if (! function_exists('seo_sitemap_images')) {
    function seo_sitemap_images()
    {
        $s = seo_settings();
        return [
            seo_abs_url($s['og_image']),
            seo_abs_url($s['business']['logo']),
            seo_abs_url('assets/ricemill/hero-poster.jpg'),
        ];
    }
}

if (! function_exists('seo_build_sitemap_xml')) {
    function seo_build_sitemap_xml()
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        $images = seo_sitemap_images();
        foreach (seo_sitemap_urls() as $i => $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . seo_e($u['loc']) . "</loc>\n";
            $xml .= '    <lastmod>' . seo_e($u['lastmod']) . "</lastmod>\n";
            $xml .= '    <changefreq>' . seo_e($u['changefreq']) . "</changefreq>\n";
            $xml .= '    <priority>' . seo_e($u['priority']) . "</priority>\n";
            if ($i === 0) {
                foreach ($images as $img) {
                    if ($img === '') {
                        continue;
                    }
                    $xml .= '    <image:image><image:loc>' . seo_e($img) . "</image:loc></image:image>\n";
                }
            }
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>' . "\n";
        return $xml;
    }
}

if (! function_exists('seo_build_image_sitemap_xml')) {
    function seo_build_image_sitemap_xml()
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        $xml .= '  <url>' . "\n    <loc>" . seo_e(base_url()) . "</loc>\n";
        foreach (seo_sitemap_images() as $img) {
            if ($img === '') {
                continue;
            }
            $xml .= '    <image:image><image:loc>' . seo_e($img) . "</image:loc></image:image>\n";
        }
        $xml .= "  </url>\n</urlset>\n";
        return $xml;
    }
}

if (! function_exists('seo_build_robots_txt')) {
    function seo_build_robots_txt()
    {
        $s     = seo_settings();
        $lines = ['User-agent: *'];
        if (empty($s['indexable'])) {
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'Disallow: /admin/';
            $lines[] = 'Disallow: /auth/';
            $lines[] = 'Disallow: /webservices/';
            $lines[] = 'Disallow: /api_services';
            $lines[] = 'Disallow: /permission_denied';
            $lines[] = 'Disallow: /index.php/';
            $lines[] = 'Disallow: /*?';
            $lines[] = 'Allow: /';
        }
        $lines[] = '';
        $lines[] = 'Sitemap: ' . base_url('sitemap.xml');
        $lines[] = 'Sitemap: ' . base_url('sitemap-images.xml');
        return implode("\n", $lines) . "\n";
    }
}

if (! function_exists('seo_send_noindex_header')) {
    /** Strongest do-not-index signal (all response types). Call from a filter for admin/auth. */
    function seo_send_noindex_header()
    {
        if (! headers_sent()) {
            header('X-Robots-Tag: noindex, nofollow, noarchive');
        } else {
            service('response')->setHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }
    }
}
