<?= view('partials/auth_top', [
    'pageTitle' => 'Sign In',
    'heroBadge' => 'Mission Control',
    'heroTitle' => 'Your whole business, <em>in one orbit.</em>',
    'heroLede'  => 'Users, roles, permissions and daily operations — all revolving around a single secure command center.',
]) ?>

                <div class="mobile-logo"><i class="bi bi-boxes"></i></div>
                <h2>Welcome back 🚀</h2>
                <p class="subtitle">Sign in to launch your admin dashboard.</p>

                <?= flash_alerts() ?>

                <form action="<?= site_url('login') ?>" method="post" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="auth-field">
                        <label class="auth-label" for="login">Username or Email</label>
                        <div class="input-wrap">
                            <span class="input-icon"><i class="bi bi-person"></i></span>
                            <input id="login" class="auth-control" type="text" name="login"
                                   value="<?= esc(old('login')) ?>" placeholder="superadmin" required autofocus>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="password">Password</label>
                        <div class="input-wrap">
                            <span class="input-icon"><i class="bi bi-lock"></i></span>
                            <input id="password" class="auth-control" type="password" name="password"
                                   placeholder="••••••••" required>
                            <button type="button" class="toggle-pass" data-toggle-pass="password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-meta">
                        <label class="remember-option" for="remember">
                            <input type="checkbox" id="remember" name="remember" value="1">
                            <span>Remember me</span>
                        </label>
                        <a href="<?= site_url('forgot-password') ?>" class="forgot-link">Forgot password?</a>
                    </div>

                    <button class="login-button" type="submit">
                        <span>Sign in to Dashboard</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <?= view('partials/social_login') ?>

                <div class="login-copyright">
                    &copy; <?= date('Y') ?> <?= esc(setting('app_name', 'ERP Admin')) ?>. All rights reserved.
                    <span class="auth-policy-sep">|</span>
                    <a href="<?= site_url('privacy') ?>" target="_blank" rel="noopener">Privacy Policy</a>
                </div>

<?= view('partials/auth_bottom') ?>
