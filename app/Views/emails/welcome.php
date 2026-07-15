<?php
/** @var string $app @var string $name @var string $url */
?>
<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>

<span style="display:inline-block;margin:0 0 14px;padding:5px 12px;background:#e7f6f2;color:#0f766e;font-size:11px;font-weight:bold;letter-spacing:.6px;text-transform:uppercase;border-radius:999px;">Welcome</span>

<h2 style="margin:0 0 12px;color:#0f766e;font-size:22px;">Welcome aboard, <?= esc($name) ?>! 🎉</h2>

<p style="margin:0 0 14px;">Your <strong><?= esc($app) ?></strong> account is ready. Manage your firms, keep your Hisaab-Kitaab (jama/naam) in order, track inventory, and stay on top of your business — all from the web and the mobile app.</p>

<p style="margin:0 0 22px;">Tap below to open your dashboard and add your first firm.</p>

<p style="margin:0 0 26px;">
    <a href="<?= esc($url, 'attr') ?>" style="background:#0f766e;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:10px;font-weight:bold;display:inline-block;">Go to dashboard</a>
</p>

<p style="margin:0;color:#5d687c;font-size:13px;">Glad to have you with us. If you ever need a hand, just reply to this email.</p>

<?= $this->endSection() ?>
