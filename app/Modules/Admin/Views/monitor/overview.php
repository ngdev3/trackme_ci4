<?php
include __DIR__ . '/_head.php';
$k = $kpis;
$esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

// Chart scaling.
$maxDaily = 1;
foreach ($series['visits'] as $i => $v) { $maxDaily = max($maxDaily, (int) $v, (int) $series['entries'][$i]); }
$maxHour = 1;
foreach ($hours as $h) { $maxHour = max($maxHour, (int) $h); }

$kindMeta = array(
    'visit' => array('Visited', 'k-visit', '#2563eb'),
    'login' => array('Logged in', 'k-login', '#7c3aed'),
    'entry_create' => array('Created', 'k-entry_create', '#16a34a'),
    'entry_update' => array('Edited', 'k-entry_update', '#2563eb'),
    'entry_delete' => array('Deleted', 'k-entry_delete', '#dc2626'),
);
?>
<div class="main-content mon-scope">
    <div class="mon-shell">
        <?php include __DIR__ . '/_tabs.php'; ?>

        <!-- KPIs -->
        <div class="mon-kpis">
            <div class="mon-kpi"><div class="mon-kpi-ic ic-blue"><i class="ti-eye"></i></div><div class="mon-kpi-t"><span>Page Views</span><strong><?= number_format($k['visits']) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-violet"><i class="ti-key"></i></div><div class="mon-kpi-t"><span>Logins</span><strong><?= number_format($k['logins']) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-green"><i class="ti-pencil-alt"></i></div><div class="mon-kpi-t"><span>Entries</span><strong><?= number_format($k['entries']) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-cyan"><i class="ti-user"></i></div><div class="mon-kpi-t"><span>Online Now</span><strong><?= number_format($k['online']) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-slate"><i class="ti-users"></i></div><div class="mon-kpi-t"><span>Active Users</span><strong><?= number_format($k['users']) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-amber"><i class="ti-world"></i></div><div class="mon-kpi-t"><span>Unique IPs</span><strong><?= number_format($k['ips']) ?></strong></div></div>
        </div>

        <div class="mon-grid-2">
            <!-- Daily activity chart -->
            <div class="mon-panel">
                <div class="mon-panel-h">
                    <b>Daily Activity</b>
                    <span class="mon-badge"><i style="color:#2563eb">&#9632;</i> Page views &nbsp; <i style="color:#16a34a">&#9632;</i> Entries</span>
                </div>
                <div class="mon-panel-b">
                    <?php if (empty($series['labels'])): ?>
                        <div class="mon-empty">No activity in this period.</div>
                    <?php else: ?>
                        <div style="display:flex; align-items:flex-end; gap:6px; height:220px; overflow-x:auto; padding-top:8px;">
                            <?php foreach ($series['labels'] as $i => $lbl):
                                $v = (int) $series['visits'][$i]; $e = (int) $series['entries'][$i];
                                $vh = (int) round($v / $maxDaily * 190); $eh = (int) round($e / $maxDaily * 190);
                            ?>
                                <div style="flex:1 0 26px; min-width:26px; display:flex; flex-direction:column; align-items:center; gap:3px;" title="<?= $esc($lbl) ?> — <?= $v ?> views, <?= $e ?> entries">
                                    <div style="flex:1; display:flex; align-items:flex-end; gap:2px;">
                                        <div style="width:9px; height:<?= max(2,$vh) ?>px; background:linear-gradient(180deg,#3b82f6,#1746a2); border-radius:3px 3px 0 0;"></div>
                                        <div style="width:9px; height:<?= max(2,$eh) ?>px; background:linear-gradient(180deg,#22c55e,#0c7048); border-radius:3px 3px 0 0;"></div>
                                    </div>
                                    <span style="font-size:9.5px; color:#94a3b8; font-weight:700; white-space:nowrap; transform:rotate(-35deg); transform-origin:center;"><?= $esc($lbl) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Online now -->
            <div class="mon-panel">
                <div class="mon-panel-h"><b>Online Now</b><span class="mon-badge">last 15 min</span></div>
                <div class="mon-panel-b">
                    <?php if (empty($online)): ?>
                        <div class="mon-empty">No one active right now.</div>
                    <?php else: ?>
                        <ul class="mon-list">
                            <?php foreach ($online as $o): ?>
                                <li>
                                    <span class="mon-dot"></span>
                                    <div style="flex:1; min-width:0;">
                                        <div style="font-weight:800; color:#0f172a;"><?= $esc($o->user_name) ?></div>
                                        <div style="font-size:11px; color:#94a3b8;"><?= $esc($o->last_action ?: 'active') ?> &middot; <?= $esc($o->ip) ?></div>
                                    </div>
                                    <span class="mon-badge"><?= date('h:i A', strtotime($o->last_seen)) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mon-grid-2">
            <!-- Recent activity -->
            <div class="mon-panel">
                <div class="mon-panel-h"><b>Recent Activity</b><a class="mon-btn" href="<?= base_url('admin/monitor/timeline') ?>">Full timeline <i class="ti-arrow-right"></i></a></div>
                <div class="mon-panel-b">
                    <?php if (empty($recent)): ?>
                        <div class="mon-empty">Nothing recorded yet.</div>
                    <?php else: ?>
                        <ul class="mon-tl">
                            <?php foreach ($recent as $r):
                                $m = isset($kindMeta[$r->kind]) ? $kindMeta[$r->kind] : array(ucfirst($r->kind), 'k-visit', '#64748b');
                            ?>
                                <li>
                                    <span class="mon-tl-dot" style="background:<?= $m[2] ?>"></span>
                                    <div class="mon-tl-head">
                                        <span class="mon-tl-user"><?= $esc($r->user_name) ?></span>
                                        <span class="mon-kind <?= $m[1] ?>"><?= $esc($m[0]) ?></span>
                                        <span class="mon-tl-time"><?= date('d M h:i A', strtotime($r->ts)) ?></span>
                                    </div>
                                    <div class="mon-tl-detail"><?= $esc($r->detail) ?><?= $r->ip ? ' <span style="color:#94a3b8">· ' . $esc($r->ip) . '</span>' : '' ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hourly heat + entry breakdown -->
            <div class="mon-panel">
                <div class="mon-panel-h"><b>By Hour of Day</b></div>
                <div class="mon-panel-b">
                    <div style="display:flex; align-items:flex-end; gap:2px; height:90px;">
                        <?php for ($h = 0; $h < 24; $h++): $val = (int) $hours[$h]; $hh = (int) round($val / $maxHour * 78); ?>
                            <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:3px;" title="<?= $h ?>:00 — <?= $val ?>">
                                <div style="width:100%; height:<?= max(2,$hh) ?>px; background:<?= $val ? 'linear-gradient(180deg,#38bdf8,#0369a1)' : '#eef2f7' ?>; border-radius:2px 2px 0 0;"></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:9px; color:#94a3b8; font-weight:700; margin-top:4px;"><span>00</span><span>06</span><span>12</span><span>18</span><span>23</span></div>

                    <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
                        <span class="mon-badge" style="background:#dcfce7; color:#15803d; padding:4px 10px; border-radius:8px;">Created <?= number_format($k['entries_create']) ?></span>
                        <span class="mon-badge" style="background:#dbeafe; color:#1d4ed8; padding:4px 10px; border-radius:8px;">Edited <?= number_format($k['entries_update']) ?></span>
                        <span class="mon-badge" style="background:#fee2e2; color:#b91c1c; padding:4px 10px; border-radius:8px;">Deleted <?= number_format($k['entries_delete']) ?></span>
                        <span class="mon-badge" style="background:#e0f2fe; color:#0369a1; padding:4px 10px; border-radius:8px;">With GPS <?= number_format($k['geo']) ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
