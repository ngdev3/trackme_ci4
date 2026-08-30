<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * AppSettingModel — CI4 port of admin/models/Appsetting_mod. Per-user dashboard
 * layout (section order + show/hide) in aa_user_dashboard_layout (one JSON row
 * per user, lazily created). resolve_layout always returns every known section
 * once (saved order first, defaults appended), so new sections auto-appear.
 */
class AppSettingModel
{
    private string $table = 'aa_user_dashboard_layout';

    protected function db()
    {
        return Database::connect();
    }

    public function defaultSections(): array
    {
        return [
            'hero' => 'Welcome & Clock', 'weather' => 'Weather Bar',
            'm_tasks' => 'Open Tasks', 'm_documents' => 'Documents Due (30d)', 'm_attendance' => 'Present Today',
            'm_bos' => 'Bill of Supply (This Month)', 'm_taxinv' => 'Tax Invoices (This Month)',
            'm_purchase' => 'Purchases (This Month)', 'm_accounts' => 'Account Names (Active)',
            'user_login' => 'User Login Analytics', 'sales_purchase' => 'Sales & Purchase Analytics',
            'ageing' => 'Ageing (Debtors & Creditors)', 'lot_report' => 'Lot Wise Report', 'sale_stock' => 'Sale Stock',
        ];
    }

    public function defaultHidden(): array
    {
        return ['lot_report' => 1, 'sale_stock' => 1];
    }

    public function ensureTable(): void
    {
        $this->db()->query("CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `layout` LONGTEXT NULL,
            `updated_at` DATETIME NULL,
            PRIMARY KEY (`id`), UNIQUE KEY `uq_dashboard_layout_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function getLayout(int $userId): array
    {
        $this->ensureTable();
        $row = $this->db()->table($this->table)->where('user_id', $userId)->get()->getRow();
        if (! $row || $row->layout === null || $row->layout === '') { return []; }
        $decoded = json_decode($row->layout, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function resolveLayout(int $userId): array
    {
        $defaults = $this->defaultSections();
        $saved    = $this->getLayout($userId);

        $result = [];
        $seen   = [];
        foreach ($saved as $item) {
            $key = $item['key'] ?? null;
            if ($key === null || ! isset($defaults[$key]) || isset($seen[$key])) { continue; }
            $seen[$key] = true;
            $result[] = ['key' => $key, 'label' => $defaults[$key], 'hidden' => ! empty($item['hidden']) ? 1 : 0];
        }
        $defaultHidden = $this->defaultHidden();
        foreach ($defaults as $key => $label) {
            if (! isset($seen[$key])) {
                $result[] = ['key' => $key, 'label' => $label, 'hidden' => isset($defaultHidden[$key]) ? 1 : 0];
            }
        }
        return $result;
    }

    public function saveLayout(int $userId, $items): bool
    {
        $this->ensureTable();
        $defaults = $this->defaultSections();

        $clean = [];
        $seen  = [];
        if (is_array($items)) {
            foreach ($items as $item) {
                $key = isset($item['key']) ? (string) $item['key'] : '';
                if ($key === '' || ! isset($defaults[$key]) || isset($seen[$key])) { continue; }
                $seen[$key] = true;
                $clean[] = ['key' => $key, 'hidden' => ! empty($item['hidden']) ? 1 : 0];
            }
        }

        $data = ['user_id' => $userId, 'layout' => json_encode($clean), 'updated_at' => date('Y-m-d H:i:s')];
        $exists = $this->db()->table($this->table)->where('user_id', $userId)->get()->getRow();
        if ($exists) {
            $this->db()->table($this->table)->where('user_id', $userId)->update($data);
        } else {
            $this->db()->table($this->table)->insert($data);
        }
        return true;
    }

    public function resetLayout(int $userId): bool
    {
        $this->ensureTable();
        $this->db()->table($this->table)->where('user_id', $userId)->delete();
        return true;
    }
}
