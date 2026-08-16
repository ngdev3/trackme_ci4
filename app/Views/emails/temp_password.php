<?php
/** @var string $app @var string $name @var string $password @var string $url */
?>
<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>

<span style="display:inline-block;margin:0 0 14px;padding:5px 12px;background:#eef6ff;color:#1d4ed8;font-size:11px;font-weight:bold;letter-spacing:.6px;text-transform:uppercase;border-radius:999px;">Account access</span>

<h2 style="margin:0 0 12px;color:#0f766e;font-size:22px;">Your new temporary password</h2>

<p style="margin:0 0 16px;">Hi <?= esc($name) ?>, our team set a new temporary password for your <strong><?= esc($app) ?></strong> account at your request.</p>

<div style="margin:0 0 20px;padding:16px 18px;background:#f6f8fb;border:1px solid #e3e8f0;border-radius:12px;text-align:center;">
    <div style="font-size:12px;color:#5d687c;letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px;">Temporary password</div>
    <div style="font-size:22px;font-weight:bold;letter-spacing:2px;color:#0f172a;font-family:'Courier New',monospace;"><?= esc($password) ?></div>
</div>

<div style="margin:0 0 22px;padding:14px 16px;background:#fff8f1;border:1px solid #f6d9bd;border-radius:12px;color:#8a5a1e;font-size:14px;">
    <strong>For your security</strong>, sign in with this password and you'll be asked to set your own password right away. This temporary one stops working once you do.
</div>

<p style="margin:0 0 22px;">
    <a href="<?= esc($url, 'attr') ?>" style="background:#0f766e;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:10px;font-weight:bold;display:inline-block;">Sign in</a>
</p>

<p style="margin:0;color:#5d687c;font-size:13px;">If you didn't request this, contact us immediately.</p>

<?= $this->endSection() ?>
