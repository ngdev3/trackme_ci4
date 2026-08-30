<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * AccountNameModel — CI4 port of admin/models/AccountName_mod (LISTING slice).
 * aa_account_name is a GLOBAL master (trade parties + farmers; no template scope).
 * This ports the core DataTables read path + status toggle + soft delete/restore
 * + quick update. The Tally-group / accounting-group / ledger-balance / GST-verify
 * enrichment (which needs the accounting + gstin subsystems) is a follow-up.
 */
class AccountNameModel
{
    private string $table = 'aa_account_name';

    protected function db()
    {
        return Database::connect();
    }

    /** Headline counts for the listing KPI tiles. */
    public function listingSummary(): array
    {
        $r = $this->db()->query("SELECT
                COUNT(*) AS total,
                SUM(status='Active' OR status IS NULL OR status='') AS active,
                SUM(status='Inactive') AS inactive,
                SUM(purchaser_gst_no IS NOT NULL AND purchaser_gst_no<>'') AS with_gst,
                SUM(is_farmer=1) AS farmers
            FROM `{$this->table}`")->getRow();
        return [
            'total'    => (int) $r->total, 'active' => (int) $r->active, 'inactive' => (int) $r->inactive,
            'with_gst' => (int) $r->with_gst, 'farmers' => (int) $r->farmers,
        ];
    }

    /** DataTables total (search-aware, honours ?status= / ?is_farmer=). */
    public function countBillingData(): int
    {
        $b = $this->db()->table($this->table . ' ab')->select('ab.account_id');
        $this->applyFilters($b);
        return $b->countAllResults();
    }

    /** One page of accounts for the DataTable. */
    public function getBillingData(): array
    {
        $req  = service('request');
        $post = $req->getPost();

        $b = $this->db()->table($this->table . ' ab')->select('ab.*');
        $this->applyFilters($b);

        $columns = [1 => 'account_id', 2 => 'name', 3 => 'contact_person_name', 4 => 'purchaser_gst_no', 8 => 'entry_source', 9 => 'status'];
        if (! empty($post['order'][0]['column']) && ! empty($post['order'][0]['dir'])) {
            $b->orderBy($columns[$post['order'][0]['column']] ?? 'account_id', $post['order'][0]['dir']);
        } else {
            $b->orderBy('account_id', 'desc');
        }
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }

    private function applyFilters($b): void
    {
        $req  = service('request');
        $post = $req->getPost();

        $status = $req->getGet('status');
        if ($status !== null && $status !== '') { $b->where('ab.status', $status); }
        if ($req->getGet('is_farmer') === '1') { $b->where('ab.is_farmer', 1); }

        if (! empty($post['search']['value'])) {
            $b->groupStart()
              ->like('ab.name', $post['search']['value'])
              ->orLike('ab.contact_person_name', $post['search']['value'])
              ->orLike('ab.purchaser_gst_no', $post['search']['value'])
              ->orLike('ab.account_id', $post['search']['value'])
              ->groupEnd();
        }
    }

    public function getOne(int $accountId)
    {
        return $this->db()->table($this->table)->where('account_id', $accountId)->get()->getRow();
    }

    public function setStatus(int $accountId, string $status): bool
    {
        $status = in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active';
        $this->db()->table($this->table)->where('account_id', $accountId)->update(['status' => $status, 'updated_date' => date('Y-m-d')]);
        return true;
    }

    public function softDelete(int $accountId): bool
    {
        $this->db()->table($this->table)->where('account_id', $accountId)->update(['status' => 'Delete', 'updated_date' => date('Y-m-d')]);
        return true;
    }

    public function restore(int $accountId): bool
    {
        $this->db()->table($this->table)->where('account_id', $accountId)->update(['status' => 'Active', 'updated_date' => date('Y-m-d')]);
        return true;
    }

    /** Inline quick-edit (name / contact / GST / status). */
    public function quickUpdate(int $accountId, array $data): bool
    {
        $allowed = ['name', 'contact_person_name', 'contact_person_number', 'purchaser_gst_no', 'status'];
        $clean = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) { $clean[$f] = $data[$f]; }
        }
        if (! $clean) { return false; }
        $clean['updated_date'] = date('Y-m-d');
        $this->db()->table($this->table)->where('account_id', $accountId)->update($clean);
        return true;
    }

    /** Is this account referenced by a cash-book entry? (delete-safety). */
    public function hasRokad(int $accountId): bool
    {
        return $this->db()->table('aa_rokad')->where('account_no', $accountId)->where('status <>', 'Delete')->countAllResults() > 0;
    }
}
