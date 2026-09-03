<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/** AccountMappingModel — CI4 port (slice: center list for the Farmer Captures inbox). */
class AccountMappingModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Active centers (aa_center_name). Returns false when none, matching CI3. */
    public function center_list()
    {
        $rows = $this->db()->table('aa_center_name')->where('status', 'Active')->get()->getResult();
        return $rows ?: false;
    }

    /* ================= Khata Naksha — farmer→account mapping (CI3 parity) ================= */

    /** Active accounts (id + name) for the mapping picker datalist. */
    public function account_name_list(): array
    {
        return $this->db()->table('aa_account_name')
            ->select('account_id, name')->orderBy('name', 'asc')
            ->get()->getResult();
    }

    /**
     * Extract the numeric account id from the "Name_ID" picker value. Names can
     * contain underscores, so take the TRAILING _<digits> only.
     */
    private function parse_account_id($raw): int
    {
        if (preg_match('/_(\d+)\s*$/', (string) $raw, $m)) { return (int) $m[1]; }
        return 0;
    }

    /** True if the account id exists in aa_account_name. */
    private function account_exists($id): bool
    {
        if ((int) $id <= 0) { return false; }
        return $this->db()->table('aa_account_name')->where('account_id', (int) $id)->countAllResults() > 0;
    }

    /** Shared multi-tenant scope: current FY + firm + product, matching center, live. */
    private function mapScope($builder, string $farmer_id, string $center)
    {
        $builder->where('Farmer_ID', $farmer_id)
            ->where('FY', fy()->FY)
            ->where('product_type', fy()->product_type)
            ->where('template_id', fy()->template_id);
        if ($center !== '') { $builder->where('CenterName', $center); }
        $builder->where("COALESCE(status,'') <>", 'Delete');
        return $builder;
    }

    /**
     * Map a farmer's Kisan Vahi purchases to an account (status_rec='done').
     * Scoped by FY + product_type + template_id (+ center guard), soft-deletes
     * excluded. Returns ['status'=>'success'|'nochange'|'error','msg'=>..,'count'=>N].
     */
    public function account_mapping(string $account_name_raw, string $farmer_id, string $center): array
    {
        $account_id = $this->parse_account_id($account_name_raw);
        if (! $this->account_exists($account_id)) {
            return ['status' => 'error', 'msg' => 'Please choose a valid account from the list.', 'count' => 0];
        }
        if ($farmer_id === '') {
            return ['status' => 'error', 'msg' => 'Farmer ID is missing.', 'count' => 0];
        }

        $matched = $this->mapScope($this->db()->table('kisanvahidata'), $farmer_id, $center)->countAllResults();
        if ($matched === 0) {
            return ['status' => 'error', 'msg' => 'No purchase rows found for this farmer in the current year / center.', 'count' => 0];
        }

        $b = $this->mapScope($this->db()->table('kisanvahidata'), $farmer_id, $center);
        $b->update(['account_no' => $account_id, 'status_rec' => 'done']);
        $changed = $this->db()->affectedRows();

        if ($changed > 0) {
            return ['status' => 'success', 'msg' => 'Mapped ' . $matched . ' purchase row(s) to the selected account.', 'count' => $matched];
        }
        return ['status' => 'nochange', 'msg' => 'This farmer is already mapped to the selected account (' . $matched . ' row(s)).', 'count' => $matched];
    }

    /**
     * Reverse a mapping: take a farmer's recorded (status_rec='done') rows back
     * to pending and clear the account (account_no=0). Same multi-tenant scope.
     */
    public function account_unmap(string $farmer_id, string $center): array
    {
        if ($farmer_id === '') {
            return ['status' => 'error', 'msg' => 'Farmer ID is missing.', 'count' => 0];
        }

        $scoped = function () use ($farmer_id, $center) {
            $b = $this->db()->table('kisanvahidata')->where('status_rec', 'done');
            return $this->mapScope($b, $farmer_id, $center);
        };

        $matched = $scoped()->countAllResults();
        if ($matched === 0) {
            return ['status' => 'error', 'msg' => 'No mapped rows found for this farmer to unmap.', 'count' => 0];
        }

        $scoped()->update(['account_no' => 0, 'status_rec' => 'pending']);
        $changed = $this->db()->affectedRows();

        if ($changed > 0) {
            return ['status' => 'success', 'msg' => 'Unmapped ' . $matched . ' purchase row(s) — set back to pending.', 'count' => $matched];
        }
        return ['status' => 'nochange', 'msg' => 'Nothing changed for this farmer.', 'count' => $matched];
    }

    /** How many rows in the current FY/firm/product are mapped to the posted account. */
    public function count_account_mapping(string $account_name_raw): int
    {
        $account_id = $this->parse_account_id($account_name_raw);
        if ($account_id <= 0) { return 0; }
        return $this->db()->table('kisanvahidata')
            ->where('account_no', $account_id)
            ->where('FY', fy()->FY)
            ->where('product_type', fy()->product_type)
            ->where('template_id', fy()->template_id)
            ->where("COALESCE(status,'') <>", 'Delete')
            ->countAllResults();
    }

    /* ================= Thumb Figure support (aa_center_thumb_target, kisanvahidata.mid) ================= */

    /** Create the target + daily tables and the kisanvahidata.mid column once. */
    public function thumb_ensure_schema()
    {
        static $done = false;
        if ($done) { return; }
        $done = true;
        $db = $this->db();

        $db->query("CREATE TABLE IF NOT EXISTS `aa_center_thumb_target` (
            `id`            INT AUTO_INCREMENT PRIMARY KEY,
            `center_id`     INT NOT NULL,
            `FY`            VARCHAR(20) NOT NULL,
            `template_id`   INT NOT NULL,
            `expected_qty`  DECIMAL(10,2) NOT NULL DEFAULT 0,
            `expected_time` VARCHAR(20) NULL,
            `added_by`      INT NULL,
            `created_at`    DATETIME NULL,
            `updated_at`    DATETIME NULL,
            UNIQUE KEY `uniq_center_fy_tpl` (`center_id`, `FY`, `template_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $col = $db->query("SHOW COLUMNS FROM `kisanvahidata` LIKE 'mid'");
        if ($col->getNumRows() === 0) {
            $db->query("ALTER TABLE `kisanvahidata` ADD COLUMN `mid` VARCHAR(50) NULL AFTER `Quantity`");
        }

        $db->query("CREATE TABLE IF NOT EXISTS `aa_thumb_figure_daily` (
            `id`          INT AUTO_INCREMENT PRIMARY KEY,
            `entry_date`  DATE NOT NULL,
            `farmer_id`   VARCHAR(255) NOT NULL,
            `farmer_name` VARCHAR(255) NOT NULL,
            `center_id`   INT NOT NULL DEFAULT 0,
            `qty`         DECIMAL(10,2) NOT NULL DEFAULT 0,
            `mid`         VARCHAR(100) NULL,
            `FY`          VARCHAR(20) NOT NULL,
            `product_type` INT NOT NULL DEFAULT 0,
            `template_id` INT NOT NULL DEFAULT 0,
            `added_by`    INT NULL,
            `created_at`  DATETIME NULL,
            `updated_at`  DATETIME NULL,
            KEY `idx_scope` (`entry_date`, `FY`, `product_type`, `template_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /**
     * Farmer autocomplete for the Temp Thumb add box: distinct farmers in the
     * current FY (from kisanvahidata + reg_kisanvahidata), matched by ID/name,
     * carrying their most-recent center and REMAINING allocatable quantity
     * (registered/left minus what temp-thumb already holds); farmers at 0 remaining
     * are dropped. Raw SQL preserved from CI3 (GROUP_CONCAT commas → escape off).
     */
    public function thumb_farmer_search($q, $limit = 30): array
    {
        $this->thumb_ensure_schema();
        $db  = $this->db();
        $q   = trim((string) $q);
        $fy  = $db->escape(fy()->FY);
        $pt  = $db->escape(fy()->product_type);
        $tid = $db->escape(fy()->template_id);
        $lim = (int) $limit;

        $reg_qcol = $db->fieldExists('left_quantity', 'reg_kisanvahidata')
            ? 'COALESCE(left_quantity, Quantity)' : 'Quantity';

        $inner = "SELECT Farmer_ID, Farmer_name, CenterName AS cname, Purchase_Date_new AS pdate, Quantity AS q, 'kv' AS src
                    FROM kisanvahidata
                   WHERE FY = $fy AND status <> 'Delete'
                  UNION ALL
                  SELECT Farmer_ID, Farmer_name, NULL AS cname, reg_date AS pdate, $reg_qcol AS q, 'reg' AS src
                    FROM reg_kisanvahidata
                   WHERE FY = $fy AND status <> 'Delete'";

        $where = '';
        if ($q !== '') {
            $like  = $db->escapeLikeString($q);
            $where = "WHERE (u.Farmer_ID LIKE '%$like%' OR u.Farmer_name LIKE '%$like%')";
        }

        $grouped = "SELECT u.Farmer_ID AS farmer_id,
                           MAX(u.Farmer_name) AS farmer_name,
                           SUBSTRING_INDEX(GROUP_CONCAT(u.cname ORDER BY u.pdate DESC), ',', 1) AS center_id,
                           CASE WHEN SUM(CASE WHEN u.src='reg' THEN u.q ELSE 0 END) > 0
                                THEN SUM(CASE WHEN u.src='reg' THEN u.q ELSE 0 END)
                                ELSE SUM(CASE WHEN u.src='kv'  THEN u.q ELSE 0 END) END AS available
                      FROM ($inner) u
                      $where
                     GROUP BY u.Farmer_ID";

        $alloc_join = '';
        $alloc_expr = '0';
        if ($db->tableExists('aa_temp_thumb')) {
            $alloc_join = "LEFT JOIN (SELECT farmer_id, SUM(qty) AS alloc FROM aa_temp_thumb
                               WHERE FY = $fy AND product_type = $pt AND template_id = $tid
                               GROUP BY farmer_id) a ON a.farmer_id = g.farmer_id";
            $alloc_expr = 'COALESCE(a.alloc,0)';
        }

        $sql = "SELECT g.farmer_id, g.farmer_name, g.center_id, g.available,
                       GREATEST(g.available - $alloc_expr, 0) AS remaining
                  FROM ($grouped) g
                  $alloc_join
                 HAVING remaining > 0
                 ORDER BY g.farmer_name ASC
                 LIMIT $lim";

        $rows = $db->query($sql)->getResult();

        $ids = [];
        foreach ($rows as $r) { if ((int) $r->center_id) { $ids[(int) $r->center_id] = true; } }
        $names = [];
        if ($ids) {
            foreach ($db->table('aa_center_name')->select('center_id, name')
                         ->whereIn('center_id', array_keys($ids))->get()->getResult() as $c) {
                $names[(int) $c->center_id] = $c->name;
            }
        }
        foreach ($rows as $r) {
            $cid = (int) $r->center_id;
            $r->center_id   = $cid;
            $r->center_name = $names[$cid] ?? '';
        }
        return $rows;
    }

    /**
     * The farmer's default Mediator = the account they are registered under
     * (reg_kisanvahidata.account_no, else kisanvahidata.account_no) as its name.
     */
    public function farmer_reg_account_name($farmer_id): string
    {
        $fid = trim((string) $farmer_id);
        if ($fid === '') { return ''; }
        $fy  = fy()->FY;
        $db  = $this->db();

        $acc = 0;
        if ($db->tableExists('reg_kisanvahidata')) {
            $row = $db->table('reg_kisanvahidata')->select('account_no')->where('Farmer_ID', $fid)->where('FY', $fy)
                ->where('status <>', 'Delete')->where("COALESCE(account_no,'') <>", '')
                ->orderBy('Kisan_ID', 'desc')->limit(1)->get()->getRow();
            if ($row) { $acc = (int) $row->account_no; }
        }
        if (! $acc) {
            $row = $db->table('kisanvahidata')->select('account_no')->where('Farmer_ID', $fid)->where('FY', $fy)
                ->where('status <>', 'Delete')->where("COALESCE(account_no,'') <>", '')
                ->orderBy('Kisan_ID', 'desc')->limit(1)->get()->getRow();
            if ($row) { $acc = (int) $row->account_no; }
        }
        if (! $acc) { return ''; }

        $an = $db->table('aa_account_name')->select('name')->where('account_id', $acc)
            ->where('status !=', 'Delete')->get()->getRow();
        return $an ? (string) $an->name : '';
    }

    /** Account-name autocomplete for the "Mid" field (global accounts, active only). */
    public function thumb_account_search($q, $limit = 30): array
    {
        $q = trim((string) $q);
        $b = $this->db()->table('aa_account_name')->select('account_id AS id, name', false)->where('status', 'Active');
        if ($q !== '') {
            $b->groupStart()->like('name', $q)->orLike('account_id', $q)->groupEnd();
        }
        return $b->orderBy('name', 'asc')->limit((int) $limit)->get()->getResult();
    }
}
