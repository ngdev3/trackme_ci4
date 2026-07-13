<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Records when a user confirmed their email via the OTP flow. NULL = unverified.
 * OAuth (Google) sign-ups arrive already email-verified by the provider, so the
 * account-creation path can stamp this immediately.
 */
class AddEmailVerifiedAtToUsers extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('email_verified_at', 'users')) {
            $this->forge->addColumn('users', [
                'email_verified_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'email'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('email_verified_at', 'users')) {
            $this->forge->dropColumn('users', 'email_verified_at');
        }
    }
}
