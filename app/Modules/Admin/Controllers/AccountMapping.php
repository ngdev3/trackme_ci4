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
}
