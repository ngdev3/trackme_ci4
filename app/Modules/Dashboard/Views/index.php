<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<?php
$k     = $kpis ?? [];
$act   = (int) ($k['active_users'] ?? 0);
$inact = (int) ($k['inactive_users'] ?? 0);
$utot  = max(1, $act + $inact);
$lok   = (int) ($loginOk ?? 0);
$lfail = (int) ($loginFail ?? 0);
$ltot  = max(1, $lok + $lfail);

$roleLabels = $usersByRole['labels'] ?? [];
$roleData   = $usersByRole['data'] ?? [];
$maxRole    = max(1, ...(count($roleData) ? $roleData : [1]));

$userLabels = $topUsers['labels'] ?? [];
$userData   = $topUsers['data'] ?? [];
$maxUser    = max(1, ...(count($userData) ? $userData : [1]));

$pct = static fn ($n, $d) => (int) round($n / max(1, $d) * 100);
?>

<!-- ===================== Row 1 : mini-stats + user status ===================== -->
<div class="row">
    <div class="col-md-4">
        <div class="tm-box">
            <span class="tm-refresh" data-tm-refresh title="Refresh"><i class="bi bi-arrow-repeat"></i></span>
            <div class="tm-ministat">
                <div class="tm-icon tm-ic-blue"><i class="bi bi-people"></i></div>
                <div class="tm-meta">
                    <p class="tm-label"><i class="bi bi-arrow-up up"></i> Total Accounts</p>
                    <div class="tm-value" data-count="<?= (int) ($k['total_users'] ?? 0) ?>">0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="tm-box">
            <span class="tm-refresh" data-tm-refresh title="Refresh"><i class="bi bi-arrow-repeat"></i></span>
            <div class="tm-ministat">
                <div class="tm-icon tm-ic-green"><i class="bi bi-person-check"></i></div>
                <div class="tm-meta">
                    <p class="tm-label"><i class="bi bi-arrow-up up"></i> Active Users</p>
                    <div class="tm-value" data-count="<?= $act ?>">0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="tm-box">
            <span class="tm-refresh" data-tm-refresh title="Refresh"><i class="bi bi-arrow-repeat"></i></span>
            <div class="tm-heading">User <span>Status</span></div>
            <div class="tm-gauges">
                <div class="tm-gauge">
                    <div class="tm-circle green"><?= $pct($act, $utot) ?>%</div>
                    <div class="tm-gauge-lbl">Active</div>
                </div>
                <div class="tm-gauge">
                    <div class="tm-circle red"><?= $pct($inact, $utot) ?>%</div>
                    <div class="tm-gauge-lbl">Inactive</div>
                </div>
            </div>
            <div class="tm-total"><strong>Total Users</strong><span><?= $act + $inact ?></span></div>
        </div>
    </div>
</div>

<!-- ===================== Row 2 : mini-stats + login health ===================== -->
<div class="row">
    <div class="col-md-4">
        <div class="tm-box">
            <span class="tm-refresh" data-tm-refresh title="Refresh"><i class="bi bi-arrow-repeat"></i></span>
            <div class="tm-ministat">
                <div class="tm-icon tm-ic-purple"><i class="bi bi-person-gear"></i></div>
                <div class="tm-meta">
                    <p class="tm-label"><i class="bi bi-arrow-up up"></i> Total Roles</p>
                    <div class="tm-value" data-count="<?= (int) ($k['total_roles'] ?? 0) ?>">0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="tm-box">
            <span class="tm-refresh" data-tm-refresh title="Refresh"><i class="bi bi-arrow-repeat"></i></span>
            <div class="tm-ministat">
                <div class="tm-icon tm-ic-cyan"><i class="bi bi-grid-3x3-gap"></i></div>
                <div class="tm-meta">
                    <p class="tm-label"><i class="bi bi-arrow-up up"></i> Modules</p>
                    <div class="tm-value" data-count="<?= (int) ($k['total_modules'] ?? 0) ?>">0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="tm-box">
            <span class="tm-refresh" data-tm-refresh title="Refresh"><i class="bi bi-arrow-repeat"></i></span>
            <div class="tm-heading">Login <span>Health</span> <small class="text-secondary">(14 days)</small></div>
            <div class="tm-gauges">
                <div class="tm-gauge">
                    <div class="tm-circle green"><?= $pct($lok, $ltot) ?>%</div>
                    <div class="tm-gauge-lbl">Success</div>
                </div>
                <div class="tm-gauge">
                    <div class="tm-circle red"><?= $pct($lfail, $ltot) ?>%</div>
                    <div class="tm-gauge-lbl">Failed</div>
                </div>
            </div>
            <div class="tm-total"><strong>Total Logins</strong><span><?= $lok + $lfail ?></span></div>
        </div>
    </div>
</div>

<!-- ===================== Row 3 : top 5 strips ===================== -->
<div class="row">
    <div class="col-md-4">
        <div class="tm-box">
            <span class="tm-refresh" data-tm-refresh title="Refresh"><i class="bi bi-arrow-repeat"></i></span>
            <div class="tm-heading">top 5 : <span>Active Users</span></div>
            <?php if (empty($userLabels)): ?>
                <p class="text-secondary mb-0">No activity in the last 30 days.</p>
            <?php else: foreach ($userLabels as $i => $name): ?>
                <div class="tm-strip">
                    <span class="tm-rank"><?= $i + 1 ?></span>
                    <div class="tm-strip-body">
                        <div class="tm-strip-name text-truncate"><?= esc($name) ?></div>
                        <div class="tm-strip-bar"><span style="width:<?= $pct($userData[$i], $maxUser) ?>%"></span></div>
                    </div>
                    <span class="tm-strip-val"><?= (int) $userData[$i] ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="tm-box">
            <span class="tm-refresh" data-tm-refresh title="Refresh"><i class="bi bi-arrow-repeat"></i></span>
            <div class="tm-heading">top 5 : <span>Users by Role</span></div>
            <?php if (empty($roleLabels)): ?>
                <p class="text-secondary mb-0">No roles found.</p>
            <?php else: foreach (array_slice($roleLabels, 0, 5) as $i => $name): ?>
                <div class="tm-strip">
                    <span class="tm-rank"><?= $i + 1 ?></span>
                    <div class="tm-strip-body">
                        <div class="tm-strip-name text-truncate"><?= esc($name) ?></div>
                        <div class="tm-strip-bar"><span style="width:<?= $pct($roleData[$i], $maxRole) ?>%"></span></div>
                    </div>
                    <span class="tm-strip-val"><?= (int) $roleData[$i] ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="tm-box">
            <span class="tm-refresh" data-tm-refresh title="Refresh"><i class="bi bi-arrow-repeat"></i></span>
            <div class="tm-heading">Recent <span>Logins</span></div>
            <?php if (empty($recentLogins)): ?>
                <p class="text-secondary mb-0">No activity yet.</p>
            <?php else: foreach (array_slice($recentLogins, 0, 6) as $log): ?>
                <div class="tm-strip">
                    <span class="tm-rank" style="background:<?= $log['status'] === 'success' ? '#1cc88a' : '#e74a3b' ?>">
                        <i class="bi bi-<?= $log['status'] === 'success' ? 'check' : 'x' ?>-lg"></i>
                    </span>
                    <div class="tm-strip-body">
                        <div class="tm-strip-name text-truncate"><?= esc($log['user_name'] ?? $log['username']) ?></div>
                        <div class="tm-strip-sub"><?= esc($log['ip_address']) ?> &middot; <?= esc(date('d M, H:i', strtotime($log['created_at']))) ?></div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- ===================== Analytics (charts, same white-box style) ===================== -->
<div class="tm-heading mt-1 mb-3" style="font-size:1.05rem">Analytics <span>Overview</span></div>
<div class="row">
    <div class="col-lg-8">
        <div class="tm-box">
            <span class="tm-refresh" data-tm-refresh title="Refresh"><i class="bi bi-arrow-repeat"></i></span>
            <div class="tm-heading">Login Activity <span>(14 days)</span></div>
            <div class="chart-box"><div class="chart-skeleton skeleton skeleton-chart"></div><canvas id="chartLogins"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="tm-box">
            <span class="tm-refresh" data-tm-refresh title="Refresh"><i class="bi bi-arrow-repeat"></i></span>
            <div class="tm-heading">Users by <span>Type</span></div>
            <div class="chart-box-sm"><div class="chart-skeleton skeleton skeleton-chart"></div><canvas id="chartUsersType"></canvas></div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-6">
        <div class="tm-box">
            <span class="tm-refresh" data-tm-refresh title="Refresh"><i class="bi bi-arrow-repeat"></i></span>
            <div class="tm-heading">New Users <span>(6 months)</span></div>
            <div class="chart-box-sm"><div class="chart-skeleton skeleton skeleton-chart"></div><canvas id="chartGrowth"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="tm-box">
            <span class="tm-refresh" data-tm-refresh title="Refresh"><i class="bi bi-arrow-repeat"></i></span>
            <div class="tm-heading">Activity by <span>Action (30 days)</span></div>
            <div class="chart-box-sm"><div class="chart-skeleton skeleton skeleton-chart"></div><canvas id="chartActivity"></canvas></div>
        </div>
    </div>
</div>
