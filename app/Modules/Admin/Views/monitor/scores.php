<?php
include __DIR__ . '/_head.php';
$esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

$sc   = isset($scores) ? $scores : array('rows' => array(), 'weights' => array(), 'window' => array(), 'prev_window' => array());
$rows = $sc['rows'];
$W    = $sc['weights'];
$win  = $sc['window'];
$pwin = $sc['prev_window'];

$topScore = 0; $total = 0; $improved = null;
foreach ($rows as $r) {
    $topScore = max($topScore, (float) $r->score);
    $total   += (float) $r->score;
    if ($r->delta !== null && ($improved === null || $r->delta > $improved->delta)) { $improved = $r; }
}
$nUsers = count($rows);
$avgUser = $nUsers ? round($total / $nUsers, 1) : 0;
$top = $nUsers ? $rows[0] : null;

// delta badge helper
$deltaBadge = function ($d) {
    if ($d === null) { return '<span class="sc-delta sc-new">NEW</span>'; }
    if ($d > 0)      { return '<span class="sc-delta sc-up"><i class="ti-arrow-up"></i> ' . (int) $d . '%</span>'; }
    if ($d < 0)      { return '<span class="sc-delta sc-down"><i class="ti-arrow-down"></i> ' . abs((int) $d) . '%</span>'; }
    return '<span class="sc-delta sc-flat">0%</span>';
};
?>
<style>
    .sc-medal { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:50%; font-size:12px; font-weight:900; color:#fff; }
    .sc-r1 { background:linear-gradient(135deg,#f0b429,#c78a12); } .sc-r2 { background:linear-gradient(135deg,#9aa7b6,#6b7787); }
    .sc-r3 { background:linear-gradient(135deg,#c88a5b,#9a5b2f); } .sc-rn { background:#eef2f7; color:#64748b; }
    .sc-user b { display:block; font-weight:900; color:#0f172a; } .sc-user small { color:#94a3b8; font-weight:700; }
    .sc-score-wrap { min-width:150px; }
    .sc-score-num { font-size:16px; font-weight:900; color:#0f172a; }
    .sc-bar { height:7px; border-radius:5px; background:#eef2f7; overflow:hidden; margin-top:5px; }
    .sc-bar i { display:block; height:100%; background:linear-gradient(90deg,#2563eb,#7c3aed); }
    .sc-chips { display:flex; gap:5px; flex-wrap:wrap; }
    .sc-chip { font-size:11px; font-weight:800; padding:2px 8px; border-radius:999px; white-space:nowrap; }
    .sc-c-create { background:#dcfce7; color:#15803d; } .sc-c-update { background:#dbeafe; color:#1d4ed8; }
    .sc-c-delete { background:#fee2e2; color:#b91c1c; } .sc-c-login { background:#f5f3ff; color:#6d28d9; } .sc-c-view { background:#eef2f7; color:#475569; }
    .sc-delta { display:inline-flex; align-items:center; gap:3px; font-size:11px; font-weight:900; padding:2px 9px; border-radius:999px; }
    .sc-up { background:#dcfce7; color:#0c6b2e; } .sc-down { background:#fee2e2; color:#b42318; }
    .sc-flat { background:#eef2f7; color:#64748b; } .sc-new { background:#fef3c7; color:#92610a; }
    .sc-legend { display:flex; gap:10px; flex-wrap:wrap; color:#7a8aa0; font-size:11.5px; font-weight:700; }
    .sc-legend b { color:#334155; }
</style>

<div class="main-content mon-scope">
    <div class="mon-shell">
        <?php include __DIR__ . '/_tabs.php'; ?>

        <div class="mon-kpis">
            <div class="mon-kpi"><div class="mon-kpi-ic ic-violet"><i class="ti-medall"></i></div><div class="mon-kpi-t"><span>Top Scorer</span><strong style="font-size:15px;"><?= $top ? $esc($top->user_name) : '—' ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-blue"><i class="ti-bar-chart"></i></div><div class="mon-kpi-t"><span>Top Score</span><strong><?= $top ? number_format($top->score, 1) : 0 ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-slate"><i class="ti-user"></i></div><div class="mon-kpi-t"><span>Active Users</span><strong><?= number_format($nUsers) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-green"><i class="ti-stats-up"></i></div><div class="mon-kpi-t"><span>Avg / User</span><strong><?= number_format($avgUser, 1) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-amber"><i class="ti-arrow-up"></i></div><div class="mon-kpi-t"><span>Most Improved</span><strong style="font-size:14px;"><?= $improved ? $esc($improved->user_name) . ' (+' . (int) $improved->delta . '%)' : '—' ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-cyan"><i class="ti-sum"></i></div><div class="mon-kpi-t"><span>Total Score</span><strong><?= number_format($total, 1) ?></strong></div></div>
        </div>

        <div class="mon-panel">
            <div class="mon-panel-h">
                <b>Activity Scoreboard</b>
                <span class="mon-badge"><?= $esc($win['from']) ?> → <?= $esc($win['to']) ?> &middot; vs previous <?= (int) $win['days'] ?> day(s) (<?= $esc($pwin['from']) ?> → <?= $esc($pwin['to']) ?>)</span>
            </div>
            <div class="mon-panel-b">
                <div class="sc-legend" style="margin-bottom:12px;">
                    Scoring: <b>Entry create ×<?= $W['create'] ?></b> · <b>update ×<?= $W['update'] ?></b> · <b>delete ×<?= $W['delete'] ?></b> · <b>login ×<?= $W['login'] ?></b> · <b>page view ×<?= $W['view'] ?></b>. “vs Previous” compares each user against their own score in the equal window just before.
                </div>
                <?php if (empty($rows)): ?>
                    <div class="mon-empty">No user activity in this period.</div>
                <?php else: ?>
                <table class="table mon-tbl">
                    <thead>
                        <tr>
                            <th>#</th><th>User</th><th>Score</th><th>Activity breakdown</th>
                            <th>Active days</th><th>Avg / day</th><th>Previous</th><th>vs Previous</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r):
                            $pct = $topScore > 0 ? round($r->score / $topScore * 100) : 0;
                            $mcls = $r->rank === 1 ? 'sc-r1' : ($r->rank === 2 ? 'sc-r2' : ($r->rank === 3 ? 'sc-r3' : 'sc-rn'));
                        ?>
                        <tr>
                            <td><span class="sc-medal <?= $mcls ?>"><?= (int) $r->rank ?></span></td>
                            <td class="sc-user"><b><?= $esc($r->user_name) ?></b><small>ID <?= (int) $r->user_id ?></small></td>
                            <td class="sc-score-wrap">
                                <span class="sc-score-num"><?= number_format($r->score, 1) ?></span>
                                <div class="sc-bar"><i style="width:<?= $pct ?>%"></i></div>
                            </td>
                            <td>
                                <div class="sc-chips">
                                    <?php if ($r->create): ?><span class="sc-chip sc-c-create" title="Entries created"><i class="ti-plus"></i> <?= (int) $r->create ?></span><?php endif; ?>
                                    <?php if ($r->update): ?><span class="sc-chip sc-c-update" title="Entries edited"><i class="ti-pencil"></i> <?= (int) $r->update ?></span><?php endif; ?>
                                    <?php if ($r->delete): ?><span class="sc-chip sc-c-delete" title="Entries deleted"><i class="ti-trash"></i> <?= (int) $r->delete ?></span><?php endif; ?>
                                    <?php if ($r->login): ?><span class="sc-chip sc-c-login" title="Logins"><i class="ti-key"></i> <?= (int) $r->login ?></span><?php endif; ?>
                                    <?php if ($r->view): ?><span class="sc-chip sc-c-view" title="Page views"><i class="ti-eye"></i> <?= number_format($r->view) ?></span><?php endif; ?>
                                    <?php if (!$r->create && !$r->update && !$r->delete && !$r->login && !$r->view): ?><span style="color:#b0b8c6;">—</span><?php endif; ?>
                                </div>
                            </td>
                            <td><?= (int) $r->days ?></td>
                            <td><?= number_format($r->avg_day, 1) ?></td>
                            <td style="color:#64748b;font-weight:700;"><?= number_format($r->prev_score, 1) ?></td>
                            <td><?= $deltaBadge($r->delta) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
