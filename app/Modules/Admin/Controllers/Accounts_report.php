<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\AccountingModel;

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
