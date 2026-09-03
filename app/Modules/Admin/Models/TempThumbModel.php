<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * TempThumbModel — CI4 port of Temp_thumb_mod.
 *
 * Backs "Temp Farmer Thumb Management" (admin/accountMapping/thumb_figure) and
 * its per-center Date-Locking engine.
 *
 * LOCK MODEL (per-center — each center advances its own working date):
 *   - A center's ACTIVE date = MAX(locked date) + 1 day, or the first entry
 *     date / today before anything is locked.
 *   - Add/Edit/Delete/Move are allowed ONLY on the active date.
 *   - Locking the active date finalizes it (posts to Kisan Vahi) and advances.
 *   - A Super-Admin-approved unlock flips a locked date to 'unlocked' (temp);
 *     while a center holds any 'unlocked' date it is FROZEN.
 *
 * Everything is scoped by FY + product_type + template_id.
 */
class TempThumbModel
{
    public function __construct()
    {
        $this->ensure_schema();
    }

    protected function db()
    {
        return Database::connect();
    }

    /* ===================== schema ===================== */

    public function ensure_schema()
    {
        static $done = false;
        if ($done) { return; }
        $done = true;
        $db = $this->db();

        $db->query("CREATE TABLE IF NOT EXISTS `aa_temp_thumb` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `entry_date` DATE NOT NULL,
            `farmer_id` VARCHAR(255) NOT NULL,
            `farmer_name` VARCHAR(255) NOT NULL,
            `qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `mediator_name` VARCHAR(255) NULL,
            `center_id` INT NOT NULL DEFAULT 0,
            `FY` VARCHAR(20) NOT NULL,
            `product_type` INT NOT NULL DEFAULT 0,
            `template_id` INT NOT NULL DEFAULT 0,
            `added_by` INT NULL,
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            KEY `idx_farmer_date` (`farmer_id`(100),`entry_date`),
            KEY `idx_scope` (`center_id`,`entry_date`,`FY`,`product_type`,`template_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `aa_center_lock` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `center_id` INT NOT NULL,
            `entry_date` DATE NOT NULL,
            `FY` VARCHAR(20) NOT NULL,
            `product_type` INT NOT NULL DEFAULT 0,
            `template_id` INT NOT NULL DEFAULT 0,
            `status` ENUM('locked','unlocked') NOT NULL DEFAULT 'locked',
            `locked_by` INT NULL, `locked_at` DATETIME NULL,
            `unlocked_by` INT NULL, `unlocked_for` INT NULL, `unlocked_at` DATETIME NULL,
            UNIQUE KEY `uniq_center_date` (`center_id`,`entry_date`,`FY`,`template_id`),
            KEY `idx` (`center_id`,`FY`,`template_id`,`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `aa_center_unlock_request` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `center_id` INT NOT NULL, `entry_date` DATE NOT NULL,
            `FY` VARCHAR(20) NOT NULL, `product_type` INT NOT NULL DEFAULT 0, `template_id` INT NOT NULL DEFAULT 0,
            `requested_by` INT NOT NULL, `reason` TEXT NOT NULL,
            `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            `approved_by` INT NULL, `approved_at` DATETIME NULL, `admin_remark` TEXT NULL,
            `created_at` DATETIME NULL,
            KEY `idx_status` (`status`,`template_id`,`FY`),
            KEY `idx_center` (`center_id`,`entry_date`,`template_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `aa_center_lock_audit` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `center_id` INT NOT NULL, `entry_date` DATE NULL, `action` VARCHAR(40) NOT NULL,
            `user_id` INT NULL, `requested_by` INT NULL, `approved_by` INT NULL,
            `reason` TEXT NULL, `remarks` TEXT NULL, `ip_address` VARCHAR(64) NULL,
            `FY` VARCHAR(20) NULL, `template_id` INT NULL, `created_at` DATETIME NULL,
            KEY `idx_center` (`center_id`,`entry_date`), KEY `idx_action` (`action`,`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Trace column: the kisanvahidata row this thumb entry posted on lock.
        $col = $db->query("SHOW COLUMNS FROM `aa_temp_thumb` LIKE 'posted_kisan_id'");
        if ($col->getNumRows() === 0) {
            $db->query("ALTER TABLE `aa_temp_thumb` ADD COLUMN `posted_kisan_id` INT NULL AFTER `updated_at`");
        }
    }

    /* ===================== scope helpers ===================== */

    /** Add FY + product_type + template_id filters to a builder (optionally aliased). */
    private function scope($builder, string $alias = '')
    {
        $p = $alias ? $alias . '.' : '';
        $builder->where($p . 'FY', fy()->FY)
            ->where($p . 'product_type', fy()->product_type)
            ->where($p . 'template_id', fy()->template_id);
        return $builder;
    }

    private function uid(): int { return (int) (currentuserinfo()->id ?? 0); }
    private function is_super(): bool { return function_exists('erp_is_super_admin') && erp_is_super_admin(); }

    /* ===================== lock engine ===================== */

    /**
     * Resolve a center's lock context.
     * ['mode'=>'normal'|'frozen','active_date'=>Y-m-d|null,'frozen_date'=>Y-m-d|null,'frozen_for'=>int|null]
     */
    public function lock_context($center_id): array
    {
        $center_id = (int) $center_id;

        $b = $this->db()->table('aa_center_lock')->where('center_id', $center_id)->where('status', 'unlocked');
        $this->scope($b);
        $frozen = $b->orderBy('entry_date', 'asc')->get()->getRow();
        if ($frozen) {
            return ['mode' => 'frozen', 'active_date' => null,
                'frozen_date' => $frozen->entry_date, 'frozen_for' => (int) $frozen->unlocked_for];
        }

        $b = $this->db()->table('aa_center_lock')->selectMax('entry_date', 'mx')
            ->where('center_id', $center_id)->where('status', 'locked');
        $this->scope($b);
        $mx = $b->get()->getRow();
        if ($mx && $mx->mx) {
            $active = date('Y-m-d', strtotime($mx->mx . ' +1 day'));
        } else {
            $b = $this->db()->table('aa_temp_thumb')->selectMin('entry_date', 'mn')->where('center_id', $center_id);
            $this->scope($b);
            $mn = $b->get()->getRow();
            $active = ($mn && $mn->mn) ? $mn->mn : date('Y-m-d');
        }
        return ['mode' => 'normal', 'active_date' => $active, 'frozen_date' => null, 'frozen_for' => null];
    }

    /** Is a specific date locked (finalized) for a center? */
    public function is_locked($center_id, $date): bool
    {
        $b = $this->db()->table('aa_center_lock')
            ->where('center_id', (int) $center_id)->where('entry_date', $date)->where('status', 'locked');
        $this->scope($b);
        return $b->countAllResults() > 0;
    }

    /** Master gate: may $uid change records for ($center, $date) right now? */
    public function can_edit($center_id, $date, $uid = null): bool
    {
        if ($this->is_super()) { return true; }
        $uid = $uid === null ? $this->uid() : (int) $uid;
        $ctx = $this->lock_context($center_id);
        if ($ctx['mode'] === 'frozen') {
            return ($date === $ctx['frozen_date'] && $uid === (int) $ctx['frozen_for']);
        }
        if ($this->is_fresh_center($center_id)) { return true; }
        return ($date === $ctx['active_date']);
    }

    /** Can this center RECEIVE an entry on $date now? No Super-Admin bypass (a lock is a lock). */
    public function can_receive($center_id, $date): bool
    {
        $ctx = $this->lock_context($center_id);
        if ($ctx['mode'] === 'frozen') {
            return ($date === $ctx['frozen_date']);
        }
        if ($this->is_fresh_center($center_id)) { return true; }
        return ($date === $ctx['active_date']);
    }

    /** True when a center has no temp-thumb rows AND no lock rows yet (never started). */
    public function is_fresh_center($center_id): bool
    {
        $center_id = (int) $center_id;
        $b = $this->db()->table('aa_temp_thumb')->where('center_id', $center_id);
        $this->scope($b);
        if ($b->countAllResults() > 0) { return false; }
        $b = $this->db()->table('aa_center_lock')->where('center_id', $center_id);
        $this->scope($b);
        return $b->countAllResults() === 0;
    }

    /** Human reason why an edit is blocked (for toast messages). */
    public function block_reason($center_id, $date): string
    {
        $ctx = $this->lock_context($center_id);
        if ($ctx['mode'] === 'frozen') {
            return 'This center is unlocked for ' . date('d-m-Y', strtotime($ctx['frozen_date']))
                . ' — re-lock it before working on other dates.';
        }
        if ($this->is_locked($center_id, $date)) {
            return 'Date ' . date('d-m-Y', strtotime($date)) . ' is locked. Request unlock to edit.';
        }
        return 'Only the active date (' . date('d-m-Y', strtotime($ctx['active_date'])) . ') can be edited.';
    }

    /** Lock (finalize) a center's active date; advances the center by a day. */
    public function lock_date($center_id, $date): array
    {
        $center_id = (int) $center_id;
        $ctx = $this->lock_context($center_id);
        if ($ctx['mode'] === 'frozen') { return [false, 'A previous date is unlocked; re-lock it first.']; }
        if ($ctx['active_date'] !== $date) { return [false, 'Only the active date can be locked.']; }
        if ($this->is_locked($center_id, $date)) { return [false, 'That date is already locked.']; }

        $now = date('Y-m-d H:i:s');
        $this->db()->table('aa_center_lock')->insert([
            'center_id' => $center_id, 'entry_date' => $date,
            'FY' => fy()->FY, 'product_type' => fy()->product_type, 'template_id' => fy()->template_id,
            'status' => 'locked', 'locked_by' => $this->uid(), 'locked_at' => $now,
        ]);
        $posted = $this->post_date($center_id, $date);
        $this->audit($center_id, $date, 'lock', ['user_id' => $this->uid()]);
        return [true, $posted
            ? 'Date locked & ' . $posted . ' entr' . ($posted === 1 ? 'y' : 'ies') . ' posted to Kisan Vahi. Next date is now open.'
            : 'Date locked. Next date is now open.'];
    }

    /** Operator requests to unlock a locked date (goes to Super Admin). */
    public function request_unlock($center_id, $date, $reason): array
    {
        $center_id = (int) $center_id;
        $reason = trim((string) $reason);
        if ($reason === '') { return [false, 'A reason is required.']; }
        if (! $this->is_locked($center_id, $date)) { return [false, 'That date is not locked.']; }

        $now = date('Y-m-d H:i:s');
        $this->db()->table('aa_center_unlock_request')->insert([
            'center_id' => $center_id, 'entry_date' => $date,
            'FY' => fy()->FY, 'product_type' => fy()->product_type, 'template_id' => fy()->template_id,
            'requested_by' => $this->uid(), 'reason' => $reason, 'status' => 'pending', 'created_at' => $now,
        ]);
        $this->audit($center_id, $date, 'unlock_request', [
            'user_id' => $this->uid(), 'requested_by' => $this->uid(), 'reason' => $reason]);
        return [true, 'Unlock request sent to Super Admin.'];
    }

    /** Super Admin approves a request -> flips the date to temp-unlocked. */
    public function approve_request($request_id, $remark = ''): array
    {
        $req = $this->db()->table('aa_center_unlock_request')->where('id', (int) $request_id)->get()->getRow();
        if (! $req || $req->status !== 'pending') { return [false, 'Request not found or already handled.']; }

        $now = date('Y-m-d H:i:s');
        $this->db()->table('aa_center_unlock_request')->where('id', $req->id)->update([
            'status' => 'approved', 'approved_by' => $this->uid(), 'approved_at' => $now,
            'admin_remark' => trim((string) $remark),
        ]);
        $this->db()->table('aa_center_lock')
            ->where('center_id', $req->center_id)->where('entry_date', $req->entry_date)
            ->where('FY', $req->FY)->where('template_id', $req->template_id)
            ->update([
                'status' => 'unlocked', 'unlocked_by' => $this->uid(),
                'unlocked_for' => $req->requested_by, 'unlocked_at' => $now,
            ]);
        $this->unpost_date($req->center_id, $req->entry_date);
        $this->audit($req->center_id, $req->entry_date, 'approve', [
            'user_id' => $this->uid(), 'requested_by' => $req->requested_by,
            'approved_by' => $this->uid(), 'reason' => $req->reason, 'remarks' => $remark]);
        return [true, 'Approved. The date is temporarily unlocked for the requester.'];
    }

    /** Super Admin rejects a request. */
    public function reject_request($request_id, $remark = ''): array
    {
        $req = $this->db()->table('aa_center_unlock_request')->where('id', (int) $request_id)->get()->getRow();
        if (! $req || $req->status !== 'pending') { return [false, 'Request not found or already handled.']; }
        $this->db()->table('aa_center_unlock_request')->where('id', $req->id)->update([
            'status' => 'rejected', 'approved_by' => $this->uid(),
            'approved_at' => date('Y-m-d H:i:s'), 'admin_remark' => trim((string) $remark),
        ]);
        $this->audit($req->center_id, $req->entry_date, 'reject', [
            'user_id' => $this->uid(), 'requested_by' => $req->requested_by,
            'approved_by' => $this->uid(), 'reason' => $req->reason, 'remarks' => $remark]);
        return [true, 'Request rejected.'];
    }

    /** Re-lock a temp-unlocked date, restoring the normal workflow. */
    public function relock_date($center_id, $date): array
    {
        $center_id = (int) $center_id;
        $b = $this->db()->table('aa_center_lock')
            ->where('center_id', $center_id)->where('entry_date', $date)->where('status', 'unlocked');
        $this->scope($b);
        $row = $b->get()->getRow();
        if (! $row) { return [false, 'That date is not currently unlocked.']; }
        if (! $this->is_super() && (int) $row->unlocked_for !== $this->uid()) {
            return [false, 'Only the approved user can re-lock this date.'];
        }
        $this->db()->table('aa_center_lock')->where('id', $row->id)->update([
            'status' => 'locked', 'unlocked_by' => null, 'unlocked_for' => null, 'unlocked_at' => null,
        ]);
        $posted = $this->post_date($center_id, $date);
        $this->audit($center_id, $date, 'relock', ['user_id' => $this->uid()]);
        return [true, $posted
            ? 'Date re-locked & ' . $posted . ' entr' . ($posted === 1 ? 'y' : 'ies') . ' re-posted to Kisan Vahi.'
            : 'Date re-locked. Normal workflow restored.'];
    }

    /** Append-only audit writer. */
    private function audit($center_id, $date, $action, $fields = [])
    {
        $ip = service('request')->getIPAddress();
        $row = array_merge([
            'center_id' => (int) $center_id, 'entry_date' => $date, 'action' => $action,
            'ip_address' => $ip, 'FY' => fy()->FY, 'template_id' => fy()->template_id,
            'created_at' => date('Y-m-d H:i:s'),
        ], $fields);
        $this->db()->table('aa_center_lock_audit')->insert($row);
    }

    /* ===================== Kisan Vahi posting (Case 5) ===================== */

    /** The farmer's mapped account_id (0=none) from reg_kisanvahidata, then kisanvahidata. */
    private function farmer_account_id($farmer_id): int
    {
        $fid = trim((string) $farmer_id);
        if ($fid === '') { return 0; }
        $fy = fy()->FY;
        if ($this->db()->tableExists('reg_kisanvahidata')) {
            $r = $this->db()->table('reg_kisanvahidata')->select('account_no')
                ->where('Farmer_ID', $fid)->where('FY', $fy)
                ->where('status <>', 'Delete')->where("COALESCE(account_no,'') <>", '')
                ->orderBy('Kisan_ID', 'desc')->limit(1)->get()->getRow();
            if ($r) { return (int) $r->account_no; }
        }
        $r = $this->db()->table('kisanvahidata')->select('account_no')
            ->where('Farmer_ID', $fid)->where('FY', $fy)
            ->where("COALESCE(status,'') <>", 'Delete')->where("COALESCE(account_no,'') <>", '')
            ->orderBy('Kisan_ID', 'desc')->limit(1)->get()->getRow();
        return $r ? (int) $r->account_no : 0;
    }

    /** Post every not-yet-posted thumb row for a center+date into kisanvahidata. */
    public function post_date($center_id, $date): int
    {
        $center_id = (int) $center_id;
        $rate = function_exists('msp_rate') ? (float) msp_rate() : 0.0;

        $b = $this->db()->table('aa_temp_thumb')->where('center_id', $center_id)->where('entry_date', $date)
            ->where('posted_kisan_id IS NULL', null, false);
        $this->scope($b);
        $rows = $b->get()->getResult();
        if (! $rows) { return 0; }

        $uid = $this->uid();
        $posted = 0;
        foreach ($rows as $r) {
            $qty = (float) $r->qty;
            $acc = $this->farmer_account_id($r->farmer_id);
            $this->db()->table('kisanvahidata')->insert([
                'mobile_no'         => '',
                'CenterName'        => (int) $r->center_id,
                'Purchase_ID'       => '',
                'Farmer_ID'         => $r->farmer_id,
                'Farmer_name'       => $r->farmer_name,
                'Quantity'          => $qty,
                'mid'               => $r->mediator_name,
                'Ammount'           => round($qty * $rate, 2),
                'Purchase_Date'     => $date,
                'Purchase_Date_new' => $date,
                'PFMS_Status'       => '',
                'Latest_Account_no' => '',
                'account_no'        => $acc ? $acc : '',
                'ack_status'        => '',
                'payment_status'    => '',
                'payment_date'      => '',
                'utr_no'            => '',
                'status'            => '',
                'status_rec'        => 'done',
                'added_date'        => date('Y-m-d'),
                'FY'                => fy()->FY,
                'added_by'          => $uid,
                'product_type'      => fy()->product_type,
                'template_id'       => fy()->template_id,
            ]);
            $kid = (int) $this->db()->insertID();
            $this->db()->table('aa_temp_thumb')->where('id', (int) $r->id)->update(['posted_kisan_id' => $kid]);
            $this->adjust_left_quantity($r->farmer_id, -$qty);
            $posted++;
        }
        return $posted;
    }

    /** Roll back every posted thumb row for a center+date (delete + restore left qty). */
    public function unpost_date($center_id, $date): int
    {
        $center_id = (int) $center_id;
        $b = $this->db()->table('aa_temp_thumb')->where('center_id', $center_id)->where('entry_date', $date)
            ->where('posted_kisan_id IS NOT NULL', null, false);
        $this->scope($b);
        $rows = $b->get()->getResult();
        if (! $rows) { return 0; }

        $removed = 0;
        foreach ($rows as $r) {
            $this->db()->table('kisanvahidata')->where('id', (int) $r->posted_kisan_id)->delete();
            $this->adjust_left_quantity($r->farmer_id, (float) $r->qty);
            $this->db()->table('aa_temp_thumb')->where('id', (int) $r->id)->update(['posted_kisan_id' => null]);
            $removed++;
        }
        return $removed;
    }

    /** Add $delta (may be negative) to the farmer's reg_kisanvahidata.left_quantity. */
    private function adjust_left_quantity($farmer_id, $delta)
    {
        if (! $this->db()->tableExists('reg_kisanvahidata')) { return; }
        $fid = trim((string) $farmer_id);
        $row = $this->db()->table('reg_kisanvahidata')->where('Farmer_ID', $fid)->where('FY', fy()->FY)
            ->orderBy('Kisan_ID', 'desc')->limit(1)->get()->getRow();
        if (! $row) { return; }
        $base = ((string) $row->left_quantity === '0.00' && $delta < 0)
            ? (float) $row->Quantity : (float) $row->left_quantity;
        $this->db()->table('reg_kisanvahidata')->where('Farmer_ID', $fid)->where('FY', fy()->FY)
            ->update(['left_quantity' => $base + $delta]);
    }

    /* ===================== records (CRUD) ===================== */

    /** All temp records for the current FY/firm, joined to the center name. */
    public function records(): array
    {
        $b = $this->db()->table('aa_temp_thumb AS t')
            ->select('t.*, c.name AS center_name', false)
            ->join('aa_center_name AS c', 'c.center_id = t.center_id', 'left');
        $this->scope($b, 't');
        return $b->orderBy('c.name', 'asc')->orderBy('t.entry_date', 'asc')->orderBy('t.id', 'asc')
            ->get()->getResult();
    }

    public function get_record($id)
    {
        $b = $this->db()->table('aa_temp_thumb')->where('id', (int) $id);
        $this->scope($b);
        return $b->get()->getRow();
    }

    /** True if the farmer already has a record on that date (one center/day rule). */
    public function duplicate_exists($farmer_id, $date, $ignore_id = 0): bool
    {
        $b = $this->db()->table('aa_temp_thumb')->where('farmer_id', trim((string) $farmer_id))->where('entry_date', $date);
        $this->scope($b);
        if ($ignore_id) { $b->where('id !=', (int) $ignore_id); }
        return $b->countAllResults() > 0;
    }

    /** The center a farmer is assigned to in temp-thumb (0=none). Excludes $ignore_id. */
    public function farmer_center($farmer_id, $ignore_id = 0): int
    {
        $b = $this->db()->table('aa_temp_thumb')->select('center_id')->where('farmer_id', trim((string) $farmer_id));
        $this->scope($b);
        if ($ignore_id) { $b->where('id !=', (int) $ignore_id); }
        $row = $b->limit(1)->get()->getRow();
        return $row ? (int) $row->center_id : 0;
    }

    /** Center display name (falls back to "#id"). */
    public function center_name($center_id): string
    {
        $r = $this->db()->table('aa_center_name')->select('name')->where('center_id', (int) $center_id)->get()->getRow();
        return $r ? $r->name : ('#' . (int) $center_id);
    }

    /** The farmer's EXISTING available quantity for the current FY (the cap). */
    public function farmer_available_qty($farmer_id): float
    {
        $fid = trim((string) $farmer_id);
        $fy  = fy()->FY;
        if ($this->db()->tableExists('reg_kisanvahidata')) {
            $col = $this->db()->fieldExists('left_quantity', 'reg_kisanvahidata')
                ? 'COALESCE(left_quantity, Quantity)' : 'Quantity';
            $r = $this->db()->table('reg_kisanvahidata')->select('COALESCE(SUM(' . $col . '),0) AS q', false)
                ->where('Farmer_ID', $fid)->where('FY', $fy)->where('status <>', 'Delete')->get()->getRow();
            if ($r && (float) $r->q > 0) { return (float) $r->q; }
        }
        $r = $this->db()->table('kisanvahidata')->select('COALESCE(SUM(Quantity),0) AS q', false)
            ->where('Farmer_ID', $fid)->where('FY', $fy)->where('status <>', 'Delete')->get()->getRow();
        return $r ? (float) $r->q : 0.0;
    }

    /** Sum of thumb qty already recorded for a farmer (current FY/firm). Excludes $ignore_id. */
    public function farmer_allocated_qty($farmer_id, $ignore_id = 0): float
    {
        $b = $this->db()->table('aa_temp_thumb')->select('COALESCE(SUM(qty),0) AS q', false)
            ->where('farmer_id', trim((string) $farmer_id));
        $this->scope($b);
        if ($ignore_id) { $b->where('id !=', (int) $ignore_id); }
        $r = $b->get()->getRow();
        return $r ? (float) $r->q : 0.0;
    }

    /** The farmer's existing assignment in temp-thumb (center + last mediator). */
    public function farmer_existing($farmer_id): array
    {
        $b = $this->db()->table('aa_temp_thumb')->select('center_id, mediator_name')
            ->where('farmer_id', trim((string) $farmer_id));
        $this->scope($b);
        $row = $b->orderBy('id', 'desc')->limit(1)->get()->getRow();
        return [
            'center_id'     => $row ? (int) $row->center_id : 0,
            'mediator_name' => $row ? (string) $row->mediator_name : '',
        ];
    }

    /** Add a thumb entry, CLUBBING into any existing row for same farmer+date. */
    public function club_or_add($d): array
    {
        $b = $this->db()->table('aa_temp_thumb')
            ->where('farmer_id', trim((string) $d['farmer_id']))->where('entry_date', $d['date']);
        $this->scope($b);
        $existing = $b->get()->getRow();
        if ($existing) {
            $med = trim((string) $d['mediator_name']);
            $this->db()->table('aa_temp_thumb')->where('id', (int) $existing->id)->update([
                'qty'           => (float) $existing->qty + (float) $d['qty'],
                'farmer_name'   => $d['farmer_name'],
                'mediator_name' => ($med !== '') ? $med : $existing->mediator_name,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
            return ['clubbed' => true, 'id' => (int) $existing->id];
        }
        return ['clubbed' => false, 'id' => $this->add_record($d)];
    }

    public function add_record($d): int
    {
        $now = date('Y-m-d H:i:s');
        $this->db()->table('aa_temp_thumb')->insert([
            'entry_date' => $d['date'], 'farmer_id' => $d['farmer_id'], 'farmer_name' => $d['farmer_name'],
            'qty' => (float) $d['qty'], 'mediator_name' => $d['mediator_name'], 'center_id' => (int) $d['center_id'],
            'FY' => fy()->FY, 'product_type' => fy()->product_type, 'template_id' => fy()->template_id,
            'added_by' => $this->uid(), 'created_at' => $now, 'updated_at' => $now,
        ]);
        return (int) $this->db()->insertID();
    }

    public function update_record($id, $d): bool
    {
        $b = $this->db()->table('aa_temp_thumb')->where('id', (int) $id);
        $this->scope($b);
        return $b->update([
            'farmer_id' => $d['farmer_id'], 'farmer_name' => $d['farmer_name'], 'qty' => (float) $d['qty'],
            'mediator_name' => $d['mediator_name'], 'center_id' => (int) $d['center_id'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete_record($id): bool
    {
        $b = $this->db()->table('aa_temp_thumb')->where('id', (int) $id);
        $this->scope($b);
        return $b->delete();
    }

    /** Cascade a farmer's center change: move EVERY other row into $new_center. */
    public function move_farmer_all($farmer_id, $new_center, $ignore_id = 0): int
    {
        $b = $this->db()->table('aa_temp_thumb')->select('id, posted_kisan_id')
            ->where('farmer_id', trim((string) $farmer_id))->where('center_id !=', (int) $new_center);
        $this->scope($b);
        if ($ignore_id) { $b->where('id !=', (int) $ignore_id); }
        $rows = $b->get()->getResult();
        if (! $rows) { return 0; }

        $now = date('Y-m-d H:i:s');
        foreach ($rows as $r) {
            $this->db()->table('aa_temp_thumb')->where('id', (int) $r->id)
                ->update(['center_id' => (int) $new_center, 'updated_at' => $now]);
            if (! empty($r->posted_kisan_id)) {
                $this->db()->table('kisanvahidata')->where('id', (int) $r->posted_kisan_id)
                    ->update(['CenterName' => (int) $new_center]);
            }
        }
        return count($rows);
    }

    /** Drag-drop: move a record to another center (same date). */
    public function move_record($id, $new_center): bool
    {
        $b = $this->db()->table('aa_temp_thumb')->where('id', (int) $id);
        $this->scope($b);
        return $b->update(['center_id' => (int) $new_center, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /* ===================== approvals / audit reads ===================== */

    public function pending_requests(): array
    {
        $b = $this->db()->table('aa_center_unlock_request AS r')
            ->select("r.*, c.name AS center_name,
                TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) AS requested_by_name", false)
            ->join('aa_center_name AS c', 'c.center_id = r.center_id', 'left')
            ->join('users AS u', 'u.id = r.requested_by', 'left')
            ->where('r.status', 'pending');
        $this->scope($b, 'r');
        return $b->orderBy('r.created_at', 'asc')->get()->getResult();
    }

    public function request_history($limit = 200): array
    {
        $b = $this->db()->table('aa_center_unlock_request AS r')
            ->select("r.*, c.name AS center_name,
                TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) AS requested_by_name,
                TRIM(CONCAT(COALESCE(a.first_name,''),' ',COALESCE(a.last_name,''))) AS approved_by_name", false)
            ->join('aa_center_name AS c', 'c.center_id = r.center_id', 'left')
            ->join('users AS u', 'u.id = r.requested_by', 'left')
            ->join('users AS a', 'a.id = r.approved_by', 'left');
        $this->scope($b, 'r');
        return $b->orderBy('r.id', 'desc')->limit((int) $limit)->get()->getResult();
    }

    public function unlocked_dates(): array
    {
        $b = $this->db()->table('aa_center_lock AS l')
            ->select("l.*, c.name AS center_name,
                TRIM(CONCAT(COALESCE(uf.first_name,''),' ',COALESCE(uf.last_name,''))) AS unlocked_for_name,
                TRIM(CONCAT(COALESCE(ub.first_name,''),' ',COALESCE(ub.last_name,''))) AS unlocked_by_name", false)
            ->join('aa_center_name AS c', 'c.center_id = l.center_id', 'left')
            ->join('users AS uf', 'uf.id = l.unlocked_for', 'left')
            ->join('users AS ub', 'ub.id = l.unlocked_by', 'left')
            ->where('l.status', 'unlocked');
        $this->scope($b, 'l');
        return $b->orderBy('l.unlocked_at', 'desc')->get()->getResult();
    }

    public function audit_log($limit = 200): array
    {
        $b = $this->db()->table('aa_center_lock_audit AS a')
            ->select("a.*, c.name AS center_name,
                TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) AS user_name,
                TRIM(CONCAT(COALESCE(rq.first_name,''),' ',COALESCE(rq.last_name,''))) AS requested_by_name,
                TRIM(CONCAT(COALESCE(ap.first_name,''),' ',COALESCE(ap.last_name,''))) AS approved_by_name", false)
            ->join('aa_center_name AS c', 'c.center_id = a.center_id', 'left')
            ->join('users AS u',  'u.id = a.user_id', 'left')
            ->join('users AS rq', 'rq.id = a.requested_by', 'left')
            ->join('users AS ap', 'ap.id = a.approved_by', 'left')
            ->where('a.template_id', fy()->template_id)
            ->where('a.FY', fy()->FY);
        return $b->orderBy('a.id', 'desc')->limit((int) $limit)->get()->getResult();
    }
}
