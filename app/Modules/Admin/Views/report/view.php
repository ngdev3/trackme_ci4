<?php
// ---- Account Statement (all parties, date-range) ----
$users     = (!empty($users) && is_array($users)) ? $users : array();
$stmt_from = isset($stmt_from) ? $stmt_from : '';
$stmt_to   = isset($stmt_to) ? $stmt_to : '';

// KPIs. finalamt is credit-positive (deposit - expenses): >0 Creditor (जमा),
// <0 Debtor (नाम), 0 Nil.
$k_parties = 0; $k_cr = 0.0; $k_dr = 0.0; $k_debtors = 0; $k_creditors = 0;
$g_dr = 0.0; $g_cr = 0.0; $g_entries = 0;
foreach ($users as $u) {
    $k_parties++;
    $f = (float) $u->finalamt;
    if ($f > 0.004) { $k_cr += $f; $k_creditors++; }
    elseif ($f < -0.004) { $k_dr += abs($f); $k_debtors++; }
    $g_dr += (float) $u->expenses;
    $g_cr += (float) $u->deposit;
    $g_entries += (int) (isset($u->entries) ? $u->entries : 0);
}
$k_net = $k_cr - $k_dr;
if (!function_exists('stmt_money')) {
    function stmt_money($v) { return function_exists('acc_money') ? acc_money($v) : number_format((float) $v, 2); }
}

$from_disp = $stmt_from !== '' ? date('d-m-Y', strtotime($stmt_from)) : '';
$to_disp   = $stmt_to !== '' ? date('d-m-Y', strtotime($stmt_to)) : '';
$period_lbl = ($from_disp !== '' && $to_disp !== '') ? ($from_disp . ' → ' . $to_disp) : ('Full Financial Year ' . fy()->FY);

// Financial-year bounds for the "This FY" preset (FY like "2026-2027").
$fyp = explode('-', (string) fy()->FY);
$fy_start_disp = (isset($fyp[0]) && $fyp[0] !== '' ? $fyp[0] : date('Y')) . '-04-01';
$fy_end_disp   = (isset($fyp[1]) && $fyp[1] !== '' ? $fyp[1] : (date('Y') + 1)) . '-03-31';
$fy_start_disp = date('d-m-Y', strtotime($fy_start_disp));
$fy_end_disp   = date('d-m-Y', strtotime($fy_end_disp));
$range_chip = ($from_disp !== '' && $to_disp !== '') ? ($from_disp . ' to ' . $to_disp) : ('Full FY ' . fy()->FY);
$k_nil = $k_parties - $k_debtors - $k_creditors;

// ---- professional report header fields (all live, nothing hardcoded) ----
$rp_fyo      = fy();
$rp_firm     = (is_object($rp_fyo) && !empty($rp_fyo->firm_name)) ? $rp_fyo->firm_name
             : (is_object($rp_fyo) && !empty($rp_fyo->template_name) ? $rp_fyo->template_name : '');
$rp_template = (is_object($rp_fyo) && !empty($rp_fyo->template_name)) ? $rp_fyo->template_name : '';
if ($rp_template !== '' && $rp_template === $rp_firm) { $rp_template = ''; }   // avoid a duplicate row
$rp_mill     = (is_object($rp_fyo) && !empty($rp_fyo->mill_name)) ? $rp_fyo->mill_name : '';
$rp_fy       = (is_object($rp_fyo) && !empty($rp_fyo->FY)) ? $rp_fyo->FY : '';
$rp_user     = currentuserinfo();
$rp_genby    = $rp_user ? trim((isset($rp_user->first_name) ? $rp_user->first_name : '') . ' ' . (isset($rp_user->last_name) ? $rp_user->last_name : '')) : '';

// Detail bar (first-page context under the running header) — mirrors the PDF meta bar.
$print_meta = array(
    'Period'         => $range_chip,
    'Parties'        => $k_parties,
    'Total नाम (Dr)' => '₹ ' . stmt_money($g_dr),
    'Total जमा (Cr)' => '₹ ' . stmt_money($g_cr),
    'Net Position'   => '₹ ' . stmt_money(abs($k_net)) . ' ' . ($k_net > 0 ? 'जमा (Cr)' : ($k_net < 0 ? 'नाम (Dr)' : 'Nil')),
);
$print_meta_html = '<div class="rp-meta">';
foreach ($print_meta as $pl => $pv) {
    $print_meta_html .= '<span><b>' . esc($pl) . ':</b> ' . esc($pv) . '</span>';
}
$print_meta_html .= '</div>';
?>
<style>
.as-wrap { max-width: 1320px; margin: 0 auto; color: #18243c; }
.as-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin: 6px 0 16px; }
.as-title { font-size: 22px; font-weight: 900; }
.as-title small { display: block; font-size: 12px; font-weight: 700; color: #8190a5; margin-top: 3px; }
.as-actions { display: flex; gap: 8px; }
.as-btn { min-height: 40px; display: inline-flex; align-items: center; gap: 7px; padding: 0 15px; border-radius: 9px; font-weight: 800; border: 0; cursor: pointer; text-decoration: none; font-size: 13px; }
.as-btn-primary { color: #fff; background: linear-gradient(135deg,#1769c2,#0c315f); }
.as-btn-ghost { background: #eef3fa; color: #0c315f; border: 1px solid #dce6f2; }
.as-panel { border: 1px solid #dce6f2; border-radius: 14px; background: #fff; box-shadow: 0 12px 30px rgba(24,36,60,.06); }
.as-filterbar { margin-bottom: 12px; }
.as-filter { padding: 12px 14px; display: grid; grid-template-columns: minmax(360px, 2.2fr) 1fr 1.2fr auto; gap: 12px; align-items: end; }
@media (max-width: 960px){ .as-filter { grid-template-columns: 1fr 1fr; } .as-datewrap { grid-column: 1 / -1; } }
.as-filter label { display: block; font-size: 10px; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; color: #64748b; margin-bottom: 6px; }
.as-filter input, .as-filter select { width: 100%; min-height: 40px; border: 1px solid #dce6f2; border-radius: 9px; background: #fbfdff; padding: 0 11px; font-size: 13px; font-weight: 600; }
.as-daterow { display: flex; align-items: center; gap: 7px; }
.as-daterow .asDate { min-width: 0; flex: 1 1 auto; text-align: center; }
.as-daterow .as-to { color: #94a3b8; font-weight: 800; }
.as-nav { flex: 0 0 auto; min-height: 40px; padding: 0 12px; border: 1px solid #dce6f2; border-radius: 9px; background: #eef3fa; color: #0c315f; font-weight: 800; font-size: 12px; cursor: pointer; white-space: nowrap; }
.as-nav:hover { background: #1769c2; color: #fff; border-color: #1769c2; }
.as-range-chip { display: inline-flex; align-items: center; gap: 6px; background: #e8f5ec; color: #1f7a4d; border: 1px solid #bfe3c8; border-radius: 20px; padding: 5px 14px; font-weight: 800; font-size: 13px; }

/* ---- organized controls toolbar ---- */
.st-block { margin-bottom: 12px; }
.st-block-label { font-size: 11px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #8190a5; margin: 0 0 8px 4px; }
.st-datepanel { padding: 14px 16px; }
.st-daterow { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
.st-field { display: flex; flex-direction: column; }
.st-field label { font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 5px; }
.st-field input { min-height: 44px; min-width: 190px; border: 1px solid #dce6f2; border-radius: 10px; background: #fff; padding: 0 13px; font-size: 15px; font-weight: 700; color: #18243c; }
.st-field input:focus { outline: none; border-color: #1769c2; box-shadow: 0 0 0 4px rgba(23,105,194,.12); }
.st-nav, .st-btn { min-height: 44px; padding: 0 18px; border-radius: 10px; font-weight: 800; font-size: 14px; cursor: pointer; border: 0; white-space: nowrap; }
.st-nav { background: #3f3fae; color: #fff; }
.st-nav:hover { background: #32328c; }
.st-btn-primary { background: #6259d6; color: #fff; } .st-btn-primary:hover { background: #514ac0; }
.st-btn-grey { background: #94a3b8; color: #fff; } .st-btn-grey:hover { background: #7d8ca3; }
.st-presets { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
.st-current { display: inline-flex; align-items: center; gap: 6px; background: #e8f5ec; color: #1f7a4d; border: 1px solid #bfe3c8; border-radius: 20px; padding: 6px 15px; font-weight: 800; font-size: 13px; }
.st-divider { color: #cbd5e1; font-weight: 700; }
.st-preset { background: #fff; color: #6259d6; border: 1px solid #d7d5f2; border-radius: 20px; padding: 7px 16px; font-weight: 800; font-size: 13px; cursor: pointer; }
.st-preset:hover { background: #6259d6; color: #fff; border-color: #6259d6; }
.st-hr { border: 0; border-top: 1px solid #e8eef5; margin: 4px 0 14px; }
.st-showrow { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 14px; }
.st-show-label { font-weight: 900; color: #18243c; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; }
.st-tabs { display: inline-flex; background: #eef1f5; border-radius: 10px; padding: 4px; gap: 2px; }
.st-tab { display: inline-flex; align-items: center; gap: 7px; background: transparent; border: 0; border-radius: 8px; padding: 7px 15px; font-weight: 800; font-size: 13px; color: #64748b; cursor: pointer; }
.st-tab b { font-size: 12px; background: rgba(100,116,139,.16); color: #475569; border-radius: 12px; padding: 1px 8px; font-weight: 900; }
.st-tab.active { background: #6259d6; color: #fff; box-shadow: 0 4px 10px rgba(98,89,214,.3); }
.st-tab.active b { background: rgba(255,255,255,.25); color: #fff; }
.st-note { color: #94a3b8; font-size: 12px; font-style: italic; }
.st-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 14px; }
.st-exports { display: flex; gap: 10px; flex-wrap: wrap; }
.st-ex { display: inline-flex; align-items: center; gap: 6px; border: 0; border-radius: 10px; padding: 10px 18px; font-weight: 800; font-size: 14px; color: #fff; cursor: pointer; }
.st-ex-csv { background: #22a559; } .st-ex-csv:hover { background: #1c8f4c; }
.st-ex-excel { background: #157347; } .st-ex-excel:hover { background: #115e3a; }
.st-ex-pdf { background: #e5484d; } .st-ex-pdf:hover { background: #cf3b40; }
.st-ex-print { background: #6259d6; } .st-ex-print:hover { background: #514ac0; }
.st-dtctrls { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
.st-len, .st-srch { font-weight: 700; color: #334155; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; }
.st-len select { min-height: 38px; border: 1px solid #dce6f2; border-radius: 8px; padding: 0 8px; font-weight: 700; background: #fff; }
.st-srch .as-search-wrap { position: relative; }
.st-srch input { min-height: 38px; min-width: 220px; border: 1px solid #dce6f2; border-radius: 8px; padding: 0 30px 0 12px; font-weight: 600; }
@media (max-width: 780px){ .st-toolbar { flex-direction: column; align-items: stretch; } }
.as-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 16px; }
@media (max-width: 900px){ .as-kpis { grid-template-columns: 1fr 1fr; } }
.as-kpi { position: relative; overflow: hidden; border: 1px solid #dce6f2; border-radius: 12px; background: #fff; padding: 15px 17px; box-shadow: 0 8px 20px rgba(24,36,60,.05); }
.as-kpi:before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: #94a3b8; }
.as-kpi.k-par:before { background: #1769c2; } .as-kpi.k-dr:before { background: #c62828; } .as-kpi.k-cr:before { background: #1f7a4d; } .as-kpi.k-net:before { background: linear-gradient(#f59e0b,#d97706); }
.as-kpi .k-l { font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; color: #718096; }
.as-kpi .k-v { margin-top: 6px; font-size: 21px; font-weight: 900; font-variant-numeric: tabular-nums; }
.as-kpi .k-s { margin-top: 2px; font-size: 11px; font-weight: 700; color: #94a3b8; }
.as-kpi.k-dr .k-v { color: #c62828; } .as-kpi.k-cr .k-v { color: #1f7a4d; }
.as-charts { display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 14px; margin-bottom: 16px; }
.as-charts2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; }
@media (max-width: 1000px){ .as-charts, .as-charts2 { grid-template-columns: 1fr; } }
.as-cc { border: 1px solid #dce6f2; border-radius: 12px; background: #fff; padding: 14px 16px; box-shadow: 0 8px 20px rgba(24,36,60,.05); position: relative; }
.as-cc h6 { margin: 0 0 10px; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; color: #718096; }
.as-canvas { position: relative; height: 220px; }
.as-canvas.tall { height: 300px; }
.as-cc-note { position: absolute; inset: 40px 16px 16px; display: flex; align-items: center; justify-content: center; text-align: center; color: #a8b4c4; font-size: 12px; font-weight: 700; }
.as-table-wrap { padding: 6px 14px 16px; }
table.as-table { width: 100%; border-collapse: separate; border-spacing: 0; margin: 0; }
table.as-table thead th { background: linear-gradient(180deg,#eaf3ff,#fff); color: #516174; font-size: 10.5px; font-weight: 900; text-transform: uppercase; padding: 6px 8px; white-space: nowrap; border: 0 !important; }
table.as-table tbody td { border-top: 1px solid #eef2f7 !important; padding: 4px 8px !important; font-size: 12px; line-height: 1.3; vertical-align: middle; }
table.as-table tbody tr:hover { background: rgba(23,105,194,.06); }
table.as-table .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
table.as-table tfoot td { border-top: 2px solid #cfdcec !important; padding: 7px 8px !important; font-weight: 900; background: #f7fafd; font-size: 12px; }
.as-table-wrap { padding: 4px 12px 12px; }
.as-dr { color: #c62828 !important; font-weight: 800; } .as-cr { color: #1f7a4d !important; font-weight: 800; } .as-nil { color: #7a8699; font-weight: 800; }
.as-ent { display: inline-block; min-width: 26px; padding: 2px 9px; border-radius: 20px; background: #eef5ff; color: #1769c2; font-weight: 800; font-size: 12px; }
.as-pill { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 800; }
.as-pill.dr { background: #fdecec; color: #c62828; } .as-pill.cr { background: #e8f5ec; color: #1f7a4d; } .as-pill.nil { background: #eef1f5; color: #7a8699; }
.as-pill.grp { background: #eef5ff; color: #1769c2; }
.as-led { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 7px; background: #eef3fa; color: #0c315f; border: 1px solid #dce6f2; font-size: 12px; font-weight: 800; text-decoration: none; }
.as-led:hover { background: #1769c2; color: #fff; }
.as-empty { padding: 40px; text-align: center; color: #8190a5; font-weight: 700; }
table.as-table tbody tr.as-clickrow { cursor: pointer; }
.as-search-wrap { position: relative; }
.as-search-wrap .as-clear { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); border: 0; background: #eef1f5; color: #64748b; width: 22px; height: 22px; border-radius: 50%; cursor: pointer; font-weight: 900; line-height: 1; display: none; }

/* ---- row-detail modal ---- */
.amd-overlay { position: fixed; inset: 0; z-index: 10000; display: none; align-items: flex-start; justify-content: center; background: rgba(8,14,24,.55); backdrop-filter: blur(2px); padding: 30px 14px; overflow: auto; }
.amd-overlay.open { display: flex; }
.amd-modal { width: 100%; max-width: 1040px; background: #fff; border-radius: 14px; box-shadow: 0 30px 80px rgba(8,14,24,.4); overflow: hidden; }
.amd-bar { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; background: linear-gradient(135deg,#1769c2,#0c315f); color: #fff; }
.amd-bar h5 { margin: 0; font-size: 15px; font-weight: 800; }
.amd-bar .amd-x { background: rgba(255,255,255,.2); border: 0; color: #fff; width: 30px; height: 30px; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 900; }
.amd-actions { display: flex; align-items: center; gap: 7px; }
.amd-ex { display: inline-flex; align-items: center; gap: 5px; border: 0; border-radius: 8px; padding: 6px 12px; font-weight: 800; font-size: 12px; color: #fff; cursor: pointer; background: rgba(255,255,255,.18); }
.amd-ex:hover { background: rgba(255,255,255,.32); }
@media (max-width: 620px){ .amd-ex span, .amd-ex { font-size: 11px; padding: 6px 9px; } }
.amd-body { padding: 16px 18px; max-height: 74vh; overflow: auto; }
.amd-load { padding: 40px; text-align: center; color: #8190a5; font-weight: 700; }
.amd-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
.amd-name { font-size: 18px; font-weight: 900; color: #18243c; }
.amd-sub { font-size: 12px; color: #8190a5; font-weight: 700; margin-top: 2px; }
.amd-close-box { text-align: right; border-radius: 10px; padding: 8px 14px; border: 1px solid #dce6f2; background: #fbfdff; }
.amd-close-box span { display: block; font-size: 10px; font-weight: 900; text-transform: uppercase; color: #718096; }
.amd-close-box b { display: block; font-size: 18px; font-variant-numeric: tabular-nums; }
.amd-close-box small { font-size: 11px; color: #94a3b8; font-weight: 700; }
.amd-cr b { color: #1f7a4d; } .amd-dr b { color: #c62828; }
.amd-sec-h { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; color: #1769c2; margin: 14px 0 8px; }
.amd-sec-h span { font-size: 10px; background: #eef5ff; color: #1769c2; border-radius: 20px; padding: 2px 9px; }
.amd-table-wrap { border: 1px solid #eef2f7; border-radius: 10px; overflow: auto; }
table.amd-table { width: 100%; border-collapse: separate; border-spacing: 0; margin: 0; }
table.amd-table thead th { background: linear-gradient(180deg,#eaf3ff,#fff); color: #516174; font-size: 10.5px; font-weight: 900; text-transform: uppercase; padding: 9px 8px; white-space: nowrap; }
table.amd-table tbody td { border-top: 1px solid #eef2f7; padding: 8px; font-size: 12.5px; vertical-align: top; }
table.amd-table .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
table.amd-table tfoot td { border-top: 2px solid #cfdcec; padding: 9px 8px; font-weight: 900; background: #f7fafd; }
.amd-table .amd-dr, table.amd-table td.amd-dr { color: #c62828; } .amd-table .amd-cr, table.amd-table td.amd-cr { color: #1f7a4d; }
.amd-bal-cr { color: #1f7a4d; } .amd-bal-dr { color: #c62828; }
.amd-open td { background: #fbfdff; color: #516174; }
.amd-rem { display: block; font-size: 11px; color: #94a3b8; margin-top: 2px; }
.amd-src { font-size: 9.5px; font-weight: 900; padding: 1px 6px; border-radius: 12px; margin-left: 4px; }
.amd-src-web { background: #e6f0fb; color: #1769c2; } .amd-src-app { background: #e8f7ee; color: #1f9d70; } .amd-src-kv { background: #fdf0e6; color: #d97706; }
.amd-nowrap { white-space: nowrap; }
.amd-empty { padding: 30px; text-align: center; color: #8190a5; font-weight: 700; }
</style>

<main class="main-content bgc-grey-100"><div id="mainContent"><div class="container-fluid"><div class="as-wrap">
    <div class="as-head">
        <div class="as-title">Account Statement <small>All parties &middot; <?= esc($period_lbl) ?> &middot; <?= esc(fy()->template_name) ?></small></div>
        <div class="as-actions"></div>
    </div>

    <!-- KPIs -->
    <div class="as-kpis">
        <div class="as-kpi k-par"><div class="k-l">Parties</div><div class="k-v"><?= (int) $k_parties ?></div><div class="k-s"><?= (int) $k_debtors ?> नाम &middot; <?= (int) $k_creditors ?> जमा</div></div>
        <div class="as-kpi k-dr"><div class="k-l">कुल नाम (Dr)</div><div class="k-v">&#8377; <?= stmt_money($k_dr) ?></div><div class="k-s">They owe us (Dr)</div></div>
        <div class="as-kpi k-cr"><div class="k-l">कुल जमा (Cr)</div><div class="k-v">&#8377; <?= stmt_money($k_cr) ?></div><div class="k-s">We owe them (Cr)</div></div>
        <div class="as-kpi k-net"><div class="k-l">Net Position</div><div class="k-v">&#8377; <?= stmt_money(abs($k_net)) ?> <?= $k_net > 0 ? 'Cr' : ($k_net < 0 ? 'Dr' : '') ?></div><div class="k-s"><?= $k_net > 0 ? 'शुद्ध जमा' : ($k_net < 0 ? 'शुद्ध नाम' : 'Balanced') ?></div></div>
    </div>

    <?php
    // ---------- chart datasets (built from $users) ----------
    $top_debtors = array(); $top_creditors = array();
    $grp = array(); $has_groups = false;
    if (!empty($users)) {
        $ds = $users;
        usort($ds, function ($a, $b) { $x=(float)$a->finalamt; $y=(float)$b->finalamt; return ($x==$y)?0:(($x<$y)?-1:1); });
        foreach ($ds as $u) { $f=(float)$u->finalamt; if ($f < -0.004) { $top_debtors[] = array('n'=>$u->name,'v'=>abs($f)); } if (count($top_debtors)>=10) break; }
        $cs = $users;
        usort($cs, function ($a, $b) { $x=(float)$a->finalamt; $y=(float)$b->finalamt; return ($x==$y)?0:(($x>$y)?-1:1); });
        foreach ($cs as $u) { $f=(float)$u->finalamt; if ($f > 0.004) { $top_creditors[] = array('n'=>$u->name,'v'=>$f); } if (count($top_creditors)>=10) break; }
        foreach ($users as $u) {
            $g = (isset($u->group_name) && $u->group_name !== '') ? $u->group_name : 'Unclassified';
            if (isset($u->group_name) && $u->group_name !== '') { $has_groups = true; }
            $f = (float)$u->finalamt;
            if (!isset($grp[$g])) { $grp[$g] = array('dr'=>0.0,'cr'=>0.0); }
            if ($f < 0) { $grp[$g]['dr'] += abs($f); } elseif ($f > 0) { $grp[$g]['cr'] += $f; }
        }
    }
    ?>
    <?php if (!empty($users)): ?>
    <div class="as-charts">
        <div class="as-cc"><h6>नाम vs जमा (₹)</h6><div class="as-canvas"><canvas id="chDoughnut"></canvas></div></div>
        <div class="as-cc"><h6>Party Status (count)</h6><div class="as-canvas"><canvas id="chStatus"></canvas></div></div>
        <div class="as-cc as-cc-wide"><h6>Net Exposure by Ledger Group</h6><div class="as-canvas"><canvas id="chGroups"></canvas></div><?php if (!$has_groups): ?><div class="as-cc-note">Run the accounting backfill to see per-group exposure.</div><?php endif; ?></div>
    </div>
    <div class="as-charts2">
        <div class="as-cc"><h6>Top 10 नाम (Dr)</h6><div class="as-canvas tall"><canvas id="chDebtors"></canvas></div></div>
        <div class="as-cc"><h6>Top 10 जमा (Cr)</h6><div class="as-canvas tall"><canvas id="chCreditors"></canvas></div></div>
    </div>
    <?php endif; ?>

    <!-- ============ controls toolbar (above the table) ============ -->
    <!-- 1) date range -->
    <div class="st-block">
        <div class="st-block-label">Date Range</div>
        <div class="as-panel st-datepanel">
            <?php echo form_open('', array('id' => 'asFilterForm', 'class' => 'st-daterow')); ?>
                <button type="button" class="st-nav" id="asPrev" title="Previous period">&laquo; Prev</button>
                <div class="st-field"><label>Start Date</label><input type="text" name="start_date" id="asStart" class="asDate" placeholder="dd-mm-yyyy" value="<?= esc($from_disp) ?>" autocomplete="off"></div>
                <div class="st-field"><label>End Date</label><input type="text" name="end_date" id="asEnd" class="asDate" placeholder="dd-mm-yyyy" value="<?= esc($to_disp) ?>" autocomplete="off"></div>
                <button type="button" class="st-nav" id="asNext" title="Next period">Next &raquo;</button>
                <button type="submit" class="st-btn st-btn-primary">Search</button>
                <button type="button" class="st-btn st-btn-grey" id="asReset">Reset</button>
            <?php echo form_close(); ?>
        </div>
    </div>

    <!-- 2) presets -->
    <div class="st-presets">
        <span class="st-current"><i class="ti-calendar"></i> <?= esc($range_chip) ?></span>
        <span class="st-divider">|</span>
        <button type="button" class="st-preset" data-preset="fy">This FY</button>
        <button type="button" class="st-preset" data-preset="month">This Month</button>
        <button type="button" class="st-preset" data-preset="quarter">This Quarter</button>
        <button type="button" class="st-preset" data-preset="today">Today</button>
    </div>

    <hr class="st-hr">

    <!-- 3) status tabs (with counts) -->
    <div class="st-showrow">
        <span class="st-show-label"><i class="ti-filter"></i> Show:</span>
        <div class="st-tabs">
            <button type="button" class="st-tab active" data-st=""><span>All</span> <b><?= (int) $k_parties ?></b></button>
            <button type="button" class="st-tab" data-st="dr"><span>नाम</span> <b><?= (int) $k_debtors ?></b></button>
            <button type="button" class="st-tab" data-st="cr"><span>जमा</span> <b><?= (int) $k_creditors ?></b></button>
            <button type="button" class="st-tab" data-st="nil"><span>Nil</span> <b><?= (int) $k_nil ?></b></button>
        </div>
        <span class="st-note">Exports (PDF / Print / Excel / CSV) include only the filtered rows.</span>
    </div>

    <!-- 4) export + datatable controls -->
    <div class="st-toolbar">
        <div class="st-exports">
            <button type="button" class="st-ex st-ex-csv" onclick="asExport('csv')"><i class="ti-download"></i> CSV</button>
            <button type="button" class="st-ex st-ex-excel" onclick="asExport('excel')"><i class="ti-download"></i> Excel</button>
            <button type="button" class="st-ex st-ex-pdf" onclick="asExport('pdf')"><i class="ti-download"></i> PDF</button>
            <button type="button" class="st-ex st-ex-print" onclick="asPrint()"><i class="ti-printer"></i> Print</button>
        </div>
        <div class="st-dtctrls">
            <label class="st-len">Show
                <select id="asLen"><option>25</option><option selected>50</option><option>100</option><option value="-1">All</option></select>
                entries</label>
            <label class="st-srch">Search:
                <span class="as-search-wrap"><input type="text" id="asSearch" autocomplete="off"><button type="button" class="as-clear" id="asSearchClear" title="Clear">&times;</button></span>
            </label>
        </div>
    </div>

    <!-- table -->
    <div class="as-panel as-table-wrap">
        <?php if (empty($users)): ?>
            <div class="as-empty">No account activity for this period.</div>
        <?php else: ?>
        <table class="table as-table" id="asTable">
            <thead>
                <tr>
                    <th>S.No</th><th>ID</th><th>Account Name</th>
                    <th class="num">नाम (Dr)</th><th class="num">जमा (Cr)</th><th class="num">Balance</th><th>Status</th><th class="num">Entries</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 0; foreach ($users as $u):
                $i++;
                $f = (float) $u->finalamt;
                if ($f > 0.004) { $st = 'cr'; $st_lbl = 'जमा'; $st_hi = 'Cr'; }
                elseif ($f < -0.004) { $st = 'dr'; $st_lbl = 'नाम'; $st_hi = 'Dr'; }
                else { $st = 'nil'; $st_lbl = 'Nil'; $st_hi = 'कुछ नहीं'; }
                $entries = isset($u->entries) ? (int) $u->entries : 0;
            ?>
                <tr data-status="<?= $st ?>" data-acc="<?= (int) $u->account_no ?>" class="as-clickrow" title="Click to view नाम / जमा detail">
                    <td><?= $i ?></td>
                    <td><?= (int) $u->account_no ?></td>
                    <td><b><?= esc($u->name) ?></b></td>
                    <td class="num as-dr"><?= round($u->expenses, 2) > 0 ? '&#8377; ' . stmt_money($u->expenses) : '—' ?></td>
                    <td class="num as-cr"><?= round($u->deposit, 2) > 0 ? '&#8377; ' . stmt_money($u->deposit) : '—' ?></td>
                    <td class="num as-<?= $st ?>" data-order="<?= $f ?>"><b>&#8377; <?= stmt_money(abs($f)) ?> <?= $st === 'nil' ? '' : strtoupper($st) ?></b></td>
                    <td><span class="as-pill <?= $st ?>"><?= $st_lbl ?> <?= $st === 'nil' ? '' : '(' . $st_hi . ')' ?></span></td>
                    <td class="num"><span class="as-ent"><?= $entries ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="num">TOTAL (<?= (int) $k_parties ?> parties)</td>
                    <td class="num as-dr">&#8377; <?= stmt_money($g_dr) ?></td>
                    <td class="num as-cr">&#8377; <?= stmt_money($g_cr) ?></td>
                    <td class="num">&#8377; <?= stmt_money(abs($k_net)) ?> <?= $k_net > 0 ? 'Cr' : ($k_net < 0 ? 'Dr' : '') ?></td>
                    <td></td>
                    <td class="num"><?= (int) $g_entries ?></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>

    <!-- row-detail modal -->
    <div class="amd-overlay" id="amdOverlay">
        <div class="amd-modal">
            <div class="amd-bar">
                <h5><i class="ti-receipt"></i> Party Detail — नाम / जमा</h5>
                <div class="amd-actions">
                    <button type="button" class="amd-ex amd-ex-csv" onclick="asModalExport('csv')"><i class="ti-download"></i> CSV</button>
                    <button type="button" class="amd-ex amd-ex-excel" onclick="asModalExport('excel')"><i class="ti-download"></i> Excel</button>
                    <button type="button" class="amd-ex amd-ex-pdf" onclick="asModalExport('pdf')"><i class="ti-download"></i> PDF</button>
                    <button type="button" class="amd-ex amd-ex-print" onclick="asModalPrint()"><i class="ti-printer"></i> Print</button>
                    <button type="button" class="amd-x" id="amdClose">&times;</button>
                </div>
            </div>
            <div class="amd-body" id="amdBody"><div class="amd-load">Loading…</div></div>
        </div>
    </div>
</div></div></div></main>

<script src="https://momentjs.com/downloads/moment.js"></script>
<script type="text/javascript">
var asDT = null, asStatus = '';
var AS_BASE = "<?php echo base_url(); ?>";
var AS_FY_START = "<?php echo esc($fy_start_disp); ?>";
var AS_FY_END = "<?php echo esc($fy_end_disp); ?>";
// Live report-header identity (firm/mill/template/FY/user) — nothing hardcoded.
var AS_RP = {
    firm:     <?php echo json_encode($rp_firm); ?>,
    mill:     <?php echo json_encode($rp_mill); ?>,
    template: <?php echo json_encode($rp_template); ?>,
    fy:       <?php echo json_encode($rp_fy); ?>,
    genby:    <?php echo json_encode($rp_genby); ?>
};
var AS_STMT_META = <?php echo json_encode($print_meta_html); ?>;   // statement detail bar (first page)

$(function () {
    // datepickers (dd-mm-yy, matching the controller's strtotime parsing)
    if ($.fn.datepicker) {
        $('.asDate').datepicker({ dateFormat: 'dd-mm-yy', changeMonth: true, changeYear: true, yearRange: '2015:+1' });
    }
    // Prev / Next shift the window by its own length (single day → ±1 day).
    $('#asPrev').on('click', function () { asShiftRange(-1); });
    $('#asNext').on('click', function () { asShiftRange(1); });
    $('#asReset').on('click', function () { $('#asStart,#asEnd').val(''); $('#asFilterForm').submit(); });
    $('.st-preset').on('click', function () { asPreset($(this).data('preset')); });

    if ($.fn.DataTable && document.getElementById('asTable')) {
        if ($.fn.DataTable.isDataTable('#asTable')) { $('#asTable').DataTable().destroy(); }
        asDT = $('#asTable').DataTable({
            "processing": true,
            "dom": 'rtip',                 // default length/filter removed — custom controls drive it
            "pageLength": 50,
            "order": [[5, "desc"]]
        });

        // status tabs (नाम / जमा / Nil / All)
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (!asStatus) return true;
            return $(asDT.row(dataIndex).node()).data('status') === asStatus;
        });
        $('.st-tab').on('click', function () {
            $('.st-tab').removeClass('active'); $(this).addClass('active');
            asStatus = $(this).data('st') || '';
            asDT.draw();
        });

        // custom "Show N entries"
        $('#asLen').on('change', function () { asDT.page.len(parseInt(this.value, 10)).draw(); });

        // custom search
        $('#asSearch').on('keyup', function () {
            asDT.search(this.value).draw();
            $('#asSearchClear').css('display', this.value ? 'block' : 'none');
        });
        $('#asSearchClear').on('click', function () {
            $('#asSearch').val('').focus(); asDT.search('').draw(); $(this).hide();
        });
    }

    // ---- row-click detail modal ----
    $(document).on('click', 'tr.as-clickrow', function () {
        var acc = $(this).data('acc');
        if (!acc) return;
        asOpenModal(acc);
    });
    $('#amdClose, #amdOverlay').on('click', function (e) {
        if (e.target === this) asCloseModal();
    });
    $(document).on('keydown', function (e) { if (e.key === 'Escape') asCloseModal(); });
});

var asModalAcc = 0;
function asOpenModal(acc) {
    asModalAcc = acc;
    $('#amdBody').html('<div class="amd-load">Loading party detail…</div>');
    $('#amdOverlay').addClass('open');
    document.body.style.overflow = 'hidden';
    $.get("<?php echo base_url('admin/report/account_ledger_modal'); ?>/" + acc, function (html) {
        $('#amdBody').html(html);
    }).fail(function () {
        $('#amdBody').html('<div class="amd-empty">Could not load the party detail. Please try again.</div>');
    });
}
// Export / print the single party's नाम/जमा detail shown in the modal.
function asModalExport(fmt) {
    if (!asModalAcc) return;
    window.location.href = AS_BASE + 'admin/report/account_ledger_export/' + asModalAcc + '/' + fmt;
}
// ---- shared professional A4 print document (used by table view + modal) ----
function asEsc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
// Running report header (repeats on every printed page via <thead> display group).
function asPrintHeader(reportTitle, periodText) {
    var L = '<div class="rp-firm">' + asEsc(AS_RP.firm) + '</div>';
    if (AS_RP.mill)     { L += '<div class="rp-mill">Mill: ' + asEsc(AS_RP.mill) + '</div>'; }
    if (AS_RP.template) { L += '<div class="rp-tmpl">' + asEsc(AS_RP.template) + '</div>'; }
    var R = '<div class="rp-title">' + asEsc(reportTitle) + '</div>';
    if (AS_RP.fy)   { R += '<div class="rp-fy">Financial Year: ' + asEsc(AS_RP.fy) + '</div>'; }
    if (periodText) { R += '<div class="rp-period">' + asEsc(periodText) + '</div>'; }
    return '<div class="rp-hdr"><table class="rp-hdr-t"><tr>' +
        '<td class="rp-hdr-l">' + L + '</td><td class="rp-hdr-r">' + R + '</td>' +
        '</tr></table></div>';
}
// Running report footer (repeats on every printed page via <tfoot> display group).
function asPrintFooter() {
    var brand = 'Generated by Trackme ERP' + (AS_RP.genby ? ' · ' + asEsc(AS_RP.genby) : '');
    var now = moment().format('DD-MM-YYYY hh:mm A');
    return '<div class="rp-ftr"><table class="rp-ftr-t"><tr>' +
        '<td>' + brand + '</td>' +
        '<td class="c">Generated: ' + now + '</td>' +
        '<td class="r">Trackme ERP</td>' +
        '</tr></table></div>';
}
// Assemble the full print document. `landscape` picks A4 orientation; the
// header/footer repeat on every page, table headers repeat, rows never split.
function asPrintDoc(opts) {
    // Business rule: reports are ALWAYS A4 portrait — never landscape.
    var css =
        '*{box-sizing:border-box}' +
        '@page{size:A4 portrait;margin:15mm 10mm}' +
        'body{font-family:Arial,"Segoe UI",sans-serif;margin:0;color:#1a2332;-webkit-print-color-adjust:exact;print-color-adjust:exact}' +
        // page wrapper: thead repeats header, tfoot repeats footer
        'table.rp-page{width:100%;border-collapse:collapse}' +
        'table.rp-page > thead{display:table-header-group}table.rp-page > tfoot{display:table-footer-group}' +
        'table.rp-page > thead > tr > td,table.rp-page > tfoot > tr > td,table.rp-page > tbody > tr > td{border:0;padding:0}' +
        // running header
        '.rp-hdr{border-bottom:2px solid #0c315f;padding-bottom:5px;margin-bottom:4px}' +
        '.rp-hdr-t{width:100%;border-collapse:collapse}.rp-hdr-t td{border:0;padding:0;vertical-align:bottom}' +
        '.rp-hdr-r{text-align:right}' +
        '.rp-firm{font-size:19px;font-weight:bold;color:#0c315f;letter-spacing:.3px}' +
        '.rp-mill{font-size:11px;color:#33415a;margin-top:1px}.rp-tmpl{font-size:11px;color:#56657a;margin-top:1px}' +
        '.rp-title{font-size:13px;font-weight:bold;color:#33415a}' +
        '.rp-fy{font-size:11px;color:#56657a;margin-top:1px}.rp-period{font-size:11px;color:#56657a;margin-top:1px}' +
        // running footer
        '.rp-ftr{border-top:.8px solid #cdd7e3;padding-top:3px;margin-top:4px}' +
        '.rp-ftr-t{width:100%;border-collapse:collapse;font-size:9px;color:#56657a}' +
        '.rp-ftr-t td{border:0;padding:0}.rp-ftr-t .c{text-align:center}.rp-ftr-t .r{text-align:right}' +
        // first-page detail bar
        '.rp-meta{background:#eef3fa;border:1px solid #cdd7e3;border-radius:5px;padding:7px 10px;font-size:11px;line-height:1.9;color:#33415a;margin:10px 0}' +
        '.rp-meta span{margin-right:14px}.rp-meta b{color:#0c315f}' +
        // data tables (compact so wide tables fit the A4 portrait width)
        '.rp-body table{width:100%;border-collapse:collapse;font-size:9.5px;margin-bottom:10px;table-layout:fixed;word-break:break-word}' +
        '.rp-body thead{display:table-header-group}' +
        '.rp-body th,.rp-body td{border:1px solid #cdd7e3;padding:4px 5px;vertical-align:middle}' +
        '.rp-body thead th{background:#0c315f;color:#fff;text-align:left;font-size:9px;text-transform:uppercase;letter-spacing:.02em}' +
        '.rp-body tbody tr{page-break-inside:avoid}' +
        '.rp-body tbody tr:nth-child(even){background:#f6fafd}' +
        '.rp-body tfoot td{font-weight:bold;background:#eaf1f8;border-top:2px solid #0c315f}' +
        '.num{text-align:right;white-space:nowrap}' +
        // statement colour cues
        '.as-dr{color:#c62828;font-weight:bold}.as-cr{color:#1f7a4d;font-weight:bold}.as-nil{color:#7a8699}' +
        '.as-pill{display:inline-block;padding:2px 9px;border-radius:12px;font-size:10px;font-weight:bold}' +
        '.as-pill.dr{background:#fdecec;color:#c62828}.as-pill.cr{background:#e8f5ec;color:#1f7a4d}.as-pill.nil{background:#eef1f5;color:#7a8699}' +
        '.as-ent{display:inline-block;background:#eef5ff;color:#1769c2;padding:1px 9px;border-radius:12px;font-weight:bold}' +
        '.as-table td:nth-child(1),.as-table th:nth-child(1),.as-table td:nth-child(2),.as-table th:nth-child(2),.as-table td:nth-child(7),.as-table th:nth-child(7),.as-table td:nth-child(8),.as-table th:nth-child(8){text-align:center}' +
        // modal ledger colour cues
        '.amd-dr{color:#c62828;font-weight:bold}.amd-cr{color:#1f7a4d;font-weight:bold}' +
        '.amd-bal-cr{color:#1f7a4d;font-weight:bold}.amd-bal-dr{color:#c62828;font-weight:bold}' +
        '.amd-open td{background:#f2f6fb;color:#556}.amd-src,.amd-rem{font-size:10px;color:#8190a5}' +
        '.amd-sec-h{font-size:12px;font-weight:bold;color:#0c315f;text-transform:uppercase;margin:12px 0 5px}' +
        '.amd-head{margin:8px 0 6px}.amd-name{font-size:15px;font-weight:bold;color:#0c315f}.amd-sub{font-size:11px;color:#5a6b80}' +
        '.amd-close-box{border:1px solid #cdd7e3;padding:5px 11px;border-radius:8px;text-align:right;display:inline-block;margin-top:6px}.amd-close-box b{font-size:15px}';
    return '<html><head><meta charset="utf-8"><title>' + asEsc(opts.title) + '</title><style>' + css + '</style></head><body>' +
        '<table class="rp-page">' +
        '<thead><tr><td>' + asPrintHeader(opts.title, opts.period) + '</td></tr></thead>' +
        '<tfoot><tr><td>' + asPrintFooter() + '</td></tr></tfoot>' +
        '<tbody><tr><td>' + (opts.meta || '') + '<div class="rp-body">' + opts.body + '</div></td></tr></tbody>' +
        '</table></body></html>';
}
function asPrintWindow(html) {
    var win = window.open('');
    win.document.write(html);
    win.document.close(); win.focus(); win.print(); win.close();
}

function asModalPrint() {
    var content = document.getElementById('amdBody');
    if (!content) return;
    // Party name / period for the report title come from the loaded modal.
    var name = (content.querySelector('.amd-name') || {}).textContent || 'Party';
    var sub  = (content.querySelector('.amd-sub')  || {}).textContent || '';
    var period = sub.replace(/^\s*Statement detail\s*[^0-9A-Za-z]*\s*/i, '').trim();   // strip the "Statement detail ·" label
    asPrintWindow(asPrintDoc({
        title: 'Account Ledger Detail — ' + name,
        period: period,
        landscape: false,          // ledger detail fits A4 portrait
        body: content.innerHTML
    }));
}
function asCloseModal() {
    $('#amdOverlay').removeClass('open');
    document.body.style.overflow = '';
}

function asFmt(d) { function p(n){ return (n<10?'0':'')+n; } return p(d.getDate())+'-'+p(d.getMonth()+1)+'-'+d.getFullYear(); }
function asSetRange(from, to) { $('#asStart').val(from); $('#asEnd').val(to); $('#asFilterForm').submit(); }
function asPreset(kind) {
    var now = new Date(), y = now.getFullYear(), m = now.getMonth();
    if (kind === 'today') { var t = asFmt(now); asSetRange(t, t); return; }
    if (kind === 'month') { asSetRange(asFmt(new Date(y, m, 1)), asFmt(new Date(y, m + 1, 0))); return; }
    if (kind === 'quarter') {
        // Financial-year quarters: Apr–Jun, Jul–Sep, Oct–Dec, Jan–Mar.
        var qs = (m >= 3 && m <= 5) ? 3 : (m >= 6 && m <= 8) ? 6 : (m >= 9 && m <= 11) ? 9 : 0;
        asSetRange(asFmt(new Date(y, qs, 1)), asFmt(new Date(y, qs + 3, 0))); return;
    }
    if (kind === 'fy') { asSetRange(AS_FY_START, AS_FY_END); return; }
}
// Account IDs of the currently-filtered rows (status tab + search). Empty on any error → export all.
function asFilteredIds() {
    var ids = [];
    try {
        if (asDT) { asDT.rows({ search: 'applied' }).every(function () { var a = $(this.node()).data('acc'); if (a) ids.push(a); }); }
    } catch (e) { ids = []; }
    return ids;
}
// Export the currently-filtered rows with Hindi support (server mPDF/xls/csv). Simple GET download.
function asExport(fmt) {
    var ids = asFilteredIds();
    var url = AS_BASE + 'admin/report/byaccount_name_export/' + fmt;
    if (ids.length && ids.length <= 800) { url += '?ids=' + ids.join(','); }
    window.location.href = url;
}

function asParseDMY(s) {
    s = $.trim(s || '');
    var m = s.match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);
    if (!m) return null;
    var d = new Date(+m[3], +m[2] - 1, +m[1]);
    return isNaN(d.getTime()) ? null : d;
}
function asFmtDMY(d) {
    function p(n) { return (n < 10 ? '0' : '') + n; }
    return p(d.getDate()) + '-' + p(d.getMonth() + 1) + '-' + d.getFullYear();
}
function asShiftRange(dir) {
    var s = asParseDMY($('#asStart').val()), e = asParseDMY($('#asEnd').val());
    if (!s || !e) { s = new Date(); e = new Date(); }         // default: today
    if (e < s) { var t = s; s = e; e = t; }
    var len = Math.round((e - s) / 86400000) + 1;             // inclusive day count
    if (len < 1) len = 1;
    s.setDate(s.getDate() + dir * len);
    e.setDate(e.getDate() + dir * len);
    $('#asStart').val(asFmtDMY(s));
    $('#asEnd').val(asFmtDMY(e));
    $('#asFilterForm').submit();
}

function asPrint() {
    var tblEl = document.getElementById('asTable');
    if (!tblEl) { window.print(); return; }
    // Build a table of ALL currently-filtered rows (not just the visible page).
    var body = '';
    try {
        if (asDT) { asDT.rows({ search: 'applied' }).every(function () { body += '<tr>' + $(this.node()).html() + '</tr>'; }); }
    } catch (e) { body = ''; }
    if (!body) { body = $('#asTable tbody').html() || ''; }
    var thead = $('#asTable thead').html();
    var tfoot = $('#asTable tfoot').html();
    var tableHtml = '<table class="as-table"><thead>' + thead + '</thead><tbody>' + body + '</tbody><tfoot>' + tfoot + '</tfoot></table>';
    asPrintWindow(asPrintDoc({
        title: 'Account Statement',
        period: '',                // period already shown in the detail bar below
        landscape: false,          // business rule: always A4 portrait
        meta: AS_STMT_META,
        body: tableHtml
    }));
}
</script>

<?php if (!empty($users)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script type="text/javascript">
var AS_CHART = {
    dr: <?= json_encode(round($k_dr, 2)) ?>,
    cr: <?= json_encode(round($k_cr, 2)) ?>,
    deb: <?= (int) $k_debtors ?>,
    cred: <?= (int) $k_creditors ?>,
    nil: <?= (int) ($k_parties - $k_debtors - $k_creditors) ?>,
    topDeb: <?= json_encode($top_debtors) ?>,
    topCred: <?= json_encode($top_creditors) ?>,
    grp: <?= json_encode($grp) ?>
};
(function () {
    if (!window.Chart) return;
    Chart.defaults.font.family = "'Segoe UI', Arial, sans-serif";
    var RED = '#e5484d', GREEN = '#1f9d70', GREY = '#94a3b8', BLUE = '#1769c2';
    function inr(v){ v = Math.abs(+v||0); if(v>=1e7)return '₹'+(v/1e7).toFixed(2)+' Cr'; if(v>=1e5)return '₹'+(v/1e5).toFixed(2)+' L'; if(v>=1e3)return '₹'+(v/1e3).toFixed(1)+'k'; return '₹'+Math.round(v); }
    var moneyTip = { callbacks: { label: function(c){ return (c.label? c.label+': ' : '') + inr(c.parsed.x !== undefined ? c.parsed.x : (c.parsed.y !== undefined ? c.parsed.y : c.parsed)); } } };

    // 1) Debtors vs Creditors (amount)
    new Chart(document.getElementById('chDoughnut'), {
        type: 'doughnut',
        data: { labels: ['नाम (Dr)', 'जमा (Cr)'], datasets: [{ data: [AS_CHART.dr, AS_CHART.cr], backgroundColor: [RED, GREEN], borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '58%', plugins: { legend: { position: 'bottom' }, tooltip: moneyTip } }
    });

    // 2) Party status (count)
    new Chart(document.getElementById('chStatus'), {
        type: 'pie',
        data: { labels: ['नाम', 'जमा', 'Nil'], datasets: [{ data: [AS_CHART.deb, AS_CHART.cred, AS_CHART.nil], backgroundColor: [RED, GREEN, GREY], borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // 3) Net exposure by ledger group (stacked bar) — only if group data present
    var gEl = document.getElementById('chGroups');
    var gKeys = Object.keys(AS_CHART.grp || {});
    if (gEl && gKeys.length) {
        gKeys.sort(function(a,b){ var ta=AS_CHART.grp[a].dr+AS_CHART.grp[a].cr, tb=AS_CHART.grp[b].dr+AS_CHART.grp[b].cr; return tb-ta; });
        gKeys = gKeys.slice(0, 12);
        new Chart(gEl, {
            type: 'bar',
            data: { labels: gKeys, datasets: [
                { label: 'नाम (Dr)', data: gKeys.map(function(k){ return AS_CHART.grp[k].dr; }), backgroundColor: RED, borderRadius: 4 },
                { label: 'जमा (Cr)', data: gKeys.map(function(k){ return AS_CHART.grp[k].cr; }), backgroundColor: GREEN, borderRadius: 4 }
            ] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, tooltip: moneyTip },
                scales: { x: { stacked: true, ticks: { maxRotation: 40, minRotation: 0, font: { size: 10 } } }, y: { stacked: true, ticks: { callback: function(v){ return inr(v); } } } } }
        });
    }

    // 4) Top debtors (horizontal bar)
    new Chart(document.getElementById('chDebtors'), {
        type: 'bar',
        data: { labels: AS_CHART.topDeb.map(function(d){ return d.n; }), datasets: [{ label: 'Dr', data: AS_CHART.topDeb.map(function(d){ return d.v; }), backgroundColor: RED, borderRadius: 4 }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: moneyTip }, scales: { x: { ticks: { callback: function(v){ return inr(v); } } } } }
    });

    // 5) Top creditors (horizontal bar)
    new Chart(document.getElementById('chCreditors'), {
        type: 'bar',
        data: { labels: AS_CHART.topCred.map(function(d){ return d.n; }), datasets: [{ label: 'Cr', data: AS_CHART.topCred.map(function(d){ return d.v; }), backgroundColor: GREEN, borderRadius: 4 }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: moneyTip }, scales: { x: { ticks: { callback: function(v){ return inr(v); } } } } }
    });
})();
</script>
<?php endif; ?>
