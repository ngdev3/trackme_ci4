<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the two missing inventory flows to the existing ledger without a new
 * subsystem: TRANSFER (godown → godown) and PRODUCTION / CONVERSION (one input
 * → many outputs + wastage). Both reuse inv_movements / inv_stock via
 * InventoryService, so Current Stock stays derived from the ledger.
 *
 *   link_group      — ties the legs of one transfer/production batch together
 *                     (the OUT leg and IN leg(s) share the same value)
 *   to_warehouse_id — transfer destination, stored on the OUT leg for reporting
 *   wastage_bags    — production loss, recorded on the input (OUT) row; it is NOT
 *                     added to any stock, so the ledger↔balance identity holds
 *
 * All additive + guarded (fieldExists) so re-runs are safe.
 */
class AddTransferProductionToInventory extends Migration
{
    public function up()
    {
        $add = [];
        if (! $this->db->fieldExists('link_group', 'inv_movements')) {
            $add['link_group'] = ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'entry_no'];
        }
        if (! $this->db->fieldExists('to_warehouse_id', 'inv_movements')) {
            $add['to_warehouse_id'] = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'warehouse_id'];
        }
        if (! $this->db->fieldExists('wastage_bags', 'inv_movements')) {
            $add['wastage_bags'] = ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'bags'];
        }
        if ($add !== []) {
            $this->forge->addColumn('inv_movements', $add);
        }

        // Fast lookup of all legs in a transfer/production batch. Guarded — MySQL
        // has no CREATE INDEX IF NOT EXISTS, so check information_schema first.
        $exists = $this->db->query(
            "SELECT COUNT(*) AS n FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = 'inv_movements' AND index_name = 'idx_mv_linkgroup'"
        )->getRowArray();
        if ((int) ($exists['n'] ?? 0) === 0) {
            $this->db->query('CREATE INDEX `idx_mv_linkgroup` ON `inv_movements` (`company_id`, `link_group`)');
        }
    }

    public function down()
    {
        $exists = $this->db->query(
            "SELECT COUNT(*) AS n FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = 'inv_movements' AND index_name = 'idx_mv_linkgroup'"
        )->getRowArray();
        if ((int) ($exists['n'] ?? 0) > 0) {
            $this->db->query('DROP INDEX `idx_mv_linkgroup` ON `inv_movements`');
        }
        foreach (['link_group', 'to_warehouse_id', 'wastage_bags'] as $col) {
            if ($this->db->fieldExists($col, 'inv_movements')) {
                $this->forge->dropColumn('inv_movements', $col);
            }
        }
    }
}
