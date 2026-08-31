<?php
$old_date  = session()->get('setParchaDate');
$dateValue = $old_date ? date('d-m-Y', strtotime($old_date)) : '';

// Opening balance carried forward from the previous date (आगे लाया), booked to
// the "shri rokad nagad" cash account. Positive → a जमा (receipt) opening;
// negative (overdrawn) → a नाम (payment) opening. Zero → no opening row shown.
$rp_opening    = isset($opening_balance) ? (float) $opening_balance : 0.0;
$rp_cash_label = isset($cash_label) && $cash_label !== '' ? $cash_label : 'shri rokad nagad_15';
$rp_open_jama  = $rp_opening > 0.004  ? $rp_opening        : 0.0;   // opening on जमा side
$rp_open_naam  = $rp_opening < -0.004 ? abs($rp_opening)    : 0.0;   // opening on नाम side (rare)
$rp_has_open   = ($rp_open_jama > 0 || $rp_open_naam > 0);

/**
 * Build a lookup of full entry details (both sides) for the detail popup.
 * All columns already exist on aa_rokad (SELECT ar.* in the model).
 */
$restore_counts = isset($restore_counts) && is_array($restore_counts) ? $restore_counts : array();
$rokad_details = array();

// Batched IP / geo-location lookup (audit trail) for every entry on the page.
// One query for all ids avoids an N+1 per popup open. (Entry Trace module.)
$rp_all_ids = array();
foreach (array(@$naam, @$jama) as $rp_set) {
    if (!empty($rp_set)) { foreach ($rp_set as $rp_r) { $rp_all_ids[] = $rp_r->rokad_id; } }
}
$rp_traces = function_exists('entry_traces_for_batch') ? entry_traces_for_batch('rokad', $rp_all_ids) : array();

$collect = function ($rows, $side_label) use (&$rokad_details, $restore_counts, $rp_traces) {
    if (empty($rows)) return;
    foreach ($rows as $r) {
        $tr = isset($rp_traces[$r->rokad_id]) ? $rp_traces[$r->rokad_id] : null;
        $rokad_details[$r->rokad_id] = array(
            'id'           => $r->rokad_id,
            'side'         => $side_label,
            'restored'     => isset($restore_counts[$r->rokad_id]) ? (int) $restore_counts[$r->rokad_id] : 0,
            'date'         => $r->rokad_date,
            'amount'       => $r->karch_amount,
            'party'        => $r->account_name,
            'account_name' => isset($r->name) ? $r->name : '',
            'account_no'   => $r->account_no,
            'type'         => $r->type_of_account,
            'pay'          => isset($r->payment_mode) ? $r->payment_mode : '',
            'source'       => $r->entry_source,
            'added_by'     => isset($r->added_by_name) ? $r->added_by_name : '',
            'added_on'     => (!empty($r->added_type) && strtotime($r->added_type)) ? date('d-m-Y h:i A', strtotime($r->added_type)) : '',
            'truck_no'     => $r->truck_no,
            'quantity'     => $r->quantity,
            'rate'         => $r->rate,
            'challan_no'   => $r->challan_no,
            'remark'       => $r->remark,
            'image'        => !empty($r->image_path) ? base_url($r->image_path) : '',
            'voice'        => !empty($r->voice_note_path) ? base_url($r->voice_note_path) : '',
            'video'        => !empty($r->video_note_path) ? base_url($r->video_note_path) : '',
            'ip'           => ($tr && !empty($tr->ip_address)) ? $tr->ip_address : '',
            'lat'          => ($tr && $tr->latitude !== null && $tr->latitude !== '') ? $tr->latitude : '',
            'lng'          => ($tr && $tr->longitude !== null && $tr->longitude !== '') ? $tr->longitude : '',
        );
    }
};
$collect(@$naam, 'जमा (Jama)');
$collect(@$jama, 'नाम (Naam)');

// Web vs App badge markup
if (!function_exists('rp_source_badge')) {
    function rp_source_badge($src) {
        $isWeb = (strtolower(trim((string) $src)) === 'web' || trim((string) $src) === '');
        return $isWeb
            ? '<span class="rp-src rp-src-web"><i class="ti-desktop"></i> Web</span>'
            : '<span class="rp-src rp-src-app"><i class="ti-mobile"></i> App</span>';
    }
}

// True when an entry carries any attachment.
if (!function_exists('rp_has_attach')) {
    function rp_has_attach($r) {
        return !empty($r->image_path) || !empty($r->voice_note_path) || !empty($r->video_note_path);
    }
}

// Print/PDF: colour-coded per-type attachment badges (no icon font in the
// print window, so use coloured foreground text — prints without needing the
// "background graphics" option). Photo = green, Voice = blue, Video = purple.
if (!function_exists('rp_print_attach')) {
    function rp_print_attach($r) {
        $out = '';
        if (!empty($r->image_path))      { $out .= '<span class="pl-att pl-att-img" title="Photo">&#9679;IMG</span>'; }
        if (!empty($r->voice_note_path)) { $out .= '<span class="pl-att pl-att-aud" title="Voice">&#9679;AUD</span>'; }
        if (!empty($r->video_note_path)) { $out .= '<span class="pl-att pl-att-vid" title="Video">&#9679;VID</span>'; }
        return $out !== '' ? ' ' . $out : '';
    }
}

// Web listing: one icon per attachment type that is present.
if (!function_exists('rp_attach_icons')) {
    function rp_attach_icons($r) {
        $out = '';
        if (!empty($r->image_path))      $out .= '<i class="ti-camera rp-att rp-att-photo" title="Photo attached"></i>';
        if (!empty($r->voice_note_path)) $out .= '<i class="ti-microphone rp-att rp-att-voice" title="Voice note attached"></i>';
        if (!empty($r->video_note_path)) $out .= '<i class="ti-video-camera rp-att rp-att-video" title="Video attached"></i>';
        return $out ? '<span class="rp-atts">' . $out . '</span>' : '';
    }
}

// A rokad row is a "bill cross entry" when the model flags is_bill = 1, i.e.
// it was auto-generated by a Bill of Supply, Tax Invoice or UBOS.
if (!function_exists('rp_is_bill')) {
    function rp_is_bill($r) {
        return isset($r->is_bill) && (int) $r->is_bill === 1;
    }
}

// A rokad row is a "bank" entry when its account is a Bank account
// (aa_account_name.account_type = 'bank').
if (!function_exists('rp_is_bank')) {
    function rp_is_bank($r) {
        return isset($r->account_type) && strtolower(trim((string) $r->account_type)) === 'bank';
    }
}

// Rokad Parcha groups, in display order: key => title.
// "Shri Rokad Nagad Cash" is the financial-year opening cash line, so it is
// shown only on 01 April (the FY start) and only on the जमा (receipts) side.
$rp_show_nagad = (date('m-d', strtotime($old_date)) === '04-01');
$rp_group_defs = array();
if ($rp_show_nagad) { $rp_group_defs['nagad'] = 'Shri Rokad Nagad Cash'; }
$rp_group_defs['bills'] = 'Bills Entries';
$rp_group_defs['bank']  = 'Bank Entries';
$rp_group_defs['cash']  = 'Cash Entries';
$rp_group_defs_naam = $rp_group_defs;
unset($rp_group_defs_naam['nagad']);

// Cash account id (shri rokad nagad) parsed from its "name_15" label.
$rp_cash_id = (int) substr(strrchr($rp_cash_label, '_'), 1);

// Which group a row belongs to. A manual drag override (aa_rokad.parcha_group)
// always wins; otherwise auto: cash account -> nagad, bill cross-entry ->
// bills, bank account -> bank, everything else -> cash.
if (!function_exists('rp_group_of')) {
    function rp_group_of($r, $cash_id, $keys) {
        if (isset($r->parcha_group) && $r->parcha_group !== null && trim((string) $r->parcha_group) !== '') {
            $g = strtolower(trim((string) $r->parcha_group));
            if (in_array($g, $keys, true)) return $g;
        }
        if ((int) $r->account_no === (int) $cash_id) return 'nagad';
        if (rp_is_bill($r)) return 'bills';
        if (rp_is_bank($r)) return 'bank';
        return 'cash';
    }
}

// Split a side's rows into its groups (assoc: key => rows[]). $defs restricts
// which groups exist for that side; anything that would land in a group not
// present here (e.g. 'nagad' on the नाम side) is re-routed to 'cash'.
$rp_split_groups = function ($rows, $defs) use ($rp_cash_id) {
    $keys = array_keys($defs);
    $out  = array_fill_keys($keys, array());
    if (!empty($rows)) {
        foreach ($rows as $r) {
            $g = rp_group_of($r, $rp_cash_id, $keys);
            if (!isset($out[$g])) $g = 'cash';
            $out[$g][] = $r;
        }
    }
    return $out;
};

// जमा side rows come from $naam (deposits); नाम side rows come from $jama.
$jama_g = $rp_split_groups(@$naam, $rp_group_defs);
$naam_g = $rp_split_groups(@$jama, $rp_group_defs_naam);

// Avatar background from the account name's first letter (stable per name).
if (!function_exists('rp_avatar_color')) {
    function rp_avatar_color($name) {
        $palette = array('#2563eb', '#0ea5e9', '#7c3aed', '#db2777', '#e11d48', '#ea580c', '#ca8a04', '#16a34a', '#0d9488', '#4f46e5', '#9333ea', '#0891b2');
        $c = strtoupper(substr(trim((string) $name) !== '' ? $name : '#', 0, 1));
        return $palette[ord($c) % count($palette)];
    }
}

// Renders one draggable entry row. $side is 'jama' or 'naam'.
$rp_row = function ($val, $side) use ($restore_counts) {
    $rp_restored = isset($restore_counts[$val->rokad_id]) ? (int) $restore_counts[$val->rokad_id] : 0;
    $nm      = trim((string) $val->account_name);
    $initial = $nm !== '' ? mb_strtoupper(mb_substr($nm, 0, 1, 'UTF-8'), 'UTF-8') : '#';
    $txn     = '#TXN-' . str_pad((string) $val->rokad_id, 6, '0', STR_PAD_LEFT);
    $is_jama = ($side === 'jama');
    $pay     = trim((string) @$val->payment_mode);
    ob_start(); ?>
    <div class="rp-row" draggable="true" data-id="<?= $val->rokad_id ?>" data-side="<?= $side ?>" data-amount="<?= (float) $val->karch_amount ?>"
         ondragstart="rpDragStart(event)" ondragend="rpDragEnd(event)" onclick="showDetail(<?= $val->rokad_id ?>)">
        <span class="rp-drag" title="Drag to another group" onclick="event.stopPropagation();"><i class="ti-move"></i></span>
        <span class="rp-avatar" style="background: <?= rp_avatar_color($nm) ?>;"><?= htmlspecialchars($initial) ?></span>
        <div class="rp-amtwrap">
            <span class="rp-amt">&#8377; <?= number_format($val->karch_amount, 2) ?></span>
            <span class="rp-dir rp-dir-<?= $side ?>"><?= $is_jama ? 'Jama &middot; In' : 'Naam &middot; Out' ?></span>
        </div>
        <div class="rp-mid">
            <div class="rp-name"><?= htmlspecialchars($nm) ?> <span class="rp-txn"><?= $txn ?></span></div>
            <div class="rp-sub">
                <?= rp_source_badge($val->entry_source) ?>
                <?php if ($pay !== ''): ?><span class="rp-pay" title="Paid by"><i class="ti-credit-card"></i> <?= htmlspecialchars($pay) ?></span><?php endif; ?>
                <?php if (!empty(trim(@$val->added_by_name))): ?><span class="rp-by" title="Added by"><i class="ti-user"></i> <?= htmlspecialchars($val->added_by_name) ?></span><?php endif; ?>
                <?php if (!empty(@$val->added_type) && strtotime($val->added_type)): ?><span class="rp-on" title="Added on <?= date('d-m-Y h:i A', strtotime($val->added_type)) ?>"><i class="ti-calendar"></i> <?= date('d-m-Y', strtotime($val->added_type)) ?></span><?php endif; ?>
                <?php if ($rp_restored > 0): ?>
                    <span class="rp-restored" title="This entry was deleted and restored <?= $rp_restored ?> time(s)"><i class="ti-reload"></i> Restored <?= $rp_restored ?>&times;</span>
                <?php else: ?>
                    <span class="rp-fresh" title="Never deleted">&#10024; Fresh</span>
                <?php endif; ?>
                <?php if (!empty(trim(@$val->truck_no))): ?><span class="rp-chip" title="Truck no"><i class="ti-truck"></i> <?= htmlspecialchars($val->truck_no) ?></span><?php endif; ?>
                <?= rp_attach_icons($val) ?>
                <?php if (!empty(trim($val->remark))): ?><span class="rp-remark" title="<?= htmlspecialchars($val->remark) ?>"><?= htmlspecialchars($val->remark) ?></span><?php endif; ?>
            </div>
        </div>
        <span class="rp-acts" onclick="event.stopPropagation();">
            <a class="rp-act rp-act-view" title="View" href="javascript:void(0)" onclick="showDetail(<?= $val->rokad_id ?>)"><i class="ti-eye"></i></a>
            <a class="rp-act rp-act-edit" title="Edit" href="<?= base_url('admin/account/edit/' . ID_encode($val->rokad_id)); ?>"><i class="ti-pencil"></i></a>
            <a class="rp-act rp-act-del" title="Delete" href="javascript:void(0)" onclick="deleteSingle(<?= $val->rokad_id ?>)"><i class="ti-trash"></i></a>
        </span>
    </div>
    <?php return ob_get_clean();
};

// Renders a titled group that is both a drop target and a select-all scope.
// $key is the group key, $rows its entries; adds sum into $running by ref.
$rp_group = function ($key, $title, $rows, &$running, $side) use ($rp_row) {
    $sum = 0.0;
    foreach ($rows as $r) { $sum += $r->karch_amount; }
    $running += $sum;
    ob_start(); ?>
    <div class="rp-group rp-group-<?= $key ?>" data-group="<?= $key ?>" data-side="<?= $side ?>"
         ondragover="rpDragOver(event)" ondragleave="rpDragLeave(event)" ondrop="rpDrop(event)">
        <div class="rp-group-head">
            <span class="rp-group-title">
                <span class="rp-group-title-text"><?= htmlspecialchars($title) ?></span>
            </span>
            <span class="rp-group-sub"><?= number_format($sum, 2) ?></span>
        </div>
        <div class="rp-group-body">
            <?php if (empty($rows)): ?>
                <div class="rp-group-empty">यहाँ खींचें &middot; drop entries here</div>
            <?php else: foreach ($rows as $r) { echo $rp_row($r, $side); } endif; ?>
        </div>
    </div>
    <?php return ob_get_clean();
};

// Print ledger: renders a group sub-header row + its entry rows. $sn advances
// by reference so numbering is continuous across the two groups.
$rp_led_group = function ($title, $rows, &$sn) {
    $sum = 0.0;
    foreach ($rows as $r) { $sum += $r->karch_amount; }
    ob_start(); ?>
    <tr class="led-grp"><td colspan="3"><?= htmlspecialchars($title) ?></td><td class="amt"><?= number_format($sum, 2) ?></td></tr>
    <?php if (empty($rows)): ?>
        <tr><td class="c">—</td><td class="c">—</td><td>कोई एंट्री नहीं</td><td class="amt">0.00</td></tr>
    <?php else: foreach ($rows as $val): $sn++; ?>
        <tr>
            <td class="c"><?= $sn ?></td>
            <td class="c"><?= $val->rokad_id ?></td>
            <td><?= htmlspecialchars($val->account_name) ?><?= rp_print_attach($val) ?><?php if (trim($val->remark) !== ''): ?> <span class="rmk">&mdash; <?= htmlspecialchars($val->remark) ?></span><?php endif; ?><?php if (!empty(trim(@$val->added_by_name))): ?> <span class="rmk">(by <?= htmlspecialchars($val->added_by_name) ?>)</span><?php endif; ?><?php if (!empty(@$val->added_type) && strtotime($val->added_type)): ?> <span class="rmk">[<?= date('d-m-Y', strtotime($val->added_type)) ?>]</span><?php endif; ?></td>
            <td class="amt"><?= number_format($val->karch_amount, 2) ?></td>
        </tr>
    <?php endforeach; endif;
    return ob_get_clean();
};
?>

<style>
    .rp-page { padding: 24px; color: #18243c; }
    .rp-shell { max-width: 1100px; margin: 0 auto; }

    .rp-hero {
        display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap;
        padding: 20px 22px; margin-bottom: 18px;
        border: 1px solid #dce6f2; border-radius: 10px; background: #fff;
        box-shadow: 0 14px 34px rgba(24, 36, 60, .08);
    }
    .rp-title { margin: 0; font-size: 22px; font-weight: 900; color: #18243c; }
    .rp-title small { display: block; font-size: 12px; font-weight: 700; color: #8190a5; margin-top: 4px; letter-spacing: .3px; }
    .rp-controls { display: flex; align-items: flex-end; gap: 8px; flex-wrap: wrap; }
    .rp-field label { display: block; font-size: 11px; font-weight: 900; text-transform: uppercase; color: #718096; margin-bottom: 6px; }
    .rp-field .form-control { min-height: 42px; border: 1px solid #dce6f2; border-radius: 8px; background: #fbfdff; font-weight: 800; }
    .rp-btn { min-height: 42px; border: 0; border-radius: 8px !important; font-weight: 800; padding: 10px 16px; display: inline-flex; align-items: center; gap: 6px; }
    .rp-btn-nav { background: #eef3fa; color: #26374f; }
    .rp-btn-nav:hover { background: #e0e9f5; color: #18243c; }
    .rp-btn-search { background: #1f9d70; color: #fff; }
    .rp-btn-search:hover { background: #198a61; color: #fff; }
    .rp-btn-print { background: #1769c2; color: #fff; }
    .rp-btn-print:hover { background: #0c5aaa; color: #fff; }
    .rp-btn-add-jama { background: #1f9d70; color: #fff; }
    .rp-btn-add-jama:hover { background: #178a61; color: #fff; }
    .rp-btn-add-naam { background: #e5484d; color: #fff; }
    .rp-btn-add-naam:hover { background: #c93b40; color: #fff; }

    .rp-legend { font-size: 11px; font-weight: 700; color: #8190a5; margin: 0 0 14px; }
    .rp-src { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 900; padding: 2px 8px; border-radius: 20px; }
    .rp-src-web { background: #e6f0fb; color: #1769c2; }
    .rp-src-app { background: #e8f7ee; color: #1f9d70; }

    .rp-atts { display: inline-flex; align-items: center; gap: 5px; }
    .rp-att { font-size: 13px; }
    .rp-att-photo { color: #1769c2; }
    .rp-att-voice { color: #e0a800; }
    .rp-att-video { color: #e5484d; }

    .rp-board { background: #fff; border: 1px solid #dce6f2; border-radius: 12px; box-shadow: 0 14px 34px rgba(24, 36, 60, .08); padding: 22px; }
    .rp-board-date { text-align: center; font-size: 14px; font-weight: 800; color: #516174; margin-bottom: 18px; }
    .rp-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .rp-col { border: 1px solid #e7eef6; border-radius: 10px; background: #fbfdff; padding: 14px; }
    .rp-col-head { display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 17px; font-weight: 900; margin: 0 0 14px; padding-bottom: 10px; border-bottom: 2px solid #edf2f7; }
    .rp-col-jama .rp-col-head { color: #1f9d70; }
    .rp-col-naam .rp-col-head { color: #e5484d; }

    .rp-row {
        display: grid; grid-template-columns: auto auto auto 1fr auto; gap: 11px; align-items: center;
        background: #fff; border: 1px solid #e7eef6; border-radius: 11px; padding: 11px 13px; margin-bottom: 9px;
        cursor: pointer; position: relative;
        transition: box-shadow .16s ease, border-color .16s ease, transform .12s ease, opacity .15s ease;
    }
    .rp-row::before { content: ""; position: absolute; left: 0; top: 8px; bottom: 8px; width: 3px; border-radius: 3px; background: #cfe0f3; transition: background .16s ease; }
    .rp-col-jama .rp-row::before { background: #9bdcbf; }
    .rp-col-naam .rp-row::before { background: #f2b3b5; }
    .rp-row:hover { border-color: #1769c2; box-shadow: 0 6px 18px rgba(23, 105, 194, .12); transform: translateY(-1px); }
    .rp-col-jama .rp-row:hover::before { background: #1f9d70; }
    .rp-col-naam .rp-row:hover::before { background: #e5484d; }
    .rp-row.rp-dragging { opacity: .45; border-style: dashed; }
    .rp-drag { color: #c2cddb; cursor: grab; font-size: 14px; display: inline-flex; align-items: center; }
    .rp-drag:active { cursor: grabbing; }
    .rp-row:hover .rp-drag { color: #6b7a90; }

    .rp-avatar { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-weight: 900; font-size: 15px; box-shadow: 0 3px 8px rgba(24,36,60,.18); }
    .rp-amtwrap { display: flex; flex-direction: column; align-items: flex-start; line-height: 1.15; min-width: 92px; }
    .rp-group-title { display: inline-flex; align-items: center; gap: 8px; }
    .rp-amt { font-weight: 900; font-size: 15px; color: #18243c; white-space: nowrap; }
    .rp-dir { margin-top: 3px; font-size: 9.5px; font-weight: 900; text-transform: uppercase; letter-spacing: .4px; }
    .rp-dir-jama { color: #1f9d70; }
    .rp-dir-naam { color: #e5484d; }
    .rp-mid { min-width: 0; text-align: left; }
    .rp-name { font-weight: 800; font-size: 13px; color: #26374f; word-break: break-word; display: flex; align-items: baseline; gap: 7px; flex-wrap: wrap; }
    .rp-txn { font-size: 9.5px; font-weight: 800; color: #9aa8bd; letter-spacing: .4px; }
    .rp-sub { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 6px; }
    .rp-remark { font-size: 11px; color: #94a3b8; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; }
    .rp-by { display: inline-flex; align-items: center; gap: 3px; font-size: 10.5px; font-weight: 800; color: #6b7a90; background: #eef1f5; padding: 2px 7px; border-radius: 20px; white-space: nowrap; }
    .rp-on { display: inline-flex; align-items: center; gap: 3px; font-size: 10.5px; font-weight: 800; color: #8a6d3b; background: #fbf3e2; padding: 2px 7px; border-radius: 20px; white-space: nowrap; }
    .rp-pay { display: inline-flex; align-items: center; gap: 3px; font-size: 10.5px; font-weight: 800; color: #155e9c; background: #e6f0fb; border: 1px solid #cfe1f6; padding: 2px 7px; border-radius: 20px; white-space: nowrap; }
    .rp-chip { display: inline-flex; align-items: center; gap: 3px; font-size: 10.5px; font-weight: 800; color: #516174; background: #eef1f5; padding: 2px 7px; border-radius: 20px; white-space: nowrap; }
    .rp-restored { display: inline-flex; align-items: center; gap: 3px; font-size: 10.5px; font-weight: 800; color: #b26a00; background: #fff2df; border: 1px solid #ffdca8; padding: 2px 7px; border-radius: 20px; white-space: nowrap; }
    .rp-fresh { display: inline-flex; align-items: center; gap: 3px; font-size: 10.5px; font-weight: 800; color: #1f9d70; background: #e7f7ef; border: 1px solid #b6e6cd; padding: 2px 7px; border-radius: 20px; white-space: nowrap; }
    .rp-acts { display: inline-flex; gap: 5px; }
    .rp-act { width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: #fff !important; font-size: 12px; transition: transform .14s ease, box-shadow .14s ease, filter .14s ease; }
    .rp-act:hover { transform: translateY(-2px); box-shadow: 0 5px 12px rgba(24,36,60,.2); filter: saturate(1.1); }
    .rp-act-view { background: #1769c2; }
    .rp-act-edit { background: #6b7a90; }
    .rp-act-del { background: #e5484d; }
    @media (max-width: 560px) {
        .rp-row { grid-template-columns: auto auto 1fr; }
        .rp-amtwrap { grid-column: 2 / -1; flex-direction: row; align-items: baseline; gap: 8px; }
        .rp-acts { grid-column: 1 / -1; justify-content: flex-end; }
    }

    /* Opening balance (आगे लाया) carry-forward row */
    .rp-row-open { grid-template-columns: auto 1fr auto; cursor: default; background: #fff8e6; border-color: #f3d98a; }
    .rp-row-open:hover { border-color: #e0a800; box-shadow: none; }
    .rp-row-open .rp-amt { color: #a86a00; }
    .rp-open-tag { display: inline-block; font-size: 10.5px; font-weight: 900; color: #a86a00; background: #fdecc4; padding: 2px 8px; border-radius: 20px; }
    .rp-balance-note { display: block; font-size: 11px; font-weight: 700; opacity: .9; margin-top: 4px; }

    /* Entry groups (Bills / Shri Rokad Nagad Cash / Bank / Cash) */
    .rp-group { margin-bottom: 12px; }
    .rp-group:last-child { margin-bottom: 0; }
    .rp-group-head {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .3px;
        color: #516174; padding: 6px 10px; margin-bottom: 8px; border-radius: 7px; background: #f2f6fb;
    }
    .rp-group-bills .rp-group-head { color: #8a5a00; background: #fdf3dc; }
    .rp-group-nagad .rp-group-head { color: #7a3ea8; background: #f2e9fb; }
    .rp-group-bank  .rp-group-head { color: #155e9c; background: #e6f0fb; }
    .rp-group-cash  .rp-group-head { color: #266a4c; background: #e9f7ef; }
    .rp-group-head .rp-group-sub { font-size: 12px; font-weight: 900; color: #26374f; }
    .rp-group-bills .rp-group-head .rp-group-sub { color: #8a5a00; }
    .rp-group-nagad .rp-group-head .rp-group-sub { color: #7a3ea8; }
    .rp-group-bank  .rp-group-head .rp-group-sub { color: #155e9c; }
    .rp-group-cash  .rp-group-head .rp-group-sub { color: #266a4c; }
    .rp-group-body { min-height: 20px; }
    .rp-group-empty { font-size: 12px; font-weight: 700; color: #b3c0d1; padding: 12px 10px; font-style: italic; text-align: center; border: 1px dashed #dce6f2; border-radius: 8px; }
    /* Active drop target while dragging */
    .rp-group.rp-drop-hot { outline: 2px dashed #1f9d70; outline-offset: 2px; border-radius: 10px; }
    .rp-group.rp-drop-hot .rp-group-empty { border-color: #1f9d70; color: #1f9d70; background: #f2fbf6; }

    .rp-total { margin-top: 12px; padding: 12px; border-radius: 9px; text-align: center; font-weight: 900; background: #eef6ff; color: #1769c2; }
    .rp-total small { display: block; font-size: 10px; font-weight: 800; color: #8190a5; text-transform: uppercase; }
    .rp-balance { margin: 22px auto 0; max-width: 420px; padding: 16px; text-align: center; font-size: 20px; font-weight: 900; color: #fff; border-radius: 12px; background: linear-gradient(135deg, #1f9d70, #178a61); box-shadow: 0 10px 24px rgba(31, 157, 112, .25); }

    .rp-empty { text-align: center; padding: 50px 20px; }
    .rp-empty h4 { color: #e5484d; font-weight: 900; }

    /* Detail modal (animated) */
    .rk-overlay {
        position: fixed; inset: 0; background: rgba(15, 23, 42, .55); z-index: 12000;
        display: flex; align-items: flex-start; justify-content: center; padding: 40px 14px; overflow-y: auto;
        opacity: 0; visibility: hidden; transition: opacity .25s ease, visibility .25s ease;
    }
    .rk-overlay.show { opacity: 1; visibility: visible; }
    .rk-modal {
        width: 560px; max-width: 100%; background: #fff; border-radius: 12px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .35); overflow: hidden;
        transform: translateY(-22px) scale(.96); opacity: 0;
        transition: transform .3s cubic-bezier(.2, .8, .2, 1), opacity .3s ease;
    }
    .rk-overlay.show .rk-modal { transform: translateY(0) scale(1); opacity: 1; }

    /* Delete-reason dialog */
    .rk-del-body { padding: 16px 18px; }
    .rk-del-body label { font-size: 12px; font-weight: 900; color: #516174; text-transform: uppercase; }
    .rk-del-body textarea { width: 100%; min-height: 90px; border: 1px solid #dce6f2; border-radius: 8px; padding: 10px; font-size: 13px; font-weight: 600; resize: vertical; }
    .rk-del-body textarea.is-error { border-color: #e5484d; box-shadow: 0 0 0 3px rgba(229, 72, 77, .14); }
    .rk-del-err { color: #e5484d; font-size: 12px; font-weight: 800; margin-top: 6px; display: none; }
    .rk-del-foot { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 18px 16px; }
    .rk-del-foot .btn { border: 0; border-radius: 8px; font-weight: 800; padding: 9px 16px; }
    .rk-del-cancel { background: #eef3fa; color: #26374f; }
    .rk-del-confirm { background: #e5484d; color: #fff; }
    .rk-del-confirm:disabled { opacity: .6; cursor: not-allowed; }
    .rk-head { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; background: #18243c; color: #fff; }
    .rk-head h5 { margin: 0; font-size: 15px; font-weight: 900; }
    .rk-close { background: transparent; border: 0; color: #fff; font-size: 22px; line-height: 1; cursor: pointer; }
    .rk-body { padding: 16px 18px; max-height: 70vh; overflow-y: auto; }
    .rk-grid { display: grid; grid-template-columns: 130px 1fr; gap: 0; border: 1px solid #edf2f7; border-radius: 8px; overflow: hidden; }
    .rk-grid .rk-k, .rk-grid .rk-v { padding: 9px 12px; font-size: 13px; border-bottom: 1px solid #edf2f7; }
    .rk-grid .rk-k { background: #f7fafc; color: #718096; font-weight: 800; }
    .rk-grid .rk-v { color: #26374f; font-weight: 700; word-break: break-word; }
    .rk-media-wrap { margin-top: 14px; display: grid; gap: 12px; }
    .rk-media label { display: block; font-size: 11px; font-weight: 900; text-transform: uppercase; color: #718096; margin-bottom: 6px; }
    .rk-media img { max-width: 100%; border-radius: 8px; border: 1px solid #e7eef6; }
    .rk-media audio, .rk-media video { width: 100%; }
    .rk-media video { border-radius: 8px; background: #000; }

    /* Hover tooltip (dark quick-preview card) */
    .rk-tip {
        position: fixed; z-index: 13000; width: 268px; max-width: calc(100vw - 24px);
        background: #131f33; color: #eaf0f8; border: 1px solid #2a3b57; border-radius: 12px;
        box-shadow: 0 18px 44px rgba(8, 14, 26, .5); padding: 13px 14px;
        font-size: 12px; line-height: 1.45; pointer-events: none;
        opacity: 0; transform: translateY(6px) scale(.98); transition: opacity .16s ease, transform .16s ease;
    }
    .rk-tip.show { opacity: 1; transform: translateY(0) scale(1); }
    .rk-tip::after {
        content: ''; position: absolute; width: 11px; height: 11px; background: #131f33;
        border-right: 1px solid #2a3b57; border-bottom: 1px solid #2a3b57;
    }
    .rk-tip[data-place="top"]::after { left: var(--tip-ax, 24px); bottom: -6px; transform: rotate(45deg); }
    .rk-tip[data-place="bottom"]::after { left: var(--tip-ax, 24px); top: -6px; transform: rotate(-135deg); }
    .rk-tip-head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
    .rk-tip-name { font-weight: 900; font-size: 13px; color: #fff; word-break: break-word; }
    .rk-tip-txn { flex: none; font-size: 10.5px; font-weight: 800; color: #7d8ea8; letter-spacing: .3px; }
    .rk-tip-amt { margin-top: 7px; font-size: 17px; font-weight: 900; letter-spacing: .2px; }
    .rk-tip-amt.jama { color: #43d18b; }
    .rk-tip-amt.naam { color: #ff6b6f; }
    .rk-tip-amt small { font-size: 11px; font-weight: 800; color: #93a2ba; margin-left: 6px; }
    .rk-tip-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 9px; }
    .rk-tip-tag { font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 20px; background: #243349; color: #b9c7dc; }
    .rk-tip-tag.web { background: #16324f; color: #7cc0ff; }
    .rk-tip-tag.app { background: #163d2c; color: #6fe0a6; }
    .rk-tip-meta { display: flex; align-items: center; gap: 6px; margin-top: 10px; color: #aeb9cb; font-size: 11px; font-weight: 700; }
    .rk-tip-meta i { color: #7d8ea8; }
    .rk-tip-restored { display: flex; align-items: center; gap: 6px; margin-top: 8px; color: #ffcf6b; font-size: 11px; font-weight: 800; }
    .rk-tip-restored i { color: #ffcf6b; }
    .rk-tip-restored span { color: #b89452; font-weight: 700; }
    .rk-tip-fresh { display: flex; align-items: center; gap: 5px; margin-top: 8px; color: #5fe0a6; font-size: 11px; font-weight: 800; }
    .rk-tip-fresh span { color: #7d8ea8; font-weight: 700; }
    .rk-tip-remark { margin-top: 7px; color: #9fb0c6; font-size: 11px; font-weight: 600; font-style: italic; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .rk-tip-foot { margin-top: 10px; padding-top: 9px; border-top: 1px solid #26374f; color: #6e7f99; font-size: 10.5px; font-weight: 700; }
    .rk-tip-foot i { margin-right: 4px; }

    @media (max-width: 767px) {
        .rp-page { padding: 14px; }
        .rp-cols { grid-template-columns: 1fr; }
        .rp-hero { flex-direction: column; align-items: stretch; }
        .rk-tip { display: none !important; } /* touch devices: tap opens the full modal */
    }
</style>

<main class="main-content bgc-grey-100 rp-page">
    <div id="mainContent">
        <div class="rp-shell">

            <?= form_open_multipart('', ['id' => 'teamForm']); ?>
            <section class="rp-hero">
                <div>
                    <h4 class="rp-title">रोकड़ पर्चा <small>Rokad Parcha &mdash; daily cash entries</small></h4>
                </div>
                <div class="rp-controls">
                    <div class="rp-field">
                        <label>Date</label>
                        <?= form_input([
                            'id' => 'myInput', 'name' => 'search_name', 'class' => 'form-control',
                            'maxlength' => 25, 'placeholder' => 'Select date...', 'value' => $dateValue, 'autocomplete' => 'off'
                        ]); ?>
                    </div>
                    <button type="button" onclick="callInto('prev')" class="btn rp-btn rp-btn-nav"><i class="ti-angle-left"></i> Prev</button>
                    <button type="submit" id="search" class="btn rp-btn rp-btn-search"><i class="ti-search"></i> Search</button>
                    <button type="button" onclick="callInto('next')" class="btn rp-btn rp-btn-nav">Next <i class="ti-angle-right"></i></button>
                    <?php if (!empty($jama) || !empty($naam) || $rp_has_open): ?>
                        <button type="button" onclick="printData()" class="btn rp-btn rp-btn-print"><i class="ti-printer"></i> PDF / Print</button>
                    <?php endif; ?>
                    <?php $rp_add_date = $old_date ? urlencode(date('d-m-Y', strtotime($old_date))) : ''; ?>
                    <a href="<?php echo base_url('admin/account/deposite') . ($rp_add_date ? '?d=' . $rp_add_date : ''); ?>" class="btn rp-btn rp-btn-add-jama"><i class="ti-plus"></i> Add जमा</a>
                    <a href="<?php echo base_url('admin/account/expenditure') . ($rp_add_date ? '?d=' . $rp_add_date : ''); ?>" class="btn rp-btn rp-btn-add-naam"><i class="ti-plus"></i> Add नाम</a>
                    <a href="<?php echo base_url('admin/report/deleted_entries'); ?>" class="btn rp-btn rp-btn-nav"><i class="ti-trash"></i> Deleted Entries</a>
                </div>
            </section>
            <?= form_close(); ?>

            <p class="rp-legend">Source: <?= rp_source_badge('Web'); ?> entered from web panel &nbsp; <?= rp_source_badge('App'); ?> entered from mobile app &middot; click any entry to view full details.</p>

            <?php if (!empty($jama) || !empty($naam) || $rp_has_open): ?>
                <div id="printTable" class="rp-board">
                    <div class="rp-board-date">दिनांक: <?= date('d-m-Y', strtotime($old_date)); ?></div>

                    <div class="rp-cols">
                        <!-- जमा (deposit) -->
                        <div class="rp-col rp-col-jama">
                            <h6 class="rp-col-head"><i class="ti-download"></i> जमा</h6>
                            <?php $total_jama = 0; ?>
                            <?php if ($rp_open_jama > 0): $total_jama += $rp_open_jama; ?>
                                <div class="rp-row rp-row-open" title="Balance carried forward from the previous date">
                                    <span class="rp-amt"><?= number_format($rp_open_jama, 2) ?></span>
                                    <div class="rp-mid">
                                        <div class="rp-name"><?= htmlspecialchars($rp_cash_label) ?></div>
                                        <div class="rp-sub"><span class="rp-open-tag">आगे लाया &middot; Opening Balance</span></div>
                                    </div>
                                    <span class="rp-acts"></span>
                                </div>
                            <?php endif; ?>
                            <?php foreach ($rp_group_defs as $gk => $gt) { echo $rp_group($gk, $gt, $jama_g[$gk], $total_jama, 'jama'); } ?>
                            <div class="rp-total"><small>Total Jama</small><?= number_format($total_jama, 2) ?></div>
                        </div>

                        <!-- नाम (expenses) -->
                        <div class="rp-col rp-col-naam">
                            <h6 class="rp-col-head"><i class="ti-upload"></i> नाम</h6>
                            <?php $total_name = 0; ?>
                            <?php if ($rp_open_naam > 0): $total_name += $rp_open_naam; ?>
                                <div class="rp-row rp-row-open" title="Balance carried forward from the previous date">
                                    <span class="rp-amt"><?= number_format($rp_open_naam, 2) ?></span>
                                    <div class="rp-mid">
                                        <div class="rp-name"><?= htmlspecialchars($rp_cash_label) ?></div>
                                        <div class="rp-sub"><span class="rp-open-tag">आगे लाया &middot; Opening Balance</span></div>
                                    </div>
                                    <span class="rp-acts"></span>
                                </div>
                            <?php endif; ?>
                            <?php foreach ($rp_group_defs_naam as $gk => $gt) { echo $rp_group($gk, $gt, $naam_g[$gk], $total_name, 'naam'); } ?>
                            <div class="rp-total"><small>Total Naam</small><?= number_format($total_name, 2) ?></div>
                        </div>
                    </div>

                    <div class="rp-balance">शेष रोकड़ पर्चा : <?= number_format($total_jama - $total_name, 2) ?>
                        <small class="rp-balance-note">अगले दिन आगे ले जाया जाएगा &middot; carried forward to next date</small>
                    </div>
                </div>

                <!-- Print/PDF-only ledger (plain B&W register). Hidden on screen. -->
                <?php
                    // ---- figures for the print/PDF header, summary & footer ----
                    $rp_cnt_jama = ($rp_open_jama > 0 ? 1 : 0);
                    foreach ($jama_g as $rp_gg) { $rp_cnt_jama += count($rp_gg); }
                    $rp_cnt_naam = ($rp_open_naam > 0 ? 1 : 0);
                    foreach ($naam_g as $rp_gg) { $rp_cnt_naam += count($rp_gg); }
                    $rp_closing  = $total_jama - $total_name;
                    $rp_open_net = $rp_open_jama - $rp_open_naam;
                    // Media attachment tally across both sides.
                    $rp_media = array('img' => 0, 'aud' => 0, 'vid' => 0, 'entries' => 0);
                    $rp_tally = function ($groups) use (&$rp_media) {
                        foreach ($groups as $rp_grp_rows) {
                            foreach ($rp_grp_rows as $rp_row) {
                                $rp_has = false;
                                if (!empty($rp_row->image_path))      { $rp_media['img']++; $rp_has = true; }
                                if (!empty($rp_row->voice_note_path)) { $rp_media['aud']++; $rp_has = true; }
                                if (!empty($rp_row->video_note_path)) { $rp_media['vid']++; $rp_has = true; }
                                if ($rp_has) { $rp_media['entries']++; }
                            }
                        }
                    };
                    $rp_tally($jama_g); $rp_tally($naam_g);
                    $rp_media_total = $rp_media['img'] + $rp_media['aud'] + $rp_media['vid'];
                    $rp_p_fy     = @fy()->FY;
                    $rp_p_prod   = @fy()->template_name;
                    $rp_p_fid    = @fy()->template_id;
                    $rp_p_user   = '';
                    if (function_exists('currentuserinfo')) { $rp_uu = @currentuserinfo(); if ($rp_uu && isset($rp_uu->name)) { $rp_p_user = $rp_uu->name; } }
                    $rp_wk_en = date('l', strtotime($old_date));
                ?>
                <div id="printLedger" style="display:none;">
                  <div class="pl-doc">
                    <table class="pl-head"><tr>
                        <td class="pl-head-l">
                            <div class="pl-firm"><?= htmlspecialchars(@fy()->firm_name ?: 'Firm') ?></div>
                            <div class="pl-firm-sub"><?= htmlspecialchars($rp_p_prod ?: '') ?><?= $rp_p_fy ? ' &nbsp;&bull;&nbsp; FY ' . htmlspecialchars($rp_p_fy) : '' ?><?= $rp_p_fid ? ' &nbsp;&bull;&nbsp; Firm ID ' . htmlspecialchars($rp_p_fid) : '' ?></div>
                        </td>
                        <td class="pl-head-r">
                            <div class="pl-doclabel">रोकड़ पर्चा</div>
                            <div class="pl-doclabel-en">Daily Cash Book</div>
                        </td>
                    </tr></table>
                    <div class="pl-daterow">
                        <span><b>Date :</b> <?= date('d-m-Y', strtotime($old_date)); ?> &nbsp;(<?= $rp_wk_en ?>)</span>
                        <span><b>Total Entries :</b> <?= (int) ($rp_cnt_jama + $rp_cnt_naam) ?> &nbsp;(<?= (int) $rp_cnt_jama ?> जमा / <?= (int) $rp_cnt_naam ?> नाम)</span>
                    </div>
                    <table class="pl-sum"><tr>
                        <td><span>Opening Balance</span><b><?= number_format($rp_open_net, 2) ?></b></td>
                        <td class="pos"><span>Total Receipts (जमा)</span><b><?= number_format($total_jama, 2) ?></b></td>
                        <td class="neg"><span>Total Payments (नाम)</span><b><?= number_format($total_name, 2) ?></b></td>
                        <td class="bal"><span>Closing Balance</span><b>&#8377; <?= number_format($rp_closing, 2) ?></b></td>
                    </tr></table>
                    <table class="led-wrap">
                        <colgroup><col style="width:50%"><col style="width:50%"></colgroup>
                        <tbody><tr>
                            <td>
                                <table class="led">
                                    <colgroup><col style="width:8%"><col style="width:13%"><col style="width:55%"><col style="width:24%"></colgroup>
                                    <thead>
                                        <tr><th colspan="4" class="led-side-head led-jama">&#9660; जमा (Receipts)</th></tr>
                                        <tr><th>#</th><th>ID</th><th>Particulars</th><th class="amt">Amount (&#8377;)</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php $sn = 0; ?>
                                        <?php if ($rp_open_jama > 0): $sn++; ?>
                                            <tr>
                                                <td class="c"><?= $sn ?></td>
                                                <td class="c">—</td>
                                                <td><?= htmlspecialchars($rp_cash_label) ?> <span class="rmk">&mdash; आगे लाया (Opening Balance)</span></td>
                                                <td class="amt"><?= number_format($rp_open_jama, 2) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($rp_group_defs as $gk => $gt) { echo $rp_led_group($gt, $jama_g[$gk], $sn); } ?>
                                        <tr class="total"><td colspan="3">Total जमा</td><td class="amt"><?= number_format($total_jama, 2) ?></td></tr>
                                    </tbody>
                                </table>
                            </td>
                            <td>
                                <table class="led">
                                    <colgroup><col style="width:8%"><col style="width:13%"><col style="width:55%"><col style="width:24%"></colgroup>
                                    <thead>
                                        <tr><th colspan="4" class="led-side-head led-naam">&#9650; नाम (Payments)</th></tr>
                                        <tr><th>#</th><th>ID</th><th>Particulars</th><th class="amt">Amount (&#8377;)</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php $sn = 0; ?>
                                        <?php if ($rp_open_naam > 0): $sn++; ?>
                                            <tr>
                                                <td class="c"><?= $sn ?></td>
                                                <td class="c">—</td>
                                                <td><?= htmlspecialchars($rp_cash_label) ?> <span class="rmk">&mdash; आगे लाया (Opening Balance)</span></td>
                                                <td class="amt"><?= number_format($rp_open_naam, 2) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($rp_group_defs_naam as $gk => $gt) { echo $rp_led_group($gt, $naam_g[$gk], $sn); } ?>
                                        <tr class="total"><td colspan="3">Total नाम</td><td class="amt"><?= number_format($total_name, 2) ?></td></tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr></tbody>
                    </table>
                    <div class="led-balance">शेष रोकड़ पर्चा (Closing Balance) : &#8377; <?= number_format($rp_closing, 2) ?></div>
                    <table class="pl-media"><tr>
                        <td class="pl-media-h">Media Attachments</td>
                        <td class="pl-att-img">&#9679; <?= (int) $rp_media['img'] ?> Photos</td>
                        <td class="pl-att-aud">&#9679; <?= (int) $rp_media['aud'] ?> Voice</td>
                        <td class="pl-att-vid">&#9679; <?= (int) $rp_media['vid'] ?> Video</td>
                        <td class="pl-media-tot"><?= (int) $rp_media_total ?> file(s) across <?= (int) $rp_media['entries'] ?> entr<?= $rp_media['entries'] == 1 ? 'y' : 'ies' ?></td>
                    </tr></table>
                    <div class="pl-legend">Attachment key: <span class="pl-att-img">&#9679;IMG</span> Photo &nbsp; <span class="pl-att-aud">&#9679;AUD</span> Voice &nbsp; <span class="pl-att-vid">&#9679;VID</span> Video &nbsp;&bull;&nbsp; all amounts in &#8377; &nbsp;&bull;&nbsp; ID = entry number</div>
                    <table class="pl-sign"><tr>
                        <td>Prepared by<?= $rp_p_user ? '<div class="pl-sign-n">' . htmlspecialchars($rp_p_user) . '</div>' : '' ?></td>
                        <td>Checked by</td>
                        <td>Authorised Signatory</td>
                    </tr></table>
                    <div class="pl-gen">Generated on <?= date('d-m-Y H:i'); ?><?= $rp_p_user ? ' by ' . htmlspecialchars($rp_p_user) : '' ?> &nbsp;&bull;&nbsp; C R Industries ERP</div>
                  </div>
                </div>
            <?php else: ?>
                <div class="rp-board rp-empty">
                    <h4>रोकड़ पर्चा अभी उपलब्ध नहीं है</h4>
                    <p class="text-muted">इस तारीख के लिए कोई एंट्री नहीं मिली। कृपया दूसरी तारीख चुनें।</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<!-- Hover quick-preview tooltip -->
<div class="rk-tip" id="rokadTip" aria-hidden="true"></div>

<style>
    .rk-trail-wrap { margin-top: 14px; border-top: 1px dashed #dbe4f0; padding-top: 12px; }
    .rk-trail-h { font-size: 13px; font-weight: 900; color: #0f172a; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .rk-trail-actions { display: flex; gap: 6px; }
    .rk-trail-btn { border: 1px solid #dce6f2; background: #f1f5f9; color: #334155; font-size: 11px; font-weight: 800; padding: 5px 11px; border-radius: 7px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }
    .rk-trail-btn:hover { background: #e6edf6; }
    .rk-trail-btn-pdf { background: #b42318; border-color: #b42318; color: #fff; }
    .rk-trail-btn-pdf:hover { background: #97180f; }
    .rk-trail-h small { font-weight: 700; color: #94a3b8; }
    .rk-trail { position: relative; }
    .rk-t-item { position: relative; padding: 0 0 12px 22px; }
    .rk-t-item:not(:last-child)::before { content: ""; position: absolute; left: 6px; top: 14px; bottom: -2px; width: 2px; background: #e6ecf5; }
    .rk-t-dot { position: absolute; left: 0; top: 4px; width: 14px; height: 14px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 1px #dbe4f0; }
    .rk-t-dot.rk-t-create { background: #16a34a; } .rk-t-dot.rk-t-update { background: #2563eb; } .rk-t-dot.rk-t-delete { background: #e5484d; } .rk-t-dot.rk-t-other { background: #94a3b8; }
    .rk-t-card { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 10px; padding: 9px 12px; }
    .rk-t-top { font-size: 13px; color: #1f2937; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .rk-t-act { font-size: 10px; font-weight: 900; padding: 2px 8px; border-radius: 999px; color: #fff; }
    .rk-t-act.rk-t-create { background: #16a34a; } .rk-t-act.rk-t-update { background: #2563eb; } .rk-t-act.rk-t-delete { background: #e5484d; } .rk-t-act.rk-t-other { background: #94a3b8; }
    .rk-t-src { font-size: 11px; color: #64748b; font-weight: 700; }
    .rk-t-meta { display: flex; flex-wrap: wrap; gap: 6px 14px; margin-top: 5px; font-size: 12px; color: #475569; }
    .rk-t-meta i { color: #94a3b8; margin-right: 2px; }
    .rk-t-ver { font-size: 9px; font-weight: 800; background: #e0e7ff; color: #4338ca; padding: 1px 5px; border-radius: 4px; }
    .rk-t-isp { color: #94a3b8; }
    .rk-t-when { margin-top: 4px; font-size: 11px; color: #94a3b8; font-weight: 700; }
    .rk-t-chg { margin-top: 7px; padding: 7px 9px; background: #fff; border: 1px solid #e6ecf5; border-left: 3px solid #2563eb; border-radius: 6px; }
    .rk-t-chg-h { font-size: 10px; font-weight: 800; color: #2563eb; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 4px; }
    .rk-t-chg-row { display: flex; align-items: baseline; flex-wrap: wrap; gap: 6px; font-size: 12px; padding: 2px 0; }
    .rk-t-fld { min-width: 92px; font-weight: 800; color: #334155; }
    .rk-t-old { color: #b42318; text-decoration: line-through; background: #fdeeec; padding: 0 5px; border-radius: 3px; }
    .rk-t-arw { color: #94a3b8; font-weight: 700; }
    .rk-t-new { color: #127a34; background: #e9f7ef; padding: 0 5px; border-radius: 3px; font-weight: 700; }
</style>

<!-- Detail popup -->
<div class="rk-overlay" id="rokadModal">
    <div class="rk-modal">
        <div class="rk-head">
            <h5>Entry Details</h5>
            <button type="button" class="rk-close" onclick="closeRokad()">&times;</button>
        </div>
        <div class="rk-body" id="rkBody"></div>
    </div>
</div>

<!-- Delete reason dialog -->
<div class="rk-overlay" id="rokadDelModal">
    <div class="rk-modal" style="width:440px;">
        <div class="rk-head" style="background:#e5484d;">
            <h5>Delete Entry</h5>
            <button type="button" class="rk-close" onclick="closeDelModal()">&times;</button>
        </div>
        <div class="rk-del-body">
            <p style="font-size:13px;color:#516174;font-weight:700;margin-bottom:10px;">This entry will be marked as deleted (soft delete). Please provide a reason.</p>
            <label for="rkDelReason">Delete Reason <span style="color:#e5484d;">*</span></label>
            <textarea id="rkDelReason" placeholder="Why is this entry being deleted?"></textarea>
            <div class="rk-del-err" id="rkDelErr">Delete reason is required.</div>
        </div>
        <div class="rk-del-foot">
            <button type="button" class="btn rk-del-cancel" onclick="closeDelModal()">Cancel</button>
            <button type="button" class="btn rk-del-confirm" id="rkDelConfirm" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

<script>
    var rokadDetails = <?= json_encode($rokad_details, JSON_UNESCAPED_UNICODE); ?>;
    var RK_BASE = "<?php echo base_url(); ?>";

    $(function () {
        var financial_year = "<?php echo fy()->FY; ?>";
        var fy = financial_year.split("-");
        var startYear = parseInt(fy[0]);
        var endYear = parseInt(fy[1]);
        $("#myInput").datepicker({
            dateFormat: "dd-mm-yy",
            minDate: new Date(startYear, 3, 1),
            maxDate: new Date(endYear, 2, 31)
        });
    });

    function callInto(direction) {
        var dateValue = $("#myInput").val();
        if (dateValue === '') { alert('Please select date first'); return; }
        var parts = dateValue.split('-');
        var currentDate = new Date(parts[2], parts[1] - 1, parts[0]);
        if (direction === 'prev') currentDate.setDate(currentDate.getDate() - 1);
        else if (direction === 'next') currentDate.setDate(currentDate.getDate() + 1);
        var day = String(currentDate.getDate()).padStart(2, '0');
        var month = String(currentDate.getMonth() + 1).padStart(2, '0');
        var year = currentDate.getFullYear();
        $("#myInput").val(day + '-' + month + '-' + year);
        $("#teamForm").submit();
    }

    /* ---------- drag & drop: move an entry into another group ---------- */
    function rpFmt(n) {
        return (parseFloat(n) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    var rpDragEl = null, rpPointerY = 0, rpScrollTimer = null;

    // Auto-scroll the page while dragging near the top/bottom viewport edge,
    // so a row can be dragged from the last group up to the first (and back).
    function rpTrackPointer(e) { rpPointerY = e.clientY; }
    function rpStartAutoScroll() {
        if (rpScrollTimer) return;
        document.addEventListener('dragover', rpTrackPointer);
        rpScrollTimer = setInterval(function () {
            var edge = 110, maxSpeed = 26, h = window.innerHeight;
            if (rpPointerY > 0 && rpPointerY < edge) {
                window.scrollBy(0, -Math.ceil(maxSpeed * (1 - rpPointerY / edge)));
            } else if (rpPointerY > h - edge) {
                window.scrollBy(0, Math.ceil(maxSpeed * (1 - (h - rpPointerY) / edge)));
            }
        }, 16);
    }
    function rpStopAutoScroll() {
        if (rpScrollTimer) { clearInterval(rpScrollTimer); rpScrollTimer = null; }
        document.removeEventListener('dragover', rpTrackPointer);
        rpPointerY = 0;
    }

    function rpDragStart(e) {
        rpDragEl = e.target.closest('.rp-row');
        if (!rpDragEl) return;
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', rpDragEl.getAttribute('data-id')); } catch (ex) {}
        var el = rpDragEl;
        setTimeout(function () { el.classList.add('rp-dragging'); }, 0);
        rpStartAutoScroll();
    }
    function rpDragEnd() {
        if (rpDragEl) rpDragEl.classList.remove('rp-dragging');
        document.querySelectorAll('.rp-group.rp-drop-hot').forEach(function (g) { g.classList.remove('rp-drop-hot'); });
        rpStopAutoScroll();
        rpDragEl = null;
    }
    // Only allow dropping within the SAME column (jama<->jama, naam<->naam).
    function rpSameSide(group) {
        return rpDragEl && group.getAttribute('data-side') === rpDragEl.getAttribute('data-side');
    }
    function rpDragOver(e) {
        var g = e.currentTarget;
        if (!rpSameSide(g)) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        g.classList.add('rp-drop-hot');
    }
    function rpDragLeave(e) {
        if (!e.currentTarget.contains(e.relatedTarget)) e.currentTarget.classList.remove('rp-drop-hot');
    }
    function rpDrop(e) {
        var g = e.currentTarget;
        g.classList.remove('rp-drop-hot');
        if (!rpDragEl || !rpSameSide(g)) return;
        e.preventDefault();

        var fromGroup = rpDragEl.closest('.rp-group');
        if (fromGroup === g) return;                       // dropped on same group

        var moved   = rpDragEl;
        var id      = moved.getAttribute('data-id');
        var group   = g.getAttribute('data-group');
        var srcBody = fromGroup.querySelector('.rp-group-body');
        var dstBody = g.querySelector('.rp-group-body');

        dstBody.appendChild(moved);
        rpRefreshGroup(g); rpRefreshGroup(fromGroup);

        $.ajax({
            url: "<?php echo base_url(); ?>admin/report/rokad_parcha_move",
            type: "POST", dataType: "json",
            data: { rokad_id: id, group: group },
            success: function (res) {
                if (res && res.status === 'success') {
                    var name = g.querySelector('.rp-group-title-text').textContent.trim();
                    if (window.showToast) showToast('Moved to ' + name, 'success');
                } else {
                    srcBody.appendChild(moved);            // revert
                    rpRefreshGroup(g); rpRefreshGroup(fromGroup);
                    if (window.showToast) showToast(res && res.msg ? res.msg : 'Could not move entry', 'error');
                    else alert(res && res.msg ? res.msg : 'Could not move entry');
                }
            },
            error: function () {
                srcBody.appendChild(moved);                // revert
                rpRefreshGroup(g); rpRefreshGroup(fromGroup);
                if (window.showToast) showToast('Error moving entry', 'error'); else alert('Error moving entry');
            }
        });
    }
    // Recompute a group's subtotal + toggle its empty/drop placeholder.
    function rpRefreshGroup(g) {
        if (!g) return;
        var body = g.querySelector('.rp-group-body');
        var rows = body.querySelectorAll('.rp-row');
        var sum = 0;
        rows.forEach(function (r) { sum += parseFloat(r.getAttribute('data-amount')) || 0; });
        var sub = g.querySelector('.rp-group-sub');
        if (sub) sub.textContent = rpFmt(sum);
        var ph = body.querySelector('.rp-group-empty');
        if (rows.length === 0) {
            if (!ph) {
                ph = document.createElement('div');
                ph.className = 'rp-group-empty';
                ph.innerHTML = 'यहाँ खींचें &middot; drop entries here';
                body.appendChild(ph);
            }
        } else if (ph) { ph.remove(); }
    }

    /* ---------- detail popup ---------- */
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function fmtAmount(a) {
        var n = parseFloat(a) || 0;
        return n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function rkRow(k, v) { return '<div class="rk-k">' + k + '</div><div class="rk-v">' + v + '</div>'; }

    function showDetail(id) {
        var d = rokadDetails[id];
        if (!d) { return; }
        var isWeb = (String(d.source).toLowerCase() === 'web' || !d.source);
        var srcBadge = isWeb
            ? '<span class="rp-src rp-src-web"><i class="ti-desktop"></i> Web</span>'
            : '<span class="rp-src rp-src-app"><i class="ti-mobile"></i> App</span>';

        var g = '';
        g += rkRow('Type', esc(d.side));
        g += rkRow('Date', esc(d.date));
        g += rkRow('Amount', '&#8377; ' + fmtAmount(d.amount));
        g += rkRow('Party', esc(d.party) + (d.account_no ? ' <small>(A/c ' + esc(d.account_no) + ')</small>' : ''));
        if (d.account_name) g += rkRow('Account', esc(d.account_name));
        g += rkRow('Account Type', esc(d.type));
        g += rkRow('Source', srcBadge);
        if (d.added_by) g += rkRow('Added By', esc(d.added_by));
        if (d.added_on) g += rkRow('Added On', esc(d.added_on));
        if (d.truck_no) g += rkRow('Truck No', esc(d.truck_no));
        if (d.quantity) g += rkRow('Quantity', esc(d.quantity));
        if (d.rate) g += rkRow('Rate', esc(d.rate));
        if (d.challan_no) g += rkRow('Challan No', esc(d.challan_no));
        g += rkRow('Remark', d.remark ? esc(d.remark) : '<span class="text-muted">No remark</span>');
        g += rkRow('IP Address', d.ip ? esc(d.ip) : '<span class="text-muted">Not captured</span>');
        if (d.lat && d.lng) {
            g += rkRow('Location', '<a href="https://www.google.com/maps?q=' + encodeURIComponent(d.lat + ',' + d.lng) + '" target="_blank" rel="noopener"><i class="ti-location-pin"></i> ' + esc(d.lat) + ', ' + esc(d.lng) + '</a>');
        } else {
            g += rkRow('Location', '<span class="text-muted">Not captured</span>');
        }

        var m = '';
        if (d.image) m += '<div class="rk-media"><label>Image</label><a href="' + d.image + '" target="_blank"><img src="' + d.image + '" alt="image"></a></div>';
        if (d.voice) m += '<div class="rk-media"><label>Voice Recording</label><audio controls preload="none" src="' + d.voice + '"></audio></div>';
        if (d.video) m += '<div class="rk-media"><label>Video Recording</label><video controls preload="none" src="' + d.video + '"></video></div>';
        if (!m) m = '<div class="text-muted" style="font-size:12px;">No attachments for this entry.</div>';

        document.getElementById('rkBody').innerHTML =
            '<div class="rk-grid">' + g + '</div><div class="rk-media-wrap">' + m + '</div>' +
            '<div class="rk-trail-wrap"><div class="rk-trail-h">' +
            '<span><i class="ti-list-ol"></i> Full Audit Trail <small>(every access &amp; change)</small></span>' +
            '<span class="rk-trail-actions">' +
            '<button type="button" class="rk-trail-btn" onclick="printTrail()"><i class="ti-printer"></i> Print</button>' +
            '<button type="button" class="rk-trail-btn rk-trail-btn-pdf" onclick="pdfTrail()"><i class="ti-download"></i> PDF</button>' +
            '</span></div>' +
            '<div id="rkTrail" class="rk-trail"><div class="text-muted" style="font-size:12px;padding:10px 2px;">Loading trail&hellip;</div></div></div>';
        document.getElementById('rokadModal').classList.add('show');
        rkCurrentId = id;
        loadTrail(id);
    }

    var rkCurrentId = null, rkTrailData = [];

    // Open the server-generated PDF of this entry's audit trail (popup-safe).
    function pdfTrail() {
        if (!rkCurrentId) return;
        var a = document.createElement('a');
        a.href = RK_BASE + 'admin/report/entry_trail_pdf?rokad_id=' + encodeURIComponent(rkCurrentId);
        a.target = '_blank'; a.rel = 'noopener';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
    }

    // Print the audit trail in a clean, colourful window (client-side).
    function printTrail() {
        if (!rkCurrentId) return;
        var d = rokadDetails[rkCurrentId] || {};
        var data = rkTrailData || [];
        var actColor = function (a) { a = (a || '').toLowerCase(); return a === 'create' ? '#127a34' : a === 'update' ? '#1d4ed8' : a === 'delete' ? '#b42318' : '#64748b'; };
        var actBg = function (a) { a = (a || '').toLowerCase(); return a === 'create' ? '#e9f7ef' : a === 'update' ? '#eaf1fe' : a === 'delete' ? '#fdeeec' : '#f1f5f9'; };

        // Created / last-activity / counts.
        var createdOn = d.added_on || '—', createdBy = d.added_by || '—', lastAct = '—', nUpd = 0;
        data.forEach(function (t) {
            if ((t.action || '').toLowerCase() === 'create') { createdOn = t.when || createdOn; createdBy = t.user || createdBy; }
            if ((t.action || '').toLowerCase() === 'update') { nUpd++; }
            if (t.when) lastAct = t.when;
        });

        var events = data.map(function (t) {
            var col = actColor(t.action), bg = actBg(t.action);
            var loc = t.city || (t.lat && t.lng ? t.lat + ', ' + t.lng + ' (GPS)' : '');
            var meta = 'Source: ' + esc(t.source || 'Web') + (t.ip ? ' &nbsp;|&nbsp; IP: ' + esc(t.ip) + (t.ip_ver ? ' (IPv' + t.ip_ver + ')' : '') : '') + (loc ? ' &nbsp;|&nbsp; ' + esc(loc) : '') + (t.mac ? ' &nbsp;|&nbsp; MAC: ' + esc(t.mac) : '');
            var chg = '';
            if (t.changes && t.changes.length) {
                chg = '<table class="chg"><tr><th colspan="3">' + t.changes.length + ' field(s) changed</th></tr>'
                    + t.changes.map(function (c) { return '<tr><td class="cf">' + esc(c.field) + '</td><td class="co">' + esc(c.old !== '' ? c.old : '—') + '</td><td class="cn">&rarr; ' + esc(c.new !== '' ? c.new : '—') + '</td></tr>'; }).join('') + '</table>';
            }
            return '<div class="ev" style="border-left-color:' + col + ';background:' + bg + '">'
                + '<div class="ev-top"><span class="badge" style="background:' + col + '">' + esc((t.action || '').toUpperCase()) + '</span> <b>' + esc(t.user) + '</b>'
                + '<span class="ev-when">' + esc(t.when) + '</span></div>'
                + '<div class="meta">' + meta + '</div>' + chg + '</div>';
        }).join('');

        var w = window.open('', '_blank', 'height=880,width=1000');
        if (!w) { if (window.showToast) showToast('Popup blocked — allow popups to print.', 'error'); return; }
        w.document.write('<html><head><meta charset="utf-8"><title>Audit Trail #' + rkCurrentId + '</title><style>'
            + "@import url('https://fonts.googleapis.com/css2?family=Hind:wght@400;600;700&display=swap');"
            + '*{box-sizing:border-box}@page{size:A4;margin:12mm 0}'
            + 'body{font-family:Hind,Arial,sans-serif;color:#101828;font-size:11px;margin:0;padding:0 12mm;-webkit-print-color-adjust:exact;print-color-adjust:exact}'
            + '.band{background:linear-gradient(120deg,#12325b,#1a3f6b 60%,#2a2f6e);color:#fff;border-radius:8px;padding:12px 16px;display:flex;justify-content:space-between;align-items:center}'
            + '.band .t1{font-size:18px;font-weight:700}.band .t2{font-size:10px;color:#c7d6ea}.band .doc{font-size:9px;letter-spacing:1px;text-transform:uppercase;color:#c7d6ea;text-align:right}.band .idb{font-size:17px;font-weight:700;text-align:right}'
            + '.cards{display:flex;flex-wrap:wrap;gap:6px;margin:10px 0}'
            + '.card{flex:1;min-width:120px;border:1px solid #dbe4f0;background:#f6f9fc;border-radius:6px;padding:6px 9px}'
            + '.card .l{font-size:8px;text-transform:uppercase;letter-spacing:.3px;color:#6b7a90}.card .v{font-size:11px;font-weight:700;margin-top:1px}.card .v.amt{color:#127a34}'
            + '.sect{font-size:12px;font-weight:700;color:#1a3f6b;margin:12px 0 7px;border-bottom:1.5px solid #dbe4f0;padding-bottom:3px}'
            + '.ev{border:1px solid #e2e8f2;border-left:5px solid;border-radius:6px;padding:8px 11px;margin-bottom:7px;page-break-inside:avoid}'
            + '.ev-top{display:flex;align-items:center;gap:8px}.badge{color:#fff;font-size:9px;font-weight:700;padding:2px 9px;border-radius:9px}'
            + '.ev-when{margin-left:auto;font-size:10px;font-weight:700;color:#475569}'
            + '.meta{font-size:9px;color:#5b6b82;margin-top:4px}'
            + '.chg{width:100%;border-collapse:collapse;margin-top:6px;background:#fff;border-radius:5px;overflow:hidden}'
            + '.chg th{background:#eef2f8;color:#1d4ed8;font-size:8.5px;text-transform:uppercase;padding:3px 7px;text-align:left}'
            + '.chg td{font-size:10px;padding:3px 7px;border-top:1px solid #eef2f8}.chg .cf{font-weight:700;color:#334155;width:26%}.chg .co{color:#b42318;text-decoration:line-through;width:37%}.chg .cn{color:#127a34;font-weight:700}'
            + '.foot{margin-top:12px;font-size:8.5px;color:#666;text-align:center;border-top:1px solid #ddd;padding-top:5px}</style></head><body>'
            + '<div class="band"><div><div class="t1">' + esc(d.account_name || d.party || 'C R Industries') + '</div><div class="t2">Daily Cash Book &bull; Rokad Parcha</div></div>'
            + '<div><div class="doc">Entry Audit Trail</div><div class="idb">#' + rkCurrentId + '</div></div></div>'
            + '<div class="cards">'
            + '<div class="card"><div class="l">Party</div><div class="v">' + esc(d.party || '') + '</div></div>'
            + '<div class="card"><div class="l">Amount</div><div class="v amt">₹ ' + (d.amount ? fmtAmount(d.amount) : '—') + '</div></div>'
            + '<div class="card"><div class="l">Entry Date</div><div class="v">' + esc(d.date || '—') + '</div></div>'
            + '<div class="card"><div class="l">Created On</div><div class="v">' + esc(createdOn) + '</div></div>'
            + '<div class="card"><div class="l">Created By</div><div class="v">' + esc(createdBy) + '</div></div>'
            + '<div class="card"><div class="l">Last Activity</div><div class="v">' + esc(lastAct) + '</div></div>'
            + '<div class="card"><div class="l">Total Events</div><div class="v">' + data.length + ' (' + nUpd + ' upd)</div></div>'
            + '</div>'
            + '<div class="sect">Activity Timeline — oldest to newest</div>'
            + (events || '<div style="padding:14px;color:#888;text-align:center">No audit events.</div>')
            + '<div class="foot">C R Industries ERP &bull; generated ' + new Date().toLocaleString() + '</div></body></html>');
        w.document.close(); w.focus();
        setTimeout(function () { w.print(); }, 400);
    }

    function rkActClass(a) { a = (a || '').toLowerCase(); return a === 'create' ? 'rk-t-create' : a === 'update' ? 'rk-t-update' : a === 'delete' ? 'rk-t-delete' : 'rk-t-other'; }

    // Fetch and render the complete audit trail (create/update/delete events)
    // for one entry: user, source, IP (+ version), location, MAC, timestamp.
    function loadTrail(id) {
        var box = document.getElementById('rkTrail');
        if (!box) return;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', RK_BASE + 'admin/report/entry_trail', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            var b = document.getElementById('rkTrail');
            if (!b) return;
            var res;
            try { res = JSON.parse(xhr.responseText); } catch (e) { b.innerHTML = '<div class="text-muted" style="font-size:12px;">Could not load trail.</div>'; return; }
            rkTrailData = (res && res.trail) ? res.trail : [];
            if (!res.trail || !res.trail.length) { b.innerHTML = '<div class="text-muted" style="font-size:12px;">No audit events recorded for this entry.</div>'; return; }
            var html = '';
            res.trail.forEach(function (t) {
                var cls = rkActClass(t.action);
                var loc = (t.lat && t.lng)
                    ? '<a href="https://www.google.com/maps?q=' + encodeURIComponent(t.lat + ',' + t.lng) + '" target="_blank" rel="noopener"><i class="ti-location-pin"></i> exact GPS' + (t.acc ? ' ±' + esc(t.acc) + 'm' : '') + '</a>'
                    : (t.city ? '<span><i class="ti-location-pin"></i> ' + esc(t.city) + '</span>' : '');
                var srcIco = (String(t.source).toLowerCase() === 'app') ? '<i class="ti-mobile"></i>' : '<i class="ti-desktop"></i>';
                var chg = '';
                if (t.changes && t.changes.length) {
                    chg = '<div class="rk-t-chg"><div class="rk-t-chg-h">Changed ' + t.changes.length + ' field' + (t.changes.length > 1 ? 's' : '') + ':</div>';
                    t.changes.forEach(function (c) {
                        chg += '<div class="rk-t-chg-row"><span class="rk-t-fld">' + esc(c.field) + '</span>'
                            + '<span class="rk-t-old">' + esc(c.old !== '' ? c.old : '—') + '</span>'
                            + '<span class="rk-t-arw">&rarr;</span>'
                            + '<span class="rk-t-new">' + esc(c.new !== '' ? c.new : '—') + '</span></div>';
                    });
                    chg += '</div>';
                }
                html += '<div class="rk-t-item">'
                    + '<span class="rk-t-dot ' + cls + '"></span>'
                    + '<div class="rk-t-card">'
                    + '<div class="rk-t-top"><span class="rk-t-act ' + cls + '">' + esc((t.action || '').toUpperCase()) + '</span> <b>' + esc(t.user) + '</b> <span class="rk-t-src">' + srcIco + ' ' + esc(t.source || 'Web') + '</span></div>'
                    + '<div class="rk-t-meta">'
                    + '<span><i class="ti-world"></i> ' + (t.ip ? esc(t.ip) : '&mdash;') + (t.ip_ver ? ' <span class="rk-t-ver">IPv' + t.ip_ver + '</span>' : '') + '</span>'
                    + (loc ? '<span>' + loc + '</span>' : '')
                    + (t.mac ? '<span><i class="ti-panel"></i> ' + esc(t.mac) + '</span>' : '')
                    + (t.isp ? '<span class="rk-t-isp">' + esc(t.isp) + '</span>' : '')
                    + '</div>'
                    + chg
                    + '<div class="rk-t-when"><i class="ti-time"></i> ' + esc(t.when) + '</div>'
                    + '</div></div>';
            });
            b.innerHTML = html;
        };
        xhr.send('rokad_id=' + encodeURIComponent(id));
    }
    function closeRokad() {
        var ov = document.getElementById('rokadModal');
        ov.classList.remove('show');
        // clear media after the fade-out so it doesn't blank mid-animation
        setTimeout(function () { document.getElementById('rkBody').innerHTML = ''; }, 300);
    }
    document.getElementById('rokadModal').addEventListener('click', function (e) {
        if (e.target === this) closeRokad();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeRokad(); closeDelModal(); }
    });

    /* ---------- hover quick-preview tooltip ---------- */
    (function () {
        var tip = document.getElementById('rokadTip');
        if (!tip) return;
        var hideTimer = null;

        function pill(txt, cls) { return '<span class="rk-tip-tag ' + (cls || '') + '">' + esc(txt) + '</span>'; }

        function build(d, side) {
            var isJama = side === 'jama';
            var isWeb  = (String(d.source).toLowerCase() === 'web' || !d.source);
            var txn    = '#TXN-' + String(d.id).padStart(6, '0');

            var tags = '';
            tags += pill(isWeb ? 'Web' : 'App', isWeb ? 'web' : 'app');
            if (d.type)   tags += pill(d.type);
            if (d.pay)    tags += pill(d.pay);
            if (d.account_no) tags += pill('A/c ' + d.account_no);

            var meta = '';
            if (d.added_by) meta += '<i class="ti-user"></i> ' + esc(d.added_by);
            if (d.added_on) meta += (meta ? ' &middot; ' : '') + esc(d.added_on);
            else if (d.date) meta += (meta ? ' &middot; ' : '') + esc(d.date);

            var html =
                '<div class="rk-tip-head"><span class="rk-tip-name">' + esc(d.party || 'Entry') + '</span>' +
                    '<span class="rk-tip-txn">' + txn + '</span></div>' +
                '<div class="rk-tip-amt ' + (isJama ? 'jama' : 'naam') + '">' +
                    (isJama ? '+' : '&minus;') + ' &#8377; ' + fmtAmount(d.amount) +
                    '<small>' + (isJama ? 'Jama &middot; In' : 'Naam &middot; Out') + '</small></div>' +
                '<div class="rk-tip-tags">' + tags + '</div>' +
                (meta ? '<div class="rk-tip-meta">' + meta + '</div>' : '') +
                ((+d.restored > 0)
                    ? '<div class="rk-tip-restored"><i class="ti-reload"></i> Restored ' + (+d.restored) + '&times; <span>(was deleted)</span></div>'
                    : '<div class="rk-tip-fresh">&#10024; Fresh entry <span>&middot; never deleted</span></div>') +
                (d.remark ? '<div class="rk-tip-remark">&ldquo;' + esc(d.remark) + '&rdquo;</div>' : '') +
                '<div class="rk-tip-foot"><i class="ti-hand-point-up"></i>Click the row to open full details</div>';
            return html;
        }

        function place(row) {
            var r = row.getBoundingClientRect();
            tip.style.left = '0px'; tip.style.top = '0px';       // measure at origin first
            var tw = tip.offsetWidth, th = tip.offsetHeight, gap = 10, pad = 10;

            var left = r.left + (r.width - tw) / 2;
            left = Math.max(pad, Math.min(left, window.innerWidth - tw - pad));

            var place = 'top', top = r.top - th - gap;
            if (top < pad) { place = 'bottom'; top = r.bottom + gap; }
            tip.setAttribute('data-place', place);

            // Arrow x = centre of the row, relative to the tooltip's left edge.
            var ax = (r.left + r.width / 2) - left;
            ax = Math.max(14, Math.min(ax, tw - 20));
            tip.style.setProperty('--tip-ax', ax + 'px');

            tip.style.left = Math.round(left) + 'px';
            tip.style.top  = Math.round(top) + 'px';
        }

        function show(row) {
            if (rpDragEl) return;                                // never during a drag
            var id = row.getAttribute('data-id');
            var d = id && rokadDetails[id];
            if (!d) return;                                      // opening-balance / unknown rows
            if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
            tip.innerHTML = build(d, row.getAttribute('data-side'));
            tip.classList.add('show');
            place(row);
        }
        function hide() {
            tip.classList.remove('show');
            hideTimer = setTimeout(function () { tip.innerHTML = ''; }, 180);
        }

        document.addEventListener('mouseover', function (e) {
            var row = e.target.closest && e.target.closest('.rp-row');
            if (row && !row.classList.contains('rp-row-open')) show(row);
        });
        document.addEventListener('mouseout', function (e) {
            var row = e.target.closest && e.target.closest('.rp-row');
            if (!row) return;
            if (e.relatedTarget && row.contains(e.relatedTarget)) return; // moving within the row
            hide();
        });
        // Hide on scroll (fixed tooltip would otherwise detach from its row).
        window.addEventListener('scroll', function () { if (tip.classList.contains('show')) hide(); }, true);
    })();

    /* ---------- soft delete with mandatory reason ---------- */
    var rkDeleteId = null;
    function deleteSingle(id) {
        rkDeleteId = id;
        var ta = document.getElementById('rkDelReason');
        ta.value = '';
        ta.classList.remove('is-error');
        document.getElementById('rkDelErr').style.display = 'none';
        document.getElementById('rokadDelModal').classList.add('show');
        setTimeout(function () { ta.focus(); }, 220);
    }
    function closeDelModal() {
        document.getElementById('rokadDelModal').classList.remove('show');
    }
    document.getElementById('rokadDelModal').addEventListener('click', function (e) {
        if (e.target === this) closeDelModal();
    });
    function confirmDelete() {
        var ta = document.getElementById('rkDelReason');
        var reason = ta.value.trim();
        if (reason === '') {
            ta.classList.add('is-error');
            document.getElementById('rkDelErr').style.display = 'block';
            ta.focus();
            return;
        }
        var btn = document.getElementById('rkDelConfirm');
        btn.disabled = true; btn.textContent = 'Deleting...';
        $.ajax({
            url: "<?php echo base_url(); ?>admin/report/deleteMyEntry",
            type: "POST", dataType: 'json',
            data: { 'deleteEntry': rkDeleteId, 'delete_reason': reason },
            success: function (res) {
                btn.disabled = false; btn.textContent = 'Delete';
                if (res && res.status === 'success') {
                    closeDelModal();
                    $('#search').click();
                } else {
                    alert(res && res.msg ? res.msg : 'Not able to delete');
                }
            },
            error: function () {
                btn.disabled = false; btn.textContent = 'Delete';
                alert("Error");
            }
        });
    }

    /* ---------- PDF / print : plain B&W ledger ---------- */
    function printData() {
        var el = document.getElementById("printLedger");
        if (!el) { alert("No data available to print."); return; }
        var contents = el.innerHTML;
        var w = window.open('', '_blank', 'height=900,width=1100');
        if (!w) { alert("Popup blocked! Please allow popups for this site to print."); return; }
        w.document.write(
            '<html><head><title>रोकड़ पर्चा - <?= date('d-m-Y', strtotime($old_date)); ?></title>' +
            '<meta charset="utf-8">' +
            '<style>' +
            "@import url('https://fonts.googleapis.com/css2?family=Hind:wght@400;600;700&display=swap');" +
            /* Vertical spacing via @page (repeats on every page); horizontal
               spacing via body padding so BOTH left and right stay symmetric
               regardless of the browser print-margin setting. */
            '@page { size: A4; margin: 12mm 0; }' +
            '* { box-sizing: border-box; }' +
            'html, body { width:100%; max-width:100%; }' +
            /* force browsers to actually print the colours / tints */
            'body { font-family: Hind, Arial, sans-serif; color:#101828; font-size:11px; margin:0; padding:0 12mm; -webkit-print-color-adjust:exact; print-color-adjust:exact; }' +
            '.pl-doc { width:100%; max-width:100%; overflow:hidden; }' +
            /* Header — brand accent */
            '.pl-head { width:100%; border-collapse:collapse; border-bottom:3px solid #1a3f6b; margin-bottom:5px; }' +
            '.pl-head td { vertical-align:bottom; padding:0 0 6px; }' +
            '.pl-head-r { text-align:right; }' +
            '.pl-firm { font-size:19px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; line-height:1.1; color:#1a3f6b; }' +
            '.pl-firm-sub { font-size:10.5px; color:#5b6b82; margin-top:3px; }' +
            '.pl-doclabel { display:inline-block; font-size:15px; font-weight:700; line-height:1; color:#fff; background:#1a3f6b; padding:4px 12px; border-radius:4px; }' +
            '.pl-doclabel-en { font-size:9.5px; letter-spacing:.6px; text-transform:uppercase; color:#8a6d1f; font-weight:700; margin-top:4px; }' +
            '.pl-daterow { display:flex; justify-content:space-between; font-size:11px; margin:3px 0 9px; color:#1f2937; }' +
            /* Summary strip */
            '.pl-sum { width:100%; border-collapse:collapse; margin-bottom:11px; table-layout:fixed; }' +
            '.pl-sum td { border:1px solid #c9d4e3; padding:6px 8px; text-align:center; background:#f7f9fc; }' +
            '.pl-sum td span { display:block; font-size:8.5px; text-transform:uppercase; letter-spacing:.3px; color:#5b6b82; }' +
            '.pl-sum td b { display:block; font-size:13px; margin-top:2px; color:#101828; }' +
            '.pl-sum td.pos { background:#e9f7ef; border-color:#bfe6cd; } .pl-sum td.pos b { color:#127a34; }' +
            '.pl-sum td.neg { background:#fdeeec; border-color:#f4cfc9; } .pl-sum td.neg b { color:#b42318; }' +
            '.pl-sum td.bal { background:#1a3f6b; border-color:#1a3f6b; } .pl-sum td.bal span { color:#c7d6ea; } .pl-sum td.bal b { color:#fff; font-size:14px; }' +
            /* Two-column ledger */
            '.led-wrap { width:100%; border-collapse:collapse; table-layout:fixed; }' +
            '.led-wrap > tbody > tr > td { vertical-align:top; padding:0 4px; }' +
            '.led-wrap > tbody > tr > td:first-child { padding-left:0; } .led-wrap > tbody > tr > td:last-child { padding-right:0; }' +
            '.led { width:100%; border-collapse:collapse; }' +
            '.led th, .led td { border:1px solid #b9c4d4; padding:2.5px 5px; font-size:10.5px; line-height:1.3; vertical-align:top; }' +
            '.led th { font-weight:700; text-align:center; background:#eef2f8; color:#1a3f6b; }' +
            '.led .led-side-head { font-size:12px; letter-spacing:.4px; color:#fff; }' +
            '.led .led-jama { background:#127a34; border-color:#127a34; }' +
            '.led .led-naam { background:#b42318; border-color:#b42318; }' +
            '.led td.c, .led th.c { text-align:center; }' +
            '.led td.amt, .led th.amt { text-align:right; white-space:nowrap; }' +
            '.led .rmk { font-size:9px; color:#333; }' +
            '.clip { font-size:10px; }' +
            '.led tr.led-grp td { font-weight:700; background:#eaeef5; color:#2a3547; text-transform:uppercase; font-size:9.5px; letter-spacing:.3px; }' +
            '.led tr.total td { font-weight:700; background:#dfe7f2; color:#1a3f6b; border-top:1.5px solid #1a3f6b; }' +
            '.led-balance { margin-top:9px; text-align:right; font-weight:700; font-size:14px; color:#fff; background:#1a3f6b; border-radius:4px; padding:7px 12px; }' +
            /* Footer + signatures */
            /* Colour-coded attachment badges (foreground colour prints reliably) */
            '.pl-att { font-size:7.5px; font-weight:700; margin-left:3px; white-space:nowrap; letter-spacing:.2px; }' +
            '.pl-att-img { color:#127a34; }' +
            '.pl-att-aud { color:#1d4ed8; }' +
            '.pl-att-vid { color:#7c3aed; }' +
            /* Media totals strip */
            '.pl-media { width:100%; border-collapse:collapse; margin-top:8px; table-layout:fixed; }' +
            '.pl-media td { border:1px solid #c9d4e3; padding:4px 7px; font-size:10px; font-weight:700; text-align:center; background:#f7f9fc; }' +
            '.pl-media td.pl-media-h { text-align:left; background:#1a3f6b; color:#fff; text-transform:uppercase; font-size:9px; letter-spacing:.3px; }' +
            '.pl-media td.pl-media-tot { font-weight:400; color:#5b6b82; }' +
            '.pl-legend { margin-top:6px; font-size:9px; color:#222; text-align:left; }' +
            '.pl-legend .pl-att { margin-left:0; }' +
            '.pl-sign { width:100%; border-collapse:collapse; margin-top:30px; table-layout:fixed; }' +
            '.pl-sign td { width:33.33%; text-align:center; font-size:10.5px; font-weight:700; color:#1a3f6b; padding-top:3px; border-top:1.5px solid #1a3f6b; vertical-align:top; }' +
            '.pl-sign td:nth-child(2) { border-left:0; border-right:0; padding-left:14px; padding-right:14px; }' +
            '.pl-sign .pl-sign-n { font-weight:400; font-size:8.5px; color:#333; margin-top:2px; }' +
            '.pl-gen { margin-top:12px; font-size:8.5px; color:#444; text-align:center; border-top:1px solid #ccc; padding-top:4px; }' +
            'thead { display: table-header-group; }' +
            'tr { page-break-inside: avoid; }' +
            '.pl-sign, .pl-sum { page-break-inside: avoid; }' +
            '</style></head><body>' + contents + '</body></html>'
        );
        w.document.close();
        w.focus();
        setTimeout(function () { w.print(); }, 400);
    }
</script>
