<?php
/** Tally-style company creation form. Rendered inside layout.php. */
$err = fn ($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . esc($errors[$k]) . '</div>' : '';
$old = fn ($k, $d = '') => esc(old($k, $defaults[$k] ?? $d));

$suggests    = $nameSuggestions ?? [];
$personalSug = array_slice($suggests, 0, 10);   // "{First name} …"
$popularSug  = array_slice($suggests, 10);       // common shop/firm names
?>
<style>
/* ============ Create Company — redesigned ============ */
.cc-wrap{ --cc-brand:#0f766e; --cc-brand-2:#2563eb; --cc-ink:#152033; --cc-muted:#64748b;
          --cc-line:#e6edf6; --cc-soft:#f6f9fc; }
.cc-card{ border:1px solid var(--cc-line); border-radius:22px; overflow:hidden; background:#fff;
          box-shadow:0 18px 50px rgba(21,32,51,.08); }
/* Hero header */
.cc-hero{ position:relative; padding:26px 28px; color:#fff; overflow:hidden;
          background:radial-gradient(circle at 88% 12%,rgba(245,158,11,.35),transparent 30%),
                     linear-gradient(135deg,#083344 0%,#0f766e 52%,#1d4ed8 100%); }
.cc-hero:after{ content:""; position:absolute; right:-70px; bottom:-110px; width:260px; height:260px;
          border:30px solid rgba(255,255,255,.10); border-radius:50%; }
.cc-hero-in{ position:relative; z-index:1; display:flex; align-items:center; gap:16px; }
.cc-badge{ width:54px; height:54px; flex:0 0 auto; border-radius:16px; display:grid; place-items:center;
          background:rgba(255,255,255,.16); backdrop-filter:blur(6px); font-size:26px; }
.cc-hero h3{ margin:0; font-weight:800; letter-spacing:-.2px; font-size:1.5rem; }
.cc-hero p{ margin:3px 0 0; color:rgba(255,255,255,.85); font-size:.95rem; }

.cc-body{ padding:24px 28px; }
.cc-section-label{ display:flex; align-items:center; gap:7px; margin:0 0 14px; font-size:.72rem;
          font-weight:800; letter-spacing:.09em; text-transform:uppercase; color:var(--cc-muted); }
.cc-section-label i{ color:var(--cc-brand); }

/* Hero name field */
.cc-name-field label{ font-weight:700; color:var(--cc-ink); margin-bottom:8px; font-size:1.02rem; }
.cc-name-input{ height:auto; padding:16px 18px; font-size:1.15rem; font-weight:600; border-radius:15px;
          border:1.6px solid var(--cc-line); background:var(--cc-soft); transition:.15s; }
.cc-name-input:focus{ background:#fff; border-color:var(--cc-brand-2);
          box-shadow:0 0 0 4px rgba(37,99,235,.14); }
.cc-hint{ margin-top:9px; color:var(--cc-muted); font-size:.9rem; }
.cc-hint .btn-link{ font-weight:700; }

/* Suggestions */
.cc-suggest{ margin-top:20px; padding:18px; border-radius:18px; border:1px dashed #d7e2f0;
          background:linear-gradient(180deg,#fbfdff, #f4f8ff); }
.cc-suggest-title{ display:flex; align-items:center; gap:8px; font-weight:800; color:var(--cc-ink);
          font-size:.98rem; }
.cc-suggest-title i{ color:#f59e0b; }
.cc-suggest-title small{ font-weight:600; color:var(--cc-muted); }
.cc-group{ margin-top:14px; }
.cc-group-label{ font-size:.72rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase;
          color:var(--cc-muted); margin-bottom:9px; display:flex; align-items:center; gap:6px; }
.cc-chips{ display:flex; flex-wrap:wrap; gap:9px; }
.cc-chip{ display:inline-flex; align-items:center; gap:7px; padding:9px 15px; border-radius:999px;
          border:1.4px solid var(--cc-line); background:#fff; color:var(--cc-ink); font-size:.9rem;
          font-weight:600; cursor:pointer; transition:transform .12s, border-color .12s, box-shadow .12s, background .12s; }
.cc-chip i{ font-size:.9rem; color:var(--cc-muted); transition:color .12s; }
.cc-chip:hover{ transform:translateY(-2px); border-color:var(--cc-brand); color:var(--cc-brand);
          box-shadow:0 8px 18px rgba(15,118,110,.16); }
.cc-chip:hover i{ color:var(--cc-brand); }
.cc-chip--you{ background:rgba(37,99,235,.06); border-color:rgba(37,99,235,.22); }
.cc-chip--you i{ color:var(--cc-brand-2); }
.cc-chip--you:hover{ border-color:var(--cc-brand-2); color:var(--cc-brand-2); box-shadow:0 8px 18px rgba(37,99,235,.18); }
.cc-chip.is-active{ background:linear-gradient(135deg,var(--cc-brand),var(--cc-brand-2)); color:#fff;
          border-color:transparent; box-shadow:0 10px 22px rgba(37,99,235,.28); }
.cc-chip.is-active i{ color:#fff; }

@media (max-width:575px){ .cc-body{ padding:18px; } .cc-hero{ padding:20px; } }
</style>

<div class="row justify-content-center cc-wrap">
    <div class="col-lg-9 col-xl-8">
        <div class="cc-card">
            <!-- Hero header -->
            <div class="cc-hero">
                <div class="cc-hero-in">
                    <span class="cc-badge"><i class="bi bi-buildings"></i></span>
                    <div>
                        <h3>Create your company</h3>
                        <p>Name it and you're in — every other detail can be edited later.</p>
                    </div>
                </div>
            </div>

<?php if (empty($hasCompany)): ?>
            <!-- Standalone "skip" form: no required fields, so it bypasses the
                 app-wide HTML5 submit guard. The skip button below targets it
                 via its form="" attribute even though it sits in the main form. -->
            <form id="skipStartForm" action="<?= site_url('company/quick-start') ?>" method="post" data-no-validate class="d-none"
                  data-confirm="We'll create a starter company named &ldquo;<?= esc($accountName ?? 'My Company', 'attr') ?>&rdquo; from your account name. You can rename it and add details anytime under Company Profile."
                  data-confirm-title="Start without a name?"
                  data-confirm-btn="Yes, continue"
                  data-confirm-icon="info">
                <?= csrf_field() ?>
            </form>
<?php endif; ?>

            <form action="<?= site_url('company/store') ?>" method="post" autocomplete="off">
                <?= csrf_field() ?>
                <div class="cc-body">
                    <?php if ($m = session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc($m) ?></div>
                    <?php endif; ?>

                    <div class="cc-section-label"><i class="bi bi-info-circle"></i>Primary details</div>

                    <div class="cc-name-field">
                        <label for="cc-name">Company / Firm / Shop Name <span class="text-danger">*</span></label>
                        <input id="cc-name" type="text" name="name" class="form-control cc-name-input" required autofocus
                               value="<?= esc(old('name')) ?>" placeholder="e.g. Acme Traders Pvt Ltd">
                        <?= $err('name') ?>
                        <?php if (empty($hasCompany)): ?>
                            <div class="cc-hint">Don't have a name yet? You can
                                <button type="button" class="btn btn-link btn-sm p-0 align-baseline" onclick="document.getElementById('quickStartBtn').click()">skip and start with a starter company</button>
                                named after your account.</div>
                        <?php endif; ?>

                        <?php if (! empty($suggests)): ?>
                            <div class="cc-suggest">
                                <div class="cc-suggest-title">
                                    <i class="bi bi-lightbulb-fill"></i> Need a name? <small>Tap any idea to use it.</small>
                                </div>

                                <?php if (! empty($personalSug)): ?>
                                    <div class="cc-group">
                                        <div class="cc-group-label"><i class="bi bi-stars"></i> Made from your name</div>
                                        <div class="cc-chips">
                                            <?php foreach ($personalSug as $s): ?>
                                                <button type="button" class="cc-chip cc-chip--you name-suggest-chip" data-name="<?= esc($s, 'attr') ?>">
                                                    <i class="bi bi-stars"></i><?= esc($s) ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (! empty($popularSug)): ?>
                                    <div class="cc-group">
                                        <div class="cc-group-label"><i class="bi bi-shop"></i> Popular picks</div>
                                        <div class="cc-chips">
                                            <?php foreach ($popularSug as $s): ?>
                                                <button type="button" class="cc-chip name-suggest-chip" data-name="<?= esc($s, 'attr') ?>">
                                                    <i class="bi bi-shop-window"></i><?= esc($s) ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <script>
                            (function () {
                                var input = document.getElementById('cc-name');
                                var chips = document.querySelectorAll('.name-suggest-chip');
                                chips.forEach(function (chip) {
                                    chip.addEventListener('click', function () {
                                        if (!input) return;
                                        input.value = chip.getAttribute('data-name');
                                        input.focus();
                                        input.dispatchEvent(new Event('input', { bubbles: true }));
                                        chips.forEach(function (c) { c.classList.remove('is-active'); });
                                        chip.classList.add('is-active');
                                    });
                                });
                                // Clear the highlight if the user edits the name by hand.
                                if (input) input.addEventListener('input', function () {
                                    chips.forEach(function (c) {
                                        if (c.getAttribute('data-name') !== input.value) c.classList.remove('is-active');
                                    });
                                });
                            })();
                            </script>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Financial Year From <span class="text-danger">*</span></label>
                            <input type="date" name="financial_year_from" class="form-control" required value="<?= $old('financial_year_from') ?>">
                            <?= $err('financial_year_from') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Books Beginning From <span class="text-danger">*</span></label>
                            <input type="date" name="books_beginning_from" class="form-control" required value="<?= $old('books_beginning_from') ?>">
                            <?= $err('books_beginning_from') ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Country <span class="text-danger">*</span></label>
                            <select name="country" class="form-select" required>
                                <?php foreach (company_countries() as $c): ?>
                                    <option value="<?= esc($c, 'attr') ?>" <?= $old('country', 'India') === $c ? 'selected' : '' ?>><?= esc($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $err('country') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">State <span class="text-danger">*</span></label>
                            <select name="state" class="form-select" required>
                                <option value="">— Select State —</option>
                                <?php foreach (indian_states() as $s): ?>
                                    <option value="<?= esc($s, 'attr') ?>" <?= old('state') === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $err('state') ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">GST Registration Type <span class="text-danger">*</span></label>
                            <select name="gst_registration_type" class="form-select" required>
                                <option value="">— Select —</option>
                                <?php foreach (gst_registration_types() as $t): ?>
                                    <option value="<?= esc($t, 'attr') ?>" <?= old('gst_registration_type') === $t ? 'selected' : '' ?>><?= esc($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $err('gst_registration_type') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Number <span class="text-muted small">(optional)</span></label>
                            <input type="text" name="gst_number" class="form-control text-uppercase" maxlength="15"
                                   value="<?= esc(old('gst_number')) ?>" placeholder="27ABCDE1234F1Z5">
                            <?= $err('gst_number') ?>
                        </div>

                        <?php if (empty($hasCompany)): ?>
                        <div class="col-md-6">
                            <label class="form-label">Opening Cash Balance <span class="text-muted small">(if any)</span></label>
                            <div class="input-group">
                                <span class="input-group-text">&#8377;</span>
                                <input type="number" step="0.01" name="opening_balance" class="form-control"
                                       value="<?= esc(old('opening_balance')) ?>" placeholder="0.00">
                            </div>
                            <div class="form-text">Cash-in-hand you are starting your books with. You can change it later from <strong>Opening Balance</strong> in the menu.</div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <hr class="my-4">
                    <div class="cc-section-label"><i class="bi bi-sliders"></i>Additional details <span class="fw-normal text-lowercase ms-1" style="letter-spacing:0;">(optional)</span></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Business Type</label>
                            <select name="business_type" class="form-select">
                                <option value="">— Select —</option>
                                <?php foreach (business_types() as $b): ?>
                                    <option value="<?= esc($b, 'attr') ?>" <?= old('business_type') === $b ? 'selected' : '' ?>><?= esc($b) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control" value="<?= esc(old('mobile')) ?>" placeholder="9876543210">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= esc(old('email')) ?>" placeholder="accounts@company.com">
                            <?= $err('email') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="<?= esc(old('address')) ?>" placeholder="Street, City, PIN">
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:var(--cc-soft); border-top:1px solid var(--cc-line);">
                    <?php if (! empty($hasCompany)): ?>
                        <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Cancel</a>
                    <?php else: ?>
                        <a href="<?= site_url('logout') ?>" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-right"></i> Sign out</a>
                    <?php endif; ?>
                    <div class="d-flex gap-2 ms-auto">
                        <?php if (empty($hasCompany)): ?>
                            <button id="quickStartBtn" class="btn btn-outline-primary btn-lg" type="submit" form="skipStartForm">
                                <i class="bi bi-magic me-1"></i> Skip &amp; use my account name
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-check2-circle me-1"></i> Create Company</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
