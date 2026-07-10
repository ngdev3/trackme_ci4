<?php $u = current_user(); ?>
<style>
/* Plan-locked menu items: shown, but visually marked. Clicking one is caught by
   the 'feature' route filter and redirected to the Subscription page. */
.erp-sidebar .nav-link.is-locked { opacity: .72; }
.erp-sidebar .nav-lock { margin-left: 6px; font-size: .72rem; color: #f59e0b; }
.erp-sidebar .nav-link.is-locked:hover { opacity: 1; }
</style>
<!-- Sidebar -->
<aside class="app-sidebar erp-sidebar">
    <div class="sidebar-brand erp-sidebar-brand">
        <a href="<?= site_url('dashboard') ?>" class="brand-link">
            <span class="brand-mark"><i class="bi bi-boxes"></i></span>
            <span class="brand-copy">
                <span class="brand-kicker"><?= esc(setting('app_name', 'ERP Admin')) ?></span>
                <span class="brand-text"><?= esc(! empty($u['name']) ? $u['name'] : 'Administrator') ?></span>
            </span>
        </a>
    </div>

    <?php $sideScore = profile_score($u ?? []); ?>
    <a href="<?= site_url('profile') ?>" class="sidebar-user" title="View your profile">
        <?= user_avatar($u, 'sidebar-user-avatar', 'bi-person') ?>
        <span class="sidebar-user-copy">
            <strong><?= esc(! empty($u['name']) ? $u['name'] : 'My Profile') ?></strong>
            <small><?= esc(ucwords(str_replace('_', ' ', (string) (session('account_type') ?: 'member')))) ?></small>
            <span class="sidebar-user-score" aria-label="Profile <?= esc($sideScore['percent']) ?>% complete">
                <span class="sus-bar"><span class="sus-fill bg-<?= esc($sideScore['color']) ?>" style="width: <?= esc($sideScore['percent']) ?>%"></span></span>
                <span class="sus-pct"><?= esc($sideScore['percent']) ?>%</span>
            </span>
        </span>
    </a>

    <div class="sidebar-wrapper">
        <div class="sidebar-search">
            <i class="bi bi-search"></i>
            <input type="search" placeholder="Search menu option" aria-label="Search menu option">
        </div>
        <nav class="mt-3">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation"
                aria-label="Main navigation" data-accordion="false">
                <?= is_super_admin_account() ? render_sidebar() : render_firm_sidebar() ?>
            </ul>
        </nav>
    </div>
</aside>
