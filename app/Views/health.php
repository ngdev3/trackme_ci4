<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CI4 P0 · Health Check</title>
<style>
 body{font-family:system-ui,Segoe UI,Roboto,sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:32px}
 .wrap{max-width:820px;margin:0 auto}
 h1{font-size:20px;margin:0 0 4px}.sub{color:#94a3b8;margin:0 0 24px;font-size:13px}
 .card{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:16px 18px;margin-bottom:14px}
 .row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #263449;font-size:14px}
 .row:last-child{border-bottom:0}
 .ok{color:#4ade80;font-weight:600}.bad{color:#f87171;font-weight:600}
 .k{color:#94a3b8}.badge{background:#334155;border-radius:6px;padding:2px 8px;font-size:12px}
 code{color:#7dd3fc}
</style>
</head>
<body>
<div class="wrap">
  <h1>TrackmeNew · CodeIgniter 4 — P0 Foundation</h1>
  <p class="sub">Self-test of the migration scaffold. All rows should read <span class="ok">OK</span>.</p>

  <div class="card">
    <div class="row"><span class="k">Framework</span><span>CodeIgniter <code><?= esc($checks['framework']) ?></code></span></div>
    <div class="row"><span class="k">PHP</span><span><code><?= esc($checks['php']) ?></code></span></div>
    <div class="row"><span class="k">Environment</span><span class="badge"><?= esc($checks['environment']) ?></span></div>
  </div>

  <div class="card">
    <div class="row"><strong>Database groups (shared MySQL)</strong><span></span></div>
    <?php foreach ($checks['db'] as $group => $d): ?>
      <div class="row">
        <span class="k"><?= esc($group) ?></span>
        <span>
          <?php if (!empty($d['ok'])): ?>
            <span class="ok">OK</span> — <code><?= esc($d['database'] ?? '?') ?></code>
            <span class="k">(<?= esc($d['server'] ?? '') ?>)</span>
            <?php if (isset($d['sample_invoice_rows'])): ?>
              · invoice_system rows: <code><?= esc($d['sample_invoice_rows']) ?></code>
            <?php endif; ?>
          <?php else: ?>
            <span class="bad">FAIL</span> — <?= esc($d['error'] ?? 'unknown') ?>
          <?php endif; ?>
        </span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="row"><strong>Services &amp; helpers</strong><span></span></div>
    <div class="row"><span class="k">fyContext service</span><span class="<?= $checks['services']['fyContext']?'ok':'bad' ?>"><?= $checks['services']['fyContext']?'OK':'FAIL' ?></span></div>
    <div class="row"><span class="k">app helper (fy / flash_toast)</span><span class="<?= $checks['helpers']['app']?'ok':'bad' ?>"><?= $checks['helpers']['app']?'OK':'FAIL' ?></span></div>
    <div class="row"><span class="k">cr_cache helper (cr_remember)</span><span class="<?= $checks['helpers']['cr_cache']?'ok':'bad' ?>"><?= $checks['helpers']['cr_cache']?'OK':'FAIL' ?></span></div>
  </div>

  <p class="sub">JSON version at <code>/health/json</code>. This page is scaffolding — remove before go-live.</p>
</div>
</body>
</html>
