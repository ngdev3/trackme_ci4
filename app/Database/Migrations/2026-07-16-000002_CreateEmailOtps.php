<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One-time codes for email verification at signup (and any future email-verify
 * step). One row per issued code; the code itself is stored only as a SHA-256
 * hash. Rows are single-use (consumed_at) and short-lived (expires_at), and
 * attempts is bumped on each wrong guess so we can lock out brute force.
 */
class CreateEmailOtps extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'email'       => ['type' => 'VARCHAR', 'constraint' => 191],
            'purpose'     => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'email_verify'],
            'code_hash'   => ['type' => 'VARCHAR', 'constraint' => 64], // sha256 of the code
            'attempts'    => ['type' => 'TINYINT', 'constraint' => 2, 'default' => 0],
            'consumed_at' => ['type' => 'DATETIME', 'null' => true],
            'expires_at'  => ['type' => 'DATETIME'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['email', 'purpose']);
        $this->forge->addKey('expires_at');
        $this->forge->createTable('email_otps', true);
    }

    public function down()
    {
        $this->forge->dropTable('email_otps', true);
    }
}
