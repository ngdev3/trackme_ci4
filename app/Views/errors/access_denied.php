<?php
/**
 * Access Denied page — rendered by PermissionFilter for unauthorised users.
 * Standalone (does not require an active session context beyond helpers).
 */
helper(['url']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied &middot; ERP Admin</title>
    <script>document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('erp-theme') || 'light');</script>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
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
        <a href="<?= site_url('dashboard') ?>" class="btn btn-primary mt-2">
            <i class="bi bi-house me-1"></i> Back to Dashboard
        </a>
    </div>
</div>
</body>
</html>
