<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set New Password &middot; ERP Admin</title>
    <script>document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('erp-theme') || 'light');</script>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="auth-wrapper">
    <div class="card auth-card shadow-lg">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="bi bi-shield-lock display-4 text-primary"></i>
                <h4 class="fw-bold mt-2 mb-0">Set a new password</h4>
                <p class="text-secondary"><?= esc($email) ?></p>
            </div>

            <?= flash_alerts() ?>

            <form action="<?= site_url('reset-password') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= esc($token, 'attr') ?>">
                <div class="mb-3">
                    <label class="form-label">New password</label>
                    <input type="password" name="password" class="form-control" minlength="8" required autofocus>
                    <div class="form-text">At least 8 characters.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm password</label>
                    <input type="password" name="password_confirm" class="form-control" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check2-circle me-1"></i> Update Password</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
