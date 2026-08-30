<?= view('partials/auth_top', [
    'pageTitle' => 'Sign In',
    'heroBadge' => 'Mission Control',
    'heroTitle' => 'Your whole business, <em>in one orbit.</em>',
    'heroLede'  => 'Users, roles, permissions and daily operations — all revolving around a single secure command center.',
]) ?>

                <?php $showSignup = session('show') === 'signup'; ?>

                <?= flash_alerts() ?>

                <!-- ============ SIGN IN ============ -->
                <div id="view-signin" class="auth-view"<?= $showSignup ? ' hidden' : '' ?>>
                    <h2>Welcome back 👋</h2>
                    <p class="subtitle">Sign in to your <?= esc(brand_name()) ?> dashboard.</p>

                    <form action="<?= site_url('login') ?>" method="post" autocomplete="off">
                        <?= csrf_field() ?>

                        <div class="auth-field">
                            <label class="auth-label" for="login">Email address</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="bi bi-envelope"></i></span>
                                <input id="login" class="auth-control" type="email" name="login" inputmode="email"
                                       value="<?= esc(old('login')) ?>" placeholder="you@company.com" required>
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

                    <p class="auth-switch">New here?
                        <a href="#" data-auth-toggle="signup">Create an account</a>
                    </p>
                </div>

                <!-- ============ CREATE ACCOUNT ============ -->
                <div id="view-signup" class="auth-view"<?= $showSignup ? '' : ' hidden' ?>>
                    <h2>Create your account</h2>
                    <p class="subtitle">Sign up with your email — we'll send you an activation link to get started.</p>

                    <form action="<?= site_url('register') ?>" method="post" autocomplete="off">
                        <?= csrf_field() ?>

                        <div class="auth-field">
                            <label class="auth-label" for="su-name">Full name</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="bi bi-person"></i></span>
                                <input id="su-name" class="auth-control" type="text" name="name"
                                       value="<?= esc(old('name')) ?>" placeholder="Your name" required>
                            </div>
                        </div>

                        <div class="auth-field">
                            <label class="auth-label" for="su-email">Email address</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="bi bi-envelope"></i></span>
                                <input id="su-email" class="auth-control" type="email" name="email" inputmode="email"
                                       value="<?= esc(old('email') ?: session('prefill_email') ?: '') ?>" placeholder="you@company.com" required>
                            </div>
                        </div>

                        <div class="auth-field">
                            <label class="auth-label" for="su-password">Password</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="bi bi-lock"></i></span>
                                <input id="su-password" class="auth-control" type="password" name="password"
                                       placeholder="At least 8 characters" minlength="8" required>
                                <button type="button" class="toggle-pass" data-toggle-pass="su-password" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button class="login-button" type="submit">
                            <span>Create account</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>

                    <p class="auth-switch">Already have an account?
                        <a href="#" data-auth-toggle="signin">Sign in</a>
                    </p>
                </div>

                <div class="login-copyright">
                    &copy; <?= date('Y') ?> <?= esc(brand_name()) ?>. All rights reserved.
                    <span class="auth-policy-sep">|</span>
                    <a href="<?= site_url('privacy') ?>" target="_blank" rel="noopener">Privacy Policy</a>
                </div>

                <script nonce="{csp-script-nonce}">
                (function () {
                    var views = { signin: document.getElementById('view-signin'), signup: document.getElementById('view-signup') };
                    document.querySelectorAll('[data-auth-toggle]').forEach(function (el) {
                        el.addEventListener('click', function (e) {
                            e.preventDefault();
                            var to = el.getAttribute('data-auth-toggle');
                            views.signin.hidden = (to !== 'signin');
                            views.signup.hidden = (to !== 'signup');
                            var first = views[to].querySelector('input');
                            if (first) first.focus();
                        });
                    });
                })();
                </script>

<?= view('partials/auth_bottom') ?>
