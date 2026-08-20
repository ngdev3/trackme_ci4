<?php
/** @var string $app @var string $name @var string $companyName @var array $stats */
$rupee   = static fn ($n) => '₹' . number_format((float) $n, 2);
$fmtDate = static fn ($d) => $d ? date('d M Y', strtotime((string) $d)) : '—';
$period  = ($stats['first_date'] ?? null)
    ? ($fmtDate($stats['first_date']) . ' → ' . $fmtDate($stats['last_date']))
    : 'No entries recorded';
$net     = (float) ($stats['net'] ?? 0);
?>
<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>

<span style="display:inline-block;margin:0 0 14px;padding:5px 12px;background:#fdeaea;color:#b91c1c;font-size:11px;font-weight:bold;letter-spacing:.6px;text-transform:uppercase;border-radius:999px;">Company permanently deleted</span>

<h2 style="margin:0 0 12px;color:#b91c1c;font-size:22px;">Final report for &ldquo;<?= esc($companyName) ?>&rdquo;</h2>

<p style="margin:0 0 16px;">Hi <?= esc($name) ?>, the company <strong><?= esc($companyName) ?></strong> on <strong><?= esc($app) ?></strong> was <strong>permanently deleted</strong> on <?= esc($stats['deleted_at'] ?? date('d M Y, H:i')) ?>. Here is a final snapshot of everything it held, kept for your records.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;border:1px solid #eef2f5;border-radius:12px;overflow:hidden;font-size:14px;">
    <tr>
        <td style="padding:11px 16px;color:#5d687c;background:#f7f9fb;">Total entries</td>
        <td style="padding:11px 16px;text-align:right;font-weight:bold;color:#152033;background:#f7f9fb;"><?= esc(number_format((int) ($stats['entries'] ?? 0))) ?></td>
    </tr>
    <tr>
        <td style="padding:11px 16px;color:#5d687c;">Accounts / parties</td>
        <td style="padding:11px 16px;text-align:right;font-weight:bold;color:#152033;"><?= esc(number_format((int) ($stats['parties'] ?? 0))) ?></td>
    </tr>
    <tr>
        <td style="padding:11px 16px;color:#5d687c;background:#f7f9fb;">Total Jama (in)</td>
        <td style="padding:11px 16px;text-align:right;font-weight:bold;color:#15803d;background:#f7f9fb;"><?= esc($rupee($stats['jama'] ?? 0)) ?></td>
    </tr>
    <tr>
        <td style="padding:11px 16px;color:#5d687c;">Total Naam (out)</td>
        <td style="padding:11px 16px;text-align:right;font-weight:bold;color:#b91c1c;"><?= esc($rupee($stats['naam'] ?? 0)) ?></td>
    </tr>
    <tr>
        <td style="padding:11px 16px;color:#5d687c;background:#f7f9fb;">Net balance</td>
        <td style="padding:11px 16px;text-align:right;font-weight:bold;color:<?= $net < 0 ? '#b91c1c' : '#15803d' ?>;background:#f7f9fb;"><?= esc($rupee($net)) ?></td>
    </tr>
    <tr>
        <td style="padding:11px 16px;color:#5d687c;">Entry period</td>
        <td style="padding:11px 16px;text-align:right;color:#152033;"><?= esc($period) ?></td>
    </tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;background:#fdeaea;border:1px solid #f6cccc;border-radius:12px;">
    <tr>
        <td style="padding:14px 16px;color:#7f1d1d;font-size:13px;line-height:1.6;">
            &#9888;&#65039; <strong>This deletion is permanent.</strong> The company and all of its entries, accounts and attachments have been erased and <strong>cannot be recovered</strong> — this report is the only remaining record.
        </td>
    </tr>
</table>

<p style="margin:0;color:#5d687c;font-size:13px;">If you did not intend to delete this company, please contact our support team right away — but note that the data itself is not restorable.</p>

<?= $this->endSection() ?>
