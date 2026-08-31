<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * AccountModel — CI4 port of admin/models/Account_mod (Jama/Naam voucher core).
 *
 * The cash-book WRITE path: inserts one aa_rokad row (Deposit/जमा or
 * Expenditure/नाम), resolves/creates the aa_account_name party. Soft-delete
 * convention on aa_rokad is status='Delete' (never physically removed).
 */
class AccountModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Insert one cash-book (aa_rokad) row. Returns the new rokad_id. */
    public function add(array $data): int
    {
        $this->db()->table('aa_rokad')->insert($data);
        return (int) $this->db()->insertID();
    }

    /** Update one aa_rokad row by rokad_id. */
    public function edit($id, array $userdata): bool
    {
        $this->db()->table('aa_rokad')->where('rokad_id', $id)->update($userdata);
        return $this->db()->affectedRows() >= 0;
    }

    /** Create a new party in aa_account_name. Returns the new account_id. */
    public function add_account(array $data): int
    {
        $this->db()->table('aa_account_name')->insert($data);
        return (int) $this->db()->insertID();
    }

    /** Find a non-deleted account by exact name (case/space-insensitive). */
    public function get_account_by_name(string $name)
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        return $this->db()->table('aa_account_name')
            ->where('status !=', 'Delete')
            ->where("LOWER(TRIM(name)) =", strtolower($name))
            ->orderBy('account_id', 'asc')->limit(1)
            ->get()->getRow();
    }
}
