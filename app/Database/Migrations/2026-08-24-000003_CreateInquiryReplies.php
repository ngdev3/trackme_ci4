<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Two-way inquiry conversations. Adds `user_id` to inquiries (so a logged-in
 * customer's inquiries are tied to their account and visible to them on web +
 * app), and an inquiry_replies thread where both the super admin and the
 * customer can post messages back and forth.
 */
class CreateInquiryReplies extends Migration
{
    public function up()
    {
        // Link inquiries to the account that raised them (nullable — public
        // contact-form submissions from non-users stay unlinked).
        if (! $this->db->fieldExists('user_id', 'inquiries')) {
            $this->forge->addColumn('inquiries', [
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'id'],
            ]);
        }
        // Unread-by-customer marker so the app/web can badge new admin replies.
        if (! $this->db->fieldExists('customer_unread', 'inquiries')) {
            $this->forge->addColumn('inquiries', [
                'customer_unread' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'status'],
            ]);
        }

        if (! $this->db->tableExists('inquiry_replies')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'inquiry_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'sender_type' => ['type' => 'VARCHAR', 'constraint' => 10],            // admin | customer
                'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'name'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'message'     => ['type' => 'TEXT'],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('inquiry_id');
            $this->forge->createTable('inquiry_replies', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('inquiry_replies', true);
        if ($this->db->fieldExists('user_id', 'inquiries')) {
            $this->forge->dropColumn('inquiries', 'user_id');
        }
        if ($this->db->fieldExists('customer_unread', 'inquiries')) {
            $this->forge->dropColumn('inquiries', 'customer_unread');
        }
    }
}
