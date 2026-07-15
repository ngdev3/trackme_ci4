<?php
/**
 * Shared shell for every transactional email. Inline styles only (email clients
 * strip <style>), table-based layout for Outlook, and a fluid max-width card.
 * Child views extend this and fill the "content" section.
 *
 * The header carries the project brand lockup: the layered-diamond logo
 * (public/assets/img/favicon.svg) inside a white badge, next to the wordmark.
 * Clients that render SVG show the real mark; the rest fall back to the "HK"
 * alt text on the same badge, so the email is always branded.
 *
 * Optional per-email theming (all have safe defaults, so the Mailer needn't set
 * them): $accent (header/button colour), $accentSoft (button gradient tail),
 * $tagline (sub-line under the wordmark), $preheader (inbox preview snippet).
 *
 * @var string $app
 */
$accent     = $accent     ?? '#0f766e';
$accentSoft = $accentSoft ?? '#14b8a6';
$tagline    = $tagline    ?? 'Business ka poora Hisaab-Kitaab';
$preheader  = $preheader  ?? '';
$logoUrl    = base_url('assets/img/favicon.svg');
?><!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?= esc($app) ?></title>
</head>
<body style="margin:0;padding:0;background:#eef2f5;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <?php if ($preheader !== ''): ?>
    <!-- Preheader: shown as the inbox preview, hidden in the body. -->
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;font-size:1px;line-height:1px;color:#eef2f5;">
        <?= esc($preheader) ?>
    </div>
    <?php endif; ?>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f5;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="540" cellpadding="0" cellspacing="0" style="width:540px;max-width:100%;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 6px 24px rgba(21,32,51,.10);">
                    <!-- Header / brand lockup -->
                    <tr>
                        <td style="background:<?= esc($accent, 'attr') ?>;background:linear-gradient(135deg,<?= esc($accent, 'attr') ?> 0%,<?= esc($accentSoft, 'attr') ?> 100%);padding:26px 30px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align:middle;padding-right:14px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:13px;">
                                            <tr>
                                                <td align="center" valign="middle" style="width:46px;height:46px;text-align:center;font-family:Arial,Helvetica,sans-serif;font-size:17px;font-weight:bold;color:<?= esc($accent, 'attr') ?>;">
                                                    <img src="<?= esc($logoUrl, 'attr') ?>" width="30" height="30" alt="HK" style="display:block;border:0;outline:none;text-decoration:none;">
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td style="vertical-align:middle;">
                                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:21px;font-weight:bold;color:#ffffff;letter-spacing:.2px;line-height:1.1;"><?= esc($app) ?></div>
                                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:rgba(255,255,255,.85);padding-top:3px;"><?= esc($tagline) ?></div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:32px 30px 10px;font-family:Arial,Helvetica,sans-serif;color:#152033;font-size:15px;line-height:1.65;">
                            <?= $this->renderSection('content') ?>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:22px 30px 30px;font-family:Arial,Helvetica,sans-serif;color:#8792a6;font-size:12px;line-height:1.7;border-top:1px solid #eef2f5;">
                            You're receiving this email because you have an account with <strong style="color:#5d687c;"><?= esc($app) ?></strong>.<br>
                            &copy; <?= esc(date('Y')) ?> <?= esc($app) ?>. All rights reserved.
                        </td>
                    </tr>
                </table>
                <!-- Sub-footer outside the card -->
                <table role="presentation" width="540" cellpadding="0" cellspacing="0" style="width:540px;max-width:100%;">
                    <tr>
                        <td style="padding:16px 30px 0;text-align:center;font-family:Arial,Helvetica,sans-serif;color:#aab3c2;font-size:11px;line-height:1.6;">
                            This is an automated message — please do not reply directly.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
