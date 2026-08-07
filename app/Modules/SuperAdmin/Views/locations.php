<?php
/** Super Admin location analytics for mobile/web login data. */
$filters = $filters ?? ['days' => 30, 'source' => '', 'q' => ''];
$stats = $stats ?? [];
$maxDay = 1;
foreach (($byDay ?? []) as $d) {
    $maxDay = max($maxDay, (int) ($d['total'] ?? 0));
}
$sourceBadge = static function (?string $source): string {
    return $source === 'gps'
        ? '<span class="badge text-bg-primary">GPS</span>'
        : ($source === 'ip' ? '<span class="badge text-bg-light border">IP</span>' : '<span class="badge text-bg-secondary">Unknown</span>');
};
$dateLabel = static function ($value): string {
    return $value ? date('d M Y, H:i', strtotime((string) $value)) : '-';
};
?>

<?php if (empty($ready)): ?>
    <div class="alert alert-warning">
        <strong>Location analytics is not ready yet.</strong>
        Run the latest database migrations so <code>login_logs</code> has latitude, longitude, accuracy, source, and label columns.
    </div>
<?php else: ?>
<style>
    .location-hero {
        overflow: hidden; position: relative; border-radius: 18px; padding: 22px;
        color: #fff; background: linear-gradient(135deg, #0f766e, #1d4ed8);
        box-shadow: 0 14px 36px rgba(15, 23, 42, .16);
    }
    .location-hero:after {
        content: ""; position: absolute; right: -70px; bottom: -100px; width: 260px; height: 260px;
        border: 28px solid rgba(255,255,255,.13); border-radius: 50%;
    }
    .location-stat { border: 1px solid #e8eef7; border-radius: 14px; background: #fff; }
    .location-stat .icon {
        width: 42px; height: 42px; display: grid; place-items: center; border-radius: 12px;
        background: #eef6ff; color: #1d4ed8; font-size: 20px;
    }
    .trend-row { display: grid; grid-template-columns: 78px 1fr 44px; gap: 10px; align-items: center; }
    .trend-bar { height: 9px; border-radius: 999px; background: #e5edf8; overflow: hidden; }
    .trend-bar span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #0f766e, #2563eb); }
    .location-pin {
        border: 1px solid #e5ebf3; border-radius: 14px; padding: 12px;
        background: linear-gradient(180deg, #fff, #f8fbff);
    }
    .map-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 10px; }
    .tiny-muted { color: #6b7280; font-size: 12px; }
</style>

<div class="location-hero mb-3">
    <div class="position-relative" style="z-index:1">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="text-uppercase fw-bold small opacity-75">Mobile Location Intelligence</div>
                <h2 class="mb-1">Know where users are signing in from.</h2>
                <p class="mb-0 opacity-75">GPS updates from the mobile app are shown beside IP fallback data, suspicious logins, and user analytics.</p>
            </div>
            <a href="<?= site_url('login-logs') ?>" class="btn btn-light btn-sm"><i class="bi bi-box-arrow-in-right me-1"></i>Login logs</a>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Period</label>
                <select name="days" class="form-select form-select-sm">
                    <?php foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '1 year'] as $days => $label): ?>
                        <option value="<?= $days ?>" <?= (int) ($filters['days'] ?? 30) === $days ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Source</label>
                <select name="source" class="form-select form-select-sm">
                    <option value="">All sources</option>
                    <option value="gps" <?= ($filters['source'] ?? '') === 'gps' ? 'selected' : '' ?>>GPS only</option>
                    <option value="ip" <?= ($filters['source'] ?? '') === 'ip' ? 'selected' : '' ?>>IP fallback</option>
                </select>
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label small mb-1">Search</label>
                <input type="search" name="q" value="<?= esc($filters['q'] ?? '') ?>" class="form-control form-control-sm" placeholder="User, email, phone, IP, city, label...">
            </div>
            <div class="col-12 col-md-auto">
                <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
                <a href="<?= site_url('admin/locations') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <?php
    $cards = [
        ['Located logins', $stats['total'] ?? 0, 'bi-geo-alt-fill', 'primary'],
        ['GPS captures', $stats['gps'] ?? 0, 'bi-crosshair', 'success'],
        ['Unique users', $stats['users'] ?? 0, 'bi-people', 'info'],
        ['Suspicious', $stats['suspicious'] ?? 0, 'bi-shield-exclamation', 'danger'],
    ];
    foreach ($cards as [$label, $value, $icon, $color]): ?>
        <div class="col-6 col-lg-3">
            <div class="location-stat h-100 p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="tiny-muted"><?= esc($label) ?></div>
                        <div class="fs-3 fw-bold text-<?= esc($color) ?>"><?= (int) $value ?></div>
                    </div>
                    <div class="icon"><i class="bi <?= esc($icon) ?>"></i></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-broadcast-pin me-1"></i>Capture Quality</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom"><span>GPS precise</span><strong><?= (int) ($stats['gps'] ?? 0) ?></strong></div>
                <div class="d-flex justify-content-between py-2 border-bottom"><span>IP fallback</span><strong><?= (int) ($stats['ip'] ?? 0) ?></strong></div>
                <div class="d-flex justify-content-between py-2 border-bottom"><span>Mobile devices</span><strong><?= (int) ($stats['mobile'] ?? 0) ?></strong></div>
                <div class="d-flex justify-content-between py-2"><span>Avg GPS accuracy</span><strong><?= isset($stats['avg_accuracy']) && $stats['avg_accuracy'] !== null ? (int) $stats['avg_accuracy'] . ' m' : '-' ?></strong></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-bar-chart-line me-1"></i>Daily Trend</h3></div>
            <div class="card-body d-grid gap-2">
                <?php if (empty($byDay)): ?>
                    <div class="text-secondary small">No located logins in this period.</div>
                <?php else: foreach ($byDay as $d): $pct = max(4, ((int) $d['total'] / $maxDay) * 100); ?>
                    <div class="trend-row">
                        <small><?= esc(date('d M', strtotime($d['day']))) ?></small>
                        <div class="trend-bar"><span style="width: <?= esc((string) $pct) ?>%"></span></div>
                        <small class="text-end fw-semibold"><?= (int) $d['total'] ?></small>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-pin-map me-1"></i>Top Locations</h3></div>
            <div class="list-group list-group-flush">
                <?php if (empty($topLocations)): ?>
                    <div class="list-group-item text-secondary small">No location groups yet.</div>
                <?php else: foreach ($topLocations as $loc): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between gap-2">
                            <strong><?= esc($loc['label'] ?: 'Unknown location') ?></strong>
                            <span><?= (int) $loc['total'] ?></span>
                        </div>
                        <div class="tiny-muted"><?= (int) $loc['users'] ?> user(s) <?= $sourceBadge($loc['location_source'] ?? null) ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-map me-1"></i>Recent Map Points</h3></div>
    <div class="card-body">
        <?php if (empty($points)): ?>
            <div class="text-secondary small">No coordinates available for the selected filters.</div>
        <?php else: ?>
            <div class="map-grid">
                <?php foreach (array_slice($points, 0, 12) as $p): ?>
                    <a class="location-pin text-decoration-none text-body" target="_blank" rel="noopener" href="https://www.google.com/maps?q=<?= esc($p['lat']) ?>,<?= esc($p['lng']) ?>">
                        <div class="d-flex justify-content-between gap-2 align-items-start">
                            <strong><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= esc($p['label']) ?></strong>
                            <?= $sourceBadge($p['source'] ?? null) ?>
                        </div>
                        <div class="tiny-muted"><?= esc($p['user']) ?></div>
                        <div class="tiny-muted"><?= esc($dateLabel($p['when'])) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-clock-history me-1"></i>Recent Located Sign-ins</h3></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>User</th><th>Account</th><th>Location</th><th>Source</th><th>Accuracy</th><th>IP</th><th>Device</th><th>Login</th><th>Risk</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recent)): ?>
                <tr><td colspan="9" class="text-center text-secondary py-4">No located sign-ins found.</td></tr>
            <?php else: foreach ($recent as $row): ?>
                <tr class="<?= ! empty($row['is_suspicious']) ? 'table-danger' : '' ?>">
                    <td>
                        <strong><?= esc($row['user_name'] ?: 'Unknown') ?></strong>
                        <div class="tiny-muted"><?= esc($row['user_email'] ?: ($row['username'] ?? '')) ?></div>
                    </td>
                    <td><span class="badge text-bg-light border"><?= esc($row['account_type'] ?: '-') ?></span></td>
                    <td>
                        <a target="_blank" rel="noopener" href="https://www.google.com/maps?q=<?= esc($row['latitude']) ?>,<?= esc($row['longitude']) ?>">
                            <?= esc(trim((string) ($row['location_label'] ?? '')) ?: round((float) $row['latitude'], 4) . ', ' . round((float) $row['longitude'], 4)) ?>
                        </a>
                    </td>
                    <td><?= $sourceBadge($row['location_source'] ?? null) ?></td>
                    <td><?= $row['location_accuracy'] !== null && $row['location_accuracy'] !== '' ? (int) $row['location_accuracy'] . ' m' : '-' ?></td>
                    <td><small><?= esc($row['ip_address'] ?: '-') ?></small></td>
                    <td><small><?= esc(($row['device_type'] ?: 'Unknown') . ' / ' . ($row['operating_system'] ?: 'Unknown')) ?></small></td>
                    <td><small><?= esc($dateLabel($row['login_at'] ?: $row['created_at'])) ?></small></td>
                    <td>
                        <?php if (! empty($row['is_suspicious'])): ?>
                            <span class="badge text-bg-danger">Suspicious</span>
                        <?php else: ?>
                            <span class="badge text-bg-light border">Normal</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
