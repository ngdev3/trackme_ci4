<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the top-level account type that drives the whole role structure and
 * login flow:
 *   - super_admin : the SaaS operator (manages the whole application)
 *   - customer    : an account owner who signs up and creates firms
 *   - firm_user   : a user created by a customer inside a specific firm
 *
 * Backfill: existing local admin-panel accounts become super_admin; existing
 * social (Gmail) sign-ups become customers.
 */
class AddAccountTypeToUsers extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('account_type', 'users')) {
            $this->forge->addColumn('users', [
                'account_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'customer', 'after' => 'user_type_id'],
            ]);
        }

        // Local accounts run the admin panel → super_admin side.
        $this->db->table('users')->where('auth_provider', 'local')->update(['account_type' => 'super_admin']);
        // Social sign-ups are tenant customers.
        $this->db->table('users')->where('auth_provider', 'google')->update(['account_type' => 'customer']);

        $this->db->query('ALTER TABLE `users` ADD INDEX `idx_users_account_type` (`account_type`)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `users` DROP INDEX `idx_users_account_type`');
        $this->forge->dropColumn('users', 'account_type');
    }
}
