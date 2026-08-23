<?php
$filters = $filters ?? [];
$mine = ! empty($mine);
$base = $mine ? 'my-login-history' : 'login-logs';
$query = http_build_query(array_filter($filters, static fn ($v) => $v !== ''));
$exportSuffix = $query ? '?' . $query : '';
$duration = static function ($seconds): string {
    if ($seconds === null || $seconds === '') {
        return '-';
    }
    $seconds = max(0, (int) $seconds);
    return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
};
?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-box-arrow-in-right me-1"></i> <?= $mine ? 'My Login History' : 'Login Logs' ?></h3>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm btn-outline-success" href="<?= site_url($base . '/export/csv') . $exportSuffix ?>"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
            <a class="btn btn-sm btn-outline-success" href="<?= site_url($base . '/export/xls') . $exportSuffix ?>"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel</a>
            <a class="btn btn-sm btn-outline-danger" href="<?= site_url($base . '/export/pdf') . $exportSuffix ?>" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()" type="button"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </div>
    <div class="card-body border-bottom">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-12 col-md-3">
                <label class="form-label small mb-1">Search</label>
                <input type="search" name="q" value="<?= esc($filters['q'] ?? '') ?>" class="form-control form-control-sm" placeholder="User, IP, browser, status...">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="success" <?= ($filters['status'] ?? '') === 'success' ? 'selected' : '' ?>>Success</option>
                    <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Device</label>
                <select name="device" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['Desktop', 'Mobile', 'Robot'] as $device): ?>
                        <option value="<?= esc($device) ?>" <?= ($filters['device'] ?? '') === $device ? 'selected' : '' ?>><?= esc($device) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Suspicious</label>
                <select name="suspicious" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="1" <?= ($filters['suspicious'] ?? '') === '1' ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= ($filters['suspicious'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small mb-1">From</label>
                <input type="date" name="from" value="<?= esc($filters['from'] ?? '') ?>" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small mb-1">To</label>
                <input type="date" name="to" value="<?= esc($filters['to'] ?? '') ?>" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small mb-1">Sort</label>
                <select name="sort" class="form-select form-select-sm">
                    <?php foreach (['created_at' => 'Date', 'username' => 'User', 'status' => 'Status', 'ip_address' => 'IP', 'browser' => 'Browser', 'session_duration' => 'Duration'] as $key => $label): ?>
                        <option value="<?= esc($key) ?>" <?= ($filters['sort'] ?? '') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small mb-1">Dir</label>
                <select name="dir" class="form-select form-select-sm">
                    <option value="desc" <?= strtolower($filters['dir'] ?? '') === 'desc' ? 'selected' : '' ?>>Desc</option>
                    <option value="asc" <?= strtolower($filters['dir'] ?? '') === 'asc' ? 'selected' : '' ?>>Asc</option>
                </select>
            </div>
            <div class="col-12 col-md-auto">
                <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Apply</button>
                <a href="<?= site_url($base) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="erp-tbl-wrap">
            <table class="erp-tbl auto">
                <thead>
                <tr>
                    <th class="text-start">ID</th><th class="text-start">User</th><th class="text-start">Username</th><th class="text-start">Status</th><th class="text-start">IP</th><th class="text-start">Location</th><th class="text-start">Browser</th><th class="text-start">OS</th><th class="text-start">Device</th><th class="text-start">Login</th><th class="text-start">Logout</th><th class="text-start">Duration</th><th class="text-start">Risk</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="13" class="erp-empty"><i class="bi bi-inbox"></i><div>No login history.</div></td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr class="<?= ! empty($r['is_suspicious']) ? 'table-danger' : '' ?>">
                        <td><span class="erp-idchip"><?= esc($r['id']) ?></span></td>
                        <?php $liOk = $r['status'] === 'success'; ?>
                        <td><?= erp_cell_name((string) ($r['user_name'] ?? 'System'), [
                            'type' => 'Login', 'icon' => 'box-arrow-in-right',
                            'accent' => ! empty($r['is_suspicious']) ? 'red' : ($liOk ? 'blue' : 'gray'),
                            'chips' => array_values(array_filter([
                                ['t' => $liOk ? 'Success' : 'Failed', 'ic' => $liOk ? 'check-circle-fill' : 'x-circle-fill', 'ok' => $liOk],
                                ! empty($r['is_suspicious']) ? ['t' => 'Suspicious', 'ic' => 'exclamation-triangle-fill'] : null,
                            ])),
                            'rows' => array_values(array_filter([
                                ['ic' => 'person', 'l' => 'Username', 'v' => (string) $r['username']],
                                ! empty($r['ip_address']) ? ['ic' => 'hdd-network', 'l' => 'IP', 'v' => (string) $r['ip_address']] : null,
                                ! empty($r['location_label']) ? ['ic' => 'geo-alt', 'l' => 'Location', 'v' => (string) $r['location_label']] : null,
                                ['ic' => 'window', 'l' => 'Browser / OS', 'v' => trim(($r['browser'] ?: 'Unknown') . ' · ' . ($r['operating_system'] ?: 'Unknown'))],
                                ['ic' => 'phone', 'l' => 'Device', 'v' => (string) ($r['device_type'] ?: 'Unknown')],
                                ! empty($r['login_at']) ? ['ic' => 'box-arrow-in-right', 'l' => 'Login', 'v' => date('d M Y, H:i', strtotime($r['login_at']))] : null,
                                ['ic' => 'stopwatch', 'l' => 'Duration', 'v' => $duration($r['session_duration'] ?? null)],
                                ! empty($r['failure_reason']) ? ['ic' => 'exclamation-circle', 'l' => 'Failure', 'v' => (string) $r['failure_reason']] : null,
                                ! empty($r['suspicious_reason']) ? ['ic' => 'shield-exclamation', 'l' => 'Risk', 'v' => (string) $r['suspicious_reason']] : null,
                            ])),
                            'foot' => 'Login #' . $r['id'],
                        ], ['green' => $liOk && empty($r['is_suspicious'])]) ?></td>
                        <td><code><?= esc($r['username']) ?></code></td>
                        <td>
                            <?php if ($r['status'] === 'success'): ?>
                                <span class="erp-status active">Success</span>
                            <?php else: ?>
                                <span class="erp-status delete">Failed</span>
                            <?php endif; ?>
                            <?php if (! empty($r['failure_reason'])): ?>
                                <small class="d-block text-secondary"><?= esc($r['failure_reason']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><small><?= esc($r['ip_address']) ?></small></td>
                        <td>
                            <?php
                                $hasCoords = isset($r['latitude'], $r['longitude'])
                                    && $r['latitude'] !== null && $r['longitude'] !== null && $r['latitude'] !== '';
                                $locLabel  = trim((string) ($r['location_label'] ?? ''));
                                $locSource = $r['location_source'] ?? '';
                            ?>
                            <?php if ($hasCoords || $locLabel !== ''): ?>
                                <?php if ($hasCoords): ?>
                                    <a class="text-decoration-none" target="_blank" rel="noopener"
                                       href="https://www.google.com/maps?q=<?= esc($r['latitude']) ?>,<?= esc($r['longitude']) ?>"
                                       title="Open in Google Maps">
                                        <i class="bi bi-geo-alt-fill me-1"></i><small><?= $locLabel !== '' ? esc($locLabel) : esc(round((float) $r['latitude'], 4) . ', ' . round((float) $r['longitude'], 4)) ?></small>
                                    </a>
                                <?php else: ?>
                                    <small><i class="bi bi-geo-alt me-1"></i><?= esc($locLabel) ?></small>
                                <?php endif; ?>
                                <?php if ($locSource !== ''): ?>
                                    <span class="badge <?= $locSource === 'gps' ? 'text-bg-primary' : 'text-bg-light border' ?>" title="<?= $locSource === 'gps' ? 'Precise device GPS' : 'Approximate, from IP' ?>"><?= $locSource === 'gps' ? 'GPS' : 'IP' ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <small class="text-secondary">-</small>
                            <?php endif; ?>
                        </td>
                        <td><small title="<?= esc($r['user_agent'] ?? '') ?>"><?= esc($r['browser'] ?: 'Unknown') ?></small></td>
                        <td><small><?= esc($r['operating_system'] ?: 'Unknown') ?></small></td>
                        <td><span class="erp-badge"><?= esc($r['device_type'] ?: 'Unknown') ?></span></td>
                        <td><small><?= ! empty($r['login_at']) ? esc(date('d M Y, H:i', strtotime($r['login_at']))) : '-' ?></small></td>
                        <td><small><?= ! empty($r['logout_at']) ? esc(date('d M Y, H:i', strtotime($r['logout_at']))) : 'Active/unknown' ?></small></td>
                        <td><small><?= esc($duration($r['session_duration'] ?? null)) ?></small></td>
                        <td>
                            <?php if (! empty($r['is_suspicious'])): ?>
                                <span class="erp-status delete">Suspicious</span>
                                <small class="d-block text-danger"><?= esc($r['suspicious_reason']) ?></small>
                            <?php else: ?>
                                <span class="erp-badge">Normal</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (isset($pager)): ?><div class="card-footer d-flex justify-content-end"><?= $pager->links() ?></div><?php endif; ?>
</div>
