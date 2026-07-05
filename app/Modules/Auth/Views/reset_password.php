<?= view('partials/auth_top', [
    'pageTitle' => 'Set New Password',
    'heroBadge' => 'Secure Reset',
    'heroTitle' => 'Choose a strong <em>new password.</em>',
    'heroLede'  => 'Pick a password you have not used before. Once updated you can sign straight back into your dashboard.',
]) ?>

                <div class="mobile-logo"><i class="bi bi-shield-lock"></i></div>
                <h2>New password 🔒</h2>
                <p class="subtitle"><?= esc($email) ?></p>

                <?= flash_alerts() ?>

                <form action="<?= site_url('reset-password') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= esc($token, 'attr') ?>">

                    <div class="auth-field">
                        <label class="auth-label" for="password">New password</label>
                        <div class="input-wrap">
                            <span class="input-icon"><i class="bi bi-lock"></i></span>
                            <input id="password" class="auth-control" type="password" name="password"
                                   minlength="8" placeholder="At least 8 characters" required autofocus>
                            <button type="button" class="toggle-pass" data-toggle-pass="password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="password_confirm">Confirm password</label>
                        <div class="input-wrap">
                            <span class="input-icon"><i class="bi bi-lock-fill"></i></span>
                            <input id="password_confirm" class="auth-control" type="password" name="password_confirm"
                                   minlength="8" placeholder="Re-enter password" required>
                            <button type="button" class="toggle-pass" data-toggle-pass="password_confirm" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button class="login-button" type="submit">
                        <span>Update password</span>
                        <i class="bi bi-check2-circle"></i>
                    </button>
                </form>

                <div class="text-center">
                    <a href="<?= site_url('login') ?>" class="back-link"><i class="bi bi-arrow-left"></i> Back to sign in</a>
                </div>

<?= view('partials/auth_bottom') ?>
</content>
