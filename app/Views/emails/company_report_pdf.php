<?php
/** @var string $companyName @var string $ownerName @var array $stats @var array $parties @var string $brand */
$rs      = static fn ($n) => 'Rs ' . number_format((float) $n, 2);
$fmtDate = static fn ($d) => $d ? date('d M Y', strtotime((string) $d)) : '-';
$period  = ($stats['first_date'] ?? null)
    ? ($fmtDate($stats['first_date']) . '  to  ' . $fmtDate($stats['last_date']))
    : 'No entries recorded';
$net     = (float) ($stats['net'] ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { font-family: "DejaVu Sans", sans-serif; }
  body { color: #1f2937; font-size: 12px; margin: 0; }
  .head { border-bottom: 3px solid #b91c1c; padding-bottom: 10px; margin-bottom: 16px; }
  .brand { font-size: 13px; font-weight: bold; color: #0b2f73; letter-spacing: .5px; }
  h1 { font-size: 20px; margin: 6px 0 2px; color: #b91c1c; }
  .sub { color: #6b7280; font-size: 11px; }
  .badge { display: inline-block; background: #fdeaea; color: #b91c1c; font-size: 10px; font-weight: bold;
           text-transform: uppercase; letter-spacing: .5px; padding: 3px 9px; border-radius: 4px; margin-top: 6px; }
  h2 { font-size: 13px; color: #0b2f73; margin: 20px 0 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; }
  .kv td { padding: 7px 10px; border: 1px solid #eef2f5; font-size: 12px; }
  .kv td.k { color: #6b7280; width: 45%; background: #f8fafc; }
  .kv td.v { font-weight: bold; text-align: right; }
  .grid td { padding: 7px 9px; border: 1px solid #eef2f5; font-size: 11px; }
  .grid th { padding: 7px 9px; background: #0b2f73; color: #fff; font-size: 10px; text-transform: uppercase;
             letter-spacing: .4px; text-align: left; }
  .grid th.r, .grid td.r { text-align: right; }
  .grid tr:nth-child(even) td { background: #f8fafc; }
  .pos { color: #15803d; } .neg { color: #b91c1c; }
  .warn { margin-top: 18px; background: #fdeaea; border: 1px solid #f6cccc; color: #7f1d1d;
          padding: 11px 13px; font-size: 11px; border-radius: 4px; }
  .foot { margin-top: 16px; color: #9ca3af; font-size: 10px; text-align: center; }
</style>
</head>
<body>
  <div class="head">
    <div class="brand"><?= esc($brand) ?></div>
    <h1>Company Final Report</h1>
    <div class="sub"><strong><?= esc($companyName) ?></strong><?= $ownerName !== '' ? ' &middot; Owner: ' . esc($ownerName) : '' ?> &middot; Generated <?= esc($stats['deleted_at'] ?? date('d M Y, H:i')) ?></div>
    <span class="badge">Permanently deleted</span>
  </div>

  <h2>Summary</h2>
  <table class="kv">
    <tr><td class="k">Total entries</td><td class="v"><?= esc(number_format((int) ($stats['entries'] ?? 0))) ?></td></tr>
    <tr><td class="k">Accounts / parties</td><td class="v"><?= esc(number_format((int) ($stats['parties'] ?? 0))) ?></td></tr>
    <tr><td class="k">Total Jama (in)</td><td class="v pos"><?= esc($rs($stats['jama'] ?? 0)) ?></td></tr>
    <tr><td class="k">Total Naam (out)</td><td class="v neg"><?= esc($rs($stats['naam'] ?? 0)) ?></td></tr>
    <tr><td class="k">Net balance</td><td class="v <?= $net < 0 ? 'neg' : 'pos' ?>"><?= esc($rs($net)) ?></td></tr>
    <tr><td class="k">Entry period</td><td class="v"><?= esc($period) ?></td></tr>
  </table>

  <?php if (! empty($parties)): ?>
  <h2>Accounts breakdown (<?= count($parties) ?>)</h2>
  <table class="grid">
    <thead>
      <tr><th>Account / Party</th><th class="r">Entries</th><th class="r">Jama (in)</th><th class="r">Naam (out)</th><th class="r">Net</th></tr>
    </thead>
    <tbody>
      <?php foreach ($parties as $p): $pnet = (float) $p['jama'] - (float) $p['naam']; ?>
      <tr>
        <td><?= esc($p['party']) ?></td>
        <td class="r"><?= esc(number_format((int) $p['entries'])) ?></td>
        <td class="r pos"><?= esc($rs($p['jama'])) ?></td>
        <td class="r neg"><?= esc($rs($p['naam'])) ?></td>
        <td class="r <?= $pnet < 0 ? 'neg' : 'pos' ?>"><?= esc($rs($pnet)) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <div class="warn">
    <strong>This deletion is permanent.</strong> The company &ldquo;<?= esc($companyName) ?>&rdquo; and all of its entries, accounts and attachments have been erased and <strong>cannot be recovered</strong>. This report is the only remaining record.
  </div>

  <div class="foot"><?= esc($brand) ?> &middot; This is an automatically generated record.</div>
</body>
</html>
