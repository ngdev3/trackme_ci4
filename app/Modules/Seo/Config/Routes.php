<?php

/**
 * SEO module routes — auto-discovered by CI4 (Config\Modules discovery is on).
 * Preserves the exact public CI3 URLs (config/routes.php mapped these).
 */

use App\Modules\Seo\Controllers\Seo;

$routes->get('robots.txt', [Seo::class, 'robots']);
$routes->get('sitemap.xml', [Seo::class, 'sitemap']);
$routes->get('sitemap-images.xml', [Seo::class, 'imageSitemap']);
