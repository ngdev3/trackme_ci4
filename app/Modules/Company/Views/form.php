<?php
/** Tally-style company creation form. Rendered inside layout.php. */
$err = fn ($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . esc($errors[$k]) . '</div>' : '';
$old = fn ($k, $d = '') => esc(old($k, $defaults[$k] ?? $d));
?>
<div class="row justify-content-center">
    <div class="col-lg-9 col-xl-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-building-add fs-5"></i>
                <div>
                    <h3 class="card-title mb-0">Company Creation</h3>
                    <small class="text-muted">Set up your company to get started. You can edit details later.</small>
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
                <div class="card-body">
                    <?php if ($m = session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc($m) ?></div>
                    <?php endif; ?>

                    <h6 class="text-uppercase text-muted small fw-bold mb-3"><i class="bi bi-info-circle me-1"></i>Primary details</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Company / Firm / Shop Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-lg" required autofocus
                                   value="<?= esc(old('name')) ?>" placeholder="e.g. Acme Traders Pvt Ltd">
                            <?= $err('name') ?>
                            <?php if (empty($hasCompany)): ?>
                                <div class="form-text">Don't have a name yet? You can
                                    <button type="button" class="btn btn-link btn-sm p-0 align-baseline" onclick="document.getElementById('quickStartBtn').click()">skip and start with a starter company</button>
                                    named after your account.</div>
                            <?php endif; ?>
                        </div>

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
                    <h6 class="text-uppercase text-muted small fw-bold mb-3"><i class="bi bi-sliders me-1"></i>Additional details <span class="fw-normal text-lowercase">(optional)</span></h6>
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

                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
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
