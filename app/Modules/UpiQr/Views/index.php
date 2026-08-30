<?php
/** UPI QR Codes — saved payee directory + client-side QR generator. In layout.php. */
$rows = $rows ?? [];
$hue  = static fn (string $s): int => (int) (crc32($s) % 360);
?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-qr-code me-1"></i> Receive Payment <span class="erp-pill gray ms-1"><?= count($rows) ?></span></h3>
        <div class="d-flex gap-2">
            <input type="search" id="uqSearch" class="form-control form-control-sm" placeholder="Search payee, UPI ID, bank…" style="min-width:200px">
            <button class="btn btn-sm btn-primary" data-uq-add><i class="bi bi-plus-lg me-1"></i>Add payee</button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($rows)): ?>
            <div class="erp-empty py-5 text-center">
                <i class="bi bi-qr-code" style="font-size:38px;opacity:.5"></i>
                <div class="mt-2">No payees yet. Add a UPI ID or bank account to generate a reusable payment QR.</div>
                <button class="btn btn-primary btn-sm mt-3" data-uq-add><i class="bi bi-plus-lg me-1"></i>Add payee</button>
            </div>
        <?php else: ?>
            <div class="uq-grid" id="uqGrid">
                <?php foreach ($rows as $r):
                    $h      = $hue($r['payee_name'] ?: $r['label']);
                    $target = $r['method'] === 'upi'
                        ? (string) $r['upi_id']
                        : 'A/C ••••' . substr((string) $r['account_number'], -4) . ' · ' . $r['ifsc'];
                    $hay = strtolower(trim($r['label'] . ' ' . $r['payee_name'] . ' ' . $r['upi_id'] . ' ' . $r['bank_name'] . ' ' . $r['branch'] . ' ' . $r['ifsc']));
                ?>
                <div class="uq-card" style="--h:<?= $h ?>" data-uq-search="<?= esc($hay, 'attr') ?>"
                    data-method="<?= esc($r['method'], 'attr') ?>"
                    data-id="<?= (int) $r['id'] ?>"
                    data-label="<?= esc($r['label'], 'attr') ?>"
                    data-payee="<?= esc($r['payee_name'], 'attr') ?>"
                    data-upi="<?= esc($r['upi_id'], 'attr') ?>"
                    data-bank="<?= esc($r['bank_name'], 'attr') ?>"
                    data-branch="<?= esc($r['branch'], 'attr') ?>"
                    data-city="<?= esc($r['city'], 'attr') ?>"
                    data-account="<?= esc($r['account_number'], 'attr') ?>"
                    data-ifsc="<?= esc($r['ifsc'], 'attr') ?>"
                    data-amount="<?= esc((string) $r['amount'], 'attr') ?>"
                    data-note="<?= esc($r['note'], 'attr') ?>">
                    <div class="uq-card-top">
                        <span class="uq-ava"><?= esc(strtoupper(substr($r['payee_name'] ?: '?', 0, 1))) ?></span>
                        <div class="uq-card-info">
                            <div class="uq-card-label"><?= esc($r['label']) ?>
                                <span class="uq-tag uq-tag-<?= esc($r['method']) ?>"><i class="bi <?= $r['method'] === 'upi' ? 'bi-at' : 'bi-bank' ?>"></i><?= $r['method'] === 'upi' ? 'UPI ID' : 'Bank' ?></span>
                            </div>
                            <div class="uq-card-target"><?= esc($target) ?></div>
                            <?php if ($r['method'] === 'bank' && ($r['bank_name'] || $r['branch'])): ?>
                                <div class="uq-card-bank"><?= esc(trim($r['bank_name'] . ($r['branch'] ? ' · ' . $r['branch'] : ''))) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="uq-card-actions">
                        <button class="btn btn-sm btn-primary flex-fill" data-uq-show><i class="bi bi-qr-code-scan me-1"></i>Show QR</button>
                        <button class="btn btn-sm btn-outline-secondary" data-uq-edit title="Edit"><i class="bi bi-pencil"></i></button>
                        <form method="post" action="<?= site_url('upi-qr/delete/' . (int) $r['id']) ?>" data-confirm="Remove &ldquo;<?= esc($r['label'], 'attr') ?>&rdquo;?" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-trash3"></i></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="erp-empty py-5 text-center d-none" id="uqNoMatch"><i class="bi bi-search"></i><div class="mt-2">No payee matches your search.</div></div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== Add / Edit modal ===== -->
<div class="modal fade" id="uqFormModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content uq-modal">
      <form method="post" action="<?= site_url('upi-qr/save') ?>" id="uqForm">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="uq-id">
        <div class="modal-header">
          <h5 class="modal-title" id="uqFormTitle"><i class="bi bi-qr-code me-1"></i> Add Payee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Method -->
          <div class="uq-method mb-3">
            <label class="uq-mt"><input type="radio" name="method" value="upi" checked><span><i class="bi bi-at"></i> UPI ID</span></label>
            <label class="uq-mt"><input type="radio" name="method" value="bank"><span><i class="bi bi-bank"></i> Bank Account</span></label>
          </div>

          <div class="uq-fld"><input class="form-control" name="label" id="uq-label" placeholder=" " maxlength="80"><label>Label (for your list)</label></div>
          <div class="uq-fld"><input class="form-control" name="payee_name" id="uq-payee" placeholder=" " maxlength="80" required><label>Payee / Business name</label></div>

          <!-- UPI -->
          <div data-uq-upi>
            <div class="uq-fld"><input class="form-control" name="upi_id" id="uq-upi" placeholder=" " autocomplete="off"><label>UPI ID (VPA) — name@bank</label></div>
          </div>

          <!-- Bank -->
          <div data-uq-bank class="d-none">
            <div class="uq-fld uq-ifsc">
              <input class="form-control" name="ifsc" id="uq-ifsc" placeholder=" " maxlength="11" autocomplete="off" style="text-transform:uppercase">
              <label>IFSC code</label>
              <span class="uq-ifsc-state" id="uq-ifsc-state"></span>
            </div>
            <div class="uq-ifsc-info" id="uq-ifsc-info"></div>
            <div class="uq-fld"><input class="form-control" name="bank_name" id="uq-bank" placeholder=" " maxlength="80"><label>Bank name</label></div>
            <input type="hidden" name="branch" id="uq-branch"><input type="hidden" name="city" id="uq-city">
            <div class="uq-fld"><input class="form-control" name="account_number" id="uq-account" inputmode="numeric" placeholder=" " maxlength="20"><label>Account number</label></div>
            <div class="uq-note-amber"><i class="bi bi-info-circle me-1"></i>Bank-account QR uses the NPCI IFSC handle. If an app can’t scan it, use a UPI ID or verify with a ₹1 test.</div>
          </div>

          <div class="row g-2">
            <div class="col-6"><div class="uq-fld"><input class="form-control" name="amount" id="uq-amount" type="number" step="0.01" placeholder=" "><label>Fixed amount (₹) — optional</label></div></div>
            <div class="col-6"><div class="uq-fld"><input class="form-control" name="note" id="uq-note" maxlength="50" placeholder=" "><label>Note — optional</label></div></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="uqSaveBtn"><i class="bi bi-check-circle me-1"></i>Save Payee</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== QR popup modal ===== -->
<div class="modal fade" id="uqQrModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content uq-modal">
      <div class="modal-body text-center p-4">
        <button type="button" class="btn-close position-absolute end-0 top-0 m-3" data-bs-dismiss="modal"></button>
        <h5 class="fw-bold mb-1" id="uqQrName">Payee</h5>
        <div class="text-muted small mb-1" id="uqQrTarget"></div>
        <div class="mb-3"><span class="badge bg-success-subtle text-success" id="uqQrAmt">Any amount</span></div>
        <div class="uq-qr-frame mx-auto"><div id="uqQrImg"></div></div>
        <div class="text-muted small mt-2"><i class="bi bi-shield-check text-success me-1"></i>Scan with any UPI app to pay</div>
        <div class="d-flex gap-2 mt-3">
          <button class="btn btn-success btn-sm flex-fill" id="uqQrDownload"><i class="bi bi-download me-1"></i>Download</button>
          <button class="btn btn-outline-secondary btn-sm flex-fill" id="uqQrCopy"><i class="bi bi-clipboard me-1"></i>Copy link</button>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.uq-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:14px; }
.uq-card { position:relative; background:#fff; border:1px solid #eef2f7; border-radius:16px; padding:14px 14px 12px 18px; overflow:hidden; box-shadow:0 8px 22px -18px rgba(15,23,42,.5); }
.uq-card::before { content:''; position:absolute; left:0; top:0; bottom:0; width:5px; background:linear-gradient(hsl(var(--h) 78% 58%),hsl(var(--h) 78% 46%)); }
.uq-card-top { display:flex; gap:12px; align-items:center; }
.uq-ava { flex:0 0 auto; width:46px; height:46px; border-radius:14px; display:grid; place-items:center; color:#fff; font-size:19px; font-weight:900; background:linear-gradient(135deg,hsl(var(--h) 80% 60%),hsl(var(--h) 72% 44%)); }
.uq-card-info { min-width:0; flex:1; }
.uq-card-label { font-size:15px; font-weight:800; color:#18243c; display:flex; align-items:center; gap:7px; }
.uq-tag { font-size:9.5px; font-weight:800; text-transform:uppercase; padding:2px 7px; border-radius:20px; display:inline-flex; align-items:center; gap:3px; background:#e9f1fc; color:#1769c2; }
.uq-tag i { font-size:11px; } .uq-tag-bank { background:#fff4ed; color:#c2410c; }
.uq-card-target { font-size:12.5px; font-weight:600; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.uq-card-bank { font-size:11px; color:#94a3b8; }
.uq-card-actions { display:flex; gap:6px; margin-top:12px; }
/* Modal */
.uq-modal { border:0; border-radius:20px; overflow:hidden; }
.uq-method { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.uq-mt input { position:absolute; opacity:0; pointer-events:none; }
.uq-mt span { display:flex; align-items:center; justify-content:center; gap:6px; padding:11px; border:1.5px solid #e2e9f2; border-radius:12px; cursor:pointer; font-size:13px; font-weight:800; color:#64748b; }
.uq-mt input:checked + span { border-color:#1769c2; background:#e9f1fc; color:#1769c2; box-shadow:0 4px 12px rgba(23,105,194,.16); }
.uq-fld { position:relative; margin-bottom:12px; }
.uq-fld input { width:100%; padding:16px 12px 7px; border:1.5px solid #e2e9f2; border-radius:11px; font-size:14px; font-weight:600; color:#18243c; outline:none; }
.uq-fld input:focus { border-color:#1769c2; box-shadow:0 0 0 3px rgba(23,105,194,.12); }
.uq-fld label { position:absolute; left:12px; top:13px; font-size:13px; font-weight:600; color:#97a3b4; pointer-events:none; transition:all .14s; }
.uq-fld input:focus + label, .uq-fld input:not(:placeholder-shown) + label { top:5px; font-size:10px; font-weight:800; letter-spacing:.02em; color:#1769c2; text-transform:uppercase; }
.uq-ifsc-state { position:absolute; right:12px; top:14px; font-size:13px; }
.uq-ifsc-info { font-size:12px; font-weight:600; color:#0f7a37; margin:-4px 2px 12px; min-height:0; }
.uq-note-amber { font-size:11.5px; color:#b45309; background:#fff4ed; border-radius:10px; padding:8px 10px; margin-bottom:10px; }
.uq-qr-frame { width:220px; max-width:100%; padding:12px; border-radius:16px; background:#fff; border:1px solid #eef2f7; box-shadow:inset 0 0 0 4px rgba(11,35,80,.04); }
.uq-qr-frame img, .uq-qr-frame canvas { width:100%; height:auto; display:block; }
</style>

<script src="<?= base_url('assets/vendor/qrcode/qrcode.min.js') ?>"></script>
<script>
// Run AFTER the layout's vendor JS (bootstrap loads near </body>, i.e. after this
// view's content) — otherwise `bootstrap` is undefined here and every handler
// below (Add payee, Show QR) fails to bind.
document.addEventListener('DOMContentLoaded', function () {
  var formModal = new bootstrap.Modal(document.getElementById('uqFormModal'));
  var qrModal   = new bootstrap.Modal(document.getElementById('uqQrModal'));
  var form      = document.getElementById('uqForm');
  var $ = function (id) { return document.getElementById(id); };

  // --- Method toggle ---
  function applyMethod(m) {
    document.querySelector('[data-uq-upi]').classList.toggle('d-none', m !== 'upi');
    document.querySelector('[data-uq-bank]').classList.toggle('d-none', m !== 'bank');
  }
  form.querySelectorAll('input[name="method"]').forEach(function (r) {
    r.addEventListener('change', function () { applyMethod(this.value); });
  });

  // --- Open Add ---
  function resetForm() {
    form.reset(); $('uq-id').value = '';
    form.querySelector('input[name="method"][value="upi"]').checked = true;
    applyMethod('upi'); $('uq-ifsc-info').textContent = ''; $('uq-ifsc-state').innerHTML = '';
    $('uqFormTitle').innerHTML = '<i class="bi bi-qr-code me-1"></i> Add Payee';
  }
  document.querySelectorAll('[data-uq-add]').forEach(function (b) {
    b.addEventListener('click', function () { resetForm(); formModal.show(); });
  });

  // --- Open Edit (from a card) ---
  document.querySelectorAll('[data-uq-edit]').forEach(function (b) {
    b.addEventListener('click', function () {
      var c = this.closest('.uq-card'); resetForm();
      var m = c.dataset.method || 'upi';
      form.querySelector('input[name="method"][value="' + m + '"]').checked = true; applyMethod(m);
      $('uq-id').value = c.dataset.id; $('uq-label').value = c.dataset.label; $('uq-payee').value = c.dataset.payee;
      $('uq-upi').value = c.dataset.upi; $('uq-bank').value = c.dataset.bank; $('uq-branch').value = c.dataset.branch;
      $('uq-city').value = c.dataset.city; $('uq-account').value = c.dataset.account; $('uq-ifsc').value = c.dataset.ifsc;
      $('uq-amount').value = c.dataset.amount; $('uq-note').value = c.dataset.note;
      if (c.dataset.bank) { $('uq-ifsc-info').textContent = c.dataset.bank + (c.dataset.branch ? ' — ' + c.dataset.branch : ''); }
      $('uqFormTitle').innerHTML = '<i class="bi bi-pencil me-1"></i> Edit Payee';
      formModal.show();
    });
  });

  // --- IFSC auto-lookup (Razorpay, keyless) ---
  var ifscTimer;
  $('uq-ifsc').addEventListener('input', function () {
    var code = this.value.trim().toUpperCase();
    $('uq-ifsc-info').textContent = ''; $('uq-ifsc-state').innerHTML = '';
    if (!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(code)) return;
    clearTimeout(ifscTimer);
    ifscTimer = setTimeout(function () {
      $('uq-ifsc-state').innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
      fetch('https://ifsc.razorpay.com/' + encodeURIComponent(code))
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (d) {
          if (d && d.BANK) {
            $('uq-bank').value = d.BANK; $('uq-branch').value = d.BRANCH || ''; $('uq-city').value = d.CITY || '';
            $('uq-ifsc-info').textContent = d.BANK + (d.BRANCH ? ' — ' + d.BRANCH : '') + (d.CITY ? ', ' + d.CITY : '');
            $('uq-ifsc-state').innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
          } else {
            $('uq-ifsc-info').textContent = ''; $('uq-ifsc-state').innerHTML = '<i class="bi bi-x-circle text-danger"></i>';
          }
        }).catch(function () { $('uq-ifsc-state').innerHTML = ''; });
    }, 250);
  });

  // --- Build UPI link ---
  function buildUri(d) {
    var enc = encodeURIComponent;
    var pa = d.method === 'upi' ? d.upi : (d.account + '@' + (d.ifsc || '').toLowerCase() + '.ifsc.npci');
    var parts = ['pa=' + enc(pa), 'pn=' + enc(d.payee || d.label)];
    if (d.amount && Number(d.amount) > 0) parts.push('am=' + Number(d.amount).toFixed(2));
    parts.push('cu=INR');
    if (d.note) parts.push('tn=' + enc(d.note));
    return 'upi://pay?' + parts.join('&');
  }

  // --- Show QR ---
  var currentUri = '', currentUrl = '';
  document.querySelectorAll('[data-uq-show]').forEach(function (b) {
    b.addEventListener('click', function () {
      var c = this.closest('.uq-card');
      var d = { method: c.dataset.method, payee: c.dataset.payee, label: c.dataset.label, upi: c.dataset.upi,
                account: c.dataset.account, ifsc: c.dataset.ifsc, amount: c.dataset.amount, note: c.dataset.note };
      currentUri = buildUri(d);
      $('uqQrName').textContent = d.payee || d.label;
      $('uqQrTarget').textContent = d.method === 'upi' ? d.upi : ('A/C ••••' + (d.account || '').slice(-4) + ' · ' + d.ifsc);
      var amt = Number(d.amount) || 0;
      $('uqQrAmt').textContent = amt > 0 ? ('₹ ' + amt.toFixed(2)) : 'Any amount';
      var holder = $('uqQrImg'); holder.innerHTML = ''; currentUrl = '';
      try {
        // qrcodejs (davidshimjs) renders a <canvas> (+img fallback) synchronously.
        new QRCode(holder, {
          text: currentUri, width: 240, height: 240,
          colorDark: '#0b2350', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M
        });
        // qrcodejs draws to a <canvas>, then shows an <img> of it and HIDES the
        // canvas — the <img> is the visible QR, so DON'T remove it. Read the
        // download data-URL from the canvas (works even while it's display:none).
        var cv = holder.querySelector('canvas');
        currentUrl = cv ? cv.toDataURL('image/png')
                        : (holder.querySelector('img') ? holder.querySelector('img').src : '');
      } catch (e) { holder.textContent = 'Could not render QR'; }
      qrModal.show();
    });
  });

  $('uqQrCopy').addEventListener('click', function () {
    navigator.clipboard.writeText(currentUri).then(function () { window.toast && window.toast('Link copied'); });
  });
  $('uqQrDownload').addEventListener('click', function () {
    if (!currentUrl) return;
    var a = document.createElement('a'); a.href = currentUrl; a.download = 'upi-qr.png'; document.body.appendChild(a); a.click(); a.remove();
  });

  // --- Search filter ---
  var search = $('uqSearch'), grid = $('uqGrid'), noMatch = $('uqNoMatch');
  if (search && grid) {
    search.addEventListener('input', function () {
      var q = this.value.trim().toLowerCase(), shown = 0;
      grid.querySelectorAll('.uq-card').forEach(function (c) {
        var hit = c.getAttribute('data-uq-search').indexOf(q) !== -1;
        c.classList.toggle('d-none', !hit); if (hit) shown++;
      });
      noMatch.classList.toggle('d-none', shown !== 0);
    });
  }
});
</script>
