<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<?php $k = $kpis ?? []; ?>

<!-- Toolbar -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-0 fw-bold">Welcome back, <?= esc(session('user_name') ?? 'Admin') ?> 👋</h4>
        <small class="text-secondary">Here's what's happening across your ERP today.</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="text-secondary small" id="lastUpdated"></span>
        <button class="btn btn-sm btn-outline-primary" id="btnRefresh"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
        <button class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" data-bs-target="#themePanel"><i class="bi bi-palette me-1"></i>Theme</button>
    </div>
</div>

<!-- KPI cards (server-rendered, instant) -->
<div class="row g-3 mb-1">
    <?php
    $cards = [
        ['label' => 'Total Users',    'value' => $k['total_users'] ?? 0,    'icon' => 'bi-people',        'grad' => 'kpi-grad-1', 'url' => 'users'],
        ['label' => 'Active Users',   'value' => $k['active_users'] ?? 0,   'icon' => 'bi-person-check',  'grad' => 'kpi-grad-2', 'url' => 'users'],
        ['label' => 'Roles',          'value' => $k['total_roles'] ?? 0,    'icon' => 'bi-person-gear',   'grad' => 'kpi-grad-3', 'url' => 'roles'],
        ['label' => 'Modules',        'value' => $k['total_modules'] ?? 0,  'icon' => 'bi-grid-3x3-gap',  'grad' => 'kpi-grad-4', 'url' => 'modules'],
        ['label' => 'Logins Today',   'value' => $k['logins_today'] ?? 0,   'icon' => 'bi-box-arrow-in-right', 'grad' => 'kpi-grad-6', 'url' => 'login-logs'],
        ['label' => 'Actions Today',  'value' => $k['activity_today'] ?? 0, 'icon' => 'bi-activity',      'grad' => 'kpi-grad-5', 'url' => 'activity-logs'],
    ];
    foreach ($cards as $c): ?>
        <div class="col-6 col-md-4 col-xl-2 dash-widget">
            <div class="kpi-card <?= $c['grad'] ?> h-100 shadow-sm">
                <div class="kpi-body">
                    <div class="kpi-value" data-count="<?= (int) $c['value'] ?>">0</div>
                    <div class="kpi-label"><?= esc($c['label']) ?></div>
                    <i class="bi <?= $c['icon'] ?> kpi-icon"></i>
                </div>
                <a class="kpi-foot text-white text-decoration-none" href="<?= site_url($c['url']) ?>">
                    <span>View details</span> <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Quick actions -->
<div class="card mb-3 dash-widget">
    <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-lightning-charge me-1"></i>Quick Actions</h3></div>
    <div class="card-body">
        <div class="row g-2 row-cols-2 row-cols-md-3 row-cols-lg-6">
            <?php if (can('users', 'add')): ?>
                <div class="col"><a class="quick-action" href="<?= site_url('users/create') ?>"><i class="bi bi-person-plus"></i><span>Add User</span></a></div>
            <?php endif; ?>
            <?php if (can('roles', 'view')): ?>
                <div class="col"><a class="quick-action" href="<?= site_url('roles') ?>"><i class="bi bi-person-gear"></i><span>Roles</span></a></div>
            <?php endif; ?>
            <?php if (can('permissions', 'view')): ?>
                <div class="col"><a class="quick-action" href="<?= site_url('permissions') ?>"><i class="bi bi-key"></i><span>Permissions</span></a></div>
            <?php endif; ?>
            <?php if (can('modules', 'view')): ?>
                <div class="col"><a class="quick-action" href="<?= site_url('modules') ?>"><i class="bi bi-grid-3x3-gap"></i><span>Modules</span></a></div>
            <?php endif; ?>
            <?php if (can('activity_logs', 'view')): ?>
                <div class="col"><a class="quick-action" href="<?= site_url('activity-logs') ?>"><i class="bi bi-list-check"></i><span>Activity</span></a></div>
            <?php endif; ?>
            <div class="col"><a class="quick-action" href="<?= site_url('profile') ?>"><i class="bi bi-person-circle"></i><span>My Profile</span></a></div>
        </div>
    </div>
</div>

<!-- Row: login trend + users by type -->
<div class="row g-3">
    <div class="col-lg-8 dash-widget">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="bi bi-graph-up-arrow me-1"></i>Login Activity (14 days)</h3>
                <span class="badge text-bg-light border">success vs failed</span>
            </div>
            <div class="card-body">
                <div class="chart-box">
                    <div class="chart-skeleton skeleton skeleton-chart"></div>
                    <canvas id="chartLogins"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 dash-widget">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-pie-chart me-1"></i>Users by Type</h3></div>
            <div class="card-body">
                <div class="chart-box-sm">
                    <div class="chart-skeleton skeleton skeleton-chart"></div>
                    <canvas id="chartUsersType"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row: growth + activity -->
<div class="row g-3 mt-0">
    <div class="col-lg-6 dash-widget">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-bar-chart me-1"></i>New Users (6 months)</h3></div>
            <div class="card-body">
                <div class="chart-box-sm">
                    <div class="chart-skeleton skeleton skeleton-chart"></div>
                    <canvas id="chartGrowth"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 dash-widget">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-activity me-1"></i>Activity by Action (30 days)</h3></div>
            <div class="card-body">
                <div class="chart-box-sm">
                    <div class="chart-skeleton skeleton skeleton-chart"></div>
                    <canvas id="chartActivity"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row: roles pie + top users + progress -->
<div class="row g-3 mt-0">
    <div class="col-lg-4 dash-widget">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-diagram-3 me-1"></i>Users by Role</h3></div>
            <div class="card-body">
                <div class="chart-box-sm">
                    <div class="chart-skeleton skeleton skeleton-chart"></div>
                    <canvas id="chartUsersRole"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 dash-widget">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-trophy me-1"></i>Top Active Users</h3></div>
            <div class="card-body">
                <div id="topUsers" class="rank-list">
                    <div class="skeleton skeleton-line" style="width:90%"></div>
                    <div class="skeleton skeleton-line" style="width:75%"></div>
                    <div class="skeleton skeleton-line" style="width:80%"></div>
                    <div class="skeleton skeleton-line" style="width:60%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 dash-widget">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-speedometer2 me-1"></i>User Health</h3></div>
            <div class="card-body" id="userHealth">
                <div class="metric-row">
                    <div class="d-flex justify-content-between"><span>Active users</span><span id="mActive">–</span></div>
                    <div class="progress" role="progressbar"><div class="progress-bar bg-success" id="barActive" style="width:0%"></div></div>
                </div>
                <div class="metric-row">
                    <div class="d-flex justify-content-between"><span>Inactive users</span><span id="mInactive">–</span></div>
                    <div class="progress" role="progressbar"><div class="progress-bar bg-secondary" id="barInactive" style="width:0%"></div></div>
                </div>
                <div class="metric-row">
                    <div class="d-flex justify-content-between"><span>Modules configured</span><span id="mModules">–</span></div>
                    <div class="progress" role="progressbar"><div class="progress-bar bg-info" id="barModules" style="width:0%"></div></div>
                </div>
                <div class="metric-row mb-0">
                    <div class="d-flex justify-content-between"><span>Roles defined</span><span id="mRoles">–</span></div>
                    <div class="progress" role="progressbar"><div class="progress-bar" id="barRoles" style="width:0%;background-color:var(--bs-primary)"></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Finance analytics (placeholder — structure ready, connect real data) -->
<div class="card mt-3 dash-widget">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-cash-coin me-1"></i>Financial Analytics</h3>
        <span class="badge text-bg-warning placeholder-badge" title="No accounting tables in this build yet">
            <i class="bi bi-exclamation-triangle me-1"></i>Sample — connect real data
        </span>
    </div>
    <div class="card-body">
        <div class="alert alert-info d-flex align-items-center small">
            <i class="bi bi-info-circle me-2"></i>
            These finance widgets are wired to <code>DashboardModel::financeKpis()</code> /
            <code>financeSeries()</code>. They currently return zeros — search <code>CONNECT-DATA</code>
            in the model and plug in your transactions/accounts queries. Suggested SQL is in the README.
        </div>
        <div class="row g-3">
            <div class="col-6 col-lg-3"><div class="border rounded p-3 h-100"><div class="text-secondary small">Total Income</div><div class="fs-4 fw-bold text-success" id="fIncome">₹0</div></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-3 h-100"><div class="text-secondary small">Total Expense</div><div class="fs-4 fw-bold text-danger" id="fExpense">₹0</div></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-3 h-100"><div class="text-secondary small">Balance</div><div class="fs-4 fw-bold text-primary" id="fBalance">₹0</div></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-3 h-100"><div class="text-secondary small">Debtors / Creditors</div><div class="fs-6 fw-bold" id="fDC">₹0 / ₹0</div></div></div>
        </div>
        <div class="row g-3 mt-0">
            <div class="col-lg-8"><div class="chart-box-sm"><div class="chart-skeleton skeleton skeleton-chart"></div><canvas id="chartFinance"></canvas></div></div>
            <div class="col-lg-4"><div class="chart-box-sm"><div class="chart-skeleton skeleton skeleton-chart"></div><canvas id="chartRvP"></canvas></div></div>
        </div>
    </div>
</div>

<!-- Recent logins (original table, kept) -->
<div class="card mt-3 dash-widget">
    <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-clock-history me-1"></i>Recent Logins</h3></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead><tr><th>User</th><th>Status</th><th>IP</th><th>When</th></tr></thead>
                <tbody>
                <?php if (empty($recentLogins)): ?>
                    <tr><td colspan="4" class="text-center text-secondary py-3">No activity yet.</td></tr>
                <?php else: foreach ($recentLogins as $log): ?>
                    <tr>
                        <td><?= esc($log['user_name'] ?? $log['username']) ?></td>
                        <td><?= $log['status'] === 'success' ? '<span class="badge text-bg-success">success</span>' : '<span class="badge text-bg-danger">failed</span>' ?></td>
                        <td><small><?= esc($log['ip_address']) ?></small></td>
                        <td><small><?= esc(date('d M, H:i', strtotime($log['created_at']))) ?></small></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
