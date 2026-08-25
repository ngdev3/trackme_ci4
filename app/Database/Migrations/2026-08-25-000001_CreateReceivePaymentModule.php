<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Register the "Receive Payment" (UPI QR) module in the module registry so it
 * appears on the /modules admin page, can carry per-role permissions, and shows
 * up in the mobile /me permission map — mirroring how other feature modules
 * (e.g. Push Notification Center) self-register. Idempotent: upserts by code and
 * only adds missing role grants. Mirrors the web Modules\UpiQr route (upi-qr).
 */
class CreateReceivePaymentModule extends Migration
{
    public function up()
    {
        $now     = date('Y-m-d H:i:s');
        $modules = $this->db->table('modules');
        $existing = $modules->where('code', 'upi_qr')->get()->getRowArray();
        $data = [
            'name'       => 'Receive Payment',
            'code'       => 'upi_qr',
            'url'        => 'upi-qr',
            'icon'       => 'bi bi-qr-code',
            'parent_id'  => null,
            'sort_order' => 81,
            'is_menu'    => 1,
            'status'     => 1,
            'updated_at' => $now,
            'deleted_at' => null,
        ];

        if ($existing) {
            $modules->where('id', (int) $existing['id'])->update($data);
        } else {
            $modules->insert($data + ['created_at' => $now]);
        }

        $module = $modules->where('code', 'upi_qr')->get()->getRowArray();
        if (! $module) {
            return;
        }

        $permRows = $this->db->table('permissions')->select('id, code')->get()->getResultArray();
        $perms = [];
        foreach ($permRows as $row) {
            $perms[$row['code']] = (int) $row['id'];
        }

        $roles = $this->db->table('roles')
            ->select('id, code, is_superadmin')
            ->groupStart()
                ->where('is_superadmin', 1)
                ->orWhere('code', 'admin')
            ->groupEnd()
            ->get()->getResultArray();

        $grants = [];
        foreach ($roles as $role) {
            foreach (['view', 'add', 'edit', 'delete'] as $action) {
                if (! isset($perms[$action])) {
                    continue;
                }
                $exists = $this->db->table('role_permissions')
                    ->where('role_id', (int) $role['id'])
                    ->where('module_id', (int) $module['id'])
                    ->where('permission_id', $perms[$action])
                    ->countAllResults();
                if (! $exists) {
                    $grants[] = [
                        'role_id'       => (int) $role['id'],
                        'module_id'     => (int) $module['id'],
                        'permission_id' => $perms[$action],
                    ];
                }
            }
        }

        if ($grants !== []) {
            $this->db->table('role_permissions')->insertBatch($grants);
        }
    }

    public function down()
    {
        $module = $this->db->table('modules')->where('code', 'upi_qr')->get()->getRowArray();
        if (! $module) {
            return;
        }
        $this->db->table('role_permissions')->where('module_id', (int) $module['id'])->delete();
        $this->db->table('modules')->where('id', (int) $module['id'])->delete();
    }
}
