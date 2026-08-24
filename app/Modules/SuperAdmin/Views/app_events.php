<?php
/** Super Admin — app usage analytics (menu/screen taps). Rendered in layout.php. */
$rows   = $rows ?? [];
$top    = $top ?? [];
$total  = $total ?? 0;
$userId = $userId ?? 0;
$maxC   = 0;
foreach ($top as $t) { $maxC = max($maxC, (int) $t['c']); }
$ago = static function ($ts): string {
    if (! $ts) { return ''; }
    $d = time() - strtotime((string) $ts);
    if ($d < 60)      { return 'just now'; }
    if ($d < 3600)    { return floor($d / 60) . 'm ago'; }
    if ($d < 86400)   { return floor($d / 3600) . 'h ago'; }
    if ($d < 2592000) { return floor($d / 86400) . 'd ago'; }
    return floor($d / 2592000) . ' mo ago';
};
?>
<div class="row g-3">
    <!-- Top menus -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-bar-chart me-1"></i> Top Menus <small class="text-secondary">(30 days)</small></h3></div>
            <div class="card-body">
                <?php if (empty($top)): ?>
                    <p class="text-secondary small mb-0">No taps recorded yet.</p>
                <?php else: foreach ($top as $t): $pct = $maxC > 0 ? round((int) $t['c'] / $maxC * 100) : 0; ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small fw-semibold"><span><?= esc($t['label']) ?></span><span class="erp-muted"><?= (int) $t['c'] ?></span></div>
                        <div style="height:8px;border-radius:20px;background:#eef2f7;overflow:hidden"><span style="display:block;height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,#1769c2,#2f8fd6)"></span></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent events -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="bi bi-activity me-1"></i> Recent Menu Taps <span class="erp-pill gray ms-1"><?= number_format($total) ?> total</span></h3>
                <?php if ($userId > 0): ?><a href="<?= site_url('admin/app-events') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear filter</a><?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="erp-tbl-wrap">
                    <table class="erp-tbl auto">
                        <thead><tr>
                            <th class="text-start">When</th>
                            <th class="text-start">User</th>
                            <th class="text-start">Menu</th>
                            <th class="text-start">Route</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="4" class="erp-empty"><i class="bi bi-activity"></i><div>No usage events recorded yet.</div></td></tr>
                        <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td class="text-start"><span class="erp-muted" title="<?= esc(date('d M Y, H:i:s', strtotime((string) $r['created_at'])), 'attr') ?>"><?= esc($ago($r['created_at'])) ?></span></td>
                                <td class="text-start">
                                    <a href="<?= site_url('admin/app-events?user=' . (int) $r['user_id']) ?>" class="text-decoration-none fw-semibold" title="Filter to this user"><?= esc($r['user_name'] ?: ('#' . $r['user_id'])) ?></a>
                                </td>
                                <td class="text-start"><span class="erp-pill"><?= esc($r['label'] ?: '—') ?></span></td>
                                <td class="text-start"><span class="erp-muted"><code><?= esc($r['route'] ?: '') ?></code></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
