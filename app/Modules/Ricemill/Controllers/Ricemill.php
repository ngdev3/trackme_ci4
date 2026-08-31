<?php

namespace App\Modules\Ricemill\Controllers;

use App\Controllers\BaseController;
use App\Modules\Ricemill\Models\RicemillSiteModel;

/**
 * Ricemill — CI4 port of the CI3 public marketing website (default route).
 * Renders the standalone rice-mill site with a public inquiry form. Submissions
 * are stored in aa_ricemill_inquiry and managed from admin/ricemill_inquiry.
 * Public (no adminAuth filter). The APK-download block is skipped until the APK
 * Manager is ported (apk_latest passed null), matching a mill with no build.
 */
class Ricemill extends BaseController
{
    protected $helpers = ['url'];

    /** The website. */
    public function index()
    {
        $model = new RicemillSiteModel();
        $mill  = $model->millProfile('CR INDUSTRIES');

        $raw  = ($mill && ! empty($mill->name)) ? $mill->name : 'CR Industries';
        $name = implode(' ', array_map(static function ($w) {
            return (strlen($w) <= 2) ? strtoupper($w) : ucfirst(strtolower($w));
        }, preg_split('/\s+/', trim($raw))));

        return view('\App\Modules\Ricemill\Views\site', [
            'mill'         => $mill,
            'title'        => $name . ' — Premium Quality Rice Mill',
            'apk_latest'   => null,   // APK Manager not yet ported → section hidden
            'apk_play_url' => '',
            'apk_public'   => false,
            'apk_app_name' => 'C R Industries ERP',
        ]);
    }

    /** Handle the public inquiry form submit. */
    public function inquiry()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return redirect()->to(base_url('ricemill'));
        }

        if (! $this->validate([
            'name'      => 'trim|required|max_length[150]',
            'mobile_no' => 'trim|required|min_length[7]|max_length[20]',
            'product'   => 'trim|required|max_length[190]',
        ])) {
            $errs = implode(' ', $this->validator->getErrors());
            return redirect()->to(base_url('ricemill') . '#inquiry')
                ->withInput()
                ->with('error', $errs !== '' ? strip_tags($errs) : 'Please fill the required fields.');
        }

        $id = (new RicemillSiteModel())->addInquiry([
            'name'       => $this->request->getPost('name'),
            'mobile_no'  => $this->request->getPost('mobile_no'),
            'address'    => $this->request->getPost('address'),
            'product'    => $this->request->getPost('product'),
            'quantity'   => $this->request->getPost('quantity'),
            'message'    => $this->request->getPost('message'),
            'ip_address' => $this->request->getIPAddress(),
        ]);

        if ($id) {
            return redirect()->to(base_url('ricemill') . '#inquiry')
                ->with('success', 'Thank you! Your inquiry has been submitted. Our team will contact you shortly.');
        }
        return redirect()->to(base_url('ricemill') . '#inquiry')
            ->with('error', 'Sorry, something went wrong. Please try again.');
    }
}
