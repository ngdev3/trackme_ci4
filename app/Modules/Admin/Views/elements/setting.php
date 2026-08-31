<style>
    /* ============================================================
       Change Firm / Workspace switcher — full redesign
       (functional hooks preserved: #template_fy, #fyTemplate*,
        #submitBtn, change_fy(), .fy-recent-chip[data-tid].is-current)
       ============================================================ */
    .fy-modal .modal-dialog { width: 660px; max-width: calc(100% - 32px); margin: 26px auto; pointer-events: auto; }
    /* overflow must stay visible so the template dropdown is never clipped */
    .fy-modal .modal-content { overflow: visible; border: 0; border-radius: 18px; background: #fff; box-shadow: 0 30px 90px rgba(16, 28, 56, .30); }

    /* Header */
    .fy-modal .modal-header {
        position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 14px;
        padding: 15px 20px; border: 0; color: #fff; border-radius: 14px 14px 0 0;
        background: radial-gradient(circle at 90% -40%, rgba(120,170,255,.55), transparent 42%),
                    linear-gradient(125deg, #0f2748, var(--tm-brand, #1769c2) 60%, #3b1e6e);
    }
    .fy-modal .modal-header:after { content: ""; position: absolute; inset: 0; pointer-events: none; border-radius: 14px 14px 0 0;
        background: repeating-linear-gradient(90deg, rgba(255,255,255,.06) 0 1px, transparent 1px 80px); }
    .fy-head-l { display: flex; align-items: center; gap: 12px; position: relative; z-index: 1; }
    .fy-head-ic { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; font-size: 19px;
        background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.28); flex: none; }
    .fy-modal .modal-title { margin: 0; color: #fff; font-size: 18px; font-weight: 900; letter-spacing: -.2px; }
    .fy-modal-subtitle { display: block; margin-top: 2px; color: rgba(232,240,255,.82); font-size: 12px; font-weight: 600; line-height: 1.4; }
    .fy-modal .close { position: relative; z-index: 1; float: none; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
        margin: 0; padding: 0; border: 1px solid rgba(255,255,255,.28); border-radius: 9px; color: #fff; opacity: 1; text-shadow: none;
        line-height: 1; background: rgba(255,255,255,.14); transition: background .15s ease, transform .15s ease; }
    .fy-modal .close > i { display: block; font-size: 14px; line-height: 1; }
    .fy-modal .close:hover, .fy-modal .close:focus { color: #fff; background: rgba(255,255,255,.28); transform: rotate(90deg); outline: 0; }

    /* Body */
    .fy-modal .modal-body { position: relative; padding: 16px 18px 18px; overflow: visible; background: #f6f9ff; border-radius: 0 0 14px 14px; }
    .fy-note { display: inline-flex; align-items: center; gap: 7px; margin-bottom: 12px; padding: 6px 11px; border-radius: 999px;
        border: 1px solid rgba(var(--tm-brand-rgb, 23,105,194), .16); color: var(--tm-brand-dark, #0c315f);
        background: #fff; font-size: 11px; font-weight: 800; box-shadow: 0 6px 16px rgba(24,36,60,.05); }
    .fy-note i { color: var(--tm-brand, #1769c2); }

    .fy-sec-label { display: flex; align-items: center; gap: 7px; margin: 0 0 8px; color: #64748b;
        font-size: 10.5px; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }
    .fy-sec-label i { color: var(--tm-brand, #1769c2); font-size: 13px; }
    .fy-sec-label .fy-count { margin-left: auto; color: #94a3b8; font-weight: 800; letter-spacing: 0; text-transform: none; }

    /* Current-workspace strip */
    .fy-current {
        position: relative; display: grid; grid-template-columns: repeat(5, 1fr); gap: 1px; margin-bottom: 16px;
        border-radius: 12px; overflow: hidden; border: 1px solid #e2e9f4; background: #e2e9f4;
        box-shadow: 0 10px 24px rgba(24,36,60,.07);
    }
    .fy-cur-cell { padding: 11px 13px; background: linear-gradient(180deg,#fff,#fbfdff); min-width: 0; }
    .fy-cur-pill { display: inline-block; padding: 1px 9px; border-radius: 999px; font-size: 11.5px; font-weight: 900; }
    .fy-cur-cell small { display: flex; align-items: center; gap: 5px; margin-bottom: 4px; color: #8794a8; font-size: 9.5px; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; }
    .fy-cur-cell small i { color: var(--tm-accent, #f0a020); font-size: 11px; }
    .fy-cur-cell strong { display: block; color: #14213d; font-size: 13.5px; font-weight: 900; line-height: 1.25; overflow: hidden; text-overflow: ellipsis; }
    .fy-cur-badge { position: absolute; top: 9px; right: 9px; display: inline-flex; align-items: center; gap: 5px;
        padding: 2px 8px; border-radius: 999px; background: #dcfce7; color: #0c6b2e; font-size: 9px; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
    .fy-cur-badge::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.2); }

    /* Recently selected — table view */
    .fy-recent { margin-bottom: 16px; }
    .fy-recent-tblwrap { border: 1px solid #e2e9f4; border-radius: 11px; overflow: hidden; background: #fff; box-shadow: 0 8px 20px rgba(24,36,60,.05); }
    .fy-recent-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    /* vertical scroll: ~5 rows visible at a time, rest on scroll */
    .fy-recent-scrollv { max-height: 232px; overflow-y: auto; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .fy-recent-scrollv::-webkit-scrollbar { width: 7px; height: 7px; }
    .fy-recent-scrollv::-webkit-scrollbar-thumb { background: #cbd6e6; border-radius: 8px; }
    table.fy-recent-tbl { width: 100%; border-collapse: collapse; }
    table.fy-recent-tbl thead th { position: sticky; top: 0; z-index: 2; padding: 8px 12px; text-align: left; white-space: nowrap; background: #f7faff; border-bottom: 1px solid #eef2f8; box-shadow: 0 1px 0 #eef2f8;
        color: #8794a8; font-size: 10px; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; }
    table.fy-recent-tbl th.sno, table.fy-recent-tbl td.fy-rt-sno { width: 30px; text-align: center; color: #94a3b8; font-weight: 800; }
    table.fy-recent-tbl thead th.num { text-align: right; }
    table.fy-recent-tbl td.fy-rt-num { text-align: right; }
    .fy-rt-prod { display: inline-block; padding: 1px 9px; border-radius: 999px; font-size: 11px; font-weight: 900; white-space: nowrap; }
    .fy-rt-ec { font-weight: 900; color: #1746a2; font-variant-numeric: tabular-nums; }
    table.fy-recent-tbl thead th.act { text-align: right; }
    table.fy-recent-tbl tbody td { padding: 7px 12px; border-top: 1px solid #f0f3f9; vertical-align: middle; font-size: 12.5px; white-space: nowrap; }
    table.fy-recent-tbl tbody tr.fy-recent-chip { cursor: pointer; transition: background .13s ease; }
    table.fy-recent-tbl tbody tr.fy-recent-chip:hover { background: #f5f9ff; }
    /* active template is display-only — not clickable */
    table.fy-recent-tbl tbody tr.is-current { background: #f1fbf5; cursor: default; }
    table.fy-recent-tbl tbody tr.is-current:hover { background: #f1fbf5; }

    .fy-rt-firm { display: flex; align-items: center; gap: 8px; min-width: 0; }
    .fy-rt-dot { width: 26px; height: 26px; border-radius: 8px; display: grid; place-items: center; color: #fff; font-size: 12px; flex: none; }
    .fy-rt-name { max-width: 230px; overflow: hidden; color: #14213d; font-size: 12.5px; font-weight: 900; line-height: 1.2; text-overflow: ellipsis; white-space: nowrap; }
    .fy-rt-sub { color: #8794a8; font-size: 11px; font-weight: 700; }
    .fy-rt-fy { display: inline-block; padding: 3px 10px; border-radius: 999px; background: #eef2f8; color: #334155; font-size: 11.5px; font-weight: 800; }
    .fy-rt-ptype { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 900; letter-spacing: .03em; text-transform: uppercase; }
    .fy-rt-count { text-align: right; }
    .fy-rt-count b { color: #14213d; font-size: 15px; font-weight: 900; }
    .fy-rt-count span { display: block; margin-top: 1px; color: #8794a8; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; }
    .fy-rt-when { color: #64748b; font-size: 12px; font-weight: 800; }
    .fy-rt-when i { margin-right: 5px; color: var(--tm-brand, #1769c2); }
    .fy-rt-act { text-align: right; }
    .fy-rt-go { display: inline-flex; align-items: center; gap: 5px; color: var(--tm-brand, #1769c2); font-size: 11.5px; font-weight: 900; transition: gap .13s ease; }
    tr.is-current .fy-rt-go { color: #16a34a; }
    tr.fy-recent-chip:hover .fy-rt-go { gap: 8px; }
    .fy-rt-now { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px; background: #dcfce7; color: #0c6b2e; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: .03em; }

    /* Switch panel */
    .fy-switch { padding: 14px; margin-bottom: 16px; border: 1px solid #e2e9f4; border-radius: 12px; background: #fff; box-shadow: 0 8px 20px rgba(24,36,60,.06); }
    .fy-switch label.fy-switch-lbl { display: block; margin-bottom: 8px; color: #263655; font-size: 12.5px; font-weight: 900; }
    .fy-switch label.fy-switch-lbl span { color: #b91c1c; }

    .fy-native-select { position: absolute !important; width: 1px !important; height: 1px !important; overflow: hidden !important; clip: rect(0,0,0,0) !important; white-space: nowrap !important; }

    .fy-template-picker { position: relative; }
    .fy-template-trigger { width: 100%; min-height: 54px; display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 10px 13px; border: 1.5px solid #dce6f2; border-radius: 11px; color: #14213d;
        background: linear-gradient(180deg,#fff,#f7faff); text-align: left; cursor: pointer;
        transition: border-color .16s ease, box-shadow .16s ease; }
    .fy-template-trigger:hover, .fy-template-trigger:focus { border-color: rgba(var(--tm-brand-rgb, 23,105,194), .5); box-shadow: 0 0 0 4px rgba(var(--tm-brand-rgb, 23,105,194), .1); outline: 0; }
    .fy-template-trigger.is-open { border-color: var(--tm-brand, #1769c2); box-shadow: 0 0 0 4px rgba(var(--tm-brand-rgb, 23,105,194), .14); }
    .fy-template-selected { min-width: 0; display: grid; gap: 3px; }
    .fy-template-eyebrow { color: #8794a8; font-size: 10.5px; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; }
    .fy-template-text { overflow: hidden; color: #14213d; font-size: 15px; font-weight: 900; text-overflow: ellipsis; white-space: normal; line-height: 1.3; }
    .fy-template-caret { width: 36px; height: 36px; min-width: 36px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 9px; color: #fff; background: linear-gradient(135deg, var(--tm-brand, #1769c2), var(--tm-brand-dark, #0c315f)); transition: transform .18s ease; }
    .fy-template-trigger.is-open .fy-template-caret { transform: rotate(180deg); }

    .fy-template-menu { position: absolute; z-index: 1065; top: calc(100% + 8px); left: 0; right: 0; display: none; overflow: hidden;
        border: 1px solid rgba(var(--tm-brand-rgb, 23,105,194), .16); border-radius: 13px; background: #fff;
        box-shadow: 0 24px 54px rgba(24,36,60,.24); overscroll-behavior: contain; }
    .fy-template-picker.is-open .fy-template-menu { display: block; }
    /* flips above the trigger when there isn't room below */
    .fy-template-menu.open-up { top: auto; bottom: calc(100% + 8px); }
    .fy-template-search-wrap { padding: 13px; border-bottom: 1px solid #eef2f8; background: #f8fbff; }
    .fy-template-search { width: 100%; min-height: 44px; padding: 10px 14px; border: 1px solid #dce6f2; border-radius: 10px; color: #14213d; background: #fff; font-size: 13px; font-weight: 700; outline: 0; }
    .fy-template-search:focus { border-color: var(--tm-brand, #1769c2); box-shadow: 0 0 0 4px rgba(var(--tm-brand-rgb, 23,105,194), .1); }
    .fy-template-options { max-height: 260px; overflow-y: auto; overflow-x: hidden; padding: 9px; -webkit-overflow-scrolling: touch; overscroll-behavior: contain; touch-action: pan-y; }
    .fy-template-option { width: 100%; display: block; margin: 0 0 7px; padding: 13px 14px; border: 1px solid transparent; border-radius: 11px; color: #14213d; background: transparent; text-align: left; cursor: pointer; transition: background .15s ease, border-color .15s ease, transform .15s ease; }
    .fy-template-option:hover, .fy-template-option:focus { border-color: rgba(var(--tm-brand-rgb, 23,105,194), .16); background: var(--tm-brand-soft, #eaf3ff); outline: 0; transform: translateX(2px); }
    .fy-template-option.is-selected { border-color: rgba(var(--tm-brand-rgb, 23,105,194), .3); background: linear-gradient(135deg, var(--tm-brand-soft, #eaf3ff), #fff); box-shadow: inset 4px 0 0 var(--tm-brand, #1769c2); }
    .fy-option-main { display: block; color: #14213d; font-size: 13.5px; font-weight: 900; line-height: 1.4; }
    .fy-option-meta { display: block; margin-top: 5px; color: #8794a8; font-size: 12px; font-weight: 800; }
    .fy-template-empty { display: none; padding: 16px; color: #8794a8; font-size: 13px; font-weight: 800; text-align: center; }

    .fy-error { margin-top: 6px; }
    .fy-modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 4px; }
    .fy-modal-actions .btn { min-height: 40px; padding: 9px 20px; border-radius: 10px; font-weight: 900; font-size: 13px; }
    .fy-modal-actions .btn-secondary { border: 1px solid #dce6f2; color: #40506b; background: #fff; }
    .fy-modal-actions .btn-secondary:hover, .fy-modal-actions .btn-secondary:focus { color: var(--tm-brand-dark, #0c315f); background: var(--tm-brand-soft, #eaf3ff); }
    .fy-modal-actions .btn-primary { border: 0; background: linear-gradient(135deg, var(--tm-brand, #1769c2), var(--tm-brand-dark, #0c315f)); box-shadow: 0 12px 26px rgba(var(--tm-brand-rgb, 23,105,194), .32); }
    .fy-modal-actions .btn-primary:hover, .fy-modal-actions .btn-primary:focus { filter: brightness(1.05); }

    .fy-spinner { width: 14px; height: 14px; display: inline-block; margin-left: 8px; border: 2px solid rgba(255,255,255,.45); border-top-color: #fff; border-radius: 50%; vertical-align: -2px; animation: fy-spin .7s linear infinite; }
    .fy-spinner.d-none { display: none; }
    @keyframes fy-spin { to { transform: rotate(360deg); } }

    @media (max-width: 640px) {
        .fy-modal .modal-dialog { width: calc(100% - 20px); margin: 16px auto; }
        .fy-modal .modal-header, .fy-modal .modal-body { padding: 18px; }
        .fy-modal .modal-title { font-size: 20px; }
        .fy-current { grid-template-columns: repeat(2, 1fr); }
        .fy-modal-actions { flex-direction: column-reverse; }
        .fy-modal-actions .btn { width: 100%; }
    }
</style>

<?php
$current_fy = fy();
$current_fy_label = (is_object($current_fy) && isset($current_fy->FY)) ? $current_fy->FY : '';
$current_firm_name = (is_object($current_fy) && isset($current_fy->firm_name)) ? $current_fy->firm_name : '';
$current_template_name = (is_object($current_fy) && isset($current_fy->template_name)) ? $current_fy->template_name : '';

if (($current_firm_name === '' || $current_template_name === '') && !empty($fy)) {
    foreach ($fy as $fy_option) {
        $is_current_template = is_object($current_fy)
            && isset($current_fy->template_id, $fy_option->template_id)
            && (string) $current_fy->template_id === (string) $fy_option->template_id;

        if (!$is_current_template) {
            continue;
        }

        if ($current_firm_name === '' && isset($fy_option->firm_name)) {
            $current_firm_name = $fy_option->firm_name;
        }
        if ($current_template_name === '' && isset($fy_option->template_name)) {
            $current_template_name = $fy_option->template_name;
        }
        break;
    }
}

$current_fy_label = $current_fy_label !== '' ? ucfirst($current_fy_label) : 'Not assigned';
$current_firm_name = $current_firm_name !== '' ? $current_firm_name : 'Not assigned';
$current_template_name = $current_template_name !== '' ? $current_template_name : 'Not assigned';

// Recently selected templates (per current user) for the quick-switch cards.
$recent_templates = array();
$__cui = function_exists('currentuserinfo') ? currentuserinfo() : null;
$__uid = (is_object($__cui) && isset($__cui->id)) ? (int) $__cui->id : null;
if ($__uid && function_exists('recent_switched_templates')) {
    $recent_templates = recent_switched_templates($__uid, 10);
}
$__cur_tid = (is_object($current_fy) && isset($current_fy->template_id)) ? (string) $current_fy->template_id : '';

// Relative-ish "last used" label.
$fy_when = function ($dt) {
    $ts = $dt ? strtotime($dt) : false;
    if (!$ts) { return ''; }
    $diff = time() - $ts;
    if ($diff >= 0 && $diff < 60)      { return 'just now'; }
    if ($diff >= 0 && $diff < 3600)    { return floor($diff / 60) . ' min ago'; }
    if ($diff >= 0 && $diff < 86400)   { return floor($diff / 3600) . ' hr ago'; }
    if ($diff >= 0 && $diff < 172800)  { return 'yesterday'; }
    return date('d M Y', $ts);
};

// Product type → label + colours (text, dot-bg, pill-bg).
$fy_ptype = function ($p) {
    $p = (string) $p;
    if ($p === '1') { return array('Paddy',    '#0c7048', 'linear-gradient(135deg,#1f9d70,#0c7048)', '#dcfce7'); }
    if ($p === '2') { return array('Wheat',    '#9a5b06', 'linear-gradient(135deg,#e08a12,#9a5b06)', '#fef3c7'); }
    if ($p === '3') { return array('Standard', '#1746a2', 'linear-gradient(135deg,#2563eb,#1746a2)', '#e0ecff'); }
    return array('Template', '#475569', 'linear-gradient(135deg,#64748b,#334155)', '#eef2f7');
};

// Current workspace extras (product + its rokad entry count) for the header card.
$cur_pt = (is_object($current_fy) && isset($current_fy->product_type)) ? $current_fy->product_type : '';
$cur_pt_lbl = $fy_ptype($cur_pt);
$cur_entries = null;
if (!empty($recent_templates)) {
    foreach ($recent_templates as $__rt) {
        if ((string) $__rt->template_id === $__cur_tid) {
            $cur_entries = (int) (isset($__rt->entry_count) ? $__rt->entry_count : 0);
            break;
        }
    }
}
?>

<!-- Modal -->
<div class="modal fade fy-modal" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="fy-head-l">
                    <div class="fy-head-ic"><i class="ti-exchange-vertical"></i></div>
                    <div class="fy-modal-title-wrap">
                        <h5 class="modal-title" id="exampleModalLabel">Change Firm</h5>
                        <span class="fy-modal-subtitle">Switch financial year, firm &amp; active template.</span>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="ti-close" aria-hidden="true"></i>
                </button>
            </div>

            <div class="modal-body">
                <!-- Current workspace -->
                <div class="fy-sec-label"><i class="ti-check-box"></i> Current Workspace</div>
                <div class="fy-current">
                    <span class="fy-cur-badge">Active</span>
                    <div class="fy-cur-cell">
                        <small><i class="ti-home"></i> Firm Name</small>
                        <strong><?= htmlspecialchars($current_firm_name, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="fy-cur-cell">
                        <small><i class="ti-calendar"></i> Financial Year</small>
                        <strong><?= htmlspecialchars($current_fy_label, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="fy-cur-cell">
                        <small><i class="ti-package"></i> Product</small>
                        <strong><span class="fy-cur-pill" style="background:<?= $cur_pt_lbl[3] ?>;color:<?= $cur_pt_lbl[1] ?>;"><?= htmlspecialchars($cur_pt_lbl[0], ENT_QUOTES, 'UTF-8') ?></span></strong>
                    </div>
                    <div class="fy-cur-cell">
                        <small><i class="ti-layout-grid2"></i> Template<?= $__cur_tid !== '' ? ' · ID ' . htmlspecialchars($__cur_tid, ENT_QUOTES, 'UTF-8') : '' ?></small>
                        <strong><?= htmlspecialchars($current_template_name, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="fy-cur-cell">
                        <small><i class="ti-receipt"></i> Rokad Entries</small>
                        <strong><?= $cur_entries !== null ? number_format($cur_entries) : '—' ?></strong>
                    </div>
                </div>

                <!-- Switch to another template (above recent) -->
                <div class="fy-sec-label"><i class="ti-exchange-vertical"></i> Switch Firm / Template</div>
                <div class="fy-switch">
                    <label class="fy-switch-lbl" for="template_fy"><i class="ti-search"></i> Switch to another template <span>*</span></label>

                    <select id="template_fy" class="form-control fy-native-select" name="template_fy" required>
                        <option value="">Select Financial Year</option>
                        <?php if (!empty($fy)) {
                            foreach ($fy as $new) {
                                $selected = (isset(fy()->template_id) && $new->template_id == fy()->template_id) ? 'selected' : '';
                                $text = 'ID-' . $new->template_id . '-' . $new->template_name;
                                if (isset($new->product_type)) {
                                    if ($new->product_type == '1') { $text .= " || Paddy"; }
                                    elseif ($new->product_type == '2') { $text .= " || Wheat"; }
                                    elseif ($new->product_type == '3') { $text .= " || Standard"; }
                                }
                                if (!empty($new->firm_name)) { $text .= " || " . $new->firm_name; }
                                ?>
                                <option value="<?php echo $new->template_id; ?>" <?php echo $selected; ?>><?php echo $text; ?></option>
                            <?php }
                        } ?>
                    </select>

                    <div class="fy-template-picker" id="fyTemplatePicker">
                        <button type="button" class="fy-template-trigger" id="fyTemplateTrigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="fy-template-selected">
                                <span class="fy-template-eyebrow">Active template</span>
                                <span class="fy-template-text" id="fyTemplateText">Select Financial Year</span>
                            </span>
                            <span class="fy-template-caret"><i class="ti-angle-down"></i></span>
                        </button>
                        <div class="fy-template-menu" id="fyTemplateMenu">
                            <div class="fy-template-search-wrap">
                                <input type="text" class="fy-template-search" id="fyTemplateSearch" placeholder="Search template, firm, paddy, wheat..." autocomplete="off">
                            </div>
                            <div class="fy-template-options" id="fyTemplateOptions" role="listbox"></div>
                            <div class="fy-template-empty" id="fyTemplateEmpty">No matching template found</div>
                        </div>
                    </div>

                    <div class="fy-error">
                        <div class="help-block" style="color:#b91c1c;font-weight:800;font-size:12px;"><?php echo form_error('rokad_type'); ?></div>
                    </div>
                </div>

                <!-- Recently selected (below the dropdown) -->
                <?php if (!empty($recent_templates)): ?>
                <div class="fy-recent">
                    <div class="fy-sec-label"><i class="ti-time"></i> Recently Selected
                        <span class="fy-count"><?= count($recent_templates) ?> template<?= count($recent_templates) === 1 ? '' : 's' ?></span>
                    </div>
                    <div class="fy-recent-tblwrap">
                        <div class="fy-recent-scroll fy-recent-scrollv">
                            <table class="fy-recent-tbl">
                                <thead>
                                    <tr>
                                        <th class="sno">#</th>
                                        <th>Firm / Template</th>
                                        <th>FY</th>
                                        <th>Product</th>
                                        <th class="num">Rokad Entries</th>
                                        <th>Last Used</th>
                                        <th class="act">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rt_i = 0; foreach ($recent_templates as $rt):
                                        $rt_i++;
                                        $rt_tid   = (string) $rt->template_id;
                                        $rt_firm  = trim((string) (isset($rt->firm_name) ? $rt->firm_name : ''));
                                        $rt_tpl   = trim((string) (isset($rt->template_name) ? $rt->template_name : ''));
                                        $rt_fy    = trim((string) (isset($rt->FY) ? $rt->FY : ''));
                                        $rt_title = $rt_firm !== '' ? $rt_firm : ($rt_tpl !== '' ? $rt_tpl : ('Template #' . $rt_tid));
                                        $rt_ec    = (int) (isset($rt->entry_count) ? $rt->entry_count : 0);
                                        $rt_when  = $fy_when(isset($rt->last_selected) ? $rt->last_selected : '');
                                        $rt_isCur = ($rt_tid !== '' && $rt_tid === $__cur_tid);
                                        $pt       = $fy_ptype(isset($rt->product_type) ? $rt->product_type : '');
                                    ?>
                                    <tr class="fy-recent-chip<?= $rt_isCur ? ' is-current' : '' ?>"
                                        data-tid="<?= htmlspecialchars($rt_tid, ENT_QUOTES, 'UTF-8') ?>"
                                        title="<?= htmlspecialchars($rt_title . ($rt_fy !== '' ? ' — FY ' . $rt_fy : ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <td class="fy-rt-sno"><?= $rt_i ?></td>
                                        <td>
                                            <div class="fy-rt-firm">
                                                <span class="fy-rt-dot" style="background: <?= $pt[2] ?>;"><i class="ti-package"></i></span>
                                                <div style="min-width:0;">
                                                    <div class="fy-rt-name"><?= htmlspecialchars($rt_title, ENT_QUOTES, 'UTF-8') ?></div>
                                                    <span class="fy-rt-sub"><?= htmlspecialchars($rt_tpl !== '' ? $rt_tpl : ('ID ' . $rt_tid), ENT_QUOTES, 'UTF-8') ?> · ID <?= htmlspecialchars($rt_tid, ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="fy-rt-fy"><?= $rt_fy !== '' ? htmlspecialchars(ucfirst($rt_fy), ENT_QUOTES, 'UTF-8') : '—' ?></span></td>
                                        <td><span class="fy-rt-prod" style="background:<?= $pt[3] ?>;color:<?= $pt[1] ?>;"><?= htmlspecialchars($pt[0], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="fy-rt-num"><span class="fy-rt-ec" title="Rokad (cash-book) entries in this template"><?= number_format($rt_ec) ?></span></td>
                                        <td><span class="fy-rt-when"><i class="ti-time"></i><?= $rt_when !== '' ? htmlspecialchars($rt_when, ENT_QUOTES, 'UTF-8') : 'earlier' ?></span></td>
                                        <td class="fy-rt-act">
                                            <?php if ($rt_isCur): ?>
                                                <span class="fy-rt-now"><i class="ti-check"></i> Active</span>
                                            <?php else: ?>
                                                <span class="fy-rt-go">Switch <i class="ti-arrow-right"></i></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actions (bottom) -->
                <div class="fy-modal-actions">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="submitBtn" onclick="change_fy()">
                        <span id="btnText">Switch Workspace</span>
                        <span id="btnSpinner" class="fy-spinner d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        function initFyTemplateDropdown() {
            var select = document.getElementById('template_fy');
            var picker = document.getElementById('fyTemplatePicker');
            var trigger = document.getElementById('fyTemplateTrigger');
            var selectedText = document.getElementById('fyTemplateText');
            var search = document.getElementById('fyTemplateSearch');
            var optionsWrap = document.getElementById('fyTemplateOptions');
            var empty = document.getElementById('fyTemplateEmpty');

            if (!select || !picker || !trigger || !selectedText || !search || !optionsWrap || !empty) {
                return;
            }

            var options = Array.prototype.slice.call(select.options).map(function (option) {
                return {
                    value: option.value,
                    text: option.text.replace(/\s+/g, ' ').trim(),
                    selected: option.selected
                };
            }).filter(function (option) {
                return option.value !== '';
            });

            function productBadge(text) {
                if (text.toLowerCase().indexOf('paddy') !== -1) {
                    return 'Paddy';
                }

                if (text.toLowerCase().indexOf('wheat') !== -1) {
                    return 'Wheat';
                }

                return 'Template';
            }

            function closeMenu() {
                picker.classList.remove('is-open');
                trigger.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
            }

            var menu = document.getElementById('fyTemplateMenu');

            // Keep the menu inside the viewport: flip up near the bottom and
            // cap the option list height to the space actually available.
            function positionMenu() {
                if (!picker.classList.contains('is-open')) { return; }
                menu.classList.remove('open-up');
                optionsWrap.style.maxHeight = '';

                var rect = trigger.getBoundingClientRect();
                var vh = window.innerHeight || document.documentElement.clientHeight;
                var margin = 16;
                var searchH = 68; // search box + padding
                var spaceBelow = vh - rect.bottom - margin;
                var spaceAbove = rect.top - margin;

                var openUp = (spaceBelow < 240 && spaceAbove > spaceBelow);
                menu.classList.toggle('open-up', openUp);

                var avail = openUp ? spaceAbove : spaceBelow;
                var optMax = Math.max(140, Math.min(280, avail - searchH));
                optionsWrap.style.maxHeight = optMax + 'px';
            }

            function openMenu() {
                picker.classList.add('is-open');
                trigger.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                search.value = '';
                renderOptions('');
                positionMenu();
                setTimeout(function () {
                    positionMenu();
                    search.focus();
                }, 0);
            }

            window.addEventListener('resize', positionMenu);
            // Reposition while the modal body scrolls under the open menu.
            var modalEl = document.getElementById('exampleModal');
            if (modalEl) { modalEl.addEventListener('scroll', positionMenu, true); }

            function syncSelectedText() {
                var selected = options.filter(function (option) {
                    return option.value === select.value;
                })[0];

                selectedText.innerText = selected ? selected.text : 'Select Financial Year';
            }

            function autoLoadTemplate() {
                if (typeof window.change_fy === 'function') {
                    window.change_fy();
                    return;
                }

                var submitButton = document.getElementById('submitBtn');
                if (submitButton) {
                    submitButton.click();
                }
            }

            function chooseTemplate(option, autoLoad) {
                if (!option || select.value === option.value && autoLoad) {
                    if (autoLoad) {
                        autoLoadTemplate();
                    }
                    return;
                }

                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                syncSelectedText();
                closeMenu();

                if (autoLoad) {
                    autoLoadTemplate();
                }
            }

            function renderOptions(query) {
                query = (query || '').toLowerCase();
                optionsWrap.innerHTML = '';

                var filtered = options.filter(function (option) {
                    return option.text.toLowerCase().indexOf(query) !== -1;
                });

                empty.style.display = filtered.length ? 'none' : 'block';

                filtered.forEach(function (option) {
                    var item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'fy-template-option' + (option.value === select.value ? ' is-selected' : '');
                    item.setAttribute('role', 'option');
                    item.setAttribute('aria-selected', option.value === select.value ? 'true' : 'false');
                    item.innerHTML = '<span class="fy-option-main"></span><span class="fy-option-meta"></span>';
                    item.querySelector('.fy-option-main').innerText = option.text;
                    item.querySelector('.fy-option-meta').innerText = productBadge(option.text) + ' | ID ' + option.value;

                    item.addEventListener('click', function () {
                        chooseTemplate(option, true);
                    });

                    optionsWrap.appendChild(item);
                });
            }

            trigger.addEventListener('click', function () {
                if (picker.classList.contains('is-open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });

            search.addEventListener('input', function () {
                renderOptions(search.value);
            });

            document.getElementById('fyTemplateMenu').addEventListener('wheel', function (event) {
                event.stopPropagation();
            }, { passive: true });

            optionsWrap.addEventListener('touchmove', function (event) {
                event.stopPropagation();
            }, { passive: true });

            document.addEventListener('click', function (event) {
                if (!picker.contains(event.target)) {
                    closeMenu();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeMenu();
                }
            });

            select.addEventListener('change', syncSelectedText);
            syncSelectedText();
            renderOptions('');
        }

        // Recently-selected cards → pick that template and switch immediately.
        function initFyRecentChips() {
            var select = document.getElementById('template_fy');
            var chips = document.querySelectorAll('.fy-recent-chip');
            if (!select || !chips.length) { return; }

            Array.prototype.forEach.call(chips, function (chip) {
                // The already-active template is not clickable — nothing to switch.
                if (chip.classList.contains('is-current')) { return; }

                chip.addEventListener('click', function () {
                    var tid = chip.getAttribute('data-tid');
                    if (!tid) { return; }

                    var match = Array.prototype.slice.call(select.options).filter(function (o) {
                        return o.value === tid;
                    })[0];
                    if (!match) { return; }

                    select.value = tid;
                    select.dispatchEvent(new Event('change', { bubbles: true }));

                    if (typeof window.change_fy === 'function') {
                        window.change_fy();
                    } else {
                        var b = document.getElementById('submitBtn');
                        if (b) { b.click(); }
                    }
                });
            });
        }

        function initFyPopup() {
            initFyTemplateDropdown();
            initFyRecentChips();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initFyPopup);
        } else {
            initFyPopup();
        }

        // Failsafe: Bootstrap 3 + jQuery 3 can leave the .modal-backdrop and
        // body.modal-open behind (the fade-out transitionend fires late/flakily),
        // freezing the page under a dark overlay. When this modal starts hiding,
        // force the teardown after the animation window if Bootstrap hasn't. The
        // ".modal.in" guard avoids nuking a backdrop that belongs to another modal
        // or a hide that was prevented.
        if (window.jQuery) {
            jQuery(document).off('hide.bs.modal.fyfix').on('hide.bs.modal.fyfix', '#exampleModal', function () {
                setTimeout(function () {
                    if (!jQuery('.modal.in').length) {
                        jQuery('.modal-backdrop').remove();
                        jQuery('body').removeClass('modal-open').css('padding-right', '');
                    }
                }, 400);
            });
        }
    })();

    function change_fy() {
        var submitBtn = document.getElementById("submitBtn");
        if (submitBtn && submitBtn.disabled) {
            return;
        }

        if (!$('#template_fy').val()) {
            alert('Please select a valid Financial Year');
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
        }
        document.getElementById("btnText").innerText = "Loading...Please wait...";
        document.getElementById("btnSpinner").classList.remove("d-none");
        console.log($('#template_fy').val());
        $.ajax({
            url: "<?php echo base_url(); ?>admin/setting/change_fy_id",
            type: "POST",
            dataType: 'json',
            data: { 'template_fy': $('#template_fy').val() },
            success: function (a) {
                setTimeout(function () {
                    document.getElementById("btnText").innerText = "Switch Workspace";
                    document.getElementById("btnSpinner").classList.add("d-none");
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                }, 2000);

                console.log(a);
                if (a.status == 'success') {
                    location.reload();
                } else if (a.access_denied || a.status == 'denied') {
                    // Handled by the global Access Denied dialog in layout.php.
                } else {
                    alert('Something went wrong');
                }
            },
            error: function (jqxhr) {
                document.getElementById("btnText").innerText = "Switch Workspace";
                document.getElementById("btnSpinner").classList.add("d-none");
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
                // 403 access-denied is shown by the global dialog; don't double-alert.
                if (jqxhr && jqxhr.status === 403) {
                    return;
                }
                alert('Something went wrong');
            }
        });
    }
</script>
