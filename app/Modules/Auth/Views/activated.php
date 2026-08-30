<?php
/** @var bool $ok @var string $appName @var string $email */
$ok = $ok ?? false;
$appName = $appName ?? 'ERP';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $ok ? 'Account activated' : 'Activation failed' ?> — <?= esc($appName) ?></title>
    <style nonce="{csp-style-nonce}">
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(160deg, #eef4fb 0%, #f7fbff 55%, #ffffff 100%);
            color: #10223d; padding: 24px;
        }
        .card {
            width: 100%; max-width: 420px; background: #fff; border-radius: 22px;
            box-shadow: 0 24px 60px rgba(38,59,92,.14); padding: 34px 28px; text-align: center;
        }
        .badge {
            width: 74px; height: 74px; margin: 0 auto 18px; border-radius: 22px;
            display: grid; place-items: center; font-size: 38px; color: #fff;
        }
        .ok  { background: linear-gradient(135deg, #10b981, #06b6d4); }
        .bad { background: linear-gradient(135deg, #ef4444, #f43f5e); }
        h1 { margin: 0 0 10px; font-size: 23px; font-weight: 800; }
        p  { margin: 0 0 8px; color: #526d97; font-size: 15px; line-height: 1.5; }
        .email { font-weight: 700; color: #10223d; word-break: break-all; }
        .hint {
            margin-top: 22px; padding: 14px 16px; border-radius: 14px;
            background: #eef4fb; color: #35507a; font-size: 14px; font-weight: 600;
        }
        .brand { margin-top: 24px; font-size: 12px; font-weight: 700; letter-spacing: .5px;
            text-transform: uppercase; color: #9fb0cb; }
        @media (prefers-color-scheme: dark) {
            body { background: linear-gradient(160deg,#0b1220,#0d1626 60%,#0a111d); color: #e8eefc; }
            .card { background: #131c2e; box-shadow: 0 24px 60px rgba(0,0,0,.45); }
            p { color: #93a6c6; } .email { color: #e8eefc; }
            .hint { background: #1a2438; color: #b7c6e0; }
        }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($ok): ?>
            <div class="badge ok">&#10003;</div>
            <h1>Account activated!</h1>
            <p>Your account <span class="email"><?= esc($email ?? '') ?></span> is now active.</p>
            <div class="hint">Open the <strong><?= esc($appName) ?></strong> app and sign in with your email and password to get started.</div>
        <?php else: ?>
            <div class="badge bad">&#33;</div>
            <h1>Activation link invalid</h1>
            <p>This activation link is invalid or has expired.</p>
            <div class="hint">Open the <strong><?= esc($appName) ?></strong> app, try to sign in, and tap <strong>Resend code</strong> to get a fresh activation email.</div>
        <?php endif; ?>
        <div class="brand"><?= esc($appName) ?></div>
    </div>
</body>
</html>
