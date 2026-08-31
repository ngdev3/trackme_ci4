<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * DashboardModel — CI4 port of the Auth_mod dashboard data (module tiles). Live
 * per-firm counts for the dashboard metric tiles, table-guarded so missing
 * tables are skipped. Heavier analytics (sales/purchase, ageing, login) are
 * supplied as safe defaults until their subsystems are ported.
 */
class DashboardModel
{
    protected function db()
    {
        return Database::connect();
    }

    public function moduleTiles(): array
    {
        $db     = $this->db();
        $tid    = (int) fy()->template_id;
        $fy     = fy()->FY;
        $pt     = fy()->product_type;
        $today  = date('Y-m-d');
        $mstart = date('Y-m-01');
        $mend   = date('Y-m-t');
        $tiles  = [];

        if ($db->tableExists('aa_task')) {
            $n = $db->table('aa_task')->where('template_id', $tid)->where('is_deleted', 0)
                ->whereIn('status', ['open', 'in_progress'])->countAllResults();
            $tiles[] = ['key' => 'm_tasks', 'label' => 'Open Tasks', 'icon' => 'ti-clipboard', 'count' => $n, 'url' => base_url('task/task')];
        }
        if ($db->tableExists('aa_document')) {
            $n = $db->table('aa_document')->where('template_id', $tid)->where('status !=', 'Delete')
                ->where("end_date <> ''", null, false)->where('end_date >=', $today)
                ->where('end_date <=', date('Y-m-d', strtotime('+30 days')))->countAllResults();
            $tiles[] = ['key' => 'm_documents', 'label' => 'Documents Due (30d)', 'icon' => 'ti-files', 'count' => $n, 'url' => base_url('admin/document/listing')];
        }
        if ($db->tableExists('aa_attendance')) {
            $n = $db->table('aa_attendance')->where('template_id', $tid)->where('attendance_date', $today)
                ->where("LOWER(attendance_status) = 'present'", null, false)->countAllResults();
            $tiles[] = ['key' => 'm_attendance', 'label' => 'Present Today', 'icon' => 'ti-user', 'count' => $n, 'url' => base_url('admin/attendance')];
        }
        if ($db->tableExists('invoice_system')) {
            $n = $db->table('invoice_system')->where('template_id', $tid)->where('FY', $fy)->where('product_type', $pt)
                ->where('type_of_invoice', 2)->where("LOWER(status) = 'active'", null, false)
                ->where('billing_date >=', $mstart)->where('billing_date <=', $mend)->countAllResults();
            $tiles[] = ['key' => 'm_bos', 'label' => 'Bill of Supply (This Month)', 'icon' => 'ti-receipt', 'count' => $n, 'url' => base_url('admin/invoice')];
        }
        if ($db->tableExists('tax_invoice_system')) {
            $n = $db->table('tax_invoice_system')->where('template_id', $tid)->where("LOWER(status) = 'active'", null, false)
                ->where('billing_date >=', $mstart)->where('billing_date <=', $mend)->countAllResults();
            $tiles[] = ['key' => 'm_taxinv', 'label' => 'Tax Invoices (This Month)', 'icon' => 'ti-receipt', 'count' => $n, 'url' => base_url('admin/taxinvoice')];
        }
        if ($db->tableExists('purchase_bills')) {
            $n = $db->table('purchase_bills')->where('template_id', $tid)->where('status', 'Active')
                ->where('invoice_date >=', $mstart)->where('invoice_date <=', $mend)->countAllResults();
            $tiles[] = ['key' => 'm_purchase', 'label' => 'Purchases (This Month)', 'icon' => 'ti-shopping-cart', 'count' => $n, 'url' => base_url('admin/purchase_module')];
        }
        if ($db->tableExists('aa_account_name')) {
            $n = $db->table('aa_account_name')->where('status !=', 'Delete')->countAllResults();
            $tiles[] = ['key' => 'm_accounts', 'label' => 'Account Names (Active)', 'icon' => 'ti-book', 'count' => $n, 'url' => base_url('admin/account_name/listing')];
        }

        return $tiles;
    }
}
