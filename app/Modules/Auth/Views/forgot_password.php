<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password &middot; ERP Admin</title>
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
                <i class="bi bi-key display-4 text-primary"></i>
                <h4 class="fw-bold mt-2 mb-0">Reset your password</h4>
                <p class="text-secondary">Enter your email to receive a reset link</p>
            </div>

            <?= flash_alerts() ?>

            <?php if ($link = session()->getFlashdata('reset_link')): ?>
                <div class="alert alert-info small">
                    <strong>Dev mode:</strong> email delivery is not configured, so use this link:<br>
                    <a href="<?= esc($link) ?>"><?= esc($link) ?></a>
                </div>
            <?php endif; ?>

            <form action="<?= site_url('forgot-password') ?>" method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" value="<?= esc(old('email')) ?>" required autofocus>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send me-1"></i> Send Reset Link</button>
            </form>

            <div class="text-center mt-3">
                <a href="<?= site_url('login') ?>" class="small"><i class="bi bi-arrow-left"></i> Back to sign in</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
