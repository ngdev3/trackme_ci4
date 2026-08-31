<?php
// Partial: single-party expense/deposit detail for the Account Statement modal.
// $ledger = { account, cash:{opening,rows[]}, kisanvahi:{opening,rows[]} }
if (!function_exists('amd_money')) {
    function amd_money($v) { return function_exists('acc_money') ? acc_money(abs($v)) : number_format(abs((float) $v), 2); }
}
$acc_obj  = isset($ledger->account) ? $ledger->account : null;
$acc_name = (is_object($acc_obj) && isset($acc_obj->name)) ? $acc_obj->name : (is_string($acc_obj) ? $acc_obj : 'Account');
$from = isset($from) ? $from : '';
$to   = isset($to) ? $to : '';
$period = ($from !== '' && $to !== '') ? (date('d-m-Y', strtotime($from)) . ' → ' . date('d-m-Y', strtotime($to))) : ('Full FY ' . fy()->FY);

// Combined closing across both books (credit-positive).
$closing = 0.0;
$sections = array();
foreach (array('cash' => 'Cash Book (Rokad)', 'kisanvahi' => 'KisanVahi') as $key => $label) {
    $sec = isset($ledger->$key) ? $ledger->$key : null;
    if (!$sec) { continue; }
    $rows = isset($sec->rows) ? $sec->rows : array();
    $opening = isset($sec->opening) ? (float) $sec->opening : 0.0;
    $tot_dr = 0.0; $tot_cr = 0.0;
    foreach ($rows as $r) { $tot_dr += (float) $r->debit; $tot_cr += (float) $r->credit; }
    $sec_close = $opening + $tot_cr - $tot_dr;
    $closing += $sec_close;
    $sections[$key] = array('label' => $label, 'rows' => $rows, 'opening' => $opening, 'tot_dr' => $tot_dr, 'tot_cr' => $tot_cr, 'close' => $sec_close);
}
$c_side = $closing > 0.004 ? 'cr' : ($closing < -0.004 ? 'dr' : 'nil');
$c_lbl  = $c_side === 'cr' ? 'जमा (Cr)' : ($c_side === 'dr' ? 'नाम (Dr)' : 'Nil');
?>
<div class="amd">
    <div class="amd-head">
        <div>
            <div class="amd-name"><?= esc($acc_name) ?></div>
            <div class="amd-sub">Statement detail &middot; <?= esc($period) ?></div>
        </div>
        <div class="amd-close-box amd-<?= $c_side ?>">
            <span>Closing Balance</span>
            <b>&#8377; <?= amd_money($closing) ?> <?= $c_side === 'nil' ? '' : strtoupper($c_side) ?></b>
            <small><?= $c_lbl ?></small>
        </div>
    </div>

    <?php if (empty($sections) || (empty($sections['cash']['rows']) && empty($sections['kisanvahi']['rows']))): ?>
        <div class="amd-empty">No नाम / जमा entries for this party in the selected period.</div>
    <?php endif; ?>

    <?php foreach ($sections as $key => $s):
        if (empty($s['rows'])) { continue; }
        $bal = $s['opening'];
    ?>
        <div class="amd-sec-h"><?= esc($s['label']) ?> <span><?= count($s['rows']) ?> entries</span></div>
        <div class="amd-table-wrap">
            <table class="amd-table">
                <thead>
                    <tr><th>#</th><th>Date</th><th>Particulars</th><th>Voucher</th><th class="num">नाम (Dr)</th><th class="num">जमा (Cr)</th><th class="num">Balance</th></tr>
                </thead>
                <tbody>
                    <tr class="amd-open"><td></td><td></td><td colspan="2"><b>Opening Balance</b></td><td></td><td></td><td class="num"><b>&#8377; <?= amd_money($s['opening']) ?> <?= $s['opening'] > 0 ? 'Cr' : ($s['opening'] < 0 ? 'Dr' : '') ?></b></td></tr>
                    <?php $i = 0; foreach ($s['rows'] as $r):
                        $i++;
                        $dr = (float) $r->debit; $cr = (float) $r->credit;
                        $bal += $cr - $dr;
                        $bcls = $bal > 0.004 ? 'cr' : ($bal < -0.004 ? 'dr' : '');
                        $date = $r->date ? date('d-m-Y', strtotime($r->date)) : '';
                        $rem = isset($r->remark) ? trim((string) $r->remark) : '';
                        $src = isset($r->source_label) ? $r->source_label : '';
                    ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td class="amd-nowrap"><?= esc($date) ?></td>
                            <td>
                                <?= esc($r->particulars) ?>
                                <?php if ($rem !== ''): ?><span class="amd-rem"><?= esc($rem) ?></span><?php endif; ?>
                            </td>
                            <td><?= esc($r->voucher) ?> <?php if ($src !== ''): ?><span class="amd-src amd-src-<?= strtolower($src) ?>"><?= esc($src) ?></span><?php endif; ?></td>
                            <td class="num amd-dr"><?= $dr > 0 ? '&#8377; ' . amd_money($dr) : '—' ?></td>
                            <td class="num amd-cr"><?= $cr > 0 ? '&#8377; ' . amd_money($cr) : '—' ?></td>
                            <td class="num amd-bal-<?= $bcls ?>"><b>&#8377; <?= amd_money($bal) ?> <?= $bcls === '' ? '' : strtoupper($bcls) ?></b></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="num">TOTAL</td>
                        <td class="num amd-dr">&#8377; <?= amd_money($s['tot_dr']) ?></td>
                        <td class="num amd-cr">&#8377; <?= amd_money($s['tot_cr']) ?></td>
                        <td class="num"><b>&#8377; <?= amd_money($s['close']) ?> <?= $s['close'] > 0 ? 'Cr' : ($s['close'] < 0 ? 'Dr' : '') ?></b></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endforeach; ?>
</div>
