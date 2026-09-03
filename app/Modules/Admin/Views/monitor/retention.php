<?php
include __DIR__ . '/_head.php';
$esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$tdays = ($traffic_row && isset($traffic_row->retention_days)) ? (int) $traffic_row->retention_days : 15;
$edays = (int) $entry_days;
$ePresets = array(0 => 'Keep all history', 15 => '15 days', 30 => '30 days', 60 => '60 days', 90 => '90 days', 180 => '180 days', 365 => '1 year');
?>
<div class="main-content mon-scope">
    <div class="mon-shell">
        <?php include __DIR__ . '/_tabs.php'; ?>

        <form method="post" action="<?= base_url('admin/monitor/retention') ?>">
            <div class="mon-grid-2">
                <!-- Traffic + login retention -->
                <div class="mon-panel">
                    <div class="mon-panel-h"><b><i class="ti-bar-chart"></i> Traffic &amp; Login History</b></div>
                    <div class="mon-panel-b">
                        <p style="color:#64748b; font-size:13px; font-weight:600; margin-bottom:12px;">Page-visit and login rows older than this are auto-deleted once a day. Keeps the analytics tables lean.</p>
                        <label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#718096;">Keep last (days)</label>
                        <input type="number" name="traffic_days" min="1" max="<?= (int) $traffic_max ?>" value="<?= $tdays ?>" class="form-control" style="max-width:200px;min-height:42px;border:1px solid #dce6f2;border-radius:10px;font-weight:700;">
                        <div style="margin-top:10px; display:flex; gap:6px; flex-wrap:wrap;">
                            <?php foreach (array(15, 20, 30, 60, 90) as $p): ?>
                                <button type="button" class="mon-btn ret-preset" data-target="traffic_days" data-v="<?= $p ?>"><?= $p ?>d</button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Entry audit retention -->
                <div class="mon-panel">
                    <div class="mon-panel-h"><b><i class="ti-shield"></i> Entry Audit History</b></div>
                    <div class="mon-panel-b">
                        <p style="color:#64748b; font-size:13px; font-weight:600; margin-bottom:12px;">Audit rows (with IP + GPS) older than this are auto-deleted daily. Default keeps everything &mdash; pick a window to cap it.</p>
                        <label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#718096;">Retention window</label>
                        <select name="entry_days" class="form-control" style="max-width:240px;min-height:42px;border:1px solid #dce6f2;border-radius:10px;font-weight:700;">
                            <?php foreach ($ePresets as $v => $lbl): ?>
                                <option value="<?= (int) $v ?>" <?= $edays === (int) $v ? 'selected' : '' ?>><?= $esc($lbl) ?></option>
                            <?php endforeach; ?>
                            <?php if (!array_key_exists($edays, $ePresets)): ?><option value="<?= $edays ?>" selected><?= $edays ?> days (custom)</option><?php endif; ?>
                        </select>
                        <?php if ($edays > 0 && (int) $entry_prune > 0): ?>
                            <div style="margin-top:10px; font-size:12px; font-weight:700; color:#b45309;"><i class="ti-info-alt"></i> <?= number_format((int) $entry_prune) ?> row(s) older than <?= $edays ?> days will be purged on save.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="margin-top:4px;">
                <button type="submit" class="mon-btn" style="background:#1d4ed8;color:#fff;border-color:#1d4ed8;min-height:44px;padding:0 26px;"><i class="ti-save"></i> Save Retention Settings</button>
            </div>
        </form>
    </div>
</div>
<script>
    $(function () {
        $('.ret-preset').on('click', function () { $('input[name=' + $(this).data('target') + ']').val($(this).data('v')); });
    });
</script>
