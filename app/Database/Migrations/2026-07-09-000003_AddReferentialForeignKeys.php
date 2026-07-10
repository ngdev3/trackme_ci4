<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * QA hardening (F-3): the money & inventory layer shipped with zero foreign
 * keys, so hard-deleting a company, user, product or warehouse would silently
 * orphan its rows. All candidate columns were verified free of dirty values
 * (no non-null id points at a missing parent), so these constraints apply
 * cleanly to the existing data.
 *
 * ON DELETE policy:
 *   RESTRICT  — tenant/identity parents (company, user, product, warehouse,
 *               party, ledger, plan). You may not hard-delete a parent that
 *               still has records; the app soft-deletes these anyway, so normal
 *               flows are unaffected — only a genuine orphaning DELETE is blocked.
 *   CASCADE   — true child rows (attachments, corrections, voucher lines) die
 *               with their immediate parent.
 *   SET NULL  — optional links on nullable columns (category, party, lot, group):
 *               deleting the referenced row keeps the record, dropping the link.
 *
 * ON UPDATE is CASCADE everywhere (surrogate keys never change, but it is the
 * safe default). Idempotent: each constraint is only added when absent.
 */
class AddReferentialForeignKeys extends Migration
{
    /**
     * [constraint name, table, column, referenced table, referenced column, ON DELETE].
     *
     * @var list<array{0:string,1:string,2:string,3:string,4:string,5:string}>
     */
    private array $fks = [
        // --- Transactions (Jama/Naam) ---
        ['fk_txn_company',           'transactions',            'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_txn_user',              'transactions',            'user_id',        'users',             'id', 'RESTRICT'],
        ['fk_txnatt_txn',            'transaction_attachments', 'transaction_id', 'transactions',      'id', 'CASCADE'],
        ['fk_txnatt_company',        'transaction_attachments', 'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_txnatt_user',           'transaction_attachments', 'user_id',        'users',             'id', 'RESTRICT'],

        // --- Company / tenancy ---
        ['fk_compusers_company',     'company_users',           'company_id',     'companies',         'id', 'CASCADE'],
        ['fk_compusers_user',        'company_users',           'user_id',        'users',             'id', 'CASCADE'],
        ['fk_companies_owner',       'companies',               'owner_id',       'users',             'id', 'RESTRICT'],

        // --- Accounting ---
        ['fk_acctgroups_company',    'accounting_groups',       'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_ledgers_company',       'ledgers',                 'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_ledgers_group',         'ledgers',                 'group_id',       'accounting_groups', 'id', 'SET NULL'],
        ['fk_vouchers_company',      'vouchers',                'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_ventries_voucher',      'voucher_entries',         'voucher_id',     'vouchers',          'id', 'CASCADE'],
        ['fk_ventries_ledger',       'voucher_entries',         'ledger_id',      'ledgers',           'id', 'RESTRICT'],
        ['fk_ventries_company',      'voucher_entries',         'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_rokad_company',         'rokad_entries',           'company_id',     'companies',         'id', 'RESTRICT'],

        // --- Passwords vault ---
        ['fk_passwords_company',     'passwords',               'company_id',     'companies',         'id', 'RESTRICT'],

        // --- Inventory ---
        ['fk_invprod_company',       'inv_products',            'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_invwh_company',         'inv_warehouses',          'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_invparty_company',      'inv_parties',             'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_invlot_company',        'inv_lots',                'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_invlot_product',        'inv_lots',                'product_id',     'inv_products',      'id', 'RESTRICT'],
        ['fk_invlot_warehouse',      'inv_lots',                'warehouse_id',   'inv_warehouses',    'id', 'RESTRICT'],
        ['fk_invlot_party',          'inv_lots',                'party_id',       'inv_parties',       'id', 'SET NULL'],
        ['fk_invmov_company',        'inv_movements',           'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_invmov_product',        'inv_movements',           'product_id',     'inv_products',      'id', 'RESTRICT'],
        ['fk_invmov_warehouse',      'inv_movements',           'warehouse_id',   'inv_warehouses',    'id', 'RESTRICT'],
        ['fk_invmov_party',          'inv_movements',           'party_id',       'inv_parties',       'id', 'SET NULL'],
        ['fk_invmov_lot',            'inv_movements',           'lot_id',         'inv_lots',          'id', 'SET NULL'],
        ['fk_invstock_company',      'inv_stock',               'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_invstock_product',      'inv_stock',               'product_id',     'inv_products',      'id', 'CASCADE'],
        ['fk_invstock_warehouse',    'inv_stock',               'warehouse_id',   'inv_warehouses',    'id', 'CASCADE'],
        ['fk_invcorr_company',       'inv_corrections',         'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_invcorr_movement',      'inv_corrections',         'movement_id',    'inv_movements',     'id', 'CASCADE'],
        ['fk_invcorr_product',       'inv_corrections',         'product_id',     'inv_products',      'id', 'RESTRICT'],
        ['fk_invcorr_warehouse',     'inv_corrections',         'warehouse_id',   'inv_warehouses',    'id', 'RESTRICT'],
        ['fk_invclose_company',      'inv_daily_closings',      'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_invatt_company',        'inv_attachments',         'company_id',     'companies',         'id', 'RESTRICT'],
        ['fk_invatt_movement',       'inv_attachments',         'movement_id',    'inv_movements',     'id', 'CASCADE'],

        // --- Notes / reminders ---
        ['fk_notes_company',         'notes',                   'company_id',     'companies',         'id', 'CASCADE'],
        ['fk_notes_category',        'notes',                   'category_id',    'note_categories',   'id', 'SET NULL'],
        ['fk_reminders_company',     'reminders',               'company_id',     'companies',         'id', 'CASCADE'],
    ];

    public function up()
    {
        foreach ($this->fks as [$name, $table, $column, $refTable, $refColumn, $onDelete]) {
            if ($this->constraintExists($name)) {
                continue;
            }
            $this->db->query(
                'ALTER TABLE `' . $table . '` ADD CONSTRAINT `' . $name . '`'
                . ' FOREIGN KEY (`' . $column . '`) REFERENCES `' . $refTable . '` (`' . $refColumn . '`)'
                . ' ON DELETE ' . $onDelete . ' ON UPDATE CASCADE'
            );
        }
    }

    public function down()
    {
        // Drop in reverse so child-table FKs go before the parents they lean on.
        foreach (array_reverse($this->fks) as [$name, $table]) {
            if ($this->constraintExists($name)) {
                $this->db->query('ALTER TABLE `' . $table . '` DROP FOREIGN KEY `' . $name . '`');
            }
        }
    }

    private function constraintExists(string $name): bool
    {
        $db = $this->db->getDatabase();

        return (bool) $this->db->query(
            'SELECT 1 FROM information_schema.table_constraints
             WHERE constraint_schema = ? AND constraint_name = ?
               AND constraint_type = \'FOREIGN KEY\' LIMIT 1',
            [$db, $name]
        )->getRow();
    }
}
