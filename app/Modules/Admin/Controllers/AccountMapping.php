<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\AccountMappingModel;

/**
 * AccountMapping — CI4 port (slice: Farmer Captures inbox). The scraped-farmer
 * inbox (admin/accountMapping/captures) with archive/delete on the staging
 * `farmer_capture` table. "Use in Kisan Vahi" pre-fills the purchase entry — that
 * write flow (kisan_vahi/entry) is still being migrated, so Use is a safe no-op
 * that keeps the capture in the inbox. Khata Naksha + Thumb Figure port later.
 */
class AccountMapping extends BaseController
{
    protected $helpers = ['url', 'app', 'farmer_capture'];

    public function captures()
    {
        $status = $this->request->getGet('status');
        $status = in_array($status, ['new', 'used', 'archived', 'all'], true) ? $status : 'new';

        return _layout('\App\Modules\Admin\Views\campaign\farmer_captures', [
            'title'       => 'Farmer Captures',
            'captures'    => fc_list($status),
            'status'      => $status,
            'count_new'   => fc_count_new(),
            'center_list' => (new AccountMappingModel())->center_list(),
        ]);
    }

    public function capture_archive($id = null)
    {
        if ((int) $id) { fc_mark((int) $id, 'archived'); }
        session()->setFlashdata('success', 'Capture archived.');
        return redirect()->to(base_url('admin/accountMapping/captures'));
    }

    public function capture_delete($id = null)
    {
        if ((int) $id) { fc_delete((int) $id); }
        session()->setFlashdata('success', 'Capture deleted.');
        return redirect()->to(base_url('admin/accountMapping/captures'));
    }

    public function capture_use($id = null)
    {
        // The Kisan Vahi entry screen is still being migrated to CI4; keep the
        // capture in the inbox (do not mark it used) until that flow is live.
        session()->setFlashdata('error', 'The Kisan Vahi entry screen is still being migrated — this capture stays in your inbox for now.');
        return redirect()->to(base_url('admin/accountMapping/captures'));
    }

    /* ==================== Khata Naksha — farmer→account mapping ==================== */

    /**
     * Map (or unmap) a farmer's Kisan Vahi purchase rows to a ledger account.
     * GET renders the 2-step picker; POST performs map/unmap (CI3 parity).
     */
    public function account_mapping()
    {
        $model = new AccountMappingModel();

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $action     = ($this->request->getPost('map_action') === 'unmap') ? 'unmap' : 'map';
            $center     = trim((string) $this->request->getPost('rokad_type'));
            $farmer_id  = trim((string) $this->request->getPost('farmer_id'));
            $farmer_nm  = trim((string) $this->request->getPost('farmer_name'));
            $accountRaw = (string) $this->request->getPost('account_name');

            // Required-field guards (CI3 form_validation parity).
            $missing = ($center === '' || $farmer_id === '' || $farmer_nm === '' || ($action === 'map' && trim($accountRaw) === ''));
            if ($missing) {
                session()->setFlashdata('error', 'Please fill in all required fields.');
            } else {
                $res = ($action === 'unmap')
                    ? $model->account_unmap($farmer_id, $center)
                    : $model->account_mapping($accountRaw, $farmer_id, $center);

                if ($res['status'] === 'success') {
                    if ($action === 'map') {
                        $total = $model->count_account_mapping($accountRaw);
                        session()->setFlashdata('success', $res['msg'] . ' Farmer ' . esc($farmer_id)
                            . ' — this account now holds ' . $total . ' row(s) in FY ' . fy()->FY . '.');
                    } else {
                        session()->setFlashdata('success', $res['msg'] . ' Farmer ' . esc($farmer_id) . '.');
                    }
                    return redirect()->to(base_url('admin/accountMapping/account_mapping'));
                }
                if ($res['status'] === 'nochange') {
                    session()->setFlashdata('success', $res['msg']);
                    return redirect()->to(base_url('admin/accountMapping/account_mapping'));
                }
                // error — re-render with the message.
                session()->setFlashdata('error', $res['msg']);
            }
        }

        return _layout('\App\Modules\Admin\Views\campaign\account_mapping', [
            'title'        => 'Track (The Rest Accounting Key) || Farmer Account Mapping',
            'center_list'  => $model->center_list(),
            'account_list' => $model->account_name_list(),
        ]);
    }
}
