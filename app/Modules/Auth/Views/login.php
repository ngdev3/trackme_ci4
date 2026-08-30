<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · C R Industries ERP</title>
    <style>
        * { box-sizing: border-box; margin: 0; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 18px; background: linear-gradient(160deg, #0c315f, #091e39); font-family: "Segoe UI", Arial, sans-serif; }
        .card { width: 100%; max-width: 380px; background: #fff; border-radius: 12px; padding: 30px 28px; box-shadow: 0 28px 70px rgba(0,0,0,.38); }
        h1 { font-size: 20px; color: #18243c; margin-bottom: 4px; }
        .sub { color: #64748b; font-size: 12.5px; margin-bottom: 20px; }
        label { display: block; margin: 12px 0 5px; color: #516174; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        input { width: 100%; min-height: 44px; padding: 9px 12px; border: 1px solid #dce6f2; border-radius: 8px; font-size: 14px; color: #18243c; }
        input:focus { outline: 0; border-color: #1769c2; box-shadow: 0 0 0 3px rgba(23,105,194,.14); }
        button { width: 100%; margin-top: 18px; min-height: 46px; border: 0; border-radius: 8px; background: #1769c2; color: #fff; font-size: 14px; font-weight: 800; cursor: pointer; }
        button:hover { background: #0c5aaa; }
        .err { margin-bottom: 14px; padding: 10px 12px; border-radius: 8px; background: #fde8e8; color: #b43333; font-size: 12.5px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h1>C R Industries ERP</h1>
        <div class="sub">Sign in to continue</div>

        <?php if (! empty($error)): ?>
            <div class="err"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= site_url('admin/auth/login') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="redirect" value="<?= esc($redirect ?? '', 'attr') ?>">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="username" required autofocus>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="submit">Sign in</button>
        </form>
    </div>
</body>
</html>
