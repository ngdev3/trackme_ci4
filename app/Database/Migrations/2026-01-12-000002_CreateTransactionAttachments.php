<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Unlimited attachments per transaction — photos, camera captures, audio/video,
 * PDF, Word, Excel and other common files. The physical file lives under
 * writable/uploads/transactions/<user_id>/; this table records the metadata and
 * a 'kind' bucket (image|audio|video|pdf|doc|sheet|file) used to pick a preview.
 */
class CreateTransactionAttachments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'transaction_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'company_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'original_name'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'stored_name'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'mime'           => ['type' => 'VARCHAR', 'constraint' => 127, 'null' => true],
            'kind'           => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'file'], // image|audio|video|pdf|doc|sheet|file
            'size'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'created_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('transaction_id');
        $this->forge->createTable('transaction_attachments', true);
    }

    public function down()
    {
        $this->forge->dropTable('transaction_attachments', true);
    }
}
