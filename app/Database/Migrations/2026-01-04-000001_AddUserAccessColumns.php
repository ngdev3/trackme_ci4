<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-user access controls:
 *   - must_change_password : force a password change on next login
 *   - mobile_login_enabled : may this account sign in to the mobile app / API
 *   - web_push_enabled     : legacy column retained for existing installs
 *   - parent_id            : the user who created/controls this account (hierarchy)
 */
class AddUserAccessColumns extends Migration
{
    public function up()
    {
        $fields = [
            'must_change_password' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'status'],
            'mobile_login_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'must_change_password'],
            'web_push_enabled'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'mobile_login_enabled'],
            'parent_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'user_type_id'],
        ];
        $this->forge->addColumn('users', $fields);
        $this->db->query('ALTER TABLE `users` ADD INDEX `users_parent_id` (`parent_id`)');
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['must_change_password', 'mobile_login_enabled', 'web_push_enabled', 'parent_id']);
    }
}
