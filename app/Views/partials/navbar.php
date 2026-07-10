<?php
$u = current_user();

/* Human-readable role label shown in the top bar so the user always knows
   which hat they are wearing: Super Admin, or their firm role (Owner/Admin/…),
   falling back to the account type. */
$erpRoleLabel = session('account_type') === 'super_admin'
    ? 'Super Admin'
    : ucwords(str_replace('_', ' ', (string) (session('company_role') ?: session('account_type') ?: 'User')));

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
            <?php
            // Build the searchable destinations for the top-bar quick search,
            // filtered by what this user can actually reach.
            helper(['company', 'auth']);
            $navSearch = [];
            $nsAdd = static function (string $label, string $url, string $icon, string $kw = '') use (&$navSearch) {
                $navSearch[] = ['label' => $label, 'url' => site_url($url), 'icon' => $icon, 'kw' => $kw];
            };
            $nsAdd('Dashboard', 'dashboard', 'bi-speedometer2', 'home overview');
            if (function_exists('can') && can('transactions', 'view')) {
                $nsAdd('Hisaab Kitaab Vahi', 'transactions', 'bi-journal-text', 'jama naam transactions ledger');
                $nsAdd('Account Statement', 'transactions/statement', 'bi-file-earmark-text', 'party ledger');
            }
            if (function_exists('firm_can') && firm_can('rokad')) {
                $nsAdd('Rokad Parcha', 'rokad', 'bi-cash-stack', 'cash book rokad');
            }
            // Accounting is reserved for the Super Admin only.
            if (is_super_admin_account() && function_exists('firm_can') && firm_can('accounting')) {
                $nsAdd('Ledgers', 'accounting/ledgers', 'bi-journals', 'accounting');
                $nsAdd('Day Book (Vouchers)', 'accounting/vouchers', 'bi-receipt', 'vouchers accounting');
            }
            if (function_exists('can') && can('notes', 'view')) { $nsAdd('Notes', 'notes', 'bi-sticky', 'notes'); }
            if (function_exists('can') && can('reminders', 'view')) { $nsAdd('Reminders', 'reminders', 'bi-alarm', 'reminders'); }
            if (function_exists('can') && can('passwords', 'view')) {
                $nsAdd('Password Manager', 'passwords/list', 'bi-shield-lock', 'password vault list');
                $nsAdd('Password List', 'passwords/list', 'bi-list-check', 'password vault credentials');
            }
            if (function_exists('can') && can('passwords', 'add')) {
                $nsAdd('Add Password', 'passwords/add', 'bi-plus-circle', 'password vault create new');
            }
            // Firm Users is reserved for the Super Admin only.
            if (is_super_admin_account() && function_exists('firm_can') && firm_can('firm_users')) { $nsAdd('Firm Users', 'firm-users', 'bi-people', 'staff team'); }
            if (session('account_type') !== 'firm_user') { $nsAdd('Company Profile', 'company/profile', 'bi-building', 'firm company switch'); }
            $nsAdd('My Profile', 'profile', 'bi-person', 'account profile');
            $nsAdd('Settings', 'settings', 'bi-gear', 'settings preferences');
            $nsAdd('Help & Support', 'help', 'bi-life-preserver', 'help support faq contact whatsapp email');
            ?>
            <li class="nav-item topbar-search-item d-none d-md-block ms-1">
                <div class="top-search" id="navSearchBox">
                    <i class="bi bi-search"></i>
                    <input type="search" id="navSearchInput" placeholder="Search ERP option..." aria-label="Search ERP option"
                           autocomplete="off" role="combobox" aria-expanded="false" aria-controls="navSearchResults">
                    <div class="nav-search-results" id="navSearchResults" role="listbox" hidden></div>
                </div>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto align-items-center gap-2 topbar-actions">
            <?php
            $activeCompany  = service('company')->current();
            $userCompanies  = service('company')->userCompanies();
            $activeCompanyId = $activeCompany['id'] ?? null;

            // Capture the current page so a company switch reloads it in place
            // (the controller sends record/detail pages to the dashboard instead).
            $switchReturnQs = (string) service('request')->getServer('QUERY_STRING');
            $switchReturn   = urlencode(uri_string() . ($switchReturnQs !== '' ? '?' . $switchReturnQs : ''));
            ?>
            <li class="nav-item topbar-company d-none d-md-flex align-items-center">
                <a class="company-chip text-decoration-none" href="<?= site_url('company/profile') ?>" title="Active company — click to view / switch">
                    <i class="bi bi-building-fill company-chip-ic"></i>
                    <?php if ($activeCompany): ?>
                        <span class="company-chip-name" data-current-firm-name><?= esc($activeCompany['name']) ?></span>
                        <span class="company-chip-fy"><strong>FY</strong> <span data-current-firm-code><?= esc(date('Y', strtotime($activeCompany['financial_year_from']))) ?></span></span>
                    <?php else: ?>
                        <span class="company-chip-name" data-current-firm-name>No company</span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item dropdown topbar-firm">
                <a class="nav-link nav-square topbar-trigger" href="#" data-bs-toggle="dropdown" aria-expanded="false" title="Change company">
                    <i class="bi bi-building"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end topbar-dropdown firm-dropdown">
                    <span class="dropdown-header">Your Companies</span>
                    <?php if ($activeCompany): ?>
                        <a class="dropdown-item" href="<?= site_url('company/profile') ?>"><i class="bi bi-building-gear me-2"></i>Company Profile</a>
                        <div class="dropdown-divider"></div>
                    <?php endif; ?>
                    <?php if (empty($userCompanies)): ?>
                        <span class="dropdown-item-text text-muted small">No companies yet.</span>
                    <?php else: foreach ($userCompanies as $firm): ?>
                        <a class="dropdown-item firm-option <?= (int) $firm['id'] === (int) $activeCompanyId ? 'active' : '' ?>" href="<?= site_url('company/switch/' . $firm['id']) . '?return=' . $switchReturn ?>">
                            <span class="firm-dot bg-primary"></span>
                            <span><strong><?= esc($firm['name']) ?></strong><small><?= esc(ucfirst($firm['membership_role'] ?? 'member')) ?> &middot; <?= esc($firm['state']) ?></small></span>
                            <?php if ((int) $firm['id'] === (int) $activeCompanyId): ?><i class="bi bi-check2 ms-auto"></i><?php endif; ?>
                        </a>
                    <?php endforeach; endif; ?>
                    <?php if (is_super_admin_account()): ?>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?= site_url('firm-users') ?>"><i class="bi bi-people me-2"></i>Manage Firm Users</a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <?php if (session('account_type') !== 'firm_user'): ?>
                        <a class="dropdown-item" href="<?= site_url('company/create') ?>"><i class="bi bi-plus-circle me-2"></i>Add Company</a>
                    <?php endif; ?>
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
                <a class="nav-link nav-square" href="<?= site_url('help') ?>" title="Help &amp; Support">
                    <i class="bi bi-question-circle"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-square" href="<?= site_url('dashboard') ?>" title="Go to dashboard">
                    <i class="bi bi-house-door"></i>
                </a>
            </li>
            <?php if (session('impersonator_id')): ?>
                <li class="nav-item">
                    <a class="nav-link nav-square text-warning" href="<?= site_url('impersonate/stop') ?>" title="Exit account — back to Super Admin">
                        <i class="bi bi-box-arrow-left"></i>
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link nav-square text-danger" href="<?= site_url('logout') ?>" title="Logout"
                   data-confirm="You will be signed out of your account." data-confirm-title="Log out?" data-confirm-btn="Yes, log out" data-confirm-icon="warning">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </li>

            <li class="nav-item d-none d-md-flex align-items-center">
                <span class="badge rounded-pill erp-role-badge" title="Your role in the app">
                    <i class="bi bi-person-badge me-1"></i><span translate="no"><?= esc($erpRoleLabel) ?></span>
                </span>
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
                    <?php if (session('impersonator_id')): ?>
                        <div class="dropdown-item-text small text-warning"><i class="bi bi-incognito me-1"></i>Accessed by Super Admin <strong><?= esc(session('impersonator_name')) ?></strong></div>
                        <a href="<?= site_url('impersonate/stop') ?>" class="dropdown-item text-warning fw-semibold"><i class="bi bi-box-arrow-left me-2"></i>Exit account — back to Super Admin</a>
                        <div class="dropdown-divider"></div>
                    <?php endif; ?>
                    <?php if (session('account_type') === 'super_admin' && session('is_superadmin') && ! session('impersonator_id')): ?>
                        <a href="<?= site_url('admin') ?>" class="dropdown-item"><i class="bi bi-shield-shaded me-2"></i>Super Admin Panel</a>
                    <?php endif; ?>
                    <a href="<?= site_url('profile') ?>" class="dropdown-item"><i class="bi bi-person me-2"></i>My Profile</a>
                    <a href="<?= site_url('profile') ?>#change-password" class="dropdown-item"><i class="bi bi-key me-2"></i>Change Password</a>
                    <a href="<?= site_url('my-login-history') ?>" class="dropdown-item"><i class="bi bi-clock-history me-2"></i>Login History</a>
                    <a href="<?= site_url('help') ?>" class="dropdown-item"><i class="bi bi-life-preserver me-2"></i>Help &amp; Support</a>
                    <a href="<?= site_url('settings') ?>#tab-appearance" class="dropdown-item"><i class="bi bi-palette me-2"></i>Appearance Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="<?= site_url('logout') ?>" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i><?= session('impersonator_id') ? 'Log out completely' : 'Logout' ?></a>
                </div>
            </li>
        </ul>
    </div>
</nav>

<!-- Top-bar quick search (client-side over the user's accessible destinations) -->
<style>
    .erp-role-badge {
        background: var(--bs-primary-bg-subtle, #e7f1ff);
        color: var(--bs-primary-text-emphasis, #0a58ca);
        border: 1px solid var(--bs-primary-border-subtle, #b6d4fe);
        font-weight: 600; font-size: .78rem; padding: .35rem .7rem; letter-spacing: .2px;
    }
    .top-search { position: relative; }
    .nav-search-results {
        position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 1080;
        background: var(--bs-body-bg, #fff); border: 1px solid var(--erp-border, rgba(0,0,0,.12));
        border-radius: 12px; box-shadow: 0 12px 34px rgba(15,23,42,.18); padding: 6px; max-height: 60vh; overflow-y: auto;
    }
    .nav-search-item {
        display: flex; align-items: center; gap: .6rem; padding: .5rem .6rem; border-radius: 8px;
        color: inherit; text-decoration: none; cursor: pointer; font-size: .92rem;
    }
    .nav-search-item .bi { font-size: 1.05rem; opacity: .8; width: 1.2rem; text-align: center; }
    .nav-search-item small { color: var(--bs-secondary-color, #6c757d); margin-left: auto; }
    .nav-search-item.active, .nav-search-item:hover { background: var(--bs-primary-bg-subtle, #e7f1ff); }
    .nav-search-empty { padding: .75rem .6rem; color: var(--bs-secondary-color, #6c757d); font-size: .9rem; text-align: center; }
</style>
<script>
(function () {
    var INDEX = <?= json_encode(array_values($navSearch ?? []), JSON_UNESCAPED_SLASHES) ?>;
    var input = document.getElementById('navSearchInput');
    var panel = document.getElementById('navSearchResults');
    if (!input || !panel) { return; }
    var active = -1, shown = [];

    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

    function render(list) {
        shown = list; active = -1;
        if (!list.length) { panel.innerHTML = '<div class="nav-search-empty">No matches. Try “ledger”, “password”, “help”…</div>'; }
        else {
            panel.innerHTML = list.map(function (it, i) {
                return '<a class="nav-search-item" href="' + it.url + '" data-i="' + i + '">'
                    + '<i class="bi ' + esc(it.icon) + '"></i><span>' + esc(it.label) + '</span></a>';
            }).join('');
        }
        panel.hidden = false; input.setAttribute('aria-expanded', 'true');
    }

    function close() { panel.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; }

    function search(q) {
        q = q.trim().toLowerCase();
        if (!q) { close(); return; }
        var res = INDEX.filter(function (it) {
            return (it.label + ' ' + (it.kw || '')).toLowerCase().indexOf(q) !== -1;
        }).slice(0, 8);
        render(res);
    }

    function highlight() {
        panel.querySelectorAll('.nav-search-item').forEach(function (el, i) {
            el.classList.toggle('active', i === active);
        });
    }

    input.addEventListener('input', function () { search(input.value); });
    input.addEventListener('focus', function () { if (input.value.trim()) { search(input.value); } });
    input.addEventListener('keydown', function (e) {
        if (panel.hidden) { return; }
        if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, shown.length - 1); highlight(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, 0); highlight(); }
        else if (e.key === 'Enter') {
            e.preventDefault();
            var pick = active >= 0 ? shown[active] : shown[0];
            if (pick) { window.location.href = pick.url; }
        } else if (e.key === 'Escape') { close(); input.blur(); }
    });
    document.addEventListener('click', function (e) {
        if (!panel.hidden && !e.target.closest('#navSearchBox')) { close(); }
    });
})();
</script>
