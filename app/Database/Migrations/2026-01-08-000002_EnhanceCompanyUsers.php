<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Reinforces firm-wise membership with the customer that owns the firm and a
 * per-user firm permission override. Satisfies "every record has Customer ID
 * and Firm ID": company_users now carries customer_id (owner) + company_id.
 */
class EnhanceCompanyUsers extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('customer_id', 'company_users')) {
            $this->forge->addColumn('company_users', [
                'customer_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'company_id'],
            ]);
        }
        if (! $this->db->fieldExists('permissions', 'company_users')) {
            $this->forge->addColumn('company_users', [
                'permissions' => ['type' => 'TEXT', 'null' => true, 'after' => 'role'],
            ]);
        }

        // Backfill customer_id from the firm owner.
        $this->db->query('UPDATE company_users cu JOIN companies c ON c.id = cu.company_id SET cu.customer_id = c.owner_id WHERE cu.customer_id IS NULL');
    }

    public function down()
    {
        foreach (['customer_id', 'permissions'] as $col) {
            if ($this->db->fieldExists($col, 'company_users')) {
                $this->forge->dropColumn('company_users', $col);
            }
        }
    }
}
