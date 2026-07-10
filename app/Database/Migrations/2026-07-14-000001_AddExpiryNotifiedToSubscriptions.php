<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One-time "trial/subscription lapsed" notification marker. When a customer's
 * trial or paid window expires we notify them once and stamp this column so the
 * lifecycle filter never re-notifies on every page load. Cleared on renewal so a
 * future expiry can notify again.
 */
class AddExpiryNotifiedToSubscriptions extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('expiry_notified_at', 'subscriptions')) {
            $this->forge->addColumn('subscriptions', [
                'expiry_notified_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'expires_at'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('expiry_notified_at', 'subscriptions')) {
            $this->forge->dropColumn('subscriptions', 'expiry_notified_at');
        }
    }
}
