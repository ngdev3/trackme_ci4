<?php /** Users — creative charts view + AJAX table fragment. Rendered inside layout.php. */ ?>
<script>window.USERS_CHARTS = <?= json_encode($charts) ?>;</script>

<div class="cust-page">
<section class="cust-hero">
    <div>
        <h4 class="cust-title">User Management</h4>
        <p class="cust-subtitle"><?= ! empty($showRoleType) ? 'Manage accounts, roles and access across your workspace.' : 'Manage assigned accounts and module access across your workspace.' ?></p>
    </div>
    <form class="cust-hero-actions" method="get" id="usersSearchForm" autocomplete="off">
        <input type="hidden" name="sort" value="<?= esc($sort) ?>">
        <input type="hidden" name="dir" value="<?= esc($dir) ?>">
        <div class="cust-search">
            <i class="bi bi-search cust-search-ic"></i>
            <input type="search" name="q" id="usersSearch" value="<?= esc($search) ?>" placeholder="Search name, email, username, mobile...">
            <?php if ($search !== ''): ?><a href="<?= site_url($baseRoute) ?>" class="cust-search-clear" title="Clear"><i class="bi bi-x-lg"></i></a><?php endif; ?>
        </div>
        <?php if (can($moduleCode, 'add')): ?>
            <a href="<?= site_url($baseRoute . '/create') ?>" class="cust-btn cust-btn-primary"><i class="bi bi-plus-lg"></i> Add User</a>
        <?php endif; ?>
    </form>
</section>

<!-- ===== Stat cards ===== -->
<section class="cust-snap-grid">
    <?php
    $cards = [
        [($scopeLabel ?? 'All Users'), $stats['total_users'], 'bi-people', 'ic-blue'],
        ['Active', $stats['active_users'], 'bi-person-check', 'ic-green'],
        ['Inactive', $stats['inactive_users'], 'bi-person-dash', 'ic-red'],
    ];
    if (! empty($showRoleType)) {
        $cards[] = ['Roles', $stats['total_roles'], 'bi-shield-lock', 'ic-violet'];
        $cards[] = ['User Types', $stats['total_types'], 'bi-person-badge', 'ic-amber'];
    }
    foreach ($cards as [$label, $val, $icon, $tone]): ?>
        <div class="cust-snap"><span class="cust-snap-ic <?= esc($tone) ?>"><i class="bi <?= esc($icon) ?>"></i></span>
            <div><p class="cust-snap-label"><?= esc($label) ?></p><p class="cust-snap-value"><?= number_format((int) $val) ?></p></div></div>
    <?php endforeach; ?>
</section>

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
</div>
