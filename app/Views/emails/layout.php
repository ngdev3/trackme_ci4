<?php
/**
 * Shared shell for every transactional email. Inline styles only (email clients
 * strip <style>), table-based layout for Outlook, and a fluid max-width card.
 * Child views extend this and fill the "content" section; $app is always set.
 *
 * @var string $app
 */
?><!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?= esc($app) ?></title>
</head>
<body style="margin:0;padding:0;background:#eef2f5;-webkit-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="520" cellpadding="0" cellspacing="0" style="width:520px;max-width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 10px rgba(21,32,51,.06);">
                    <!-- Header -->
                    <tr>
                        <td style="background:#0f766e;padding:22px 28px;">
                            <span style="font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:bold;color:#ffffff;letter-spacing:.3px;"><?= esc($app) ?></span>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:30px 28px 8px;font-family:Arial,Helvetica,sans-serif;color:#152033;font-size:15px;line-height:1.6;">
                            <?= $this->renderSection('content') ?>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 28px 28px;font-family:Arial,Helvetica,sans-serif;color:#8792a6;font-size:12px;line-height:1.6;border-top:1px solid #eef2f5;">
                            You're receiving this email because you have an account with <?= esc($app) ?>.<br>
                            &copy; <?= esc(date('Y')) ?> <?= esc($app) ?>. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
