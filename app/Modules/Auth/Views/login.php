<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In &middot; ERP Admin</title>
    <script>document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('erp-theme') || 'light');</script>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="auth-wrapper">
    <div class="card auth-card shadow-lg">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="bi bi-boxes display-4 text-primary"></i>
                <h3 class="fw-bold mt-2 mb-0">ERP Admin</h3>
                <p class="text-secondary">Sign in to your account</p>
            </div>

            <?= flash_alerts() ?>

            <form action="<?= site_url('login') ?>" method="post" autocomplete="off">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Username or Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="login" class="form-control" value="<?= esc(old('login')) ?>"
                               placeholder="superadmin" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="remember" value="1" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <a href="<?= site_url('forgot-password') ?>" class="small">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </button>
            </form>

            <div class="alert alert-light border mt-4 mb-0 small">
                <strong>Demo accounts</strong> (password: <code>Admin@123</code>)<br>
                superadmin &middot; admin &middot; manager &middot; staff &middot; viewer
            </div>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
</body>
</html>
