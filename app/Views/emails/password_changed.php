<?php
/** @var string $app @var string $name @var string $when @var string $url */
?>
<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>

<h2 style="margin:0 0 12px;color:#0f766e;font-size:22px;">Your password was changed</h2>

<p style="margin:0 0 16px;">Hi <?= esc($name) ?>, this is a confirmation that the password for your <strong><?= esc($app) ?></strong> account was changed on <strong><?= esc($when) ?></strong>.</p>

<div style="margin:0 0 20px;padding:14px 16px;background:#fff8f1;border:1px solid #f6d9bd;border-radius:12px;color:#8a5a1e;font-size:14px;">
    <strong>Didn't do this?</strong> Secure your account right away by resetting your password below and contact us.
</div>

<p style="margin:0 0 22px;">
    <a href="<?= esc($url, 'attr') ?>" style="background:#0f766e;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:10px;font-weight:bold;display:inline-block;">Reset password</a>
</p>

<p style="margin:0;color:#5d687c;font-size:13px;">If this was you, no further action is needed.</p>

<?= $this->endSection() ?>
