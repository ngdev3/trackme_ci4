<?php $u = current_user(); ?>
<!-- Sidebar -->
<aside class="app-sidebar erp-sidebar" data-bs-theme="dark">
    <div class="sidebar-brand erp-sidebar-brand">
        <a href="<?= site_url('dashboard') ?>" class="brand-link">
            <span class="brand-mark"><i class="bi bi-boxes"></i></span>
            <span class="brand-text"><?= esc($u['name'] ?? 'Administrator') ?></span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <div class="sidebar-search">
            <i class="bi bi-search"></i>
            <input type="search" placeholder="Search menu option" aria-label="Search menu option">
        </div>
        <nav class="mt-3">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation"
                aria-label="Main navigation" data-accordion="false">
                <?= render_sidebar() ?>
            </ul>
        </nav>
    </div>
</aside>
