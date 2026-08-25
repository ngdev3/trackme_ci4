<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Stock / Inventory — Product Master. A company-scoped catalogue of items with
 * pricing, units, tax and live stock. Shared across a company's members, gated
 * by the `inventory` module permissions (view/add/edit/delete). This migration
 * creates the table AND registers the `inventory` module in the registry (so the
 * /modules admin page lists it, the permission filter resolves it, and the
 * mobile /me permission map includes it), granting Super Admin + Admin full CRUD.
 * Idempotent.
 */
class CreateProducts extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('products')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'company_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'name'           => ['type' => 'VARCHAR', 'constraint' => 191],
                'sku'            => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'category'       => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'unit'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'hsn'            => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'sale_price'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'purchase_price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'opening_stock'  => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
                'current_stock'  => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
                'low_stock'      => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
                'tax_rate'       => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'description'    => ['type' => 'TEXT', 'null' => true],
                'status'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['company_id', 'deleted_at']);
            $this->forge->addKey('name');
            $this->forge->createTable('products', true);
        }

        $moduleId = $this->ensureModule();
        if ($moduleId) {
            $this->grantRoles($moduleId, ['view', 'add', 'edit', 'delete']);
        }
    }

    private function ensureModule(): int
    {
        $now      = date('Y-m-d H:i:s');
        $modules  = $this->db->table('modules');
        $existing = $modules->where('code', 'inventory')->get()->getRowArray();
        $data = [
            'name'       => 'Stock / Inventory',
            'code'       => 'inventory',
            'url'        => 'inventory',
            'icon'       => 'bi bi-box-seam',
            'parent_id'  => null,
            'sort_order' => 78,
            'is_menu'    => 1,
            'status'     => 1,
            'updated_at' => $now,
            'deleted_at' => null,
        ];
        if ($existing) {
            $modules->where('id', (int) $existing['id'])->update($data);
            return (int) $existing['id'];
        }
        $modules->insert($data + ['created_at' => $now]);
        return (int) $this->db->insertID();
    }

    /** Grant the given actions on $moduleId to Super Admin + Admin roles. */
    private function grantRoles(int $moduleId, array $actions): void
    {
        $perms = [];
        foreach ($this->db->table('permissions')->select('id, code')->get()->getResultArray() as $r) {
            $perms[$r['code']] = (int) $r['id'];
        }
        $roles = $this->db->table('roles')
            ->select('id')
            ->groupStart()->where('is_superadmin', 1)->orWhere('code', 'admin')->groupEnd()
            ->get()->getResultArray();

        $grants = [];
        foreach ($roles as $role) {
            foreach ($actions as $action) {
                if (! isset($perms[$action])) {
                    continue;
                }
                $exists = $this->db->table('role_permissions')
                    ->where('role_id', (int) $role['id'])
                    ->where('module_id', $moduleId)
                    ->where('permission_id', $perms[$action])
                    ->countAllResults();
                if (! $exists) {
                    $grants[] = ['role_id' => (int) $role['id'], 'module_id' => $moduleId, 'permission_id' => $perms[$action]];
                }
            }
        }
        if ($grants !== []) {
            $this->db->table('role_permissions')->insertBatch($grants);
        }
    }

    public function down()
    {
        $this->forge->dropTable('products', true);
        $module = $this->db->table('modules')->where('code', 'inventory')->get()->getRowArray();
        if ($module) {
            $this->db->table('role_permissions')->where('module_id', (int) $module['id'])->delete();
            $this->db->table('modules')->where('id', (int) $module['id'])->delete();
        }
    }
}
