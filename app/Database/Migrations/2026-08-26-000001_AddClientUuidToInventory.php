<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Offline-first sync support for inventory: a stable client-generated id on
 * `products` and `stock_movements` so a push retried after the server already
 * committed (app killed / response lost) is de-duplicated instead of inserting a
 * duplicate row. Mirrors transactions.client_uuid. Additive + guarded.
 */
class AddClientUuidToInventory extends Migration
{
    public function up()
    {
        foreach (['products', 'stock_movements'] as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            if (! $this->db->fieldExists('client_uuid', $table)) {
                $this->forge->addColumn($table, [
                    'client_uuid' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'company_id'],
                ]);
            }
            $idx = $this->db->query(
                "SELECT COUNT(*) AS n FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = ? AND index_name = 'uq_cuuid'",
                [$table],
            )->getRowArray();
            if ((int) ($idx['n'] ?? 0) === 0) {
                // Unique per company; multiple NULLs are allowed in MySQL, so legacy
                // rows (no uuid) don't collide.
                $this->db->query("ALTER TABLE `{$table}` ADD UNIQUE KEY `uq_cuuid` (`company_id`, `client_uuid`)");
            }
        }
    }

    public function down()
    {
        foreach (['products', 'stock_movements'] as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            $idx = $this->db->query(
                "SELECT COUNT(*) AS n FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = ? AND index_name = 'uq_cuuid'",
                [$table],
            )->getRowArray();
            if ((int) ($idx['n'] ?? 0) > 0) {
                $this->db->query("ALTER TABLE `{$table}` DROP INDEX `uq_cuuid`");
            }
            if ($this->db->fieldExists('client_uuid', $table)) {
                $this->forge->dropColumn($table, 'client_uuid');
            }
        }
    }
}
