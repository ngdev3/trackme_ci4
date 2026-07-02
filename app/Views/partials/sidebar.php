<!-- Sidebar -->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="<?= site_url('dashboard') ?>" class="brand-link">
            <i class="bi bi-boxes brand-image opacity-75 ms-2"></i>
            <span class="brand-text fw-light">ERP&nbsp;<b>Admin</b></span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation"
                aria-label="Main navigation" data-accordion="false">
                <?= render_sidebar() ?>
            </ul>
        </nav>
    </div>
</aside>
