<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * AccountingModel — CI4 port of the core of admin/models/Accounting_mod.
 *
 * The credit-positive running-balance engine shared by the account picker, the
 * ledger/statement reports and the Jama/Naam party balances. Scopes every read
 * by FY + product_type + template_id. (Only ledger_balances/ledger_balance are
 * ported here; the fuller report helpers port with their pages.)
 */
class AccountingModel
{
    protected function db()
    {
        return Database::connect();
    }

    /**
     * Net credit-positive balance for each account id, classified via
     * acc_side_from_balance(). Sums aa_rokad (deposit − expense) and
     * kisanvahidata (paid − amount). Optional $upto date cut-off (Y-m-d).
     *
     * @return array<int, array{net:float,abs:float,side:string,status:string}>
     */
    public function ledger_balances(array $account_ids, string $upto = ''): array
    {
        helper('accounting');
        $ids = array_values(array_unique(array_filter(array_map('intval', $account_ids))));
        if (empty($ids)) {
            return [];
        }
        $out = [];
        foreach ($ids as $id) {
            $out[$id] = 0.0; // credit-positive net accumulator
        }

        $db = $this->db();
        $fy = fy();
        $in = implode(',', $ids);

        // --- aa_rokad: deposit(credit) - expenses(debit) ---
        $sql = "SELECT account_no,
                    SUM(CASE WHEN LOWER(type_of_account) = 'deposit' THEN karch_amount ELSE -karch_amount END) AS net_cr
                FROM aa_rokad
                WHERE status <> 'Delete'
                  AND account_no IN ($in)
                  AND FY = " . $db->escape($fy->FY) . "
                  AND product_type = " . $db->escape($fy->product_type) . "
                  AND template_id = " . $db->escape($fy->template_id);
        if ($upto !== '') {
            $sql .= " AND rokad_date <= " . $db->escape($upto);
        }
        $sql .= " GROUP BY account_no";
        foreach ($db->query($sql)->getResult() as $r) {
            $aid = (int) $r->account_no;
            if (isset($out[$aid])) {
                $out[$aid] += (float) $r->net_cr;
            }
        }

        // --- kisanvahidata: paid(credit) - Ammount(debit) ---
        if ($db->tableExists('kisanvahidata')) {
            $sql = "SELECT account_no,
                        SUM(COALESCE(paid_amount,0) - COALESCE(Ammount,0)) AS net_cr
                    FROM kisanvahidata
                    WHERE account_no IN ($in)
                      AND FY = " . $db->escape($fy->FY) . "
                      AND product_type = " . $db->escape($fy->product_type) . "
                      AND template_id = " . $db->escape($fy->template_id);
            if ($upto !== '') {
                $sql .= " AND STR_TO_DATE(Purchase_Date, '%d-%m-%Y') <= " . $db->escape($upto);
            }
            $sql .= " GROUP BY account_no";
            foreach ($db->query($sql)->getResult() as $r) {
                $aid = (int) $r->account_no;
                if (isset($out[$aid])) {
                    $out[$aid] += (float) $r->net_cr;
                }
            }
        }

        $result = [];
        foreach ($out as $aid => $net_cr) {
            $result[$aid] = acc_side_from_balance($net_cr);
        }
        return $result;
    }

    /** Convenience single-account balance classification. */
    public function ledger_balance($account_id, string $upto = ''): array
    {
        helper('accounting');
        $all = $this->ledger_balances([(int) $account_id], $upto);
        return $all[(int) $account_id] ?? acc_side_from_balance(0);
    }

    /* =====================================================================
     * CHART OF ACCOUNTS — firm balances / ageing (Accounts_report engine)
     * ===================================================================== */

    /** True once the accounting-group chart-of-accounts schema is applied. */
    public function schema_ready(): bool
    {
        $db = $this->db();
        return $db->tableExists('aa_accounting_group')
            && $db->tableExists('aa_ledger')
            && $db->fieldExists('account_group_id', 'aa_account_name')
            && $db->fieldExists('ledger_id', 'aa_account_name')
            && $db->fieldExists('account_type', 'aa_account_name')
            && $db->fieldExists('classification', 'aa_accounting_group');
    }

    /**
     * Ledger-group options for the Account Master group-override dropdown.
     * Returns indented ($g->label) group rows for the firm, or [] when the
     * accounting schema isn't applied. CI3 Accounting_mod::group_options() —
     * without the ensure_chart() auto-create side effect (read-only here).
     *
     * @return object[]
     */
    public function group_options($template_id): array
    {
        if (! $this->schema_ready()) {
            return [];
        }
        $db   = $this->db();
        $rows = $db->table('aa_accounting_group')
            ->select('id, parent_id, name, code, nature, classification')
            ->where('template_id', (int) $template_id)
            ->where('status !=', 'Delete')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get()->getResult();

        $byParent = [];
        foreach ($rows as $r) { $byParent[(int) $r->parent_id][] = $r; }
        $out  = [];
        $walk = function ($parent_id, $level) use (&$walk, &$byParent, &$out) {
            if (empty($byParent[$parent_id])) { return; }
            foreach ($byParent[$parent_id] as $node) {
                $node->label = str_repeat('— ', $level) . $node->name;
                $out[] = $node;
                $walk((int) $node->id, $level + 1);
            }
        };
        $walk(0, 0);
        return $out;
    }

    /**
     * Every ledger's live net balance for the firm/FY, enriched with its
     * accounting-group nature/classification. The atomic dataset behind the
     * Trial Balance, Outstanding/Debtor/Creditor, Ageing, Balance Sheet and P&L.
     *
     * @return object[]
     */
    public function firm_account_balances(string $upto = '', bool $only_nonzero = false): array
    {
        helper('accounting');
        $db = $this->db();
        $fy = fy();
        $upto_sql = $upto !== '' ? (' AND rokad_date <= ' . $db->escape($upto)) : '';

        $net  = [];
        $last = [];
        $sql  = "SELECT account_no,
                    SUM(CASE WHEN LOWER(type_of_account) = 'deposit' THEN karch_amount ELSE -karch_amount END) AS net_cr,
                    MAX(rokad_date) AS last_date
                FROM aa_rokad
                WHERE status <> 'Delete'
                  AND FY = " . $db->escape($fy->FY) . "
                  AND product_type = " . $db->escape($fy->product_type) . "
                  AND template_id = " . $db->escape($fy->template_id) . $upto_sql . "
                GROUP BY account_no";
        foreach ($db->query($sql)->getResult() as $r) {
            $aid        = (int) $r->account_no;
            $net[$aid]  = ($net[$aid] ?? 0.0) + (float) $r->net_cr;
            $last[$aid] = $r->last_date;
        }

        if ($db->tableExists('kisanvahidata')) {
            $kv_upto = $upto !== '' ? (" AND STR_TO_DATE(Purchase_Date, '%d-%m-%Y') <= " . $db->escape($upto)) : '';
            $sql = "SELECT account_no,
                        SUM(COALESCE(paid_amount,0) - COALESCE(Ammount,0)) AS net_cr
                    FROM kisanvahidata
                    WHERE FY = " . $db->escape($fy->FY) . "
                      AND product_type = " . $db->escape($fy->product_type) . "
                      AND template_id = " . $db->escape($fy->template_id) . $kv_upto . "
                    GROUP BY account_no";
            foreach ($db->query($sql)->getResult() as $r) {
                $aid       = (int) $r->account_no;
                $net[$aid] = ($net[$aid] ?? 0.0) + (float) $r->net_cr;
            }
        }

        if (empty($net)) {
            return [];
        }

        $has_group = $this->schema_ready();
        $b = $db->table('aa_account_name an')
            ->select('an.account_id, an.name, ' . ($db->fieldExists('account_type', 'aa_account_name') ? 'an.account_type' : "'' AS account_type") . ', '
                . ($db->fieldExists('account_group_id', 'aa_account_name') ? 'an.account_group_id' : 'NULL AS account_group_id'), false);
        if ($has_group) {
            $b->select('ag.name AS group_name, ag.code AS group_code, ag.nature, ag.classification, ag.affects_gross_profit', false)
              ->join('aa_accounting_group ag', 'ag.id = an.account_group_id', 'left');
        } else {
            $b->select("NULL AS group_name, NULL AS group_code, NULL AS nature, 'fixed' AS classification, 0 AS affects_gross_profit", false);
        }
        $b->where('an.status !=', 'Delete')->whereIn('an.account_id', array_keys($net));
        $meta = [];
        foreach ($b->get()->getResult() as $m) {
            $meta[(int) $m->account_id] = $m;
        }

        $rows = [];
        foreach ($net as $aid => $net_cr) {
            $cls = acc_side_from_balance($net_cr);
            if ($only_nonzero && $cls['side'] === 'Nil') {
                continue;
            }
            $m = $meta[$aid] ?? null;
            if (! $m) {
                continue; // txn references a deleted/absent master row
            }
            $rows[] = (object) [
                'account_id'     => $aid,
                'name'           => $m->name,
                'account_type'   => $m->account_type ?? '',
                'group_id'       => isset($m->account_group_id) ? (int) $m->account_group_id : 0,
                'group_name'     => ($m->group_name ?? null) !== null ? $m->group_name : 'Unclassified',
                'group_code'     => ($m->group_code ?? null) !== null ? $m->group_code : 'SUSPENSE',
                'nature'         => ($m->nature ?? null) !== null ? $m->nature : 'asset',
                'classification' => ($m->classification ?? null) !== null ? $m->classification : 'by_balance',
                'affects_gp'     => isset($m->affects_gross_profit) ? (int) $m->affects_gross_profit : 0,
                'net_cr'         => (float) $net_cr,
                'abs'            => $cls['abs'],
                'side'           => $cls['side'],
                'status'         => $cls['status'],
                'last_date'      => $last[$aid] ?? null,
            ];
        }
        return $rows;
    }

    /** FIFO ageing of a party's outstanding receivable into age buckets. */
    public function ageing_for_account($account_id, string $ason = ''): array
    {
        $db         = $this->db();
        $fy         = fy();
        $account_id = (int) $account_id;
        $ason_ts    = $ason !== '' ? strtotime($ason) : time();

        $stream = $db->table('aa_rokad')
            ->select("rokad_date AS d,
                CASE WHEN LOWER(type_of_account)='deposit' THEN 0 ELSE karch_amount END AS dr,
                CASE WHEN LOWER(type_of_account)='deposit' THEN karch_amount ELSE 0 END AS cr", false)
            ->where('status !=', 'Delete')->where('account_no', $account_id)
            ->where('FY', $fy->FY)->where('product_type', $fy->product_type)->where('template_id', $fy->template_id)
            ->orderBy('rokad_date', 'asc')->get()->getResult();

        $lots        = [];
        $credit_pool = 0.0;
        foreach ($stream as $r) {
            $dr = (float) $r->dr;
            $cr = (float) $r->cr;
            if ($dr > 0) {
                $lots[] = [strtotime($r->d), $dr];
            }
            $credit_pool += $cr;
            for ($i = 0; $i < count($lots) && $credit_pool > 0; $i++) {
                if ($lots[$i][1] <= 0) {
                    continue;
                }
                $take = min($lots[$i][1], $credit_pool);
                $lots[$i][1] -= $take;
                $credit_pool -= $take;
            }
        }

        $buckets = ['b0_30' => 0.0, 'b31_60' => 0.0, 'b61_90' => 0.0, 'b90_plus' => 0.0, 'total' => 0.0];
        foreach ($lots as $lot) {
            $amt = $lot[1];
            if ($amt <= 0.005) {
                continue;
            }
            $days = $lot[0] ? floor(($ason_ts - $lot[0]) / 86400) : 0;
            if ($days <= 30) {
                $buckets['b0_30'] += $amt;
            } elseif ($days <= 60) {
                $buckets['b31_60'] += $amt;
            } elseif ($days <= 90) {
                $buckets['b61_90'] += $amt;
            } else {
                $buckets['b90_plus'] += $amt;
            }
            $buckets['total'] += $amt;
        }
        return $buckets;
    }

    /* =====================================================================
     * INTER-FIRM RECONCILIATION (sister firms)
     * ===================================================================== */

    /** Credit-positive balances for accounts scoped to an ARBITRARY firm/FY. */
    public function balances_for_firm($template_id, array $account_ids): array
    {
        helper('accounting');
        $ids = array_values(array_unique(array_filter(array_map('intval', $account_ids))));
        if (empty($ids)) {
            return [];
        }
        $db          = $this->db();
        $template_id = (int) $template_id;

        $tpl = $db->table('aa_template')->select('FY, product_type')->where('template_id', $template_id)->get()->getRow();
        $out = [];
        foreach ($ids as $id) {
            $out[$id] = 0.0;
        }
        $in       = implode(',', $ids);
        $where_fy = $tpl ? (" AND FY = " . $db->escape($tpl->FY) . " AND product_type = " . $db->escape($tpl->product_type)) : '';

        $sql = "SELECT account_no,
                    SUM(CASE WHEN LOWER(type_of_account)='deposit' THEN karch_amount ELSE -karch_amount END) AS net_cr
                FROM aa_rokad
                WHERE status <> 'Delete' AND account_no IN ($in)
                  AND template_id = " . $db->escape($template_id) . $where_fy . "
                GROUP BY account_no";
        foreach ($db->query($sql)->getResult() as $r) {
            $aid = (int) $r->account_no;
            if (isset($out[$aid])) {
                $out[$aid] += (float) $r->net_cr;
            }
        }
        $result = [];
        foreach ($out as $aid => $net_cr) {
            $result[$aid] = acc_side_from_balance($net_cr);
        }
        return $result;
    }

    /** Sister-firm reconciliation: our vs their balance of each linked ledger. */
    public function inter_firm_reconciliation(): array
    {
        helper('accounting');
        if (! $this->schema_ready()) {
            return [];
        }
        $db   = $this->db();
        $fy   = fy();
        $home = (int) $fy->template_id;

        $ours = $db->table('aa_ledger l')
            ->select('l.id, l.legacy_account_id, l.name, l.linked_template_id, t.template_name AS other_name')
            ->join('aa_template t', 't.template_id = l.linked_template_id', 'left')
            ->where('l.template_id', $home)
            ->where('l.linked_template_id IS NOT NULL', null, false)
            ->where('l.status !=', 'Delete')
            ->get()->getResult();
        if (empty($ours)) {
            return [];
        }

        $our_ids = [];
        foreach ($ours as $o) {
            if ($o->legacy_account_id) {
                $our_ids[] = (int) $o->legacy_account_id;
            }
        }
        $our_bal = $this->ledger_balances($our_ids);

        $out = [];
        foreach ($ours as $o) {
            $our   = $our_bal[(int) $o->legacy_account_id] ?? acc_side_from_balance(0);
            $their = acc_side_from_balance(0);
            $back  = $db->table('aa_ledger')->select('legacy_account_id')
                ->where('template_id', (int) $o->linked_template_id)
                ->where('linked_template_id', $home)
                ->where('status !=', 'Delete')->get()->getRow();
            if ($back && $back->legacy_account_id) {
                $tb    = $this->balances_for_firm((int) $o->linked_template_id, [(int) $back->legacy_account_id]);
                $their = $tb[(int) $back->legacy_account_id] ?? acc_side_from_balance(0);
            }

            $difference = $our['net'] + $their['net'];
            $out[]      = (object) [
                'ledger_name'   => $o->name,
                'other_firm_id' => (int) $o->linked_template_id,
                'other_firm'    => $o->other_name !== null ? $o->other_name : ('Firm #' . (int) $o->linked_template_id),
                'our_abs'       => $our['abs'], 'our_side' => $our['side'], 'our_status' => $our['status'],
                'their_abs'     => $their['abs'], 'their_side' => $their['side'], 'their_status' => $their['status'],
                'difference'    => $difference,
                'reconciled'    => abs($difference) <= 0.5,
            ];
        }
        return $out;
    }
}
