<?php

namespace App\Modules\Ricemill\Controllers;

use App\Controllers\BaseController;
use App\Modules\Ricemill\Models\RicemillModel;

/**
 * Ricemill — Public marketing website (CI4 port of the CI3 ricemill module).
 * Standalone, mobile-friendly rice-mill site with a public inquiry form.
 * This is the site's landing page (root URL) exactly as in the old project.
 *
 * URLs (preserved from CI3):
 *   /            -> website (default landing)
 *   /ricemill    -> website
 *   /ricemill/inquiry (POST) -> inquiry form submit
 */
class Ricemill extends BaseController
{
    /** The public website. */
    public function index()
    {
        helper('url');
        $mill = (new RicemillModel())->mill_profile('CR INDUSTRIES');

        $raw  = ($mill && !empty($mill->name)) ? $mill->name : 'CR Industries';
        $name = implode(' ', array_map(static function ($w) {
            return (strlen($w) <= 2) ? strtoupper($w) : ucfirst(strtolower($w));
        }, preg_split('/\s+/', trim($raw))));

        // APK Manager website widget is not ported yet — pass safe defaults so the
        // optional app-download section stays hidden (matches a site with no APK).
        return view('\App\Modules\Ricemill\Views\site', [
            'mill'         => $mill,
            'title'        => $name . ' — Premium Quality Rice Mill',
            'apk_latest'   => null,
            'apk_play_url' => '#',
            'apk_public'   => false,
            'apk_app_name' => 'C R Industries ERP',
        ]);
    }

    /** Handle the public inquiry form submit. */
    public function inquiry()
    {
        helper('url');
        $request = $this->request;
        if (strtolower($request->getMethod()) !== 'post') {
            return redirect()->to(base_url('/'));
        }

        $name    = trim((string) $request->getPost('name'));
        $mobile  = trim((string) $request->getPost('mobile_no'));
        $product = trim((string) $request->getPost('product'));

        if ($name === '' || strlen($mobile) < 7 || $product === '') {
            session()->setFlashdata('error', 'Please fill the required fields.');
            return redirect()->to(base_url('/') . '#inquiry');
        }

        $id = (new RicemillModel())->add_inquiry([
            'name'       => $name,
            'mobile_no'  => $mobile,
            'address'    => (string) $request->getPost('address'),
            'product'    => $product,
            'quantity'   => (string) $request->getPost('quantity'),
            'message'    => (string) $request->getPost('message'),
            'ip_address' => $request->getIPAddress(),
        ]);

        if ($id) {
            session()->setFlashdata('success', 'Thank you! Your inquiry has been submitted. Our team will contact you shortly.');
        } else {
            session()->setFlashdata('error', 'Sorry, something went wrong. Please try again.');
        }
        return redirect()->to(base_url('/') . '#inquiry');
    }
}
