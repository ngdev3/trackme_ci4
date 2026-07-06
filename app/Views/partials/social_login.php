<?php
/**
 * Social sign-in buttons — shared by the login (and any future sign-up) page.
 * Renders one "Continue with …" button per provider declared in Config\OAuth.
 * A single click both signs up new users and logs in existing ones.
 */
$providers = config('OAuth')->providers ?? [];
if ($providers === []) {
    return;
}
$icons = [
    'google'    => 'bi-google',
    'apple'     => 'bi-apple',
    'microsoft' => 'bi-microsoft',
    'facebook'  => 'bi-facebook',
    'github'    => 'bi-github',
];
?>
<div class="auth-divider"><span>or continue with</span></div>

<div class="social-login">
    <?php foreach ($providers as $key => $p): ?>
        <a href="<?= site_url('auth/' . $key) ?>" class="social-btn social-<?= esc($key, 'attr') ?>" rel="nofollow">
            <i class="bi <?= esc($icons[$key] ?? 'bi-box-arrow-in-right') ?>"></i>
            <span>Continue with <?= esc($p['label'] ?? ucfirst($key)) ?></span>
        </a>
    <?php endforeach; ?>
</div>
