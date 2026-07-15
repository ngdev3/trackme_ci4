<?php
/** @var string $app @var string $code @var int $ttl */
?>
<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>

<span style="display:inline-block;margin:0 0 14px;padding:5px 12px;background:#eef0ff;color:#4338ca;font-size:11px;font-weight:bold;letter-spacing:.6px;text-transform:uppercase;border-radius:999px;">Verify email</span>

<h2 style="margin:0 0 12px;color:#0f766e;font-size:22px;">Verify your email</h2>

<p style="margin:0 0 18px;">Use the one-time code below to confirm your email address for <strong><?= esc($app) ?></strong>.</p>

<div style="margin:0 0 20px;text-align:center;">
    <div style="display:inline-block;background:#f1f7f6;border:1px dashed #0f766e;border-radius:12px;padding:16px 26px;font-family:'Courier New',monospace;font-size:34px;font-weight:bold;letter-spacing:10px;color:#0f766e;">
        <?= esc($code) ?>
    </div>
</div>

<p style="margin:0 0 8px;color:#5d687c;font-size:13px;">This code expires in <?= esc($ttl) ?> minutes. Enter it in the app to continue.</p>
<p style="margin:0;color:#5d687c;font-size:13px;">If you didn't request this, you can safely ignore this email — no changes will be made.</p>

<?= $this->endSection() ?>
