<?php
helper(['url', 'app']);
include __DIR__ . '/_style.php';
$t   = $sum['totals'];
$s   = $sum['sales'];
$p   = $sum['purchase'];
$fyo = function_exists('fy') ? fy() : null;
$profit = $t['profit_base'];                        // raw Sales − Purchase (context only)
$isProfit = $profit >= 0;
$open_val  = isset($stock['opening_value']) ? (float) $stock['opening_value'] : 0;
$close_val = isset($stock['closing_value']) ? (float) $stock['closing_value'] : 0;

// ---- Realized gross profit = Sales − Cost of Goods Sold -------------------
// COGS = quantity actually SOLD × weighted-average cost rate (stock rate).
// This is stable and meaningful: it does NOT swing with buy-heavy months the
// way "Sales − Purchase + ΔStock" does (valuing the whole purchase into stock
// at a static rate almost cancels the cash outflow, giving a ~₹0 artefact).
$avg_sale   = $t['sales_qty'] > 0 ? $t['sales_base'] / $t['sales_qty'] : 0;            // ₹ / unit sold
$stock_rate = ($stock['closing_qty'] > 0) ? $close_val / $stock['closing_qty'] : 0;    // weighted avg cost
$avg_cost   = $stock_rate > 0 ? $stock_rate
              : ($t['purchase_qty'] > 0 ? $t['purchase_base'] / $t['purchase_qty'] : 0);
$margin_u   = $avg_sale - $avg_cost;                                                    // profit per unit sold
$cogs       = $t['sales_qty'] * $avg_cost;                                              // cost of what was sold
$gross      = $t['sales_base'] - $cogs;                                                 // realized gross profit
// In Full-FY mode, use the month-by-month sum so the headline ties to the breakdown table.
$fy_break   = (isset($mode) && $mode === 'fy' && !empty($month_rows));
if ($fy_break) {
    $cogs  = isset($fy_sum_cogs)  ? (float) $fy_sum_cogs  : $cogs;
    $gross = isset($fy_sum_gross) ? (float) $fy_sum_gross : $gross;
}
$isGross    = $gross >= 0;

// ---- Profit-target projection: how much more to sell to hit the target. ----
$target_total = $target_pm * $months;                                                 // ₹ for the whole period
$gap         = $target_total - $gross;
$target_met  = $gap <= 0;
$can_project = ($margin_u > 0 && $avg_sale > 0);
$extra_qty   = ($can_project && !$target_met) ? $gap / $margin_u : 0;
$extra_amt   = $extra_qty * $avg_sale;
$need_qty    = $t['sales_qty'] + $extra_qty;
$need_amt    = $t['sales_base'] + $extra_amt;

$money = function ($v) { return function_exists('acc_money') ? acc_money($v) : number_format((float) $v, 2); };
$qty   = function ($v) { return number_format((float) $v, 2); };
$lac   = function ($v) { return number_format(((float) $v) / 100000, 2) . ' L'; };
?>
<style>
    .tp-kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:16px; }
    .tp-kpi { border:1px solid #dce6f2; border-radius:12px; background:#fff; padding:16px 18px; box-shadow:0 8px 22px rgba(24,36,60,.06); }
    .tp-kpi span { display:block; font-size:11px; font-weight:900; text-transform:uppercase; letter-spacing:.03em; color:#7a8aa0; }
    .tp-kpi strong { display:block; margin-top:5px; font-size:22px; font-weight:900; color:#18243c; }
    .tp-kpi.sale strong { color:#1769c2; } .tp-kpi.buy strong { color:#b45309; }
    .tp-kpi.profit { background:linear-gradient(135deg,#0a6f49,#0c7a50); border:0; }
    .tp-kpi.profit.loss { background:linear-gradient(135deg,#b3261e,#8f1d17); }
    .tp-kpi.profit span, .tp-kpi.profit strong { color:#fff; }
    .tp-kpi.profit small { color:rgba(255,255,255,.85); font-weight:800; font-size:12px; }
    .tp-table th.num, .tp-table td.num { text-align:right; }
    .tp-table tfoot td { font-weight:900; background:#f6f9ff; border-top:2px solid #dce6f2; }
    .tp-src { font-weight:800; color:#0c315f; }
    .tp-note { margin-top:14px; padding:13px 16px; background:#f8fafc; border:1px solid #eef2f7; border-radius:9px; font-size:12px; color:#718096; line-height:1.65; }
    .tp-invctx { margin-top:12px; padding:11px 14px; background:#f6f9ff; border:1px solid #e3ecfa; border-radius:9px; font-size:12.5px; color:#334155; line-height:1.7; }
    .tp-invctx > span { color:#7a8aa0; font-weight:800; text-transform:uppercase; font-size:10.5px; letter-spacing:.02em; margin-right:6px; }
    .tp-mhead { padding:14px 16px; font-size:15px; font-weight:900; color:#0c315f; border-bottom:1px solid #eef2f7; background:linear-gradient(180deg,#fbfdff,#fff); }
    .tp-mfoot { padding:11px 16px; font-size:11.5px; color:#8190a5; border-top:1px solid #eef2f7; background:#fbfdff; }
    .tp-table tbody tr:hover td { background:rgba(23,105,194,.05); }
    .tp-drill { color:#1769c2; font-weight:900; text-decoration:none; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
    .tp-drill:hover { text-decoration:underline; }
    .tp-drill i { font-size:13px; }
    .tp-modal { position:fixed; inset:0; z-index:20000; display:none; align-items:flex-start; justify-content:center; background:rgba(15,23,42,.5); padding:40px 14px; overflow:auto; }
    .tp-modal.open { display:flex; }
    .tp-modal-card { width:100%; max-width:860px; background:#fff; border-radius:14px; box-shadow:0 30px 70px rgba(16,32,72,.35); overflow:hidden; animation:tpPop .14s ease; }
    @keyframes tpPop { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:none; } }
    .tp-modal-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:15px 18px; border-bottom:1px solid #eef2f7; background:linear-gradient(180deg,#f8fbff,#fff); }
    .tp-modal-head b { font-size:16px; color:#0c315f; }
    .tp-modal-sub { font-size:12px; color:#8190a5; font-weight:700; }
    .tp-modal-x { border:0; background:#eef3fa; color:#516174; width:34px; height:34px; border-radius:9px; font-size:20px; line-height:1; cursor:pointer; }
    .tp-modal-x:hover { background:#e0e8f4; }
    .tp-modal-body { padding:6px 10px 12px; max-height:70vh; overflow:auto; }
    .tp-billlink { color:#1769c2; font-weight:800; text-decoration:none; white-space:nowrap; }
    .tp-billlink:hover { text-decoration:underline; }
    .tp-billlink i { font-size:10px; opacity:.7; }
    .tp-modetoggle { display:inline-flex; border:1px solid #dce6f2; border-radius:8px; overflow:hidden; margin-right:6px; }
    .tp-modetoggle .tp-seg { padding:9px 14px; font-size:13px; font-weight:800; color:#516174; text-decoration:none; background:#fff; }
    .tp-modetoggle .tp-seg + .tp-seg { border-left:1px solid #dce6f2; }
    .tp-modetoggle .tp-seg.on { background:#1769c2; color:#fff; }
    .tp-monthnav { display:inline-flex; align-items:center; gap:0; border:1px solid #dce6f2; border-radius:8px; overflow:hidden; background:#fff; margin-right:6px; }
    .tp-monthnav .tp-mbtn { display:inline-flex; align-items:center; gap:5px; padding:9px 13px; font-size:13px; font-weight:800; color:#1769c2; text-decoration:none; background:#fff; }
    .tp-monthnav .tp-mbtn:hover { background:#eef4ff; }
    .tp-monthnav .tp-mbtn.disabled { color:#c2cbd8; cursor:not-allowed; }
    .tp-monthnav .tp-mcur { padding:9px 14px; font-size:13px; font-weight:900; color:#18243c; background:#f6f9ff; border-left:1px solid #dce6f2; border-right:1px solid #dce6f2; white-space:nowrap; }
    .tp-target-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:12px; }
    .tp-target-head h3 { margin:0; font-size:16px; font-weight:900; color:#0c315f; }
    .tp-target-form { display:flex; align-items:center; gap:7px; font-weight:800; color:#516174; font-size:13px; }
    .tp-target-form input[type=number] { width:130px; height:36px; border:1px solid #dce6f2; border-radius:8px; padding:6px 9px; font-weight:800; }
    .tp-target-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:12px; }
    .tp-t { border:1px solid #e3e9f2; border-radius:10px; padding:12px 13px; background:#fbfdff; }
    .tp-t span { display:block; font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:.02em; color:#7a8aa0; }
    .tp-t strong { display:block; margin-top:4px; font-size:17px; font-weight:900; color:#18243c; }
    .tp-t small { color:#9aa4b2; font-weight:700; font-size:11px; }
    .tp-callout { border-radius:10px; padding:13px 15px; font-size:13.5px; font-weight:700; line-height:1.55; }
    .tp-callout.need { background:#eef4ff; border:1px solid #cfe0fb; color:#173a7a; }
    .tp-callout.ok   { background:#e7f7ee; border:1px solid #bde8cf; color:#0a6f49; }
    .tp-callout.warn { background:#fff6e6; border:1px solid #ffe0a6; color:#8a5a00; }
    .tp-need { display:grid; grid-template-columns:repeat(2,1fr); gap:12px; margin-top:10px; }
    .tp-need > div { background:#fff; border:1px solid #cfe0fb; border-radius:8px; padding:10px 12px; }
    .tp-need span { display:block; font-size:11px; font-weight:800; text-transform:uppercase; color:#7a8aa0; }
    .tp-need strong { display:block; margin-top:3px; font-size:18px; font-weight:900; color:#1740b5; }
    /* ---- Alignment + responsiveness ---- */
    .main-content { padding:18px 22px 30px; box-sizing:border-box; }
    .rp-wrap { padding:0; }
    .rp-wrap .rp-tools { align-items:center; }
    .tp-kpi, .tp-t { min-width:0; }                 /* let grid cells shrink instead of overflowing */
    .tp-kpi strong, .tp-t strong, .tp-need strong { overflow-wrap:anywhere; }
    .tp-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; }
    .tp-scroll .tp-wide { min-width:460px; }        /* 5-col tables scroll rather than squish */
    .tp-target-head, .tp-target-form { min-width:0; flex-wrap:wrap; }
    @media (max-width:900px){ .tp-kpis, .tp-target-grid { grid-template-columns:repeat(2,1fr); } .tp-need { grid-template-columns:1fr; } }
    @media (max-width:640px){
        .tp-kpi strong { font-size:18px; } .tp-t strong { font-size:15px; }
        .rp-head { align-items:flex-start; }
        .rp-tools { width:100%; }
        .tp-modetoggle, .tp-monthnav { flex:1 1 auto; justify-content:center; margin-right:0; }
        .tp-monthnav { justify-content:space-between; }
        .tp-modetoggle .tp-seg { flex:1 1 0; text-align:center; }
        .tp-target-form { width:100%; justify-content:flex-start; }
    }
    @media (max-width:640px){ .main-content { padding:12px 13px 24px; } }
    @media (max-width:430px){ .tp-kpis, .tp-target-grid { grid-template-columns:1fr; } }
</style>

<main class="main-content bgc-grey-100"><div id="mainContent"><div class="container-fluid"><div class="rp-wrap">
    <?= get_flashdata(); ?>
    <div class="rp-head">
        <div class="rp-title">Trading Profit
            <small><?= html_escape($fyo ? $fyo->firm_name : '') ?> &middot; FY <?= html_escape($fyo ? $fyo->FY : '') ?>
                &middot; <b><?= html_escape($period_label) ?></b>
                <?php if (!empty($m_start) && !empty($m_end)): ?>(<?= html_escape($m_start) ?> to <?= html_escape($m_end) ?>)<?php endif; ?>
            </small>
        </div>
        <div class="rp-tools">
            <div class="tp-modetoggle">
                <a class="tp-seg <?= $mode === 'month' ? 'on' : '' ?>" href="<?= base_url('admin/accounts_report/trading_profit?mode=month&m=' . urlencode($month)) ?>">This Month</a>
                <a class="tp-seg <?= $mode === 'fy' ? 'on' : '' ?>" href="<?= base_url('admin/accounts_report/trading_profit?mode=fy') ?>">Full FY</a>
            </div>
            <?php if ($mode === 'month'): ?>
            <div class="tp-monthnav">
                <?php if (!empty($has_prev)): ?>
                    <a class="tp-mbtn" href="<?= base_url('admin/accounts_report/trading_profit?m=' . urlencode($prev_m)) ?>" title="Previous month"><i class="ti-angle-left"></i> Prev</a>
                <?php else: ?>
                    <span class="tp-mbtn disabled"><i class="ti-angle-left"></i> Prev</span>
                <?php endif; ?>
                <span class="tp-mcur"><?= html_escape($month_label) ?></span>
                <?php if (!empty($has_next)): ?>
                    <a class="tp-mbtn" href="<?= base_url('admin/accounts_report/trading_profit?m=' . urlencode($next_m)) ?>" title="Next month">Next <i class="ti-angle-right"></i></a>
                <?php else: ?>
                    <span class="tp-mbtn disabled">Next <i class="ti-angle-right"></i></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <a class="rp-btn ghost" href="<?= base_url('admin/accounts_report') ?>"><i class="ti-arrow-left"></i> Reports</a>
            <a class="rp-btn" href="javascript:window.print()"><i class="ti-printer"></i> Print</a>
        </div>
    </div>

    <div class="tp-kpis">
        <div class="tp-kpi sale"><span>Total Sales (taxable)</span><strong>&#8377; <?= $money($t['sales_base']) ?></strong></div>
        <div class="tp-kpi buy"><span>Total Purchase (taxable)</span><strong>&#8377; <?= $money($t['purchase_base']) ?></strong></div>
        <div class="tp-kpi"><span>Closing Stock Value</span><strong>&#8377; <?= $money($close_val) ?></strong></div>
        <div class="tp-kpi profit <?= $isGross ? '' : 'loss' ?>">
            <span><?= $isGross ? 'Gross Profit' : 'Gross Loss' ?></span>
            <strong>&#8377; <?= $money(abs($gross)) ?></strong>
            <small>Sales &minus; COGS &middot; <?= $qty($t['sales_qty']) ?> units sold</small>
        </div>
    </div>

    <div class="rp-two">
        <!-- ---------------- Sales ---------------- -->
        <div class="rp-panel">
            <div class="tp-scroll">
            <table class="rp tp-table tp-wide">
                <thead>
                    <tr><th>Sales source</th><th class="num">Bills</th><th class="num">Qty</th><th class="num">Taxable &#8377;</th><th class="num">Incl. GST &#8377;</th></tr>
                </thead>
                <tbody>
                    <tr><td class="tp-src">Bill of Supply</td><td class="num"><?= (int) $s['bos']['cnt'] ?></td><td class="num"><?= $qty($s['bos']['qty']) ?></td><td class="num"><?= $money($s['bos']['base']) ?></td><td class="num"><?= $money($s['bos']['gross']) ?></td></tr>
                    <tr><td class="tp-src">Tax Invoice</td><td class="num"><?= (int) $s['tax']['cnt'] ?></td><td class="num"><?= $qty($s['tax']['qty']) ?></td><td class="num"><?= $money($s['tax']['base']) ?></td><td class="num"><?= $money($s['tax']['gross']) ?></td></tr>
                    <tr><td class="tp-src">Un-registered BOS</td><td class="num"><?= (int) $s['unreg']['cnt'] ?></td><td class="num"><?= $qty($s['unreg']['qty']) ?></td><td class="num"><?= $money($s['unreg']['base']) ?></td><td class="num"><?= $money($s['unreg']['gross']) ?></td></tr>
                </tbody>
                <tfoot>
                    <tr><td><a href="javascript:void(0)" class="tp-drill" data-type="sales" title="View the bills in this total">Total Sales <i class="ti-receipt"></i></a></td><td class="num"><?= (int) $t['sales_cnt'] ?></td><td class="num"><?= $qty($t['sales_qty']) ?></td><td class="num">&#8377; <?= $money($t['sales_base']) ?></td><td class="num">&#8377; <?= $money($t['sales_gross']) ?></td></tr>
                </tfoot>
            </table>
            </div>
        </div>

        <!-- ---------------- Purchase ---------------- -->
        <div class="rp-panel">
            <div class="tp-scroll">
            <table class="rp tp-table tp-wide">
                <thead>
                    <tr><th>Purchase source</th><th class="num">Bills</th><th class="num">Qty</th><th class="num">Taxable &#8377;</th><th class="num">Incl. GST &#8377;</th></tr>
                </thead>
                <tbody>
                    <tr><td class="tp-src">Purchase from Kisan</td><td class="num"><?= (int) $p['kisan']['cnt'] ?></td><td class="num"><?= $qty($p['kisan']['qty']) ?></td><td class="num"><?= $money($p['kisan']['base']) ?></td><td class="num"><?= $money($p['kisan']['gross']) ?></td></tr>
                    <tr><td class="tp-src">Purchase Module</td><td class="num"><?= (int) $p['module']['cnt'] ?></td><td class="num"><?= $qty($p['module']['qty']) ?></td><td class="num"><?= $money($p['module']['base']) ?></td><td class="num"><?= $money($p['module']['gross']) ?></td></tr>
                </tbody>
                <tfoot>
                    <tr><td><a href="javascript:void(0)" class="tp-drill" data-type="purchase" title="View the bills in this total">Total Purchase <i class="ti-receipt"></i></a></td><td class="num"><?= (int) $t['purchase_cnt'] ?></td><td class="num"><?= $qty($t['purchase_qty']) ?></td><td class="num">&#8377; <?= $money($t['purchase_base']) ?></td><td class="num">&#8377; <?= $money($t['purchase_gross']) ?></td></tr>
                </tfoot>
            </table>
            </div>
        </div>
    </div>

    <div class="rp-panel" style="margin-top:16px;">
        <table class="rp tp-table">
            <tbody>
                <tr><td class="tp-src">Sales revenue (taxable)</td><td class="num">&#8377; <?= $money($t['sales_base']) ?></td></tr>
                <tr><td>Less: Cost of Goods Sold
                    <span style="color:#9aa4b2;font-weight:600"><?= $fy_break ? '(sum of monthly COGS — see breakdown below)' : '(' . $qty($t['sales_qty']) . ' units sold &times; &#8377; ' . $money($avg_cost) . ' avg cost)' ?></span></td>
                    <td class="num">&minus; &#8377; <?= $money($cogs) ?></td></tr>
            </tbody>
            <tfoot>
                <tr><td class="tp-src" style="font-size:15px;">= Gross <?= $isGross ? 'Profit' : 'Loss' ?></td>
                    <td class="num" style="font-size:17px;color:<?= $isGross ? '#0a6f49' : '#b3261e' ?>;font-weight:900;">&#8377; <?= $money($gross) ?></td></tr>
            </tfoot>
        </table>
        <div class="tp-invctx">
            <span>Inventory context (not part of profit):</span>
            <b>Purchases this period</b> &#8377; <?= $money($t['purchase_base']) ?>
            &middot; <b>Closing stock on hand</b> &#8377; <?= $money($close_val) ?> (<?= $qty($stock['closing_qty']) ?> units as on <?= html_escape($m_end) ?>)
        </div>
        <div class="tp-note">
            <b>How this is computed.</b> Sales = Bill of Supply + Tax Invoice + Un-registered BOS (live docs only, taxable value ex-GST — GST is a pass-through, not profit).
            <b>Gross profit = Sales − Cost of Goods Sold</b>, where <code>COGS = quantity sold × average cost rate</code>. The average cost rate comes from
            the live stock valuation (<a href="<?= base_url('admin/stock/listing') ?>">Stock report</a>) &mdash; so profit reflects the <b>margin on what you
            actually sold</b>, and buying stock you haven't sold yet neither adds nor removes profit (it just sits in inventory, shown above).
            Figures are for <b><?= html_escape($period_label) ?></b><?= $mode === 'month' ? ' — use Prev / Next to step months, or “Full FY” for the year total' : '' ?>. Firm
            <b><?= html_escape($fyo ? $fyo->firm_name : '') ?></b>, FY <b><?= html_escape($fyo ? $fyo->FY : '') ?></b>. Bills are dated by billing date
            (Purchase Module by invoice date); undated bills fall back to their last-updated date.
        </div>
    </div>

    <?php if ($fy_break): ?>
    <!-- ---------------- Month-wise breakdown (Full FY) ---------------- -->
    <div class="rp-panel" style="margin-top:16px;">
        <div class="tp-mhead">Month-wise Gross Profit &mdash; FY <?= html_escape($fyo ? $fyo->FY : '') ?></div>
        <div class="tp-scroll">
        <table class="rp tp-table tp-wide">
            <thead>
                <tr><th>Month</th><th class="num">Sale Bills</th><th class="num">Qty Sold</th><th class="num">Sales &#8377;</th><th class="num">COGS &#8377;</th><th class="num">Gross Profit &#8377;</th></tr>
            </thead>
            <tbody>
                <?php foreach ($month_rows as $r): $g = $r['gross']; $ym = date('Y-m', strtotime($r['label'])); ?>
                <tr>
                    <td class="tp-src"><a href="<?= base_url('admin/accounts_report/trading_profit?mode=month&m=' . $ym) ?>" style="color:#1769c2;text-decoration:none;font-weight:800;"><?= html_escape($r['label']) ?></a></td>
                    <td class="num"><?= (int) $r['bills'] ?></td>
                    <td class="num"><?= $qty($r['qty']) ?></td>
                    <td class="num"><?= $money($r['sales']) ?></td>
                    <td class="num"><?= $money($r['cogs']) ?></td>
                    <td class="num" style="font-weight:900;color:<?= $g >= 0 ? '#0a6f49' : '#b3261e' ?>"><?= $money($g) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td>Total (FY)</td><td class="num"><?= (int) $t['sales_cnt'] ?></td><td class="num"><?= $qty($t['sales_qty']) ?></td>
                    <td class="num">&#8377; <?= $money($t['sales_base']) ?></td><td class="num">&#8377; <?= $money($cogs) ?></td>
                    <td class="num" style="color:<?= $isGross ? '#0a6f49' : '#b3261e' ?>">&#8377; <?= $money($gross) ?></td></tr>
            </tfoot>
        </table>
        </div>
        <div class="tp-mfoot">Click a month to open its detailed view. Each month's COGS uses that month's average cost rate; the totals tie to the Gross Profit above.</div>
    </div>
    <?php endif; ?>

    <!-- ---------------- Profit-target projection ---------------- -->
    <div class="rp-panel tp-target" style="margin-top:16px;">
        <div class="tp-target-head">
            <h3>Profit Target &mdash; how much more to sell</h3>
            <form method="get" action="<?= base_url('admin/accounts_report/trading_profit') ?>" class="tp-target-form">
                <input type="hidden" name="mode" value="<?= html_escape($mode) ?>">
                <input type="hidden" name="m" value="<?= html_escape($month) ?>">
                <label>Target &#8377;</label>
                <input type="number" name="target" min="0" step="10000" value="<?= (int) $target_pm ?>">
                <span>/ month</span>
                <button class="rp-btn" type="submit">Apply</button>
            </form>
        </div>

        <div class="tp-target-grid">
            <div class="tp-t"><span>Target for <?= html_escape($period_label) ?></span><strong>&#8377; <?= $money($target_total) ?></strong><small><?= $lac($target_pm) ?> &times; <?= (int) $months ?> month(s)</small></div>
            <div class="tp-t"><span>Current Gross Profit</span><strong style="color:<?= $isGross ? '#0a6f49' : '#b3261e' ?>">&#8377; <?= $money($gross) ?></strong><small><?= $lac($gross) ?></small></div>
            <div class="tp-t"><span>Sale / Cost rate per unit</span><strong>&#8377; <?= $money($avg_sale) ?> / <?= $money($avg_cost) ?></strong><small>margin &#8377; <?= $money($margin_u) ?>/unit<?= $stock_rate > 0 ? ' (cost = stock rate)' : '' ?></small></div>
            <div class="tp-t"><span>Gap to Target</span><strong style="color:<?= $target_met ? '#0a6f49' : '#b45309' ?>">&#8377; <?= $money(abs($gap)) ?></strong><small><?= $target_met ? 'surplus' : 'short' ?></small></div>
        </div>

        <?php if ($target_met): ?>
            <div class="tp-callout ok">✓ Target already met for <?= html_escape($period_label) ?> — gross profit &#8377; <?= $money($gross) ?> is above the &#8377; <?= $money($target_total) ?> target (surplus &#8377; <?= $money(-$gap) ?>).</div>
        <?php elseif (!$can_project): ?>
            <div class="tp-callout warn">⚠ Per-unit margin is &#8377; <?= $money($margin_u) ?> (&le; 0)<?= $t['sales_qty'] <= 0 ? ' — no sales recorded in this period yet' : '' ?>. You cannot reach the target by selling more at the current buy/sell rates; the <b>rates or product mix</b> need to improve first.</div>
        <?php else: ?>
            <div class="tp-callout need">
                To reach <b>&#8377; <?= $money($target_total) ?></b> profit you need to sell about
                <b><?= $qty($extra_qty) ?> more units</b> (&#8377; <?= $money($extra_amt) ?> more in sales) at the current
                <b>&#8377; <?= $money($margin_u) ?>/unit</b> margin.
                <div class="tp-need">
                    <div><span>Total sales quantity to target</span><strong><?= $qty($need_qty) ?> units</strong></div>
                    <div><span>Total sales value to target</span><strong>&#8377; <?= $money($need_amt) ?></strong></div>
                </div>
            </div>
        <?php endif; ?>
        <div class="tp-note">Projection assumes the current average margin (&#8377; <?= $money($margin_u) ?>/unit) holds for the extra sales — selling stock you already hold, or buying &amp; selling more at the same rates. It's a planning estimate, not a guarantee.</div>
    </div>

    <!-- ---------------- Bills drill-down modal ---------------- -->
    <div id="tpModal" class="tp-modal" aria-hidden="true">
        <div class="tp-modal-card">
            <div class="tp-modal-head">
                <div><b id="tpModalTitle">Bills</b> <span id="tpModalSub" class="tp-modal-sub"></span></div>
                <button type="button" class="tp-modal-x" id="tpModalClose" aria-label="Close">&times;</button>
            </div>
            <div class="tp-modal-body">
                <div class="tp-scroll">
                    <table class="rp tp-table tp-wide" id="tpModalTable">
                        <thead><tr><th>#</th><th>Source</th><th>Bill No</th><th>Date</th><th>Party</th><th class="num">Qty</th><th class="num">Amount &#8377;</th></tr></thead>
                        <tbody id="tpModalRows"><tr><td colspan="7" class="rp-empty">Loading…</td></tr></tbody>
                        <tfoot><tr><td colspan="5">Total</td><td class="num" id="tpModalQty">—</td><td class="num" id="tpModalAmt">—</td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div></div></div></main>

<script>
(function () {
    var billsUrl = "<?= base_url('admin/accounts_report/trading_profit_bills') ?>?mode=<?= urlencode($mode) ?>&m=<?= urlencode($month) ?>";
    var modal = document.getElementById('tpModal');
    var rowsEl = document.getElementById('tpModalRows');
    function money(n){ n = Number(n)||0; return '₹ ' + n.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
    function qty(n){ return (Number(n)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
    function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];}); }
    function fmtDate(d){ if(!d) return '—'; var t=String(d).substr(0,10); return t; }
    function open(type){
        modal.classList.add('open'); modal.setAttribute('aria-hidden','false');
        document.getElementById('tpModalTitle').textContent = (type==='purchase'?'Purchase':'Sales') + ' bills';
        document.getElementById('tpModalSub').textContent = '';
        rowsEl.innerHTML = '<tr><td colspan="7" class="rp-empty">Loading…</td></tr>';
        document.getElementById('tpModalQty').textContent='—'; document.getElementById('tpModalAmt').textContent='—';
        fetch(billsUrl + '&type=' + type, { headers:{'X-Requested-With':'XMLHttpRequest'} })
            .then(function(r){ return r.json(); })
            .then(function(res){
                var rows = res.rows||[];
                document.getElementById('tpModalSub').textContent = '· ' + (res.label||'') + ' · ' + rows.length + ' bill(s)';
                if(!rows.length){ rowsEl.innerHTML = '<tr><td colspan="7" class="rp-empty">No bills in this period.</td></tr>'; return; }
                var html='', tq=0, ta=0;
                rows.forEach(function(r,i){
                    tq += Number(r.qty)||0; ta += Number(r.amount)||0;
                    var bill = r.url
                        ? '<a href="'+r.url+'" target="_blank" rel="noopener" class="tp-billlink" title="Open document">'+esc(r.bill_no)+' <i class="ti-new-window"></i></a>'
                        : esc(r.bill_no);
                    html += '<tr><td>'+(i+1)+'</td><td class="tp-src">'+esc(r.src)+'</td><td>'+bill+'</td><td>'+fmtDate(r.bdate)+'</td><td>'+esc(r.party||'—')+'</td><td class="num">'+qty(r.qty)+'</td><td class="num">'+money(r.amount)+'</td></tr>';
                });
                rowsEl.innerHTML = html;
                document.getElementById('tpModalQty').textContent = qty(tq);
                document.getElementById('tpModalAmt').textContent = money(ta);
            })
            .catch(function(){ rowsEl.innerHTML = '<tr><td colspan="7" class="rp-empty">Could not load bills.</td></tr>'; });
    }
    function close(){ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); }
    document.querySelectorAll('.tp-drill').forEach(function(a){ a.addEventListener('click', function(){ open(this.getAttribute('data-type')); }); });
    document.getElementById('tpModalClose').addEventListener('click', close);
    modal.addEventListener('click', function(e){ if(e.target===modal) close(); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') close(); });
})();
</script>
