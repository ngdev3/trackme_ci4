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

    // ------------------------------------------------------------------
    // Sales & Purchase analytics (sp_analytics) — CI3 Auth_mod parity.
    // ------------------------------------------------------------------
    public function salesPurchaseAnalytics(): array
    {
        $db  = $this->db();
        $tid = (int) fy()->template_id;

        $sales_union = "(
            SELECT product_name, quantity, amount, billing_date
            FROM invoice_system WHERE template_id = $tid
            UNION ALL
            SELECT product_name, quantity, amount, billing_date
            FROM tax_invoice_system WHERE template_id = $tid AND status = 'Active'
            UNION ALL
            SELECT product_name, quantity, amount, billing_date
            FROM uninvoice_system WHERE template_id = $tid AND status = 'Active'
        ) s";

        $sales = $db->query("
            SELECT s.product_name AS commodity,
                ROUND(SUM(s.quantity), 2) AS sale_qty,
                ROUND(SUM(s.amount), 2) AS sale_amount,
                ROUND(SUM(s.amount) / NULLIF(SUM(s.quantity), 0), 2) AS sale_rate
            FROM $sales_union
            WHERE TRIM(s.product_name) != ''
            GROUP BY s.product_name
        ")->getResult();

        $purchase = $db->query("
            SELECT h.product_name AS commodity,
                ROUND(SUM(p.weight), 2) AS purchase_qty,
                ROUND(SUM(p.amount), 2) AS purchase_amount,
                ROUND(SUM(p.amount) / NULLIF(SUM(p.weight), 0), 2) AS purchase_rate
            FROM purchase_bills p
            LEFT JOIN hsn_codes h ON h.id = p.hsn_code_id
            WHERE p.template_id = $tid AND p.status = 'Active'
            GROUP BY h.product_name
        ")->getResult();

        $map = [];
        foreach ($sales as $s) {
            $name = trim((string) $s->commodity);
            if ($name === '') { $name = 'Unspecified'; }
            $map[$name] = [
                'commodity' => $name,
                'sale_qty' => (float) $s->sale_qty,
                'sale_amount' => (float) $s->sale_amount,
                'sale_rate' => (float) $s->sale_rate,
                'purchase_qty' => 0, 'purchase_amount' => 0, 'purchase_rate' => 0,
            ];
        }
        foreach ($purchase as $p) {
            $name = trim((string) $p->commodity);
            if ($name === '') { $name = 'Unspecified'; }
            if (!isset($map[$name])) {
                $map[$name] = [
                    'commodity' => $name,
                    'sale_qty' => 0, 'sale_amount' => 0, 'sale_rate' => 0,
                    'purchase_qty' => 0, 'purchase_amount' => 0, 'purchase_rate' => 0,
                ];
            }
            $map[$name]['purchase_qty'] = (float) $p->purchase_qty;
            $map[$name]['purchase_amount'] = (float) $p->purchase_amount;
            $map[$name]['purchase_rate'] = (float) $p->purchase_rate;
        }
        usort($map, function ($a, $b) {
            $bt = $b['sale_amount'] + $b['purchase_amount'];
            $at = $a['sale_amount'] + $a['purchase_amount'];
            if ($bt == $at) { return 0; }
            return ($bt > $at) ? 1 : -1;
        });
        $commodity = array_values($map);

        // 6-month trend
        $months = [];
        for ($k = 5; $k >= 0; $k--) {
            $ym = date('Y-m', strtotime("first day of -$k month"));
            $months[$ym] = ['label' => date('M Y', strtotime("first day of -$k month")), 'sale' => 0, 'purchase' => 0];
        }

        foreach ($db->query("
            SELECT DATE_FORMAT(s.billing_date, '%Y-%m') AS ym, ROUND(SUM(s.amount), 2) AS amt
            FROM $sales_union
            WHERE s.billing_date > '1970-01-01'
            GROUP BY ym
        ")->getResult() as $r) {
            if (isset($months[$r->ym])) { $months[$r->ym]['sale'] = (float) $r->amt; }
        }

        foreach ($db->query("
            SELECT DATE_FORMAT(STR_TO_DATE(p.invoice_date, '%Y-%m-%d'), '%Y-%m') AS ym, ROUND(SUM(p.amount), 2) AS amt
            FROM purchase_bills p
            WHERE p.template_id = $tid AND p.status = 'Active'
            GROUP BY ym
        ")->getResult() as $r) {
            if ($r->ym && isset($months[$r->ym])) { $months[$r->ym]['purchase'] = (float) $r->amt; }
        }

        // commodity-wise monthly
        $commodity_monthly = [];
        $month_keys = array_keys($months);
        $empty_month_values = array_fill(0, count($month_keys), 0);

        foreach ($db->query("
            SELECT TRIM(s.product_name) AS commodity,
                DATE_FORMAT(s.billing_date, '%Y-%m') AS ym,
                ROUND(SUM(s.amount), 2) AS amt
            FROM $sales_union
            WHERE TRIM(s.product_name) != '' AND s.billing_date > '1970-01-01'
            GROUP BY TRIM(s.product_name), ym
        ")->getResult() as $r) {
            $name = trim((string) $r->commodity);
            if ($name === '' || !isset($months[$r->ym])) { continue; }
            $ck = strtolower($name);
            if (!isset($commodity_monthly[$ck])) {
                $commodity_monthly[$ck] = ['commodity' => $name, 'sale' => $empty_month_values, 'purchase' => $empty_month_values, 'turnover' => 0];
            }
            $mi = array_search($r->ym, $month_keys, true);
            if ($mi !== false) {
                $commodity_monthly[$ck]['sale'][$mi] = (float) $r->amt;
                $commodity_monthly[$ck]['turnover'] += (float) $r->amt;
            }
        }

        foreach ($db->query("
            SELECT TRIM(h.product_name) AS commodity,
                DATE_FORMAT(STR_TO_DATE(p.invoice_date, '%Y-%m-%d'), '%Y-%m') AS ym,
                ROUND(SUM(p.amount), 2) AS amt
            FROM purchase_bills p
            LEFT JOIN hsn_codes h ON h.id = p.hsn_code_id
            WHERE p.template_id = $tid AND p.status = 'Active'
              AND TRIM(COALESCE(h.product_name, '')) != ''
            GROUP BY TRIM(h.product_name), ym
        ")->getResult() as $r) {
            $name = trim((string) $r->commodity);
            if ($name === '' || !$r->ym || !isset($months[$r->ym])) { continue; }
            $ck = strtolower($name);
            if (!isset($commodity_monthly[$ck])) {
                $commodity_monthly[$ck] = ['commodity' => $name, 'sale' => $empty_month_values, 'purchase' => $empty_month_values, 'turnover' => 0];
            }
            $mi = array_search($r->ym, $month_keys, true);
            if ($mi !== false) {
                $commodity_monthly[$ck]['purchase'][$mi] = (float) $r->amt;
                $commodity_monthly[$ck]['turnover'] += (float) $r->amt;
            }
        }
        usort($commodity_monthly, function ($a, $b) {
            if ($a['turnover'] == $b['turnover']) { return strcasecmp($a['commodity'], $b['commodity']); }
            return ($a['turnover'] < $b['turnover']) ? 1 : -1;
        });

        $total_sale = 0; $total_purchase = 0;
        foreach ($commodity as $c) { $total_sale += $c['sale_amount']; $total_purchase += $c['purchase_amount']; }

        return [
            'commodity' => $commodity,
            'months' => array_values($months),
            'commodity_monthly' => array_values($commodity_monthly),
            'totals' => ['sale' => $total_sale, 'purchase' => $total_purchase, 'profit' => $total_sale - $total_purchase],
        ];
    }

    // ------------------------------------------------------------------
    // Ageing analysis (debtors / creditors) — CI3 Auth_mod parity.
    // ------------------------------------------------------------------
    public function ageingAnalysis(): array
    {
        $db  = $this->db();
        $tid = (int) fy()->template_id;
        $gstin = "^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][0-9A-Z]Z[0-9A-Z]$";

        $rows = $db->query("
            SELECT ar.account_no, ar.rokad_date, ar.type_of_account, ar.karch_amount, an.name AS name
            FROM aa_rokad ar
            JOIN aa_account_name an ON an.account_id = ar.account_no
            WHERE ar.status = 'Active'
              AND ar.template_id = $tid
              AND ar.type_of_account IN ('deposit', 'expenses')
              AND an.purchaser_gst_no REGEXP '$gstin'
        ")->getResult();

        $today = strtotime(date('Y-m-d'));
        $acc = [];
        foreach ($rows as $r) {
            $amt = (float) $r->karch_amount;
            if ($amt == 0) { continue; }
            $ts = strtotime($r->rokad_date);
            if (!$ts) { $ts = $today; }
            $id = $r->account_no;
            if (!isset($acc[$id])) {
                $acc[$id] = ['name' => $r->name, 'debits' => [], 'credits' => [], 'debit' => 0, 'credit' => 0];
            }
            if ($r->type_of_account === 'expenses') {
                $acc[$id]['debits'][] = [$ts, $amt];
                $acc[$id]['debit'] += $amt;
            } else {
                $acc[$id]['credits'][] = [$ts, $amt];
                $acc[$id]['credit'] += $amt;
            }
        }

        $blank = ['b0' => 0, 'b1' => 0, 'b2' => 0, 'b3' => 0, 'total' => 0];
        $debtors = $blank; $creditors = $blank;
        $debtor_parties = []; $creditor_parties = [];

        $by_date = fn($x, $y) => ($x[0] == $y[0]) ? 0 : (($x[0] < $y[0]) ? -1 : 1);
        $bucket_of = function ($days) {
            if ($days <= 30) { return 'b0'; }
            if ($days <= 60) { return 'b1'; }
            if ($days <= 90) { return 'b2'; }
            return 'b3';
        };

        foreach ($acc as $a) {
            $net = $a['debit'] - $a['credit'];
            if (abs($net) < 1) { continue; }
            $is_debtor = $net > 0;
            $entries = $is_debtor ? $a['debits'] : $a['credits'];
            $offset = $is_debtor ? $a['credit'] : $a['debit'];
            usort($entries, $by_date);

            $party = $blank; $oldest = null;
            foreach ($entries as $e) {
                $amt = $e[1];
                if ($offset > 0) { $use = min($offset, $amt); $amt -= $use; $offset -= $use; }
                if ($amt <= 0) { continue; }
                $days = floor(($today - $e[0]) / 86400);
                $b = $bucket_of($days);
                $party[$b] += $amt;
                $party['total'] += $amt;
                if ($oldest === null || $e[0] < $oldest) { $oldest = $e[0]; }
            }
            if ($party['total'] < 1) { continue; }
            $row = [
                'name' => $a['name'],
                'b0' => $party['b0'], 'b1' => $party['b1'], 'b2' => $party['b2'], 'b3' => $party['b3'],
                'total' => $party['total'],
                'oldest_days' => $oldest ? (int) floor(($today - $oldest) / 86400) : 0,
            ];
            if ($is_debtor) {
                foreach ($blank as $k => $v) { $debtors[$k] += $party[$k]; }
                $debtor_parties[] = $row;
            } else {
                foreach ($blank as $k => $v) { $creditors[$k] += $party[$k]; }
                $creditor_parties[] = $row;
            }
        }

        $by_total = fn($x, $y) => ($x['total'] == $y['total']) ? 0 : (($x['total'] < $y['total']) ? 1 : -1);
        usort($debtor_parties, $by_total);
        usort($creditor_parties, $by_total);

        return [
            'labels' => ['0-30 days', '31-60 days', '61-90 days', '90+ days'],
            'debtors' => ['buckets' => $debtors, 'parties' => array_slice($debtor_parties, 0, 10), 'count' => count($debtor_parties)],
            'creditors' => ['buckets' => $creditors, 'parties' => array_slice($creditor_parties, 0, 10), 'count' => count($creditor_parties)],
        ];
    }

    // ------------------------------------------------------------------
    // Real-time paddy figures (RealTimeDataCount) — CI3 Auth_mod parity.
    // ------------------------------------------------------------------
    public function realTimeDataCount(): array
    {
        $db = $this->db();
        $fy = fy()->FY; $pt = fy()->product_type;

        $agg = fn($col) => $db->table('aa_billing')->select("$col AS v")
            ->where('FY', $fy)->where('product_type', $pt)->get()->getRow();

        $billing          = $agg('SUM(total_weight)');
        $finalAmountPaddy = $agg('SUM(final_amount)');
        $totalKatti       = $agg('SUM(total_katti)');
        $maxpurchaser     = $agg('MAX(final_amount)');

        $totalQuant = $db->query("SELECT acn.name, ROUND(SUM(Quantity),2) AS totalQuant FROM kisanvahidata LEFT JOIN aa_center_name AS acn ON kisanvahidata.CenterName = acn.center_id WHERE status_rec = 'done' AND FY = '" . $fy . "' AND product_type = '" . $pt . "' GROUP BY CenterName")->getResult();

        return [
            'billing'          => $billing ? (float) $billing->v : 0,
            'FinalAmountPaddy' => $finalAmountPaddy ? (float) $finalAmountPaddy->v : 0,
            'TotalKatti'       => $totalKatti ? (float) $totalKatti->v : 0,
            'maxpurchaser'     => $maxpurchaser ? (float) $maxpurchaser->v : 0,
            'first'            => $totalQuant ?: [],
        ];
    }

    public function realTimeLotStatus(): array
    {
        $db = $this->db();
        $fy = (int) fy()->FY; $pt = (int) fy()->product_type;
        return $db->query("SELECT COUNT(lot_id) AS TotalLot, acn.name AS CenterName FROM lot_detail AS l_de LEFT JOIN aa_center_name AS acn ON acn.center_id = l_de.center_id WHERE l_de.status = 'accept' AND l_de.FY = $fy AND l_de.product_type = $pt GROUP BY l_de.center_id")->getResult();
    }

    public function lotStatusReport(): array
    {
        $db = $this->db();
        $fy = fy()->FY; $pt = fy()->product_type;
        $one = function ($status, $alias) use ($db, $fy, $pt) {
            return $db->table('lot_detail l_de')->select("COUNT(lot_id) AS $alias")
                ->where('status', $status)->where('FY', $fy)->where('product_type', $pt)->get()->getRow();
        };
        return [
            'accept_lot'  => $one('accept', 'accept_lot'),
            'hold_lot'    => $one('hold', 'hold_lot'),
            'fci_gate'    => $one('fci_gate', 'fci_gate'),
            'shipped'     => $one('shipped', 'shipped'),
            'pending_lot' => $one('pending', 'pending_lot'),
        ];
    }

    // ------------------------------------------------------------------
    // Sale-stock summaries (BOS / E-Invoice / Unregistered) — parity.
    // ------------------------------------------------------------------
    private function stockSummary(string $table, bool $activeOnly): array
    {
        $db  = $this->db();
        $tid = (int) fy()->template_id;
        $b = $db->table("$table i")
            ->select("ROUND(SUM(i.quantity), 2) AS total_quantity, FORMAT(SUM(i.amount), 2) AS total_amount, i.product_name, i.hsn_code, i.template_id, f.name AS firm_name", false)
            ->join('aa_template t', 'i.template_id = t.template_id')
            ->join('firm_name f', 't.firm_name_id = f.id')
            ->where('i.template_id', $tid);
        if ($activeOnly) { $b->where('i.status', 'Active'); }
        $b->groupBy(['i.hsn_code', 'i.template_id', 'i.product_name', 'f.name'])->orderBy('i.template_id', 'ASC');
        return $b->get()->getResult();
    }

    public function stockDetailsBos(): array          { return $this->stockSummary('invoice_system', false); }
    public function stockDetailsEinvoice(): array      { return $this->stockSummary('tax_invoice_system', true); }
    public function stockDetailsUnregistered(): array  { return $this->stockSummary('uninvoice_system', true); }

    public function todaysKisanVahi(): array
    {
        $db = $this->db();
        $posted = service('request')->getPost('activeKishan');
        $middle = $posted ? @strtotime($posted) : false;
        $new_date = date('d-m-Y', $middle ?: 0);
        return $db->table('kisanvahidata kv')
            ->select('ROUND(SUM(Quantity),2) as quant, count(Kisan_ID) as totalKisan, acn.*', false)
            ->join('aa_center_name as acn', 'kv.CenterName = acn.center_id', 'left')
            ->where('FY', fy()->FY)
            ->where('Purchase_Date', $new_date)
            ->where('product_type', fy()->product_type)
            ->groupBy('kv.CenterName')
            ->get()->getResult();
    }

    public function realTimeActiveParcha(): array
    {
        $db = $this->db();
        $row = $db->table('aa_rokad')->orderBy('rokad_id', 'desc')->limit(1)->get()->getRow();
        return ['activeParcha' => $row ? $row->rokad_date : null];
    }

    // ------------------------------------------------------------------
    // User login analytics (Super Admin only) — CI3 Auth_mod parity.
    // ------------------------------------------------------------------
    public function userLoginAnalytics(int $days = 14, int $limit_users = 50): array
    {
        $db = $this->db();
        $out = ['summary' => [], 'users' => [], 'logins_by_day' => [], 'top_users' => []];

        $out['summary']['total']        = (int) $db->table('users')->where('status !=', 'Delete')->countAllResults();
        $out['summary']['active']        = (int) $db->table('users')->where('status', 'Active')->countAllResults();
        $out['summary']['inactive']      = (int) $db->table('users')->where('status', 'Inactive')->countAllResults();
        $out['summary']['total_logins']  = (int) $db->table('aa_login_detail')->countAllResults();
        $out['summary']['active_today']  = (int) $db->table('users')->where('last_login >=', date('Y-m-d 00:00:00'))->countAllResults();

        $out['users'] = $db->query(
            "SELECT u.id, u.first_name, u.last_name, u.email, u.status, u.user_type, u.last_login,
                    (SELECT COUNT(*) FROM aa_login_detail l WHERE l.user_id = u.id) AS access_count
             FROM users u
             WHERE u.status != 'Delete'
             ORDER BY (u.last_login IS NULL) ASC, u.last_login DESC
             LIMIT ?",
            [$limit_users]
        )->getResult();

        $rows = $db->query(
            "SELECT DATE(added_date) d, COUNT(*) c FROM aa_login_detail WHERE added_date >= ? GROUP BY DATE(added_date)",
            [date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'))]
        )->getResult();
        $by_day = [];
        foreach ($rows as $r) { $by_day[$r->d] = (int) $r->c; }
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $out['logins_by_day'][] = ['date' => $d, 'count' => $by_day[$d] ?? 0];
        }

        $out['top_users'] = $db->query(
            "SELECT l.user_id, COUNT(*) c, u.first_name, u.last_name
             FROM aa_login_detail l
             LEFT JOIN users u ON u.id = l.user_id
             WHERE l.user_id IS NOT NULL AND l.user_id != '' AND u.id IS NOT NULL
             GROUP BY l.user_id
             ORDER BY c DESC
             LIMIT 8"
        )->getResult();

        return $out;
    }
}
