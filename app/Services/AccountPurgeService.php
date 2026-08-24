<?php

namespace App\Services;

use Config\Database;
use RuntimeException;

/**
 * Hard-deletes a customer (or user) and EVERY dependency, permanently.
 *
 * A customer owns companies (companies.owner_id); their business data is
 * company-scoped (company_id) and their personal/auth data is user-scoped
 * (user_id / customer_id). Firm-users created under the customer are removed
 * too. Because most foreign keys are ON DELETE RESTRICT, rows must be deleted
 * children-first, so the whole operation runs inside one DB transaction and
 * rolls back completely on any error.
 *
 * This is irreversible — callers must gate it behind a super-admin check and an
 * explicit confirmation. Super-admin accounts can never be purged.
 */
class AccountPurgeService
{
    protected $db;

    /** Business/company-scoped tables, ordered children → parents (FK-safe). */
    protected array $companyScoped = [
        'transaction_attachments',
        'voucher_entries',
        'vouchers',
        'rokad_entries',
        'transactions',
        'ledgers',
        'accounting_groups',
        'note_history',
        'notes',
        'note_categories',
        'passwords',
        'reminder_notifications',
        'reminders',
        'company_settings',
        'load_test_records',
        'load_test_runs',
    ];

    /** Personal / auth tables that are genuinely per-user and safe to wipe. */
    protected array $userScoped = [
        'activity_logs',
        'api_tokens',
        'calculator_history',
        'login_logs',
        'notifications',
        'push_subscriptions',
        'settings',
        'user_permissions',
        'user_roles',
        'google_play_purchases',
    ];

    /** Billing/customer-scoped tables keyed by the owning customer id. */
    protected array $customerScoped = [
        'subscriptions',
        'payment_orders',
        'coupon_redemptions',
        'google_play_purchases',
    ];

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Permanently delete $userId and everything that depends on them.
     *
     * @return array{user: array, companies: int, firm_users: int, deleted: array<string,int>}
     * @throws RuntimeException on guard failure or if the transaction rolls back.
     */
    public function purge(int $userId): array
    {
        $user = $this->db->table('users')->where('id', $userId)->get()->getRowArray();
        if (! $user) {
            throw new RuntimeException('Account not found.');
        }
        if (($user['account_type'] ?? '') === 'super_admin') {
            throw new RuntimeException('Super-admin accounts cannot be deleted.');
        }
        if ((int) $userId === (int) session('user_id')) {
            throw new RuntimeException('You cannot delete your own account.');
        }

        // Companies owned by this customer.
        $companyIds = array_map('intval', array_column(
            $this->db->table('companies')->select('id')->where('owner_id', $userId)->get()->getResultArray(),
            'id'
        ));

        // Firm-users that belong to this customer (by parent_id and by company_users link).
        $firmUserIds = array_map('intval', array_column(
            $this->db->table('users')->select('id')->where('parent_id', $userId)->get()->getResultArray(),
            'id'
        ));
        if ($this->db->fieldExists('customer_id', 'company_users')) {
            $linked = array_map('intval', array_column(
                $this->db->table('company_users')->select('user_id')->where('customer_id', $userId)->get()->getResultArray(),
                'user_id'
            ));
            $firmUserIds = array_merge($firmUserIds, $linked);
        }
        $firmUserIds = array_values(array_filter(array_unique($firmUserIds), static fn ($id) => $id !== $userId));

        $allUserIds = array_values(array_unique(array_merge([$userId], $firmUserIds)));
        $deleted    = [];

        $this->db->transException(true);
        $this->db->transStart();

        // 1. Company business data (children first).
        foreach ($this->companyScoped as $t) {
            $this->del($t, 'company_id', $companyIds, $deleted);
        }

        // 2. Any business rows still pointing at these users but in OTHER companies
        //    (e.g. a firm-user who also posted in a company we are not deleting):
        //    reassign them to that company's owner so the RESTRICT FK on users
        //    does not block the delete and the other firm keeps its data.
        $this->reassignForeignBusinessRows($allUserIds, $companyIds);

        // 3. Personal / auth data for the customer and their firm-users.
        foreach ($this->userScoped as $t) {
            $this->del($t, 'user_id', $allUserIds, $deleted);
        }

        // 4. Billing data owned by the customer.
        foreach ($this->customerScoped as $t) {
            $this->del($t, 'customer_id', [$userId], $deleted);
        }

        // 5. Company membership links, then the companies themselves.
        $this->del('company_users', 'company_id', $companyIds, $deleted);
        $this->del('company_users', 'user_id', $allUserIds, $deleted);
        $this->del('companies', 'id', $companyIds, $deleted);

        // 6. The firm-user accounts, then the customer.
        $this->del('users', 'id', $firmUserIds, $deleted);
        $this->del('users', 'id', [$userId], $deleted);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new RuntimeException('Deletion failed and was rolled back. No data was removed.');
        }

        return [
            'user'       => $user,
            'companies'  => count($companyIds),
            'firm_users' => count($firmUserIds),
            'deleted'    => array_filter($deleted),
        ];
    }

    /**
     * Reversible soft-delete: mark the customer + their firm-users and companies
     * as deleted (and deactivate them) WITHOUT removing any data, so a super admin
     * can restore them from Trash. Same guards as purge().
     *
     * @return array{user: array, companies: int, firm_users: int}
     */
    public function softDelete(int $userId): array
    {
        $user = $this->db->table('users')->where('id', $userId)->get()->getRowArray();
        if (! $user) {
            throw new RuntimeException('Account not found.');
        }
        if (($user['account_type'] ?? '') === 'super_admin') {
            throw new RuntimeException('Super-admin accounts cannot be deleted.');
        }
        if ((int) $userId === (int) session('user_id')) {
            throw new RuntimeException('You cannot delete your own account.');
        }

        [$companyIds, $firmUserIds] = $this->relations($userId);
        $allUserIds = array_values(array_unique(array_merge([$userId], $firmUserIds)));
        $now        = date('Y-m-d H:i:s');

        $this->db->transException(true);
        $this->db->transStart();
        $this->db->table('users')->whereIn('id', $allUserIds)->update(['deleted_at' => $now, 'status' => 0]);
        if ($companyIds) {
            $this->db->table('companies')->whereIn('id', $companyIds)->update(['deleted_at' => $now]);
        }
        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            throw new RuntimeException('Could not move the account to Trash.');
        }

        return ['user' => $user, 'companies' => count($companyIds), 'firm_users' => count($firmUserIds)];
    }

    /**
     * Restore a soft-deleted account: clear deleted_at + reactivate the customer,
     * their firm-users and companies.
     *
     * @return array{user: array, companies: int, firm_users: int}
     */
    public function restore(int $userId): array
    {
        $user = $this->db->table('users')->where('id', $userId)->get()->getRowArray();
        if (! $user) {
            throw new RuntimeException('Account not found.');
        }

        [$companyIds, $firmUserIds] = $this->relations($userId);
        $allUserIds = array_values(array_unique(array_merge([$userId], $firmUserIds)));

        $this->db->transException(true);
        $this->db->transStart();
        $this->db->table('users')->whereIn('id', $allUserIds)->update(['deleted_at' => null, 'status' => 1]);
        if ($companyIds) {
            $this->db->table('companies')->whereIn('id', $companyIds)->update(['deleted_at' => null]);
        }
        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            throw new RuntimeException('Could not restore the account.');
        }

        return ['user' => $user, 'companies' => count($companyIds), 'firm_users' => count($firmUserIds)];
    }

    /** Company ids owned by + firm-user ids under a customer (incl. soft-deleted). */
    private function relations(int $userId): array
    {
        $companyIds = array_map('intval', array_column(
            $this->db->table('companies')->select('id')->where('owner_id', $userId)->get()->getResultArray(),
            'id'
        ));
        $firmUserIds = array_map('intval', array_column(
            $this->db->table('users')->select('id')->where('parent_id', $userId)->get()->getResultArray(),
            'id'
        ));
        if ($this->db->fieldExists('customer_id', 'company_users')) {
            $linked = array_map('intval', array_column(
                $this->db->table('company_users')->select('user_id')->where('customer_id', $userId)->get()->getResultArray(),
                'user_id'
            ));
            $firmUserIds = array_merge($firmUserIds, $linked);
        }
        $firmUserIds = array_values(array_filter(array_unique($firmUserIds), static fn ($id) => $id !== $userId));

        return [$companyIds, $firmUserIds];
    }

    /**
     * Delete rows of $table where $column is in $ids, guarding table/column
     * existence and accumulating affected-row counts.
     */
    protected function del(string $table, string $column, array $ids, array &$deleted): void
    {
        if (empty($ids) || ! $this->db->tableExists($table) || ! $this->db->fieldExists($column, $table)) {
            return;
        }
        $this->db->table($table)->whereIn($column, $ids)->delete();
        $deleted[$table] = ($deleted[$table] ?? 0) + $this->db->affectedRows();
    }

    /**
     * For business tables that reference users with RESTRICT, move any rows that
     * belong to companies we are NOT deleting off the about-to-be-deleted users
     * and onto that company's current owner. Keeps other firms intact while
     * letting the user rows delete cleanly.
     */
    protected function reassignForeignBusinessRows(array $userIds, array $deletingCompanyIds): void
    {
        if (empty($userIds)) {
            return;
        }
        foreach (['transactions', 'transaction_attachments'] as $t) {
            if (! $this->db->tableExists($t) || ! $this->db->fieldExists('user_id', $t) || ! $this->db->fieldExists('company_id', $t)) {
                continue;
            }
            $builder = $this->db->table($t . ' t')
                ->select('t.company_id, c.owner_id')
                ->join('companies c', 'c.id = t.company_id', 'inner')
                ->whereIn('t.user_id', $userIds)
                ->groupBy('t.company_id, c.owner_id');
            if (! empty($deletingCompanyIds)) {
                $builder->whereNotIn('t.company_id', $deletingCompanyIds);
            }
            foreach ($builder->get()->getResultArray() as $row) {
                $owner = (int) $row['owner_id'];
                if ($owner === 0 || in_array($owner, $userIds, true)) {
                    continue; // no safe owner to hand off to
                }
                $this->db->table($t)
                    ->where('company_id', (int) $row['company_id'])
                    ->whereIn('user_id', $userIds)
                    ->update(['user_id' => $owner]);
            }
        }
    }
}
