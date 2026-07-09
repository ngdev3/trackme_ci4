<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Mandi Inventory — core schema. A worker-friendly stock system for mandis, rice
 * mills, cold storages and traders. Everything is company-scoped (multi-tenant).
 *
 * Tables:
 *   inv_products    — product master (potato, rice, wheat …) with avg bag weight
 *   inv_warehouses  — godowns / warehouses
 *   inv_parties     — suppliers / farmers / customers
 *   inv_lots        — a batch of goods received (auto lot number)
 *   inv_movements   — THE inventory ledger: every inward / outward / adjustment
 *   inv_stock       — running balance per (product, warehouse) for fast reads
 *   inv_attachments — photo / doc / voice proof linked to a movement
 *
 * Also registers the `inventory` module and grants Admin + Viewer roles CRUD so
 * customers/firm users (Viewer) and admins can use it.
 */
class CreateInventoryCore extends Migration
{
    public function up()
    {
        // ---- Products ----
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'unit'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'bag'],
            'avg_weight'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0], // kg per bag
            'sku'         => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],     // QR / barcode
            'low_stock'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],         // low-stock threshold (bags)
            'status'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'name']);
        $this->forge->createTable('inv_products', true);

        // ---- Warehouses / godowns ----
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'location'   => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'capacity'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0], // capacity in bags (0 = unlimited)
            'status'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'name']);
        $this->forge->createTable('inv_warehouses', true);

        // ---- Parties (suppliers / farmers / customers) ----
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'both'], // supplier|customer|both
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'status'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'name']);
        $this->forge->createTable('inv_parties', true);

        // ---- Lots (a received batch) ----
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'lot_no'          => ['type' => 'VARCHAR', 'constraint' => 40],
            'product_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'warehouse_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'party_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'rack'            => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'opening_bags'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'opening_weight'  => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'remaining_bags'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'lot_no']);
        $this->forge->createTable('inv_lots', true);

        // ---- Movements = the inventory ledger ----
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'entry_no'      => ['type' => 'VARCHAR', 'constraint' => 40],
            'movement_type' => ['type' => 'VARCHAR', 'constraint' => 12], // inward|outward|adjustment
            'direction'     => ['type' => 'TINYINT', 'constraint' => 2, 'default' => 1], // +1 in, -1 out
            'product_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'warehouse_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'party_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'lot_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'bags'          => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'weight'        => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'rack'          => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'vehicle_no'    => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'reason'        => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true], // adjustment reason
            'notes'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'photo'         => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'source'        => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'web'], // web|mobile|voice
            'day_closed'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_by'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'movement_type']);
        $this->forge->addKey(['company_id', 'product_id', 'warehouse_id']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('inv_movements', true);

        // ---- Stock balance (fast current stock per product+warehouse) ----
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'product_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'warehouse_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'bags'         => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'weight'       => ['type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('inv_stock', true);
        // One balance row per company+product+warehouse.
        $this->db->query('ALTER TABLE `inv_stock` ADD UNIQUE KEY `uq_stock` (`company_id`,`product_id`,`warehouse_id`)');

        // ---- Attachments (proof) ----
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'movement_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'product_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'lot_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'party_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'kind'          => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'image'], // image|video|audio|pdf|doc
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 191],
            'stored_name'   => ['type' => 'VARCHAR', 'constraint' => 191],
            'mime'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'size'          => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_by'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'movement_id']);
        $this->forge->createTable('inv_attachments', true);

        // ---- Register module + grant Admin/Viewer roles ----
        $this->registerModule();
    }

    private function registerModule(): void
    {
        if (! $this->db->tableExists('modules')) {
            return;
        }
        $module = $this->db->table('modules')->where('code', 'inventory')->get()->getRowArray();
        if ($module) {
            $moduleId = (int) $module['id'];
        } else {
            $maxSort = (int) ($this->db->table('modules')->selectMax('sort_order')->get()->getRowArray()['sort_order'] ?? 0);
            $this->db->table('modules')->insert([
                'name' => 'Inventory', 'code' => 'inventory', 'url' => 'inventory',
                'icon' => 'bi bi-box-seam', 'parent_id' => null, 'sort_order' => $maxSort + 1,
                'is_menu' => 1, 'status' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $moduleId = (int) $this->db->insertID();
        }

        $perms = [];
        foreach ($this->db->table('permissions')->select('id, code')->get()->getResultArray() as $p) {
            $perms[$p['code']] = (int) $p['id'];
        }
        foreach (['admin', 'viewer'] as $roleCode) {
            $role = $this->db->table('roles')->where('code', $roleCode)->get()->getRowArray();
            if (! $role) {
                continue;
            }
            $rows = [];
            foreach (['view', 'add', 'edit', 'delete', 'approve', 'export', 'print'] as $action) {
                if (! isset($perms[$action])) {
                    continue;
                }
                $has = $this->db->table('role_permissions')
                    ->where('role_id', (int) $role['id'])->where('module_id', $moduleId)->where('permission_id', $perms[$action])
                    ->countAllResults();
                if ($has === 0) {
                    $rows[] = ['role_id' => (int) $role['id'], 'module_id' => $moduleId, 'permission_id' => $perms[$action]];
                }
            }
            if ($rows !== []) {
                $this->db->table('role_permissions')->insertBatch($rows);
            }
        }
    }

    public function down()
    {
        $module = $this->db->table('modules')->where('code', 'inventory')->get()->getRowArray();
        if ($module) {
            $this->db->table('role_permissions')->where('module_id', (int) $module['id'])->delete();
            $this->db->table('modules')->where('id', (int) $module['id'])->delete();
        }
        foreach (['inv_attachments', 'inv_stock', 'inv_movements', 'inv_lots', 'inv_parties', 'inv_warehouses', 'inv_products'] as $t) {
            $this->forge->dropTable($t, true);
        }
    }
}
