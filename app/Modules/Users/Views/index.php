<?php /** Users — creative charts view + AJAX table fragment. Rendered inside layout.php. */ ?>
<script>window.USERS_CHARTS = <?= json_encode($charts) ?>;</script>

<!-- ===== Header ===== -->
<div class="users-hero fade-up">
    <div class="users-hero-copy">
        <h3 class="mb-1"><i class="bi bi-people-fill me-2"></i>User Management</h3>
        <p class="mb-0"><?= ! empty($showRoleType) ? 'Manage accounts, roles and access across your workspace.' : 'Manage assigned accounts and module access across your workspace.' ?></p>
    </div>
    <form class="users-hero-actions" method="get" id="usersSearchForm" autocomplete="off">
        <input type="hidden" name="sort" value="<?= esc($sort) ?>">
        <input type="hidden" name="dir" value="<?= esc($dir) ?>">
        <div class="users-search">
            <i class="bi bi-search"></i>
            <input type="search" name="q" id="usersSearch" value="<?= esc($search) ?>" placeholder="Search name, email, username, mobile...">
            <span class="users-search-spin"><span class="spinner-border spinner-border-sm"></span></span>
        </div>
        <?php if (can($moduleCode, 'add')): ?>
            <a href="<?= site_url($baseRoute . '/create') ?>" class="btn btn-light btn-sm text-nowrap fw-semibold"><i class="bi bi-plus-lg"></i> Add User</a>
        <?php endif; ?>
    </form>
</div>

<!-- ===== Stat cards ===== -->
<div class="row g-3 mb-3">
    <?php
    $cards = [
        [($scopeLabel ?? 'All Users'), $stats['total_users'], 'bi-people', '#6366f1', '#8b5cf6'],
        ['Active', $stats['active_users'], 'bi-person-check', '#22c55e', '#4ade80'],
        ['Inactive', $stats['inactive_users'], 'bi-person-dash', '#ef4444', '#fb7185'],
    ];
    if (! empty($showRoleType)) {
        $cards[] = ['Roles', $stats['total_roles'], 'bi-shield-lock', '#0ea5e9', '#22d3ee'];
        $cards[] = ['User Types', $stats['total_types'], 'bi-person-badge', '#f59e0b', '#fbbf24'];
    }
    foreach ($cards as $idx => [$label, $val, $icon, $c1, $c2]): ?>
        <div class="col-6 col-md-4 col-xl">
            <div class="stat-card fade-up" style="animation-delay:<?= $idx * 60 ?>ms">
                <div class="stat-icon" style="background:linear-gradient(135deg,<?= $c1 ?>,<?= $c2 ?>)"><i class="bi <?= $icon ?>"></i></div>
                <div>
                    <div class="stat-num" data-count="<?= (int) $val ?>">0</div>
                    <div class="stat-label"><?= esc($label) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ===== Charts ===== -->
<div class="row g-3 mb-3">
    <?php if (! empty($showRoleType)): ?>
    <div class="col-lg-3 col-md-6">
        <div class="card chart-card fade-up h-100"><div class="card-body">
            <h6 class="chart-title"><i class="bi bi-pie-chart me-1"></i>By Role</h6>
            <div class="chart-box"><canvas id="roleChart"></canvas></div>
        </div></div>
    </div>
    <?php endif; ?>
    <div class="col-lg-3 col-md-6">
        <div class="card chart-card fade-up h-100" style="animation-delay:80ms"><div class="card-body">
            <h6 class="chart-title"><i class="bi bi-pie-chart-fill me-1"></i>Active vs Inactive</h6>
            <div class="chart-box"><canvas id="statusChart"></canvas></div>
        </div></div>
    </div>
    <?php if (! empty($showRoleType)): ?>
    <div class="col-lg-3 col-md-6">
        <div class="card chart-card fade-up h-100" style="animation-delay:160ms"><div class="card-body">
            <h6 class="chart-title"><i class="bi bi-bar-chart me-1"></i>By Type</h6>
            <div class="chart-box"><canvas id="typeChart"></canvas></div>
        </div></div>
    </div>
    <?php endif; ?>
    <div class="col-lg-3 col-md-6">
        <div class="card chart-card fade-up h-100" style="animation-delay:240ms"><div class="card-body">
            <h6 class="chart-title"><i class="bi bi-graph-up-arrow me-1"></i>Growth (6 mo)</h6>
            <div class="chart-box"><canvas id="growthChart"></canvas></div>
        </div></div>
    </div>
</div>

<!-- ===== Users list (AJAX-swappable fragment) ===== -->
<div id="usersList" data-list-url="<?= site_url('users/list') ?>">
    <?= view('Modules\Users\Views\_list', [
        'rows'       => $rows,
        'sort'       => $sort,
        'dir'        => $dir,
        'per'        => $per,
        'search'     => $search,
        'pager'      => $pager,
        'scopeLabel' => $scopeLabel ?? 'All Users',
        'showRoleType' => $showRoleType ?? false,
        'moduleCode' => $moduleCode,
        'baseRoute'  => $baseRoute,
    ]) ?>
</div>
