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
}
