<?php
/**
 * Shared public legal page (Terms, Refund policy, …). Standalone + self-contained.
 * Expects: $legalTitle, $legalUpdated, $legalIntro, $legalSections (list of
 * ['h' => heading, 'p' => paragraph|list of paragraphs]).
 */
$appName       = $appName       ?? brand_name();
$company       = $company       ?? 'CR Industries';
$contactEmail  = $contactEmail  ?? 'support@hissabkitaab.com';
$legalTitle    = $legalTitle    ?? 'Legal';
$legalUpdated  = $legalUpdated  ?? date('d M Y');
$legalIntro    = $legalIntro    ?? '';
$legalSections = $legalSections ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="index, follow">
<title><?= esc($legalTitle) ?> — <?= esc($appName) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= erp_asset('assets/img/favicon.svg') ?>">
<link rel="stylesheet" href="<?= erp_asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--ink:#0b1220;--muted:#5d687c;--line:#e2e8f2;--brand:#2563eb;--teal:#0f766e;--accent:#f59e0b}
*{box-sizing:border-box}html{scroll-behavior:smooth}
body{margin:0;color:#1e293b;background:linear-gradient(180deg,#eef5f7,#f8fafc 40%,#fff);font-family:Inter,system-ui,sans-serif;line-height:1.7}
h1,h2,.wm{font-family:Sora,Inter,sans-serif;color:var(--ink);letter-spacing:-.02em}
a{color:var(--brand);font-weight:600;text-decoration:none}
a:hover{text-decoration:underline}
.wrap{max-width:900px;margin:0 auto;padding:26px 20px 72px}
.top{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px}
.wm{font-weight:800;font-size:1.3rem;text-decoration:none}
.wm .a{color:var(--accent)}
.back{display:inline-flex;align-items:center;gap:7px;font-weight:700;color:var(--ink);background:#fff;border:1px solid var(--line);border-radius:10px;padding:9px 15px;box-shadow:0 6px 18px rgba(15,23,42,.06)}
.hero{border-radius:22px;padding:34px;color:#fff;background:linear-gradient(120deg,#0f766e,#2563eb 55%,#7c3aed);box-shadow:0 22px 60px rgba(15,23,42,.16)}
.hero h1{margin:0 0 8px;font-size:clamp(1.7rem,4vw,2.4rem);color:#fff}
.hero p{margin:0;color:rgba(255,255,255,.9)}
.updated{display:inline-block;margin-top:14px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);border-radius:20px;padding:5px 13px;font-size:.82rem;font-weight:700}
.card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:28px 30px;margin-top:22px;box-shadow:0 10px 30px rgba(15,23,42,.06)}
.card h2{font-size:1.18rem;margin:0 0 8px;display:flex;gap:10px;align-items:center}
.card h2 .n{flex:0 0 auto;width:30px;height:30px;border-radius:9px;display:grid;place-items:center;font-size:.9rem;color:#fff;background:linear-gradient(135deg,var(--teal),var(--brand));font-weight:800}
.card p{margin:0 0 12px;color:#334155}
.card p:last-child{margin-bottom:0}
.foot{margin-top:34px;text-align:center;color:var(--muted);font-size:.9rem}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <a href="<?= site_url('/') ?>" class="wm">Hissab-<span class="a">Kitaab</span></a>
    <a href="<?= site_url('/') ?>" class="back"><i class="bi bi-arrow-left"></i> Back to home</a>
  </div>

  <div class="hero">
    <h1><?= esc($legalTitle) ?></h1>
    <?php if ($legalIntro !== ''): ?><p><?= esc($legalIntro) ?></p><?php endif; ?>
    <span class="updated"><i class="bi bi-clock-history"></i> Last updated: <?= esc($legalUpdated) ?></span>
  </div>

  <?php foreach ($legalSections as $i => $s): ?>
    <div class="card">
      <h2><span class="n"><?= $i + 1 ?></span> <?= esc($s['h']) ?></h2>
      <?php foreach ((array) $s['p'] as $para): ?>
        <p><?= esc($para) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <div class="foot">
    Questions? Email <a href="mailto:<?= esc($contactEmail) ?>"><?= esc($contactEmail) ?></a><br>
    &copy; <?= date('Y') ?> <?= esc($company) ?>. All rights reserved.
  </div>
</div>
</body>
</html>
