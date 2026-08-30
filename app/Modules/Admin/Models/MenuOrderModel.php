<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * MenuOrderModel — CI4 port of the CI3 user_menu_order_* helpers
 * (function_helper.php). Persists each user's personalised left-menu order +
 * hidden keys in aa_user_menu_order (one row per user). Table is lazily created.
 */
class MenuOrderModel
{
    protected function db()
    {
        return Database::connect();
    }

    public function ensureTable(): void
    {
        $db = $this->db();
        $db->query("CREATE TABLE IF NOT EXISTS `aa_user_menu_order` (
            `user_id`     INT(11)   NOT NULL,
            `menu_order`  TEXT      NOT NULL,
            `hidden_keys` TEXT      NULL,
            `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        if (! $db->fieldExists('hidden_keys', 'aa_user_menu_order')) {
            $db->query("ALTER TABLE `aa_user_menu_order` ADD `hidden_keys` TEXT NULL AFTER `menu_order`");
        }
    }

    /** Upsert the user's order (+ optional hidden list; null keeps the current). */
    public function save(int $userId, array $order, ?array $hidden): bool
    {
        if (! $userId) { return false; }
        $this->ensureTable();
        $db  = $this->db();
        $row = $db->table('aa_user_menu_order')->where('user_id', $userId)->get()->getRow();

        $data = ['menu_order' => json_encode(array_values($order))];
        if ($hidden !== null) {
            $data['hidden_keys'] = json_encode(array_values($hidden));
        }

        if ($row) {
            $db->table('aa_user_menu_order')->where('user_id', $userId)->update($data);
        } else {
            $data['user_id'] = $userId;
            if (! isset($data['hidden_keys'])) { $data['hidden_keys'] = json_encode([]); }
            $db->table('aa_user_menu_order')->insert($data);
        }
        return true;
    }

    public function reset(int $userId): bool
    {
        if (! $userId) { return false; }
        $this->ensureTable();
        $this->db()->table('aa_user_menu_order')->where('user_id', $userId)->delete();
        return true;
    }

    /** Return ['order'=>[...], 'hidden'=>[...]] for a user (empty when unset). */
    public function get(int $userId): array
    {
        $this->ensureTable();
        $row = $this->db()->table('aa_user_menu_order')->where('user_id', $userId)->get()->getRow();
        if (! $row) { return ['order' => [], 'hidden' => []]; }
        $order  = json_decode((string) $row->menu_order, true);
        $hidden = json_decode((string) ($row->hidden_keys ?? ''), true);
        return ['order' => is_array($order) ? $order : [], 'hidden' => is_array($hidden) ? $hidden : []];
    }
}
