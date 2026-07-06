<?= view('partials/auth_top', [
    'pageTitle' => 'Forgot Password',
    'heroBadge' => 'Account Recovery',
    'heroTitle' => 'Locked out? <em>We\'ll help you back in.</em>',
    'heroLede'  => 'Enter your account email and we will send a secure link to reset your password and relaunch your console.',
]) ?>

                <div class="mobile-logo"><i class="bi bi-key"></i></div>
                <h2>Reset password 🔑</h2>
                <p class="subtitle">Enter your email to receive a reset link.</p>

                <?= flash_alerts() ?>

                <?php if ($link = session()->getFlashdata('reset_link')): ?>
                    <div class="auth-alert info">
                        <strong>Dev mode:</strong> email delivery is not configured, so use this link:<br>
                        <a href="<?= esc($link) ?>"><?= esc($link) ?></a>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('forgot-password') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="auth-field">
                        <label class="auth-label" for="email">Email address</label>
                        <div class="input-wrap">
                            <span class="input-icon"><i class="bi bi-envelope"></i></span>
                            <input id="email" class="auth-control" type="email" name="email"
                                   value="<?= esc(old('email')) ?>" placeholder="you@company.com" required autofocus>
                        </div>
                    </div>

                    <button class="login-button" type="submit">
                        <span>Send reset link</span>
                        <i class="bi bi-send"></i>
                    </button>
                </form>

                <div class="text-center">
                    <a href="<?= site_url('login') ?>" class="back-link"><i class="bi bi-arrow-left"></i> Back to sign in</a>
                </div>

<?= view('partials/auth_bottom') ?>
