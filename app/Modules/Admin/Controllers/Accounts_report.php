<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\AccountingModel;
use App\Modules\Admin\Models\StockModel;

/**
 * Accounts_report — CI4 port of admin/Accounts_report (balance-driven reports).
 *
 * Every figure is computed LIVE from aa_rokad (+ kisanvahidata) via
 * AccountingModel — nothing is stored as debtor/creditor. Ported here:
 *   index, trial_balance, outstanding/debtors/creditors, ageing,
 *   balance_sheet, profit_loss.
 * (trading_profit + inter_firm follow with the Stock-valuation / cross-firm
 * subsystems they depend on.)  RBAC key = 'accounts_report'.
 */
class Accounts_report extends BaseController
{
    protected $helpers = ['url', 'form', 'text', 'app', 'cr_cache', 'accounting'];

    private function model(): AccountingModel
    {
        return new AccountingModel();
    }

    /** as-on date from ?ason=Y-m-d ('' if unset/invalid). */
    private function ason(): string
    {
        $v = $this->request->getGet('ason');
        return ($v && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) ? $v : '';
    }

    /** Landing page linking to each report. */
    public function index()
    {
        return _layout('\App\Modules\Admin\Views\accounts_report\index', [
            'title' => 'Accounting Reports',
            'ready' => $this->model()->schema_ready(),
        ]);
    }

    /* -------------------------------------------------------------- Trial */
    public function trial_balance()
    {
        $ason = $this->ason();
        $rows = $this->model()->firm_account_balances($ason, true);
        usort($rows, static fn ($a, $b) => strcasecmp($a->group_name . $a->name, $b->group_name . $b->name));
        $dr = 0.0;
        $cr = 0.0;
        foreach ($rows as $r) {
            if ($r->side === 'Dr') {
                $dr += $r->abs;
            } elseif ($r->side === 'Cr') {
                $cr += $r->abs;
            }
        }
        return _layout('\App\Modules\Admin\Views\accounts_report\trial_balance', [
            'title' => 'Trial Balance',
            'rows'  => $rows,
            'dr'    => $dr,
            'cr'    => $cr,
            'diff'  => $cr - $dr,
            'ason'  => $ason,
            'ready' => $this->model()->schema_ready(),
        ]);
    }

    /* ------------------------------------------------ Outstanding / party */
    public function outstanding()
    {
        return $this->party_report('outstanding', 'Outstanding Report');
    }

    public function debtors()
    {
        return $this->party_report('debtor', 'Debtor Report');
    }

    public function creditors()
    {
        return $this->party_report('creditor', 'Creditor Report');
    }

    private function party_report(string $mode, string $title)
    {
        helper('accounting'); // outstanding.php/debtors view uses acc_account_type_label()
        $ason   = $this->ason();
        $all    = $this->model()->firm_account_balances($ason, true);
        $rows   = [];
        $tot_dr = 0.0;
        $tot_cr = 0.0;
        foreach ($all as $r) {
            if ($r->classification !== 'by_balance') {
                continue;
            }
            if ($mode === 'debtor' && $r->side !== 'Dr') {
                continue;
            }
            if ($mode === 'creditor' && $r->side !== 'Cr') {
                continue;
            }
            $rows[] = $r;
            if ($r->side === 'Dr') {
                $tot_dr += $r->abs;
            } elseif ($r->side === 'Cr') {
                $tot_cr += $r->abs;
            }
        }
        usort($rows, static fn ($a, $b) => $b->abs <=> $a->abs);

        return _layout('\App\Modules\Admin\Views\accounts_report\outstanding', [
            'title'  => $title,
            'rows'   => $rows,
            'mode'   => $mode,
            'tot_dr' => $tot_dr,
            'tot_cr' => $tot_cr,
            'ason'   => $ason,
            'ready'  => $this->model()->schema_ready(),
        ]);
    }

    /* ------------------------------------------------------------ Ageing */
    public function ageing()
    {
        $ason          = $this->ason();
        $ason_for_calc = $ason !== '' ? $ason : date('Y-m-d');
        $all           = $this->model()->firm_account_balances($ason, true);
        $rows          = [];
        $tot           = ['b0_30' => 0.0, 'b31_60' => 0.0, 'b61_90' => 0.0, 'b90_plus' => 0.0, 'total' => 0.0];
        foreach ($all as $r) {
            if ($r->classification !== 'by_balance' || $r->side !== 'Dr') {
                continue;
            }
            $buckets = $this->model()->ageing_for_account($r->account_id, $ason_for_calc);
            if ($buckets['total'] <= 0.005) {
                continue;
            }
            $r->ageing = $buckets;
            $rows[]    = $r;
            foreach ($tot as $k => $v) {
                $tot[$k] += $buckets[$k];
            }
        }
        usort($rows, static fn ($a, $b) => $b->ageing['total'] <=> $a->ageing['total']);

        return _layout('\App\Modules\Admin\Views\accounts_report\ageing', [
            'title' => 'Ageing Report (Receivables)',
            'rows'  => $rows,
            'tot'   => $tot,
            'ason'  => $ason_for_calc,
            'ready' => $this->model()->schema_ready(),
        ]);
    }

    /* ------------------------------------------------------ P&L / BS core */
    private function classify(string $ason = ''): array
    {
        $rows = $this->model()->firm_account_balances($ason, false);

        $pl = ['gross_income' => 0.0, 'gross_expense' => 0.0, 'net_income' => 0.0, 'net_expense' => 0.0,
               'income_lines' => [], 'expense_lines' => []];
        $bs = ['assets' => [], 'liabilities' => [], 'asset_total' => 0.0, 'liability_total' => 0.0];

        foreach ($rows as $r) {
            $nature = $r->nature;
            if ($nature === 'income' || $nature === 'expense') {
                if ($nature === 'income') {
                    $val         = $r->net_cr;
                    $r->pl_value = $val;
                    $pl['income_lines'][] = $r;
                    if ($r->affects_gp) {
                        $pl['gross_income'] += $val;
                    } else {
                        $pl['net_income'] += $val;
                    }
                } else {
                    $val         = -$r->net_cr;
                    $r->pl_value = $val;
                    $pl['expense_lines'][] = $r;
                    if ($r->affects_gp) {
                        $pl['gross_expense'] += $val;
                    } else {
                        $pl['net_expense'] += $val;
                    }
                }
                continue;
            }
            if ($r->classification === 'by_balance') {
                if ($r->side === 'Dr') {
                    $bs['assets'][] = $r;
                    $bs['asset_total'] += $r->abs;
                } elseif ($r->side === 'Cr') {
                    $bs['liabilities'][] = $r;
                    $bs['liability_total'] += $r->abs;
                }
                continue;
            }
            if ($nature === 'asset') {
                $val         = -$r->net_cr;
                $r->bs_value = $val;
                $bs['assets'][] = $r;
                $bs['asset_total'] += $val;
            } else {
                $val         = $r->net_cr;
                $r->bs_value = $val;
                $bs['liabilities'][] = $r;
                $bs['liability_total'] += $val;
            }
        }

        $pl['gross_profit']  = $pl['gross_income'] - $pl['gross_expense'];
        $pl['total_income']  = $pl['gross_income'] + $pl['net_income'];
        $pl['total_expense'] = $pl['gross_expense'] + $pl['net_expense'];
        $pl['net_profit']    = $pl['total_income'] - $pl['total_expense'];
        return ['pl' => $pl, 'bs' => $bs, 'rows' => $rows];
    }

    private function group_lines(array $rows, string $value_key): array
    {
        $groups = [];
        foreach ($rows as $r) {
            $g = $r->group_name;
            if (! isset($groups[$g])) {
                $groups[$g] = ['total' => 0.0, 'lines' => []];
            }
            if ($value_key === 'pl') {
                $val = $r->pl_value ?? 0.0;
            } elseif (isset($r->bs_value)) {
                $val = $r->bs_value;
            } else {
                $val = $r->abs;
            }
            $r->line_value = $val;
            $groups[$g]['total'] += $val;
            $groups[$g]['lines'][] = $r;
        }
        ksort($groups);
        return $groups;
    }

    /* ------------------------------------------------------ Balance Sheet */
    public function balance_sheet()
    {
        $ason = $this->ason();
        $c    = $this->classify($ason);
        $bs   = $c['bs'];
        $pl   = $c['pl'];

        $asset_total     = $bs['asset_total'];
        $liability_total = $bs['liability_total'];
        if ($pl['net_profit'] >= 0) {
            $liability_total += $pl['net_profit'];
        } else {
            $asset_total += -$pl['net_profit'];
        }

        return _layout('\App\Modules\Admin\Views\accounts_report\balance_sheet', [
            'title'           => 'Balance Sheet',
            'assets'          => $this->group_lines($bs['assets'], 'asset'),
            'liabilities'     => $this->group_lines($bs['liabilities'], 'liability'),
            'asset_total'     => $asset_total,
            'liability_total' => $liability_total,
            'net_profit'      => $pl['net_profit'],
            'difference'      => $asset_total - $liability_total,
            'ason'            => $ason,
            'ready'           => $this->model()->schema_ready(),
        ]);
    }

    /* -------------------------------------------------------- Profit & Loss */
    public function profit_loss()
    {
        $ason = $this->ason();
        $c    = $this->classify($ason);
        return _layout('\App\Modules\Admin\Views\accounts_report\profit_loss', [
            'title'          => 'Profit & Loss',
            'pl'             => $c['pl'],
            'income_groups'  => $this->group_lines($c['pl']['income_lines'], 'pl'),
            'expense_groups' => $this->group_lines($c['pl']['expense_lines'], 'pl'),
            'ason'           => $ason,
            'ready'          => $this->model()->schema_ready(),
        ]);
    }

    /* ------------------------------------------------------ Trading Profit */

    /**
     * Trading Profit — month-wise (default, Prev/Next stepper) or whole-FY.
     * Gross profit = (Sales − Purchase) + (Closing − Opening stock). CI3 parity.
     */
    public function trading_profit()
    {
        $f = fy();
        [$fyStart, $fyEnd] = fy_date_range();

        $mode = ($this->request->getGet('mode') === 'fy') ? 'fy' : 'month';
        $m = (string) $this->request->getGet('m');
        if (! preg_match('/^\d{4}-\d{2}$/', $m)) { $m = date('Y-m'); }
        $ms = date('Y-m-01', strtotime($m . '-01'));
        $me = date('Y-m-t',  strtotime($m . '-01'));
        // Snap to the FY-start month if the chosen month falls outside the FY.
        if ($fyStart !== '' && $fyEnd !== '' && ($me < $fyStart || $ms > $fyEnd)) {
            $m  = substr($fyStart, 0, 7);
            $ms = date('Y-m-01', strtotime($fyStart));
            $me = date('Y-m-t',  strtotime($fyStart));
        }
        if ($fyStart !== '' && $ms < $fyStart) { $ms = $fyStart; }
        if ($fyEnd   !== '' && $me > $fyEnd)   { $me = $fyEnd; }

        $prev = date('Y-m', strtotime($m . '-01 -1 month'));
        $next = date('Y-m', strtotime($m . '-01 +1 month'));
        $has_prev = ($fyStart === '' || date('Y-m-t', strtotime($prev . '-01')) >= $fyStart);
        $has_next = ($fyEnd   === '' || date('Y-m-01', strtotime($next . '-01')) <= $fyEnd);

        if ($mode === 'fy') {
            $rangeStart = ($fyStart !== '') ? $fyStart : $ms;
            $rangeEnd   = ($fyEnd   !== '') ? $fyEnd   : $me;
            $period_label = 'Full FY ' . $f->FY;
        } else {
            $rangeStart = $ms;
            $rangeEnd   = $me;
            $period_label = date('F Y', strtotime($m . '-01'));
        }

        $stock = new StockModel();
        $data = [];
        $data['sum']   = $this->model()->trading_profit_summary($f->template_id, $f->product_type, $rangeStart, $rangeEnd);
        $data['stock'] = $stock->firm_stock_valuation($rangeStart, $rangeEnd, $fyStart);

        // Full FY: month-by-month gross-profit breakdown (Sales − COGS).
        $data['month_rows']   = [];
        $data['fy_sum_cogs']  = 0;
        $data['fy_sum_gross'] = 0;
        if ($mode === 'fy' && $fyStart !== '' && $fyEnd !== '') {
            $cursor = $fyStart; $guard = 0;
            while ($cursor <= $fyEnd && $guard < 24) {
                $guard++;
                $cms = date('Y-m-01', strtotime($cursor));
                $cme = date('Y-m-t',  strtotime($cursor));
                if ($cms < $fyStart) { $cms = $fyStart; }
                if ($cme > $fyEnd)   { $cme = $fyEnd; }
                $ms2 = $this->model()->trading_profit_summary($f->template_id, $f->product_type, $cms, $cme);
                $st2 = $stock->firm_stock_valuation($cms, $cme, $fyStart);
                $sb  = $ms2['totals']['sales_base'];
                $sq  = $ms2['totals']['sales_qty'];
                $cr  = ($st2['closing_qty'] > 0) ? $st2['closing_value'] / $st2['closing_qty'] : 0;
                $cg  = $sq * $cr;
                $gr  = $sb - $cg;
                $data['month_rows'][] = [
                    'label' => date('M Y', strtotime($cms)),
                    'sales' => $sb, 'qty' => $sq, 'cost_rate' => $cr, 'cogs' => $cg, 'gross' => $gr,
                    'bills' => $ms2['totals']['sales_cnt'],
                ];
                $data['fy_sum_cogs']  += $cg;
                $data['fy_sum_gross'] += $gr;
                $cursor = date('Y-m-d', strtotime($cms . ' +1 month'));
            }
        }

        // Profit target projection: how much more to sell to hit ₹X/month.
        $target_pm = (float) $this->request->getGet('target');
        if ($target_pm <= 0) { $target_pm = 2500000; } // default ₹25 lakh / month
        $months = 1;
        if ($rangeStart !== '' && $rangeEnd !== '') {
            $d1 = new \DateTime(date('Y-m-01', strtotime($rangeStart)));
            $d2 = new \DateTime(date('Y-m-01', strtotime($rangeEnd)));
            $months = ((int) $d2->format('Y') - (int) $d1->format('Y')) * 12 + ((int) $d2->format('n') - (int) $d1->format('n')) + 1;
            if ($months < 1) { $months = 1; }
        }

        $data['target_pm']    = $target_pm;
        $data['months']       = $months;
        $data['mode']         = $mode;
        $data['month']        = $m;
        $data['month_label']  = date('F Y', strtotime($m . '-01'));
        $data['period_label'] = $period_label;
        $data['m_start']      = $rangeStart;
        $data['m_end']        = $rangeEnd;
        $data['prev_m']       = $prev;
        $data['next_m']       = $next;
        $data['has_prev']     = $has_prev;
        $data['has_next']     = $has_next;
        $data['fy_start']     = $fyStart;
        $data['fy_end']       = $fyEnd;
        $data['ready']        = $this->model()->schema_ready();
        $data['title']        = 'Trading Profit';

        return _layout('\App\Modules\Admin\Views\accounts_report\trading_profit', $data);
    }

    /** Shared period range from mode/m GET params (report + bill drill-down). */
    private function tp_range(): array
    {
        $f = fy();
        [$fyStart, $fyEnd] = fy_date_range();
        $mode = ($this->request->getGet('mode') === 'fy') ? 'fy' : 'month';
        $m = (string) $this->request->getGet('m');
        if (! preg_match('/^\d{4}-\d{2}$/', $m)) { $m = date('Y-m'); }
        $ms = date('Y-m-01', strtotime($m . '-01'));
        $me = date('Y-m-t',  strtotime($m . '-01'));
        if ($fyStart !== '' && $fyEnd !== '' && ($me < $fyStart || $ms > $fyEnd)) {
            $m = substr($fyStart, 0, 7); $ms = date('Y-m-01', strtotime($fyStart)); $me = date('Y-m-t', strtotime($fyStart));
        }
        if ($fyStart !== '' && $ms < $fyStart) { $ms = $fyStart; }
        if ($fyEnd   !== '' && $me > $fyEnd)   { $me = $fyEnd; }
        if ($mode === 'fy') {
            $rs = ($fyStart !== '') ? $fyStart : $ms; $re = ($fyEnd !== '') ? $fyEnd : $me; $label = 'Full FY ' . $f->FY;
        } else {
            $rs = $ms; $re = $me; $label = date('F Y', strtotime($m . '-01'));
        }
        return ['start' => $rs, 'end' => $re, 'label' => $label];
    }

    /** AJAX: individual bills behind the Sales / Purchase totals (drill-down). */
    public function trading_profit_bills()
    {
        $f    = fy();
        $type = ($this->request->getGet('type') === 'purchase') ? 'purchase' : 'sales';
        $r    = $this->tp_range();
        $rows = $type === 'purchase'
            ? $this->model()->trading_purchase_bills($f->template_id, $f->product_type, $r['start'], $r['end'])
            : $this->model()->trading_sales_bills($f->template_id, $f->product_type, $r['start'], $r['end']);

        $map = [
            'Bill of Supply'      => 'admin/invoice/DownloadGeneratePdf/',
            'Tax Invoice'         => 'admin/taxinvoice/GeneratePdf/',
            'Un-registered BOS'   => 'admin/uninvoice/GeneratePdf/',
            'Purchase from Kisan' => 'admin/payment_receipt/DownloadGeneratePdf/',
            'Purchase Module'     => 'admin/purchase_module/view/',
        ];
        foreach ($rows as $row) {
            $row->url = (isset($map[$row->src]) && $row->doc_id !== null)
                ? base_url($map[$row->src] . ID_encode($row->doc_id)) : '';
            unset($row->doc_id);
        }

        return $this->response->setJSON(['type' => $type, 'label' => $r['label'], 'rows' => $rows]);
    }

    /* --------------------------------------------------------- Inter-firm */
    public function inter_firm()
    {
        return _layout('\App\Modules\Admin\Views\accounts_report\inter_firm', [
            'title' => 'Sister-Firm Reconciliation',
            'rows'  => $this->model()->inter_firm_reconciliation(),
            'ready' => $this->model()->schema_ready(),
        ]);
    }
}
