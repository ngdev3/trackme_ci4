<?php

/**
 * farmer_capture_helper (CI4) — inbox/read side of the Farmer Capture feature.
 * The browser extension pushes scraped farmer rows into `farmer_capture`
 * (public receiver, ported separately); this helper powers the admin inbox
 * (admin/accountMapping/captures): list, fetch, mark, delete, count. The table
 * is lazily created (self-heals). CI3 parity of farmer_capture_helper.php.
 */

use Config\Database;

if (! function_exists('fc_db')) {
    function fc_db()
    {
        return Database::connect();
    }
}

if (! function_exists('fc_ensure_table')) {
    function fc_ensure_table(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        fc_db()->query("CREATE TABLE IF NOT EXISTS farmer_capture (
            id INT AUTO_INCREMENT PRIMARY KEY,
            source_url   VARCHAR(500) NULL,
            farmer_id    VARCHAR(100) NULL,
            farmer_name  VARCHAR(255) NULL,
            father_name  VARCHAR(255) NULL,
            mobile_no    VARCHAR(20)  NULL,
            aadhaar_masked VARCHAR(20) NULL,
            village      VARCHAR(255) NULL,
            bank_name    VARCHAR(255) NULL,
            account_no   VARCHAR(50)  NULL,
            ifsc         VARCHAR(20)  NULL,
            quantity     VARCHAR(50)  NULL,
            raw_json     MEDIUMTEXT   NULL,
            status       ENUM('new','used','archived') NOT NULL DEFAULT 'new',
            captured_ip  VARCHAR(45)  NULL,
            captured_by  INT          NULL,
            created_at   DATETIME     NULL,
            used_at      DATETIME     NULL,
            KEY idx_fc_status (status),
            KEY idx_fc_farmer (farmer_id),
            KEY idx_fc_capturedby (captured_by)
        )");

        // Existing installs (table created before per-user attribution): add the
        // column in place so captures can be tied to the user who sent them.
        if (! fc_db()->fieldExists('captured_by', 'farmer_capture')) {
            fc_db()->query("ALTER TABLE farmer_capture ADD COLUMN captured_by INT NULL AFTER captured_ip");
            fc_db()->query("ALTER TABLE farmer_capture ADD KEY idx_fc_capturedby (captured_by)");
        }
    }
}

if (! function_exists('fc_list')) {
    /** Captured farmers by status ('new'|'used'|'archived'|'all'), newest first. */
    function fc_list($status = 'new', $limit = 200): array
    {
        fc_ensure_table();
        $limit = max(1, min((int) $limit, 200));
        $b = fc_db()->table('farmer_capture')
            ->select('farmer_capture.id, farmer_capture.source_url, farmer_capture.farmer_id, farmer_capture.farmer_name, farmer_capture.father_name, farmer_capture.mobile_no, farmer_capture.aadhaar_masked, farmer_capture.village, farmer_capture.bank_name, farmer_capture.account_no, farmer_capture.ifsc, farmer_capture.quantity, farmer_capture.status, farmer_capture.captured_ip, farmer_capture.captured_by, farmer_capture.created_at, farmer_capture.used_at, u.id AS cap_uid, u.first_name AS cap_first, u.last_name AS cap_last, u.mobile AS cap_mobile')
            ->join('users u', 'u.id = farmer_capture.captured_by', 'left');
        if ($status !== 'all') {
            $b->where('farmer_capture.status', $status);
        }
        return $b->orderBy('farmer_capture.id', 'desc')->limit($limit)->get()->getResult();
    }
}

if (! function_exists('fc_get')) {
    /** One capture row by id. */
    function fc_get($id)
    {
        fc_ensure_table();
        return fc_db()->table('farmer_capture')->where('id', (int) $id)->get()->getRow();
    }
}

if (! function_exists('fc_mark')) {
    /** Set a capture's status ('used'|'archived'|'new'). */
    function fc_mark($id, $status): void
    {
        fc_ensure_table();
        $data = ['status' => $status];
        if ($status === 'used') {
            $data['used_at'] = date('Y-m-d H:i:s');
        }
        fc_db()->table('farmer_capture')->where('id', (int) $id)->update($data);
    }
}

if (! function_exists('fc_delete')) {
    /** Hard-delete a capture (staging data, no soft-delete needed). */
    function fc_delete($id): void
    {
        fc_ensure_table();
        fc_db()->table('farmer_capture')->where('id', (int) $id)->delete();
    }
}

if (! function_exists('fc_count_new')) {
    /** Count of unreviewed ('new') captures — for a menu badge. */
    function fc_count_new(): int
    {
        fc_ensure_table();
        return (int) fc_db()->table('farmer_capture')->where('status', 'new')->countAllResults();
    }
}

if (! function_exists('fc_mask_aadhaar')) {
    function fc_mask_aadhaar($value)
    {
        $value = (string) $value;
        return preg_replace_callback('/\b(\d{12})\b/', fn($m) => 'XXXX-XXXX-' . substr($m[1], -4), $value);
    }
}
