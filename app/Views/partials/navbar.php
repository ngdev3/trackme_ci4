<?php $u = current_user(); ?>
<!-- Header / Navbar -->
<nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="bi bi-list"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-block">
                <a href="<?= site_url('dashboard') ?>" class="nav-link">Home</a>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto">
            <!-- Dark / light toggle -->
            <li class="nav-item">
                <a class="nav-link" href="#" data-theme-toggle title="Toggle dark / light">
                    <i class="bi bi-moon-stars-fill" data-theme-icon></i>
                </a>
            </li>
            <!-- Theme customizer -->
            <li class="nav-item">
                <a class="nav-link" href="#" data-bs-toggle="offcanvas" data-bs-target="#themePanel" title="Customize theme">
                    <i class="bi bi-palette"></i>
                </a>
            </li>

            <!-- User menu -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" href="#">
                    <?php if (! empty($u['profile_image'])): ?>
                        <img src="<?= base_url('uploads/users/' . $u['profile_image']) ?>" class="avatar-sm" alt="avatar">
                    <?php else: ?>
                        <i class="bi bi-person-circle fs-5"></i>
                    <?php endif; ?>
                    <span class="d-none d-sm-inline"><?= esc($u['name'] ?? 'User') ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <span class="dropdown-header"><?= esc($u['email'] ?? '') ?></span>
                    <div class="dropdown-divider"></div>
                    <a href="<?= site_url('profile') ?>" class="dropdown-item"><i class="bi bi-person me-2"></i>My Profile</a>
                    <a href="<?= site_url('logout') ?>" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                </div>
            </li>
        </ul>
    </div>
</nav>
