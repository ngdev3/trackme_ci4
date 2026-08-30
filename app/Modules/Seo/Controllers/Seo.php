<?php

namespace App\Modules\Seo\Controllers;

use App\Controllers\BaseController;

/**
 * SEO — public endpoints. CI4 port of the CI3 `seo` module (which extended
 * CI_Controller). Serves robots.txt and the sitemaps dynamically. Physical
 * static copies written by admin/seo (if present) are served by the web
 * server first and win over these routes.
 */
class Seo extends BaseController
{
    protected $helpers = ['url', 'seo'];

    public function robots()
    {
        return $this->response
            ->setContentType('text/plain')
            ->setBody(seo_build_robots_txt());
    }

    public function sitemap()
    {
        return $this->response
            ->setContentType('application/xml')
            ->setBody(seo_build_sitemap_xml());
    }

    public function imageSitemap()
    {
        return $this->response
            ->setContentType('application/xml')
            ->setBody(seo_build_image_sitemap_xml());
    }
}
