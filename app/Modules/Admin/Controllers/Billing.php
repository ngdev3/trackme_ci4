<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\AccountNameModel;

/**
 * Billing — CI4 port of admin/Billing (picker slice).
 *
 * Currently ports only account_options(): the JSON feed behind the shared
 * account picker (assets/js/acc_picker.js), used by the reports, the Jama/Naam
 * voucher and the invoice forms. Other Billing endpoints port with their pages.
 */
class Billing extends BaseController
{
    protected $helpers = ['url', 'form', 'text', 'app', 'cr_cache', 'accounting'];

    /** JSON: account picker options, optional ?reg=registered|unregistered filter. */
    public function account_options()
    {
        $reg = $this->request->getGet('reg');
        $reg = in_array($reg, ['registered', 'unregistered'], true) ? $reg : 'all';
        return $this->response->setJSON((new AccountNameModel())->pickerOptions($reg));
    }
}
