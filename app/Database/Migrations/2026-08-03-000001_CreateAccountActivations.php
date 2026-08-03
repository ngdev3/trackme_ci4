<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One-click account-activation links for self-service email/password signups.
 * A row holds a hashed activation token (sha256) with an expiry; visiting the
 * emailed link (/activate/{token}) marks the account active. Mirrors
 * password_resets. See AuthApiController::register + AuthController::activate.
 */
class CreateAccountActivations extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('account_activations')) {
            return;
        }
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 191],
            'token'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('email');
        $this->forge->createTable('account_activations', true);
    }

    public function down()
    {
        $this->forge->dropTable('account_activations', true);
    }
}
