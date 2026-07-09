<?= view('partials/auth_top', [
    'pageTitle' => 'Change Password',
    'heroBadge' => 'Account Security',
    'heroTitle' => 'Keep your account <em>secure.</em>',
    'heroLede'  => 'Set a strong password you have not used before to continue.',
]) ?>

                <div class="mobile-logo"><i class="bi bi-shield-lock"></i></div>
                <h2><?= $forced ? 'Update your password' : 'Change password' ?></h2>
                <p class="subtitle">
                    <?= $forced
                        ? 'For your security, you must set a new password before continuing.'
                        : 'Choose a new password for your account.' ?>
                </p>

                <?= flash_alerts() ?>

                <?php if (! empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('account/change-password') ?>" method="post" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="auth-field">
                        <label class="auth-label" for="current_password">Current Password</label>
                        <div class="input-wrap">
                            <span class="input-icon"><i class="bi bi-lock"></i></span>
                            <input id="current_password" class="auth-control" type="password" name="current_password"
                                   placeholder="••••••••" required autofocus>
                            <button type="button" class="toggle-pass" data-toggle-pass="current_password" aria-label="Show password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="new_password">New Password</label>
                        <div class="input-wrap">
                            <span class="input-icon"><i class="bi bi-key"></i></span>
                            <input id="new_password" class="auth-control" type="password" name="new_password"
                                   placeholder="At least 8 characters" required>
                            <button type="button" class="toggle-pass" data-toggle-pass="new_password" aria-label="Show password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="confirm_password">Confirm New Password</label>
                        <div class="input-wrap">
                            <span class="input-icon"><i class="bi bi-key-fill"></i></span>
                            <input id="confirm_password" class="auth-control" type="password" name="confirm_password"
                                   placeholder="Re-enter new password" required>
                        </div>
                    </div>

                    <button class="login-button" type="submit">
                        <span>Update Password</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <?php if (! $forced): ?>
                    <div class="form-meta mt-3">
                        <a href="<?= site_url('dashboard') ?>" class="forgot-link">Back to dashboard</a>
                    </div>
                <?php endif; ?>

<?= view('partials/auth_bottom') ?>
