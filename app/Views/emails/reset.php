<?php
/** @var string $app @var string $link @var int $ttl */
?>
<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>

<span style="display:inline-block;margin:0 0 14px;padding:5px 12px;background:#fdf1e3;color:#b45309;font-size:11px;font-weight:bold;letter-spacing:.6px;text-transform:uppercase;border-radius:999px;">Password reset</span>

<h2 style="margin:0 0 12px;color:#0f766e;font-size:22px;">Reset your password</h2>

<p style="margin:0 0 16px;">We received a request to reset the password for your <strong><?= esc($app) ?></strong> account. Tap the button below to choose a new one.</p>

<p style="margin:0 0 22px;">
    <a href="<?= esc($link, 'attr') ?>" style="background:#0f766e;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:10px;font-weight:bold;display:inline-block;">Reset password</a>
</p>

<p style="margin:0 0 8px;color:#5d687c;font-size:13px;">Or paste this link into your browser:<br>
    <a href="<?= esc($link, 'attr') ?>" style="color:#0f766e;word-break:break-all;"><?= esc($link) ?></a>
</p>

<p style="margin:0;color:#5d687c;font-size:13px;">This link expires in <?= esc($ttl) ?> minutes. If you didn't request this, you can safely ignore this email — your password won't change.</p>

<?= $this->endSection() ?>
