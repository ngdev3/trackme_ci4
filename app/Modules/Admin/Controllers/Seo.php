<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Seo (admin) — CI4 port of CI3 admin/Seo. Manages the SEO settings
 * (Config/seo_settings.json via seo_helper). robots.txt / sitemap.xml are served
 * dynamically by the public App\Modules\Seo module, so no static generation step
 * is required. Route: admin/seo (GET form + POST save), admin/seo/generate.
 */
class Seo extends BaseController
{
    protected $helpers = ['url', 'app', 'form', 'seo'];

    public function index()
    {
        if (strtoupper($this->request->getMethod()) === 'POST') {
            return $this->save();
        }

        return _layout('\App\Modules\Admin\Views\seo\index', [
            'title'    => 'SEO & Search Optimization',
            'settings' => seo_settings(),
            'suggest'  => ['email' => '', 'address' => ''],
        ]);
    }

    private function save()
    {
        $business     = (array) ($this->request->getPost('business') ?? []);
        $verification = (array) ($this->request->getPost('verification') ?? []);
        $analytics    = (array) ($this->request->getPost('analytics') ?? []);

        $schema_in = (array) ($this->request->getPost('schema') ?? []);
        $schema = [];
        foreach (['organization', 'localbusiness', 'website', 'breadcrumb', 'faq'] as $k) {
            $schema[$k] = ! empty($schema_in[$k]) ? 1 : 0;
        }

        $faqs = [];
        $fq = $this->request->getPost('faq_q');
        $fa = $this->request->getPost('faq_a');
        if (is_array($fq)) {
            foreach ($fq as $i => $q) {
                $q = trim((string) $q);
                $a = isset($fa[$i]) ? trim((string) $fa[$i]) : '';
                if ($q !== '' && $a !== '') {
                    $faqs[] = ['q' => $q, 'a' => $a];
                }
            }
        }

        $settings = [
            'site_name'           => trim((string) $this->request->getPost('site_name')),
            'default_title'       => trim((string) $this->request->getPost('default_title')),
            'title_suffix'        => (string) $this->request->getPost('title_suffix'),
            'default_description' => trim((string) $this->request->getPost('default_description')),
            'default_keywords'    => trim((string) $this->request->getPost('default_keywords')),
            'og_image'            => trim((string) $this->request->getPost('og_image')),
            'twitter_card'        => trim((string) $this->request->getPost('twitter_card')) ?: 'summary_large_image',
            'twitter_handle'      => trim((string) $this->request->getPost('twitter_handle')),
            'robots_extra'        => trim((string) $this->request->getPost('robots_extra')),
            'indexable'           => $this->request->getPost('indexable') ? 1 : 0,
            'business'            => $business,
            'verification'        => $verification,
            'analytics'           => $analytics,
            'schema'              => $schema,
            'faqs'                => $faqs,
        ];

        if (seo_save_settings($settings)) {
            session()->setFlashdata('success', 'SEO settings saved successfully.');
        } else {
            session()->setFlashdata('error', 'Could not write the settings file. Check that app/Config is writable.');
        }

        return redirect()->to(base_url('admin/seo'));
    }

    /** robots.txt / sitemap.xml are served dynamically — nothing to pre-generate. */
    public function generate()
    {
        session()->setFlashdata('success', 'Sitemaps & robots.txt are served dynamically — always up to date.');
        return redirect()->to(base_url('admin/seo'));
    }
}
