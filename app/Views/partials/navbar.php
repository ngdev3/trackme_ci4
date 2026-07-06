<?php
$u = current_user();

/* 12 most-spoken languages for the in-app UI translator (shared helper). */
$erpLanguages = erp_languages();

$topNotifications = [];
$unreadNotifications = 0;
try {
    $notificationModel = new \App\Models\NotificationModel();
    $topNotifications = $notificationModel->latestVisible(7);
    $unreadNotifications = $notificationModel->unreadCount();
} catch (\Throwable $e) {
    $topNotifications = [];
    $unreadNotifications = 0;
}
$notificationIconMap = [
    'success'       => ['text-bg-success', 'bi-check-circle'],
    'error'         => ['text-bg-danger', 'bi-x-octagon'],
    'warning'       => ['text-bg-warning', 'bi-exclamation-triangle'],
    'info'          => ['text-bg-info', 'bi-info-circle'],
    'system_update' => ['text-bg-primary', 'bi-arrow-repeat'],
    'user_activity' => ['text-bg-secondary', 'bi-person-check'],
];
?>
<!-- Header / Navbar -->
<nav class="app-header navbar navbar-expand erp-topbar">
    <div class="container-fluid">
        <ul class="navbar-nav align-items-center topbar-left">
            <li class="nav-item">
                <a class="nav-link nav-square" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </a>
            </li>
            <li class="nav-item topbar-search-item d-none d-md-block ms-1">
                <div class="top-search">
                    <i class="bi bi-search"></i>
                    <input type="search" placeholder="Search ERP option..." aria-label="Search ERP option">
                </div>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto align-items-center gap-2 topbar-actions">
            <li class="nav-item topbar-company d-none d-xxl-block">
                <div class="company-chip">
                    <span data-current-firm-name>CR INDUSTRIES</span>
                    <strong>ID-</strong>
                    <span data-current-firm-code>20_FY_2026_2027_1</span>
                </div>
            </li>

            <li class="nav-item dropdown topbar-firm">
                <a class="nav-link nav-square topbar-trigger" href="#" data-bs-toggle="dropdown" aria-expanded="false" title="Change firm">
                    <i class="bi bi-building"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end topbar-dropdown firm-dropdown">
                    <span class="dropdown-header">Change Firm</span>
                    <?php foreach ([
                        ['CR INDUSTRIES', '20_FY_2026_2027_1', 'bg-primary'],
                        ['North Unit', 'NU_FY_2026_2027_1', 'bg-success'],
                        ['South Branch', 'SB_FY_2026_2027_1', 'bg-warning'],
                        ['Central Warehouse', 'CW_FY_2026_2027_1', 'bg-info'],
                        ['Export Division', 'EX_FY_2026_2027_1', 'bg-danger'],
                    ] as $idx => $firm): ?>
                        <button class="dropdown-item firm-option <?= $idx === 0 ? 'active' : '' ?>" type="button" data-firm-option data-firm-name="<?= esc($firm[0]) ?>" data-firm-code="<?= esc($firm[1]) ?>">
                            <span class="firm-dot <?= esc($firm[2]) ?>"></span>
                            <span><strong><?= esc($firm[0]) ?></strong><small><?= esc($firm[1]) ?></small></span>
                            <i class="bi bi-check2 ms-auto"></i>
                        </button>
                    <?php endforeach; ?>
                </div>
            </li>

            <li class="nav-item dropdown topbar-notification">
                <a class="nav-link nav-square topbar-trigger <?= $unreadNotifications > 0 ? 'has-alert' : '' ?>" href="#" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="notification-count-badge" data-notification-badge><?= esc($unreadNotifications > 99 ? '99+' : $unreadNotifications) ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end topbar-dropdown notification-dropdown">
                    <div class="dropdown-head">
                        <strong>Notifications</strong>
                        <span class="badge text-bg-primary" data-notification-count><?= esc($unreadNotifications) ?> unread</span>
                    </div>
                    <?php if (empty($topNotifications)): ?>
                        <div class="notification-empty">
                            <i class="bi bi-bell-slash"></i>
                            <span>No notifications</span>
                        </div>
                    <?php else: foreach ($topNotifications as $n): ?>
                        <?php $meta = $notificationIconMap[$n['type']] ?? ['text-bg-light', 'bi-bell']; ?>
                        <a href="<?= esc(! empty($n['action_url']) ? $n['action_url'] : site_url('notifications')) ?>" class="dropdown-item notification-item <?= empty($n['is_read']) ? 'is-unread' : '' ?>" data-notification-id="<?= esc($n['id']) ?>">
                            <span class="notification-icon <?= esc($meta[0]) ?>"><i class="bi <?= esc($meta[1]) ?>"></i></span>
                            <span>
                                <strong><?= esc($n['title']) ?></strong>
                                <small><?= esc($n['module'] ?: 'Global') ?> &middot; <?= esc(date('d M Y, H:i', strtotime($n['created_at']))) ?></small>
                            </span>
                        </a>
                    <?php endforeach; endif; ?>
                    <div class="dropdown-divider"></div>
                    <form method="post" action="<?= site_url('notifications/mark-all-read') ?>" class="px-2 pb-2">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-primary w-100" type="submit"><i class="bi bi-check-all me-1"></i>Mark all as read</button>
                    </form>
                    <a href="<?= site_url('notifications') ?>" class="dropdown-item text-center fw-semibold">View all notifications</a>
                </div>
            </li>

            <li class="nav-item dropdown topbar-icon-menu">
                <a class="nav-link nav-square topbar-trigger" href="#" data-bs-toggle="dropdown" aria-expanded="false" title="Quick actions">
                    <i class="bi bi-grid-3x3-gap"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end topbar-dropdown icon-dropdown">
                    <div class="dropdown-head">
                        <strong>Quick Actions</strong>
                        <span class="badge text-bg-light border">Demo</span>
                    </div>
                    <div class="icon-action-grid">
                        <a href="<?= site_url('users') ?>" class="icon-action"><i class="bi bi-people"></i><span>Users</span></a>
                        <a href="<?= site_url('roles') ?>" class="icon-action"><i class="bi bi-shield-lock"></i><span>Roles</span></a>
                        <a href="<?= site_url('permissions') ?>" class="icon-action"><i class="bi bi-grid-3x3-gap"></i><span>Permissions</span></a>
                        <a href="<?= site_url('modules') ?>" class="icon-action"><i class="bi bi-boxes"></i><span>Modules</span></a>
                        <a href="<?= site_url('activity-logs') ?>" class="icon-action"><i class="bi bi-activity"></i><span>Activity</span></a>
                        <a href="<?= site_url('login-logs') ?>" class="icon-action"><i class="bi bi-clock-history"></i><span>Logins</span></a>
                    </div>
                </div>
            </li>

            <li class="nav-item dropdown topbar-session">
                <a class="session-chip dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false" title="Session status">
                    <span class="session-icon"><i class="bi bi-stopwatch"></i></span>
                    <span class="session-label">ACTIVE<br>LEFT</span>
                    <strong><span data-session-active>01:05:56</span><br><span data-session-left>03:54:04</span></strong>
                </a>
                <div class="dropdown-menu dropdown-menu-end topbar-dropdown session-dropdown">
                    <div class="dropdown-head">
                        <strong>Session Status</strong>
                        <span class="badge text-bg-success">Active</span>
                    </div>
                    <div class="session-detail">
                        <span><i class="bi bi-play-circle"></i> Active Time</span>
                        <strong data-session-active-detail>01:05:56</strong>
                    </div>
                    <div class="session-detail">
                        <span><i class="bi bi-hourglass-split"></i> Time Left</span>
                        <strong data-session-left-detail>03:54:04</strong>
                    </div>
                    <div class="session-progress"><span style="width: 22%"></span></div>
                    <small class="session-note">Demo timer shown for UI preview. Real expiry can be wired to server session later.</small>
                    <div class="dropdown-divider"></div>
                    <a href="<?= site_url('logout') ?>" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>End Session</a>
                </div>
            </li>

            <li class="nav-item dropdown topbar-lang">
                <a class="nav-link nav-square topbar-trigger" href="#" data-bs-toggle="dropdown" aria-expanded="false" title="Language">
                    <i class="bi bi-translate"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end topbar-dropdown lang-dropdown">
                    <div class="dropdown-head">
                        <strong>Language</strong>
                        <span class="badge text-bg-light border" data-lang-current translate="no">English</span>
                    </div>
                    <div class="lang-list">
                        <?php foreach ($erpLanguages as $code => $lang): ?>
                            <button type="button" class="dropdown-item lang-option" data-lang="<?= esc($code, 'attr') ?>" data-lang-label="<?= esc($lang['native'], 'attr') ?>" translate="no">
                                <span class="lang-flag"><?= $lang['flag'] ?></span>
                                <span>
                                    <strong><?= esc($lang['native']) ?></strong>
                                    <small><?= esc($lang['name']) ?></small>
                                </span>
                                <i class="bi bi-check2 lang-check"></i>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link nav-square" href="#" data-theme-toggle title="Toggle dark / light">
                    <i class="bi bi-moon-stars-fill" data-theme-icon></i>
                </a>
            </li>
            <li class="nav-item topbar-theme-panel d-none d-md-block">
                <a class="nav-link nav-square" href="#" data-bs-toggle="offcanvas" data-bs-target="#themePanel" title="Customize theme">
                    <i class="bi bi-palette"></i>
                </a>
            </li>

            <li class="nav-item dropdown">
                <a class="nav-link user-chip dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                    <?php if (! empty($u['profile_image'])): ?>
                        <img src="<?= base_url('uploads/users/' . $u['profile_image']) ?>" class="avatar-sm" alt="avatar">
                    <?php elseif (! empty($u['avatar_url'])): ?>
                        <img src="<?= esc($u['avatar_url'], 'attr') ?>" class="avatar-sm" alt="avatar" referrerpolicy="no-referrer">
                    <?php else: ?>
                        <span class="avatar-sm avatar-fallback"><i class="bi bi-person"></i></span>
                    <?php endif; ?>
                    <span class="d-none d-lg-inline fw-semibold"><?= esc(! empty($u['name']) ? $u['name'] : 'Super Admin') ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end topbar-dropdown profile-dropdown">
                    <div class="profile-menu-head">
                        <?php if (! empty($u['profile_image'])): ?>
                            <img src="<?= base_url('uploads/users/' . $u['profile_image']) ?>" class="avatar-sm" alt="avatar">
                        <?php else: ?>
                            <span class="avatar-sm avatar-fallback"><i class="bi bi-person"></i></span>
                        <?php endif; ?>
                        <span>
                            <strong><?= esc(! empty($u['name']) ? $u['name'] : 'Super Admin') ?></strong>
                            <small><?= esc(! empty($u['email']) ? $u['email'] : 'Signed in') ?></small>
                        </span>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="<?= site_url('profile') ?>" class="dropdown-item"><i class="bi bi-person me-2"></i>My Profile</a>
                    <a href="<?= site_url('profile') ?>#change-password" class="dropdown-item"><i class="bi bi-key me-2"></i>Change Password</a>
                    <a href="<?= site_url('my-login-history') ?>" class="dropdown-item"><i class="bi bi-clock-history me-2"></i>Login History</a>
                    <a href="#" class="dropdown-item" data-theme-toggle><i class="bi bi-moon-stars me-2"></i>Toggle Theme</a>
                    <div class="dropdown-divider"></div>
                    <div class="profile-palette">
                        <span>Color Combination</span>
                        <div class="palette-row">
                            <button type="button" class="palette-choice blue" data-palette-choice data-primary="#0d6efd" data-secondary="#6c757d" title="Blue"></button>
                            <button type="button" class="palette-choice green" data-palette-choice data-primary="#198754" data-secondary="#20c997" title="Green"></button>
                            <button type="button" class="palette-choice orange" data-palette-choice data-primary="#e85d24" data-secondary="#f59f00" title="Orange"></button>
                            <button type="button" class="palette-choice slate" data-palette-choice data-primary="#526f8b" data-secondary="#78909c" title="Slate"></button>
                        </div>
                    </div>
                    <a href="<?= site_url('logout') ?>" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                </div>
            </li>
        </ul>
    </div>
</nav>
