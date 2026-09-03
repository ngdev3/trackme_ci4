<?php
include __DIR__ . '/_head.php';
$esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$kindMeta = array(
    'visit' => array('Visited', '#2563eb'),
    'login' => array('Logged in', '#7c3aed'),
    'entry_create' => array('Created', '#16a34a'),
    'entry_update' => array('Edited', '#2563eb'),
    'entry_delete' => array('Deleted', '#dc2626'),
);
// Group by day for readability.
$byDay = array();
foreach ($rows as $r) { $byDay[date('Y-m-d', strtotime($r->ts))][] = $r; }
?>
<div class="main-content mon-scope">
    <div class="mon-shell">
        <?php include __DIR__ . '/_tabs.php'; ?>
        <div class="mon-panel">
            <div class="mon-panel-h">
                <b>Unified Activity Timeline</b>
                <span class="mon-badge">visits · entries · logins &middot; <?= count($rows) ?> events</span>
            </div>
            <div class="mon-panel-b">
                <?php if (empty($rows)): ?>
                    <div class="mon-empty">No activity for this filter. Pick a user or widen the dates in the header.</div>
                <?php else: ?>
                    <?php foreach ($byDay as $day => $items): ?>
                        <div style="font-weight:900; color:#0f172a; font-size:13px; margin:14px 0 10px;"><?= date('l, d M Y', strtotime($day)) ?> <span style="color:#94a3b8;font-weight:700;">(<?= count($items) ?>)</span></div>
                        <ul class="mon-tl">
                            <?php foreach ($items as $r):
                                $m = isset($kindMeta[$r->kind]) ? $kindMeta[$r->kind] : array(ucfirst(str_replace('_', ' ', $r->kind)), '#64748b');
                                $isGeo = (strpos($r->kind, 'entry_') === 0 && $r->extra && strpos($r->extra, ',') !== false);
                            ?>
                                <li>
                                    <span class="mon-tl-dot" style="background:<?= $m[1] ?>"></span>
                                    <div class="mon-tl-head">
                                        <span class="mon-tl-user"><?= $esc($r->user_name) ?></span>
                                        <span class="mon-kind k-<?= $esc($r->kind) ?>" style="background:<?= $m[1] ?>22;color:<?= $m[1] ?>;"><?= $esc($m[0]) ?></span>
                                        <span class="mon-tl-time"><?= date('h:i:s A', strtotime($r->ts)) ?></span>
                                    </div>
                                    <div class="mon-tl-detail">
                                        <?= $esc($r->detail) ?>
                                        <?php if ($r->ip): ?><span style="color:#94a3b8"> &middot; <?= $esc($r->ip) ?></span><?php endif; ?>
                                        <?php if ($isGeo): ?> &middot; <a class="et-loc" target="_blank" rel="noopener" href="https://www.google.com/maps?q=<?= rawurlencode($r->extra) ?>"><i class="ti-location-pin"></i> map</a><?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
