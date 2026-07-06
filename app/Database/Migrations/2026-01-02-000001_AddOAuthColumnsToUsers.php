<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds social-login (OAuth) support to the users table:
 *   - auth_provider : which provider created/owns the account ('local', 'google', …)
 *   - provider_id   : the provider's stable user id (Google "sub")
 *   - avatar_url    : remote profile-picture URL (kept separate from the
 *                     local-upload `profile_image` so path logic never collides)
 * Also relaxes `password` to NULL — social accounts have no local password.
 */
class AddOAuthColumnsToUsers extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('auth_provider', 'users')) {
            $fields['auth_provider'] = [
                'type' => 'VARCHAR', 'constraint' => 30, 'default' => 'local', 'after' => 'password',
            ];
        }
        if (! $this->db->fieldExists('provider_id', 'users')) {
            $fields['provider_id'] = [
                'type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'after' => 'auth_provider',
            ];
        }
        if (! $this->db->fieldExists('avatar_url', 'users')) {
            $fields['avatar_url'] = [
                'type' => 'VARCHAR', 'constraint' => 512, 'null' => true, 'after' => 'profile_image',
            ];
        }
        if ($fields !== []) {
            $this->forge->addColumn('users', $fields);
        }

        // Social accounts have no local password.
        $this->forge->modifyColumn('users', [
            'password' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);

        // Fast lookup + integrity for (provider, provider_id) pairs.
        $indexes = $this->db->getIndexData('users');
        if (! isset($indexes['idx_users_provider'])) {
            $this->db->query('ALTER TABLE `users` ADD INDEX `idx_users_provider` (`auth_provider`, `provider_id`)');
        }
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `users` DROP INDEX `idx_users_provider`');
        foreach (['auth_provider', 'provider_id', 'avatar_url'] as $col) {
            if ($this->db->fieldExists($col, 'users')) {
                $this->forge->dropColumn('users', $col);
            }
        }
    }
}
