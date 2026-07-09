<?php
/**
 * Access Denied page — rendered by PermissionFilter for unauthorised users.
 * Standalone (does not require an active session context beyond helpers).
 */
helper(['url', 'settings', 'ui']);
$appName = function_exists('setting') ? setting('app_name', 'ERP Admin') : 'ERP Admin';
$themeMode = function_exists('setting') ? setting('theme_mode', 'system') : 'system';
$themeMode = in_array($themeMode, ['light', 'dark', 'system'], true) ? $themeMode : 'system';
$isImpersonating = (bool) session('impersonator_id');
$deniedModule    = $module ?? '';
$dashboardLoops  = $deniedModule === 'dashboard'; // the "Back to Dashboard" button would loop
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied &middot; <?= esc($appName) ?></title>
    <script>
        (function () {
            var mode = <?= json_encode($themeMode) ?>;
            document.documentElement.setAttribute('data-bs-theme', mode === 'system' && window.matchMedia
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : (mode === 'dark' ? 'dark' : 'light'));
        })();
    </script>
    <link rel="stylesheet" href="<?= erp_asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= erp_asset('assets/vendor/bootstrap/bootstrap.min.css') ?>">
</head>
<body class="bg-body-tertiary">
<div class="d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="text-center p-4" style="max-width:520px">
        <i class="bi bi-shield-lock display-1 text-danger"></i>
        <h1 class="mt-3 fw-bold">403 &mdash; Access Denied</h1>
        <p class="text-secondary">
            You don't have permission to
            <strong><?= esc($action ?? 'view') ?></strong> the
            <strong><?= esc(str_replace('_', ' ', $module ?? 'requested')) ?></strong> module.
            <br>Please contact your administrator if you believe this is a mistake.
        </p>
        <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
            <?php if ($isImpersonating): ?>
                <a href="<?= site_url('impersonate/stop') ?>" class="btn btn-warning">
                    <i class="bi bi-box-arrow-left me-1"></i> Return to Super Admin
                </a>
            <?php endif; ?>
            <?php if (! $dashboardLoops): ?>
                <a href="<?= site_url('dashboard') ?>" class="btn btn-primary">
                    <i class="bi bi-house me-1"></i> Back to Dashboard
                </a>
            <?php endif; ?>
            <a href="<?= site_url('logout') ?>" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right me-1"></i> Log out
            </a>
        </div>
    </div>
</div>
</body>
</html>
