<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\GstSettingModel;

/**
 * Gst_setting — CI4 port of admin/Gst_setting (SUPER ADMIN ONLY). Default
 * CGST/SGST/IGST rates that pre-fill new Tax Invoice / E-Invoice forms.
 */
class Gst_setting extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function index()
    {
        if (! (function_exists('erp_is_super_admin') && erp_is_super_admin())) {
            return redirect()->to(base_url('permission_denied'));
        }

        $model = new GstSettingModel();

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $cgst = $this->request->getPost('cgst');
            $sgst = $this->request->getPost('sgst');
            $igst = $this->request->getPost('igst');
            $ok = true;
            foreach ([$cgst, $sgst, $igst] as $v) {
                if ($v === null || $v === '' || ! is_numeric($v) || (float) $v < 0 || (float) $v > 100) { $ok = false; }
            }
            if (! $ok) {
                return redirect()->to(base_url('admin/gst_setting'))->with('error', 'Enter valid rates between 0 and 100.');
            }
            $model->save($cgst, $sgst, $igst, (int) (currentuserinfo()->id ?? 0) ?: null);
            return redirect()->to(base_url('admin/gst_setting'))->with('success', 'GST default rates saved.');
        }

        return _layout('\App\Modules\Admin\Views\gst_setting\index', [
            'title' => 'GST Settings · C R Industries ERP',
            'rates' => $model->defaults(),
        ]);
    }
}
