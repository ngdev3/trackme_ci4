<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Password Manager — a company-scoped, encrypted credential vault. Each row
 * stores a title, the website/app, a username/email, an ENCRYPTED password
 * (never plaintext), free-text notes and a category. Shared across a company's
 * members but gated by the `passwords` module permissions (view/add/edit/delete).
 *
 * This migration also registers the module in the `modules` table (so the
 * permission filter can resolve it and the Super-Admin sidebar lists it) and
 * grants the base Admin + Viewer roles full CRUD — the same roles that own the
 * other firm modules (notes, reminders, transactions). Idempotent.
 */
class CreatePasswords extends Migration
{
    public function up()
    {
        // ---- 1. The vault table ----
        if (! $this->db->tableExists('passwords')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'company_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'title'        => ['type' => 'VARCHAR', 'constraint' => 191],
                'website'      => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
                'username'     => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
                'password_enc' => ['type' => 'TEXT'], // AES-encrypted, base64-wrapped
                'notes'        => ['type' => 'TEXT', 'null' => true],
                'category'     => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'created_by'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
                'updated_at'   => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['company_id', 'category']);
            $this->forge->addKey('title');
            $this->forge->createTable('passwords', true);
        }

        // ---- 2. Register the module ----
        $moduleId = $this->ensureModule();

        // ---- 3. Grant Admin + Viewer roles full CRUD ----
        if ($moduleId) {
            $this->grantRoles($moduleId, ['admin', 'viewer'], ['view', 'add', 'edit', 'delete']);
        }
    }

    /** Insert the `passwords` module row if missing; return its id. */
    private function ensureModule(): int
    {
        $existing = $this->db->table('modules')->where('code', 'passwords')->get()->getRowArray();
        if ($existing) {
            return (int) $existing['id'];
        }

        // Place it after the last top-level menu item.
        $maxSort = (int) ($this->db->table('modules')->selectMax('sort_order')->get()->getRowArray()['sort_order'] ?? 0);

        $this->db->table('modules')->insert([
            'name'       => 'Password Manager',
            'code'       => 'passwords',
            'url'        => 'passwords',
            'icon'       => 'bi bi-shield-lock',
            'parent_id'  => null,
            'sort_order' => $maxSort + 1,
            'is_menu'    => 1,
            'status'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    /**
     * Grant the listed action codes on a module to the given role codes,
     * skipping grants that already exist.
     *
     * @param list<string> $roleCodes
     * @param list<string> $actionCodes
     */
    private function grantRoles(int $moduleId, array $roleCodes, array $actionCodes): void
    {
        $perms = [];
        foreach ($this->db->table('permissions')->select('id, code')->get()->getResultArray() as $p) {
            $perms[$p['code']] = (int) $p['id'];
        }

        foreach ($roleCodes as $roleCode) {
            $role = $this->db->table('roles')->where('code', $roleCode)->get()->getRowArray();
            if (! $role) {
                continue;
            }
            $roleId = (int) $role['id'];
            $rows   = [];
            foreach ($actionCodes as $action) {
                if (! isset($perms[$action])) {
                    continue;
                }
                $has = $this->db->table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('module_id', $moduleId)
                    ->where('permission_id', $perms[$action])
                    ->countAllResults();
                if ($has === 0) {
                    $rows[] = ['role_id' => $roleId, 'module_id' => $moduleId, 'permission_id' => $perms[$action]];
                }
            }
            if ($rows !== []) {
                $this->db->table('role_permissions')->insertBatch($rows);
            }
        }
    }

    public function down()
    {
        $module = $this->db->table('modules')->where('code', 'passwords')->get()->getRowArray();
        if ($module) {
            $this->db->table('role_permissions')->where('module_id', (int) $module['id'])->delete();
            $this->db->table('modules')->where('id', (int) $module['id'])->delete();
        }
        $this->forge->dropTable('passwords', true);
    }
}
