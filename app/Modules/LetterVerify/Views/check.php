<?php
$verdict = $result['verdict'];
$letter  = $result['letter'];
$badge   = [
    'empty'     => ['bg' => '#eef2f7', 'fg' => '#33445f', 'icon' => '&#128269;', 'label' => 'Letter Verification'],
    'valid'     => ['bg' => '#e4f5eb', 'fg' => '#18794e', 'icon' => '&#10004;',  'label' => 'GENUINE LETTER'],
    'cancelled' => ['bg' => '#fff3d6', 'fg' => '#8c6410', 'icon' => '&#9888;',   'label' => 'CANCELLED LETTER'],
    'invalid'   => ['bg' => '#fde8e8', 'fg' => '#b43333', 'icon' => '&#10006;',  'label' => 'NOT VERIFIED'],
    'not_found' => ['bg' => '#fde8e8', 'fg' => '#b43333', 'icon' => '&#10006;',  'label' => 'NOT FOUND'],
];
$b = $badge[$verdict] ?? $badge['empty'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Letter Verification</title>
    <style>
        * { box-sizing: border-box; margin: 0; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 18px; background: linear-gradient(160deg, #0c315f, #091e39); font-family: "Segoe UI", Arial, sans-serif; color: #18243c; }
        .card { width: 100%; max-width: 460px; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 28px 70px rgba(0, 0, 0, .38); }
        .badge { display: flex; align-items: center; gap: 12px; padding: 20px 22px; background: <?= $b['bg'] ?>; color: <?= $b['fg'] ?>; }
        .badge .icon { width: 44px; height: 44px; flex: 0 0 44px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: rgba(255, 255, 255, .75); font-size: 20px; }
        .badge h1 { font-size: 19px; letter-spacing: .5px; }
        .badge p { margin-top: 3px; font-size: 12.5px; font-weight: 600; line-height: 1.45; }
        .body { padding: 20px 22px 24px; }
        .row { display: flex; justify-content: space-between; gap: 14px; padding: 9px 0; border-bottom: 1px solid #edf2f7; font-size: 13px; }
        .row:last-child { border-bottom: 0; }
        .row .k { color: #64748b; font-weight: 700; white-space: nowrap; }
        .row .v { color: #0f172a; font-weight: 700; text-align: right; word-break: break-word; }
        .note { margin-top: 14px; padding: 10px 12px; border-radius: 8px; background: #f4f7fb; color: #516174; font-size: 11.5px; line-height: 1.5; }
        form { margin-top: 4px; }
        label { display: block; margin: 12px 0 5px; color: #516174; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        input { width: 100%; min-height: 44px; padding: 9px 12px; border: 1px solid #dce6f2; border-radius: 8px; font-size: 14px; font-weight: 700; color: #18243c; }
        input:focus { outline: 0; border-color: #1769c2; box-shadow: 0 0 0 3px rgba(23, 105, 194, .14); }
        button { width: 100%; margin-top: 16px; min-height: 46px; border: 0; border-radius: 8px; background: #1769c2; color: #fff; font-size: 14px; font-weight: 800; cursor: pointer; }
        button:hover { background: #0c5aaa; }
        .foot { padding: 12px 22px; border-top: 1px solid #edf2f7; color: #8a97aa; font-size: 10.5px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">
            <span class="icon"><?= $b['icon'] ?></span>
            <div>
                <h1><?= $b['label'] ?></h1>
                <p><?= esc($result['message']) ?></p>
            </div>
        </div>
        <div class="body">
            <?php if (! empty($letter)): ?>
                <div class="row"><span class="k">Letter No.</span><span class="v"><?= esc($letter['letter_no']) ?></span></div>
                <div class="row"><span class="k">Issued by</span><span class="v"><?= esc($letter['firm']) ?></span></div>
                <?php if (! empty($letter['subject'])): ?>
                    <div class="row"><span class="k">Subject</span><span class="v"><?= esc($letter['subject']) ?></span></div>
                <?php endif; ?>
                <?php if (! empty($letter['letter_date'])): ?>
                    <div class="row"><span class="k">Letter date</span><span class="v"><?= esc($letter['letter_date']) ?></span></div>
                <?php endif; ?>
                <div class="row"><span class="k">Signed by</span><span class="v"><?= esc($letter['signed_by']) ?><?= ! empty($letter['designation']) ? ' (' . esc($letter['designation']) . ')' : '' ?></span></div>
                <?php if (! empty($letter['page_count'])): ?>
                    <div class="row"><span class="k">Total pages</span><span class="v"><?= (int) $letter['page_count'] ?> page<?= $letter['page_count'] > 1 ? 's' : '' ?></span></div>
                <?php endif; ?>
                <?php if (! empty($letter['issued_at'])): ?>
                    <div class="row"><span class="k">Issued on</span><span class="v"><?= esc($letter['issued_at']) ?></span></div>
                <?php endif; ?>
                <div class="note">Compare the letter number, firm, subject and date above with the printed letter in your hand.<?php if (! empty($letter['page_count']) && $letter['page_count'] > 1): ?> This letter has <b><?= (int) $letter['page_count'] ?> pages</b> — every sheet must show the same Letter No., the QR code, and its own "Sheet X of <?= (int) $letter['page_count'] ?>" marker in the header.<?php endif; ?> If anything differs, treat the printed copy as tampered.</div>
            <?php elseif (! empty($result['letter_no']) && in_array($verdict, ['invalid', 'not_found'], true)): ?>
                <div class="row"><span class="k">Letter No. checked</span><span class="v"><?= esc($result['letter_no']) ?></span></div>
                <div class="note">Scan the QR code printed on the letter for automatic verification, or re-enter the letter number and verification code exactly as printed.</div>
            <?php endif; ?>

            <?php if (empty($letter)): ?>
                <form method="get" action="" onsubmit="return goVerify(this);">
                    <label for="lv-no">Letter Number</label>
                    <input id="lv-no" name="no" type="text" placeholder="e.g. LP-260702-7K3QD9" value="<?= esc($result['letter_no']) ?>" required>
                    <label for="lv-code">Verification Code</label>
                    <input id="lv-code" name="code" type="text" placeholder="Code from the QR link" required>
                    <button type="submit">Verify Letter</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="foot">Automated letter authenticity check &bull; the letter content itself is never displayed here.</div>
    </div>
    <script>
        function goVerify(f) {
            var no = encodeURIComponent(f.no.value.trim());
            var code = encodeURIComponent(f.code.value.trim());
            if (!no || !code) { return false; }
            window.location = '<?= rtrim(site_url('letter_verify/check'), '/') ?>/' + no + '/' + code;
            return false;
        }
    </script>
</body>
</html>
