<?php
/** @var string $app @var string $name @var string $plan @var string $currency
 *  @var string $invoiceNo @var string $expiresAt @var string $url
 *  @var float|int|string|null $amount */
?>
<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>

<h2 style="margin:0 0 12px;color:#0f766e;font-size:22px;">Payment received — you're all set ✅</h2>

<p style="margin:0 0 16px;">Hi <?= esc($name) ?>, thank you for subscribing. Your <strong><?= esc($plan) ?></strong> plan on <strong><?= esc($app) ?></strong> is now active.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;border:1px solid #eef2f5;border-radius:12px;overflow:hidden;font-size:14px;">
    <tr>
        <td style="padding:11px 16px;color:#5d687c;background:#f7f9fb;">Plan</td>
        <td style="padding:11px 16px;text-align:right;font-weight:bold;color:#152033;"><?= esc($plan) ?></td>
    </tr>
    <?php if ($amount !== null && $amount !== ''): ?>
    <tr>
        <td style="padding:11px 16px;color:#5d687c;">Amount paid</td>
        <td style="padding:11px 16px;text-align:right;font-weight:bold;color:#152033;"><?= esc($currency) ?><?= esc(number_format((float) $amount, 2)) ?></td>
    </tr>
    <?php endif; ?>
    <?php if ($invoiceNo !== ''): ?>
    <tr>
        <td style="padding:11px 16px;color:#5d687c;background:#f7f9fb;">Invoice no.</td>
        <td style="padding:11px 16px;text-align:right;color:#152033;background:#f7f9fb;"><?= esc($invoiceNo) ?></td>
    </tr>
    <?php endif; ?>
    <?php if ($expiresAt !== ''): ?>
    <tr>
        <td style="padding:11px 16px;color:#5d687c;">Valid until</td>
        <td style="padding:11px 16px;text-align:right;color:#152033;"><?= esc($expiresAt) ?></td>
    </tr>
    <?php endif; ?>
</table>

<p style="margin:0 0 26px;">
    <a href="<?= esc($url, 'attr') ?>" style="background:#0f766e;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:10px;font-weight:bold;display:inline-block;">View payment history &amp; receipt</a>
</p>

<p style="margin:0;color:#5d687c;font-size:13px;">All premium features are unlocked across the web and the mobile app. Enjoy!</p>

<?= $this->endSection() ?>
