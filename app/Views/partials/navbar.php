<?php $u = current_user(); ?>
<!-- Header / Navbar -->
<nav class="app-header navbar navbar-expand erp-topbar">
    <div class="container-fluid">
        <ul class="navbar-nav align-items-center">
            <li class="nav-item">
                <a class="nav-link nav-square" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-block ms-1">
                <div class="top-search">
                    <i class="bi bi-search"></i>
                    <input type="search" placeholder="Search ERP option..." aria-label="Search ERP option">
                </div>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto align-items-center gap-2">
            <li class="nav-item d-none d-xl-block">
                <div class="company-chip">
                    <span>CR INDUSTRIES</span>
                    <strong>ID-</strong>
                    <span>20_FY_2026_2027_1</span>
                </div>
            </li>
            <li class="nav-item topbar-notification">
                <a class="nav-link nav-square" href="#" title="Notifications">
                    <i class="bi bi-bell"></i>
                </a>
            </li>
            <li class="nav-item d-none d-lg-block">
                <div class="session-chip">
                    <span class="session-icon"><i class="bi bi-stopwatch"></i></span>
                    <span class="session-label">ACTIVE<br>LEFT</span>
                    <strong>01:05:56<br>03:54:04</strong>
                </div>
            </li>
            <!-- Dark / light toggle -->
            <li class="nav-item">
                <a class="nav-link nav-square" href="#" data-theme-toggle title="Toggle dark / light">
                    <i class="bi bi-moon-stars-fill" data-theme-icon></i>
                </a>
            </li>
            <!-- Theme customizer -->
            <li class="nav-item topbar-theme-panel">
                <a class="nav-link nav-square" href="#" data-bs-toggle="offcanvas" data-bs-target="#themePanel" title="Customize theme">
                    <i class="bi bi-palette"></i>
                </a>
            </li>

            <!-- User menu -->
            <li class="nav-item dropdown">
                <a class="nav-link user-chip dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" href="#">
                    <?php if (! empty($u['profile_image'])): ?>
                        <img src="<?= base_url('uploads/users/' . $u['profile_image']) ?>" class="avatar-sm" alt="avatar">
                    <?php else: ?>
                        <span class="avatar-sm avatar-fallback"><i class="bi bi-person"></i></span>
                    <?php endif; ?>
                    <span class="d-none d-sm-inline fw-semibold"><?= esc($u['name'] ?? 'Super Admin') ?></span>
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
