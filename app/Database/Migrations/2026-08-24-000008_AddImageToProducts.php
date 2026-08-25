<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Product image — a per-product photo shown as the item's picture in the app.
 * Stores a relative path under public/uploads/products/<company>/.
 */
class AddImageToProducts extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('image_path', 'products')) {
            $this->forge->addColumn('products', [
                'image_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'category'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('image_path', 'products')) {
            $this->forge->dropColumn('products', 'image_path');
        }
    }
}
