<?php
/**
 * Company profile — the active firm's details, its completeness score, and a
 * switcher to jump between the companies the user belongs to. Owner/admin can
 * edit the details inline; everyone else sees them read-only.
 * Rendered inside app/Views/layout.php via BaseController::render().
 */
$err = fn ($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . esc($errors[$k]) . '</div>' : '';
$val = fn ($k, $d = '') => esc(old($k, $row[$k] ?? $d));
$ro  = ! $canEdit; // read-only flag
?>

<!-- ============================ Header: identity + switcher ============================ -->
<div class="row g-3">
    <div class="col-lg-5 col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="bi bi-building me-1"></i> Active Company</h3>
                <span class="badge text-bg-light border text-capitalize"><?= esc($role ?: 'member') ?></span>
            </div>
            <div class="card-body text-center">
                <div class="company-emblem mx-auto mb-3">
                    <?= esc(strtoupper(mb_substr((string) $row['name'], 0, 1))) ?>
                </div>
                <h4 class="mb-1"><?= esc($row['name']) ?></h4>
                <div class="text-muted small mb-2">
                    <?= esc($row['state'] ?? '') ?><?= ! empty($row['country']) ? ', ' . esc($row['country']) : '' ?>
                </div>
                <?php if (! empty($row['gst_number'])): ?>
                    <span class="badge text-bg-light border"><i class="bi bi-patch-check me-1"></i>GSTIN: <?= esc($row['gst_number']) ?></span>
                <?php endif; ?>

                <hr class="my-3">
                <div class="text-start">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase text-muted small fw-bold">Switch Company</span>
                        <span class="badge text-bg-secondary rounded-pill"><?= count($companies) ?></span>
                    </div>
                    <div class="company-switch-list">
                        <?php foreach ($companies as $firm): ?>
                            <?php $isActive = (int) $firm['id'] === (int) $activeId; ?>
                            <a class="company-switch-item d-flex align-items-center gap-2 p-2 rounded text-decoration-none <?= $isActive ? 'active' : '' ?>"
                               href="<?= $isActive ? '#' : site_url('company/switch/' . $firm['id'] . '?return=profile') ?>">
                                <span class="firm-dot bg-primary"></span>
                                <span class="flex-grow-1 text-truncate">
                                    <span class="fw-semibold d-block text-truncate"><?= esc($firm['name']) ?></span>
                                    <small class="text-muted text-capitalize"><?= esc($firm['membership_role'] ?? 'member') ?> &middot; <?= esc($firm['state'] ?? '') ?></small>
                                </span>
                                <?php if ($isActive): ?>
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                <?php else: ?>
                                    <i class="bi bi-arrow-right-circle text-muted"></i>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (session('account_type') !== 'firm_user'): ?>
                        <a href="<?= site_url('company/create') ?>" class="btn btn-outline-primary btn-sm w-100 mt-2">
                            <i class="bi bi-plus-circle me-1"></i> Add Company
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Completeness score -->
    <div class="col-lg-7 col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="bi bi-speedometer2 me-1"></i> Company Score</h3>
                <span class="badge text-bg-<?= esc($score['color']) ?>"><?= esc($score['label']) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <?php
                        $pct   = (int) $score['percent'];
                        $deg   = round($pct * 3.6);
                        $ringC = 'var(--bs-' . $score['color'] . ', #0d6efd)';
                        ?>
                        <div class="score-ring" style="background: conic-gradient(<?= $ringC ?> <?= $deg ?>deg, var(--erp-border, #e5e7eb) 0deg);">
                            <div class="score-ring-hole">
                                <span class="score-ring-pct"><?= esc($pct) ?>%</span>
                                <span class="score-ring-sub"><?= esc($score['done']) ?>/<?= esc($score['total']) ?> done</span>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <p class="text-muted small mb-2">A complete company profile keeps your books and GST compliance ready. Each item below improves the score.</p>
                        <ul class="score-checklist list-unstyled mb-0">
                            <?php foreach ($score['items'] as $item): ?>
                                <li class="d-flex align-items-start gap-2 <?= $item['done'] ? 'is-done' : '' ?>">
                                    <i class="bi <?= $item['done'] ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?>"></i>
                                    <span>
                                        <span class="fw-semibold"><?= esc($item['label']) ?></span>
                                        <?php if (! $item['done']): ?>
                                            <small class="d-block text-muted"><?= esc($item['hint']) ?></small>
                                        <?php endif; ?>
                                    </span>
                                    <span class="ms-auto badge rounded-pill text-bg-light border"><?= esc($item['weight']) ?> pts</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================ Details form ============================ -->
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="bi bi-pencil-square me-1"></i> Company Details</h3>
                <?php if ($ro): ?>
                    <span class="badge text-bg-light border"><i class="bi bi-lock me-1"></i>Read only</span>
                <?php endif; ?>
            </div>

            <form action="<?= site_url('company/update') ?>" method="post" autocomplete="off">
                <?= csrf_field() ?>
                <div class="card-body">
                    <?php if ($m = session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc($m) ?></div>
                    <?php endif; ?>

                    <h6 class="text-uppercase text-muted small fw-bold mb-3"><i class="bi bi-info-circle me-1"></i>Primary details</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Company / Firm Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?= $val('name') ?>" <?= $ro ? 'disabled' : '' ?>>
                            <?= $err('name') ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Financial Year From <span class="text-danger">*</span></label>
                            <input type="date" name="financial_year_from" class="form-control" required value="<?= $val('financial_year_from') ?>" <?= $ro ? 'disabled' : '' ?>>
                            <?= $err('financial_year_from') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Books Beginning From <span class="text-danger">*</span></label>
                            <input type="date" name="books_beginning_from" class="form-control" required value="<?= $val('books_beginning_from') ?>" <?= $ro ? 'disabled' : '' ?>>
                            <?= $err('books_beginning_from') ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Country <span class="text-danger">*</span></label>
                            <select name="country" class="form-select" required <?= $ro ? 'disabled' : '' ?>>
                                <?php foreach (company_countries() as $c): ?>
                                    <option value="<?= esc($c, 'attr') ?>" <?= old('country', $row['country'] ?? 'India') === $c ? 'selected' : '' ?>><?= esc($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $err('country') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">State <span class="text-danger">*</span></label>
                            <select name="state" class="form-select" required <?= $ro ? 'disabled' : '' ?>>
                                <option value="">— Select State —</option>
                                <?php foreach (indian_states() as $s): ?>
                                    <option value="<?= esc($s, 'attr') ?>" <?= old('state', $row['state'] ?? '') === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $err('state') ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">GST Registration Type <span class="text-danger">*</span></label>
                            <select name="gst_registration_type" class="form-select" required <?= $ro ? 'disabled' : '' ?>>
                                <option value="">— Select —</option>
                                <?php foreach (gst_registration_types() as $t): ?>
                                    <option value="<?= esc($t, 'attr') ?>" <?= old('gst_registration_type', $row['gst_registration_type'] ?? '') === $t ? 'selected' : '' ?>><?= esc($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $err('gst_registration_type') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Number <span class="text-muted small">(optional)</span></label>
                            <input type="text" name="gst_number" class="form-control text-uppercase" maxlength="15"
                                   value="<?= $val('gst_number') ?>" placeholder="27ABCDE1234F1Z5" <?= $ro ? 'disabled' : '' ?>>
                            <?= $err('gst_number') ?>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="text-uppercase text-muted small fw-bold mb-3"><i class="bi bi-sliders me-1"></i>Additional details <span class="fw-normal text-lowercase">(optional)</span></h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Business Type</label>
                            <select name="business_type" class="form-select" <?= $ro ? 'disabled' : '' ?>>
                                <option value="">— Select —</option>
                                <?php foreach (business_types() as $b): ?>
                                    <option value="<?= esc($b, 'attr') ?>" <?= old('business_type', $row['business_type'] ?? '') === $b ? 'selected' : '' ?>><?= esc($b) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control" value="<?= $val('mobile') ?>" placeholder="9876543210" <?= $ro ? 'disabled' : '' ?>>
                            <?= $err('mobile') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= $val('email') ?>" placeholder="accounts@company.com" <?= $ro ? 'disabled' : '' ?>>
                            <?= $err('email') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="<?= $val('address') ?>" placeholder="Street, City, PIN" <?= $ro ? 'disabled' : '' ?>>
                        </div>
                    </div>
                </div>

                <?php if ($canEdit): ?>
                    <div class="card-footer text-end">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i> Save Changes</button>
                    </div>
                <?php else: ?>
                    <div class="card-footer text-muted small"><i class="bi bi-info-circle me-1"></i>Only the owner or an admin can edit these details.</div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<style>
.company-emblem {
    width: 72px; height: 72px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; font-weight: 700; color: #fff;
    background: linear-gradient(135deg, var(--bs-primary, #0d6efd), var(--bs-info, #0dcaf0));
}
.company-switch-list { display: flex; flex-direction: column; gap: 4px; max-height: 260px; overflow-y: auto; }
.company-switch-item { border: 1px solid var(--erp-border, #e5e7eb); transition: background .15s, border-color .15s; }
.company-switch-item:hover { background: var(--bs-primary-bg-subtle, #e7f1ff); }
.company-switch-item.active { border-color: var(--bs-primary, #0d6efd); background: var(--bs-primary-bg-subtle, #e7f1ff); }
.company-switch-item .firm-dot { width: 8px; height: 8px; border-radius: 50%; flex: 0 0 auto; }
</style>
