<?php
/** Farmer Captures inbox (admin/accountMapping/captures) — CI4 port. */
helper(['url']);
$rows = ! empty($captures) ? $captures : [];
$base = base_url('admin/accountMapping/captures');
$tab = function ($k) use ($status) { return $status === $k ? 'is-on' : ''; };
?>
<style>
  .fc{color:#1e2a3d;padding:22px}
  .fc *{box-sizing:border-box}
  .fc-shell{margin:0 auto;max-width:1240px}
  .fc-hero{align-items:center;background:linear-gradient(135deg,#0f8a5f,#0a6f49);border-radius:16px;box-shadow:0 18px 44px rgba(15,138,95,.26);color:#fff;display:flex;flex-wrap:wrap;gap:16px;justify-content:space-between;margin-bottom:18px;padding:22px 26px}
  .fc-hero-l{align-items:center;display:flex;gap:16px}
  .fc-hero-ic{align-items:center;background:rgba(255,255,255,.16);border-radius:14px;display:flex;font-size:26px;height:58px;justify-content:center;width:58px}
  .fc-hero h4{font-size:22px;font-weight:900;margin:0}
  .fc-hero p{font-size:13px;font-weight:600;margin:4px 0 0;opacity:.92}
  .fc-chip{align-items:center;background:rgba(255,255,255,.18);border-radius:10px;color:#fff;display:inline-flex;font-weight:800;gap:8px;padding:9px 15px;font-size:13px}
  .fc-card{background:#fff;border:1px solid #e5ecf5;border-radius:16px;box-shadow:0 14px 36px rgba(24,36,60,.07);margin-bottom:18px;overflow:hidden}
  .fc-card-h{align-items:center;border-bottom:1px solid #eef2f7;display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;padding:15px 22px;background:#fbfdff}
  .fc-card-h h5{font-size:15px;font-weight:900;margin:0}
  .fc-tabs{display:inline-flex;border:1px solid #d4dbe6;border-radius:8px;overflow:hidden}
  .fc-tabs a{padding:7px 15px;font-size:12.5px;font-weight:800;text-decoration:none;color:#3b4860;background:#fff}
  .fc-tabs a + a{border-left:1px solid #d4dbe6}
  .fc-tabs a.is-on{background:#0f8a5f;color:#fff}
  .fc-note{background:#f0f7ff;border:1px solid #cfe3fb;border-radius:12px;color:#25507e;font-size:12.5px;font-weight:700;margin-bottom:18px;padding:12px 16px}
  .fc-note b{color:#173a5e}
  .fc-note code{background:#e6effb;border-radius:5px;padding:1px 6px;font-weight:800}
  .fc-tablewrap{padding:6px 8px 12px;overflow-x:auto}
  table.fc-table{width:100%;border-collapse:separate;border-spacing:0;font-size:13px}
  table.fc-table thead th{background:#f1f7f3;color:#2f5847;font-weight:800;padding:10px 12px;border-bottom:2px solid #dcece3;text-align:left;white-space:nowrap;font-size:11px;text-transform:uppercase;letter-spacing:.02em}
  table.fc-table tbody td{padding:10px 12px;border-bottom:1px solid #eef2f6;vertical-align:top}
  table.fc-table tbody tr:hover{background:#f7fbf9}
  .fc-strong{font-weight:800;color:#1e2a3d}
  .fc-muted{color:#8394a7;font-weight:700;font-size:11.5px}
  .fc-badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:10.5px;font-weight:800}
  .fc-badge-new{background:#e7f6ee;color:#0a6f49}
  .fc-badge-used{background:#eef4ff;color:#2557a7}
  .fc-badge-archived{background:#f1f5f9;color:#64748b}
  .fc-actions{display:flex;flex-direction:column;gap:6px;min-width:150px}
  .fc-btn{align-items:center;border:0;border-radius:8px;cursor:pointer;display:inline-flex;font-size:12px;font-weight:800;gap:6px;justify-content:center;padding:7px 12px;text-decoration:none;white-space:nowrap}
  .fc-btn-use{background:linear-gradient(135deg,#0f8a5f,#0a6f49);color:#fff}
  .fc-btn-use:hover{filter:brightness(1.06);color:#fff}
  .fc-btn-arch{background:#eef3fa;color:#48607d}.fc-btn-arch:hover{background:#e2ebf6;color:#48607d}
  .fc-btn-del{background:#fff1f2;color:#be123c}.fc-btn-del:hover{background:#be123c;color:#fff}
  .fc-empty{align-items:center;color:#64748b;display:flex;flex-direction:column;gap:8px;justify-content:center;padding:44px 20px;text-align:center;font-weight:700}
  .fc-empty .em-ic{font-size:38px;opacity:.45}
  @media(max-width:640px){.fc{padding:14px}.fc-hero{flex-direction:column;align-items:flex-start}}
</style>

<main class="main-content bgc-grey-100 fc">
  <div id="mainContent">
    <div class="container-fluid fc-shell">
      <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?><div class="alert alert-warning"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

      <section class="fc-hero">
        <div class="fc-hero-l">
          <span class="fc-hero-ic"><i class="ti-download"></i></span>
          <div>
            <h4>Farmer Captures</h4>
            <p>Farmer data pushed in from external portals by the browser extension — review &amp; use in Kisan Vahi.</p>
          </div>
        </div>
        <div class="fc-hero-r">
          <span class="fc-chip"><i class="ti-bell"></i> <?= (int) $count_new ?> new</span>
        </div>
      </section>

      <div class="fc-note">
        <b>How it works:</b> install the <b>TrackMe Farmer Capture</b> browser extension, open a portal like
        the UP e-procurement farmer page, fill/verify the farmer, then click <code>Send to TrackMe</code>.
        The row lands here. Aadhaar is stored masked only.
      </div>

      <div class="fc-card">
        <div class="fc-card-h">
          <h5><i class="ti-clipboard" style="color:#0f8a5f"></i> Captured Farmers</h5>
          <div class="fc-tabs">
            <a class="<?= $tab('new') ?>" href="<?= $base ?>?status=new">New</a>
            <a class="<?= $tab('used') ?>" href="<?= $base ?>?status=used">Used</a>
            <a class="<?= $tab('archived') ?>" href="<?= $base ?>?status=archived">Archived</a>
            <a class="<?= $tab('all') ?>" href="<?= $base ?>?status=all">All</a>
          </div>
        </div>

        <?php if (empty($rows)): ?>
          <div class="fc-empty">
            <div class="em-ic"><i class="ti-inbox"></i></div>
            <div>No <?= esc($status === 'all' ? '' : $status) ?> captures yet.</div>
            <div class="fc-muted">Capture a farmer from a portal using the extension, and it will appear here.</div>
          </div>
        <?php else: ?>
          <div class="fc-tablewrap">
            <table class="fc-table">
              <thead>
                <tr>
                  <th>Farmer</th><th>Contact</th><th>Bank</th><th>Qty</th>
                  <th>Captured by</th><th>Source / When</th><th>Status</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td>
                      <div class="fc-strong"><?= esc($r->farmer_name ? $r->farmer_name : '—') ?></div>
                      <div class="fc-muted">ID: <?= esc($r->farmer_id ? $r->farmer_id : '—') ?></div>
                      <?php if (! empty($r->father_name)): ?><div class="fc-muted">S/o: <?= esc($r->father_name) ?></div><?php endif; ?>
                      <?php if (! empty($r->aadhaar_masked)): ?><div class="fc-muted">Aadhaar: <?= esc($r->aadhaar_masked) ?></div><?php endif; ?>
                    </td>
                    <td>
                      <div><?= esc($r->mobile_no ? $r->mobile_no : '—') ?></div>
                      <?php if (! empty($r->village)): ?><div class="fc-muted"><?= esc($r->village) ?></div><?php endif; ?>
                    </td>
                    <td>
                      <div><?= esc($r->bank_name ? $r->bank_name : '—') ?></div>
                      <?php if (! empty($r->account_no)): ?><div class="fc-muted">A/C: <?= esc($r->account_no) ?></div><?php endif; ?>
                      <?php if (! empty($r->ifsc)): ?><div class="fc-muted">IFSC: <?= esc($r->ifsc) ?></div><?php endif; ?>
                    </td>
                    <td><?= esc($r->quantity ? $r->quantity : '—') ?></td>
                    <td>
                      <?php $cap_name = trim((string) (($r->cap_first ?? '') . ' ' . ($r->cap_last ?? ''))); ?>
                      <?php if ($cap_name !== ''): ?>
                        <div class="fc-strong"><?= esc($cap_name) ?></div>
                        <div class="fc-muted">User #<?= (int) $r->captured_by ?><?= ! empty($r->cap_mobile) ? ' · ' . esc($r->cap_mobile) : '' ?></div>
                      <?php elseif (! empty($r->captured_by)): ?>
                        <div class="fc-strong">User #<?= (int) $r->captured_by ?></div>
                      <?php else: ?>
                        <span class="fc-badge fc-badge-archived" title="Sent with the shared global key">Shared key</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (! empty($r->source_url)): ?>
                        <a href="<?= esc($r->source_url) ?>" target="_blank" rel="noopener" class="fc-muted" style="text-decoration:underline;">portal ↗</a>
                      <?php else: ?><span class="fc-muted">—</span><?php endif; ?>
                      <div class="fc-muted"><?= esc($r->created_at ? date('d-M-Y h:i A', strtotime($r->created_at)) : '') ?></div>
                    </td>
                    <td><span class="fc-badge fc-badge-<?= esc($r->status) ?>"><?= esc(ucfirst($r->status)) ?></span></td>
                    <td>
                      <div class="fc-actions">
                        <a class="fc-btn fc-btn-use" href="<?= base_url('admin/accountMapping/capture_use/' . (int) $r->id) ?>"><i class="ti-import"></i> Use in Kisan Vahi</a>
                        <?php if ($r->status !== 'archived'): ?>
                          <a class="fc-btn fc-btn-arch" href="<?= base_url('admin/accountMapping/capture_archive/' . (int) $r->id) ?>"><i class="ti-archive"></i> Archive</a>
                        <?php endif; ?>
                        <a class="fc-btn fc-btn-del" href="<?= base_url('admin/accountMapping/capture_delete/' . (int) $r->id) ?>" onclick="return confirm('Delete this capture permanently?');"><i class="ti-trash"></i> Delete</a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>
