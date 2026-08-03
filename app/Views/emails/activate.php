<?php
/** @var string $app @var string $link @var string $code @var int $ttl */
?>
<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>

<span style="display:inline-block;margin:0 0 14px;padding:5px 12px;background:#e6f7f1;color:#0f766e;font-size:11px;font-weight:bold;letter-spacing:.6px;text-transform:uppercase;border-radius:999px;">Activate account</span>

<h2 style="margin:0 0 12px;color:#0f766e;font-size:22px;">Confirm your email to get started</h2>

<p style="margin:0 0 16px;">Welcome to <strong><?= esc($app) ?></strong>! Tap the button below to activate your account. Once activated, open the app and sign in.</p>

<p style="margin:0 0 22px;">
    <a href="<?= esc($link, 'attr') ?>" style="background:#0f766e;color:#ffffff;text-decoration:none;padding:13px 30px;border-radius:10px;font-weight:bold;display:inline-block;font-size:15px;">Activate my account</a>
</p>

<p style="margin:0 0 8px;color:#5d687c;font-size:13px;">Or paste this link into your browser:<br>
    <a href="<?= esc($link, 'attr') ?>" style="color:#0f766e;word-break:break-all;"><?= esc($link) ?></a>
</p>

<?php if (! empty($code)): ?>
<div style="margin:22px 0;padding:14px 16px;background:#f4f7fb;border-radius:10px;text-align:center;">
    <div style="color:#5d687c;font-size:12px;font-weight:bold;letter-spacing:.5px;text-transform:uppercase;margin:0 0 6px;">Prefer to type a code in the app?</div>
    <div style="font-size:26px;font-weight:bold;letter-spacing:8px;color:#0f213c;"><?= esc($code) ?></div>
</div>
<?php endif; ?>

<p style="margin:0;color:#5d687c;font-size:13px;">This link expires in <?= esc($ttl) ?> hours. If you didn't create this account, you can safely ignore this email.</p>

<?= $this->endSection() ?>
