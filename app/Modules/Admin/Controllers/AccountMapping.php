<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\AccountMappingModel;
use App\Modules\Admin\Models\TempThumbModel;

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

    /* ==================== Temp Farmer Thumb — thumb_figure + lock engine ==================== */

    private function thumb(): TempThumbModel
    {
        return new TempThumbModel();
    }

    public function thumb_figure()
    {
        return _layout('\App\Modules\Admin\Views\campaign\thumb_figure', [
            'title'         => 'Track (The Rest Accounting Key) || Temp Farmer Thumb',
            'centers'       => $this->temp_centers_payload(),
            'center_list'   => (new AccountMappingModel())->center_list(),
            'pending_count' => (function_exists('erp_is_super_admin') && erp_is_super_admin())
                ? count($this->thumb()->pending_requests()) : 0,
        ]);
    }

    /** Per-center payload: rows + live lock context, grouped by center. */
    private function temp_centers_payload(): array
    {
        $rows = $this->thumb()->records();
        $byId = [];
        foreach ($rows as $r) {
            $cid = (int) $r->center_id;
            if (! isset($byId[$cid])) {
                $byId[$cid] = ['center_id' => $cid, 'center_name' => $r->center_name, 'rows' => []];
            }
            $byId[$cid]['rows'][] = $r;
        }
        foreach ($byId as $cid => &$c) {
            $c['lock'] = $this->thumb()->lock_context($cid);
        }
        unset($c);
        return array_values($byId);
    }

    /** Uniform JSON responder that also returns the refreshed center payload. */
    private function temp_json(bool $ok, string $message, array $extra = [])
    {
        return $this->response->setJSON(array_merge([
            'status'  => $ok ? 'success' : 'error',
            'message' => $message,
            'centers' => $this->temp_centers_payload(),
        ], $extra));
    }

    /** Trim trailing zeros ("979.00" -> "979"). */
    private function temp_num($n): string
    {
        return rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    }

    /** Enforce "qty must not exceed the farmer's available quantity". '' = allowed. */
    private function temp_qty_block($fid, $qty, $ignore_id = 0): string
    {
        $available = $this->thumb()->farmer_available_qty($fid);
        if ($available <= 0) { return ''; }
        $allocated = $this->thumb()->farmer_allocated_qty($fid, $ignore_id);
        $remaining = $available - $allocated;
        if ((float) $qty > $remaining + 0.0001) {
            return 'Qty ' . $this->temp_num($qty) . ' exceeds the farmer\'s available quantity ('
                . $this->temp_num(max($remaining, 0)) . ' left of ' . $this->temp_num($available) . ').';
        }
        return '';
    }

    public function temp_add()
    {
        $t      = $this->thumb();
        $date   = date('Y-m-d', strtotime((string) $this->request->getPost('date')));
        $center = (int) $this->request->getPost('center_id');
        $fid    = trim((string) $this->request->getPost('farmer_id'));
        $fname  = trim((string) $this->request->getPost('farmer_name'));
        $qty    = $this->request->getPost('qty');
        $med    = trim((string) $this->request->getPost('mediator_name'));

        if ($fid === '' || $center <= 0 || $qty === '' || $qty === null || ! is_numeric($qty)) {
            return $this->temp_json(false, 'Farmer, Center and a valid Qty are required.');
        }
        if (! $t->can_edit($center, $date)) {
            return $this->temp_json(false, $t->block_reason($center, $date));
        }
        $existing = $t->farmer_center($fid);
        if ($existing > 0 && $existing !== $center) {
            return $this->temp_json(false, 'This farmer is already assigned to center "'
                . $t->center_name($existing) . '". A farmer can be added in one center only.');
        }
        $qblock = $this->temp_qty_block($fid, $qty);
        if ($qblock !== '') { return $this->temp_json(false, $qblock); }

        $res = $t->club_or_add([
            'date' => $date, 'farmer_id' => $fid, 'farmer_name' => $fname,
            'qty' => $qty, 'mediator_name' => $med, 'center_id' => $center]);
        return $this->temp_json(true, $res['clubbed']
            ? 'Quantity added to this farmer\'s existing entry for ' . date('d-m-Y', strtotime($date)) . '.'
            : 'Entry added.');
    }

    public function temp_get()
    {
        $rec = $this->thumb()->get_record((int) $this->request->getPost('id'));
        return $this->response->setJSON(['status' => $rec ? 'success' : 'error', 'record' => $rec]);
    }

    /** AJAX: a farmer's REMAINING allocatable quantity + auto-select center/mediator. */
    public function thumb_farmer_qty()
    {
        $t      = $this->thumb();
        $fid    = trim((string) $this->request->getPost('farmer_id'));
        $ignore = (int) $this->request->getPost('ignore_id');
        $base   = $t->farmer_available_qty($fid);
        $alloc  = $t->farmer_allocated_qty($fid, $ignore);
        $remaining = max($base - $alloc, 0);
        $ex = $t->farmer_existing($fid);
        $mediator = trim((string) $ex['mediator_name']);
        if ($mediator === '') {
            $mediator = (new AccountMappingModel())->farmer_reg_account_name($fid);
        }
        return $this->response->setJSON([
            'status'          => 'success',
            'registered'      => $base,
            'allocated'       => $alloc,
            'remaining'       => $remaining,
            'has_cap'         => $base > 0,
            'existing_center' => $ex['center_id'],
            'existing_center_name' => $ex['center_id'] ? $t->center_name($ex['center_id']) : '',
            'existing_mediator' => $mediator,
        ]);
    }

    public function temp_edit()
    {
        $t   = $this->thumb();
        $id  = (int) $this->request->getPost('id');
        $rec = $t->get_record($id);
        if (! $rec) { return $this->temp_json(false, 'Record not found.'); }

        $center = (int) $this->request->getPost('center_id');
        $fid    = trim((string) $this->request->getPost('farmer_id'));
        $fname  = trim((string) $this->request->getPost('farmer_name'));
        $qty    = $this->request->getPost('qty');
        $med    = trim((string) $this->request->getPost('mediator_name'));
        if ($fid === '' || $center <= 0 || $qty === '' || $qty === null || ! is_numeric($qty)) {
            return $this->temp_json(false, 'Farmer, Center and a valid Qty are required.');
        }
        if (! $t->can_edit($rec->center_id, $rec->entry_date) || ! $t->can_edit($center, $rec->entry_date)) {
            return $this->temp_json(false, $t->block_reason($rec->center_id, $rec->entry_date));
        }
        if ($t->duplicate_exists($fid, $rec->entry_date, $id)) {
            return $this->temp_json(false, 'Another entry for this farmer already exists on that date.');
        }
        $existing = $t->farmer_center($fid, $id);
        $qblock = $this->temp_qty_block($fid, $qty, $id);
        if ($qblock !== '') { return $this->temp_json(false, $qblock); }

        $t->update_record($id, [
            'farmer_id' => $fid, 'farmer_name' => $fname, 'qty' => $qty,
            'mediator_name' => $med, 'center_id' => $center]);
        $moved = ($existing > 0 && $existing !== $center) ? $t->move_farmer_all($fid, $center, $id) : 0;
        return $this->temp_json(true, $moved
            ? 'Entry updated. This farmer\'s other ' . $moved . ' entr' . ($moved === 1 ? 'y' : 'ies')
                . ' also moved to "' . $t->center_name($center) . '".'
            : 'Entry updated.');
    }

    public function temp_delete()
    {
        $t   = $this->thumb();
        $id  = (int) $this->request->getPost('id');
        $rec = $t->get_record($id);
        if (! $rec) { return $this->temp_json(false, 'Record not found.'); }
        if (! $t->can_edit($rec->center_id, $rec->entry_date)) {
            return $this->temp_json(false, $t->block_reason($rec->center_id, $rec->entry_date));
        }
        $t->delete_record($id);
        return $this->temp_json(true, 'Entry deleted.');
    }

    public function temp_move()
    {
        $t      = $this->thumb();
        $id     = (int) $this->request->getPost('id');
        $target = (int) $this->request->getPost('center_id');
        $rec    = $t->get_record($id);
        if (! $rec || $target <= 0) { return $this->temp_json(false, 'Invalid move.'); }
        if (! $t->can_edit($rec->center_id, $rec->entry_date)) {
            return $this->temp_json(false, 'Locked date — this entry cannot be moved.');
        }
        if (! $t->can_receive($target, $rec->entry_date)) {
            return $this->temp_json(false, 'The destination center has ' . date('d-m-Y', strtotime($rec->entry_date))
                . ' locked — you cannot move an entry into a locked date.');
        }
        if ($t->duplicate_exists($rec->farmer_id, $rec->entry_date, $id)) {
            return $this->temp_json(false, 'That farmer already has an entry on this date elsewhere.');
        }
        $existing = $t->farmer_center($rec->farmer_id, $id);
        if ($existing > 0 && $existing !== $target) {
            return $this->temp_json(false, 'This farmer has other entries in center "'
                . $t->center_name($existing) . '". A farmer can be in one center only.');
        }
        $t->move_record($id, $target);
        return $this->temp_json(true, 'Moved to the new center.');
    }

    public function temp_lock()
    {
        [$ok, $msg] = $this->thumb()->lock_date(
            (int) $this->request->getPost('center_id'),
            date('Y-m-d', strtotime((string) $this->request->getPost('date'))));
        return $this->temp_json($ok, $msg);
    }

    public function temp_request_unlock()
    {
        [$ok, $msg] = $this->thumb()->request_unlock(
            (int) $this->request->getPost('center_id'),
            date('Y-m-d', strtotime((string) $this->request->getPost('date'))),
            $this->request->getPost('reason'));
        return $this->temp_json($ok, $msg);
    }

    public function temp_relock()
    {
        [$ok, $msg] = $this->thumb()->relock_date(
            (int) $this->request->getPost('center_id'),
            date('Y-m-d', strtotime((string) $this->request->getPost('date'))));
        return $this->temp_json($ok, $msg);
    }

    public function temp_unlock_requests()
    {
        if (! (function_exists('erp_is_super_admin') && erp_is_super_admin())) {
            return redirect()->to(site_url('permission_denied'));
        }
        $t = $this->thumb();
        return _layout('\App\Modules\Admin\Views\campaign\thumb_unlock_requests', [
            'title'       => 'Track (The Rest Accounting Key) || Unlock Requests',
            'can_approve' => true,
            'requests'    => $t->pending_requests(),
            'unlocked'    => $t->unlocked_dates(),
            'history'     => $t->request_history(),
            'audit'       => $t->audit_log(),
        ]);
    }

    public function temp_approve()
    {
        if (! (function_exists('erp_is_super_admin') && erp_is_super_admin())) {
            return $this->temp_json(false, 'Only Super Admin can approve.');
        }
        [$ok, $msg] = $this->thumb()->approve_request(
            (int) $this->request->getPost('id'), $this->request->getPost('remark'));
        return $this->response->setJSON(['status' => $ok ? 'success' : 'error', 'message' => $msg]);
    }

    public function temp_reject()
    {
        if (! (function_exists('erp_is_super_admin') && erp_is_super_admin())) {
            return $this->temp_json(false, 'Only Super Admin can reject.');
        }
        [$ok, $msg] = $this->thumb()->reject_request(
            (int) $this->request->getPost('id'), $this->request->getPost('remark'));
        return $this->response->setJSON(['status' => $ok ? 'success' : 'error', 'message' => $msg]);
    }

    /** AJAX: farmer autocomplete for the add box. */
    public function thumb_farmer_search()
    {
        return $this->response->setJSON(
            (new AccountMappingModel())->thumb_farmer_search($this->request->getPost('q')));
    }

    /** AJAX: account-name autocomplete for the Mid field. */
    public function thumb_account_search()
    {
        return $this->response->setJSON(
            (new AccountMappingModel())->thumb_account_search($this->request->getPost('q')));
    }
}
