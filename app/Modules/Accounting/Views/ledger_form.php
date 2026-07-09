<?php
/** Add/Edit ledger. Rendered inside layout.php. */
$err = fn ($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . esc(is_array($errors[$k]) ? implode(' ', $errors[$k]) : $errors[$k]) . '</div>' : '';
$action = $mode === 'edit' ? site_url('accounting/ledgers/update/' . $row['id']) : site_url('accounting/ledgers/store');
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-journal-plus me-1"></i> <?= esc($title) ?></h3></div>
            <form action="<?= $action ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Ledger Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?= esc($row['name'] ?? old('name')) ?>">
                            <?= $err('name') ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Group <span class="text-danger">*</span></label>
                            <select name="group_id" class="form-select" required>
                                <option value="">— Select —</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?= $g['id'] ?>" <?= (int) ($row['group_id'] ?? 0) === (int) $g['id'] ? 'selected' : '' ?>><?= esc($g['name']) ?> (<?= esc($g['nature']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <?= $err('group_id') ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Opening Balance</label>
                            <input type="number" step="0.01" name="opening_balance" class="form-control" value="<?= esc($row['opening_balance'] ?? '0') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select name="opening_type" class="form-select">
                                <option value="Dr" <?= ($row['opening_type'] ?? 'Dr') === 'Dr' ? 'selected' : '' ?>>Dr</option>
                                <option value="Cr" <?= ($row['opening_type'] ?? '') === 'Cr' ? 'selected' : '' ?>>Cr</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Number</label>
                            <input type="text" name="gst_number" class="form-control text-uppercase" value="<?= esc($row['gst_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact</label>
                            <input type="text" name="contact" class="form-control" value="<?= esc($row['contact'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" value="<?= esc($row['notes'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= site_url('accounting/ledgers') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Ledger</button>
                </div>
            </form>
        </div>
    </div>
</div>
