<?php
include __DIR__ . '/_head.php';
$esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$sevOrder = array('high' => 0, 'med' => 1, 'low' => 2);
usort($flags, function ($a, $b) use ($sevOrder) {
    $sa = isset($sevOrder[$a->sev]) ? $sevOrder[$a->sev] : 3;
    $sb = isset($sevOrder[$b->sev]) ? $sevOrder[$b->sev] : 3;
    return $sa - $sb;
});
$counts = array('high' => 0, 'med' => 0, 'low' => 0);
foreach ($flags as $fl) { if (isset($counts[$fl->sev])) $counts[$fl->sev]++; }
?>
<div class="main-content mon-scope">
    <div class="mon-shell">
        <?php include __DIR__ . '/_tabs.php'; ?>

        <div class="mon-kpis" style="grid-template-columns:repeat(4,1fr);">
            <div class="mon-kpi"><div class="mon-kpi-ic ic-red"><i class="ti-alert"></i></div><div class="mon-kpi-t"><span>High</span><strong><?= (int) $counts['high'] ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-amber"><i class="ti-flag"></i></div><div class="mon-kpi-t"><span>Medium</span><strong><?= (int) $counts['med'] ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-cyan"><i class="ti-info"></i></div><div class="mon-kpi-t"><span>Info</span><strong><?= (int) $counts['low'] ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-slate"><i class="ti-list"></i></div><div class="mon-kpi-t"><span>Total Flags</span><strong><?= count($flags) ?></strong></div></div>
        </div>

        <div class="mon-panel">
            <div class="mon-panel-h"><b>Security &amp; Anomaly Flags</b><span class="mon-badge">shared IPs · odd-hour entries · roaming users</span></div>
            <div class="mon-panel-b">
                <?php if (empty($flags)): ?>
                    <div class="mon-empty"><i class="ti-check" style="font-size:26px;color:#16a34a;"></i><br>No anomalies detected for this period. All clear.</div>
                <?php else: ?>
                    <div class="mon-flags">
                        <?php foreach ($flags as $fl): ?>
                            <div class="mon-flag <?= $esc($fl->sev) ?>">
                                <div class="mon-flag-ty"><?= $esc($fl->type) ?></div>
                                <div class="mon-flag-t"><?= $esc($fl->title) ?></div>
                                <div class="mon-flag-d"><?= $esc($fl->detail) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
