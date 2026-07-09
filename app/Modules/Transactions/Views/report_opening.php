<?php
/**
 * Shri Rokad Nagad — set the opening cash-in-hand for each financial year, and
 * rename the label. Fed on 1 April; it carries forward through every month/day
 * of that FY, and the 31-March closing auto-rolls into the next year's opening.
 * Rendered in layout.php.
 */
use Modules\Transactions\Controllers\ReportController;

$fmt = fn ($n) => number_format((float) $n, 2);
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card tm-table-card">
            <div class="tm-table-head">
                <h3 class="tm-table-title"><i class="bi bi-cash-stack"></i> <?= esc($shriLabel) ?> &mdash; Opening Cash</h3>
                <a href="<?= site_url('transactions') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Rokadh Parcha</a>
            </div>
            <div class="card-body">
                <p class="text-secondary">
                    Enter the opening cash-in-hand at the start of a financial year (1&nbsp;April). It becomes the base
                    balance and carries forward automatically to every month and day of that year. The 31&nbsp;March
                    closing balance auto-rolls into the next year's opening &mdash; you only need to feed it once (or
                    override any year below).
                </p>

                <!-- Rename the label -->
                <form action="<?= site_url('transactions/opening') ?>" method="post" class="row g-2 align-items-end mb-3">
                    <?= csrf_field() ?>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Display name</label>
                        <input type="text" name="label" class="form-control" maxlength="60" required
                               value="<?= esc($shriLabel) ?>" placeholder="Shri Rokad Nagad">
                        <div class="invalid-feedback">Please enter a name (up to 60 characters).</div>
                    </div>
                    <div class="col-12 col-md-auto">
                        <?php if (can($moduleCode, 'edit')): ?>
                            <button class="btn btn-outline-primary"><i class="bi bi-tag me-1"></i> Rename</button>
                        <?php endif; ?>
                    </div>
                </form>
                <hr>

                <!-- Set / update the amount for a chosen FY -->
                <form action="<?= site_url('transactions/opening') ?>" method="post" class="row g-2 align-items-end mb-4">
                    <?= csrf_field() ?>
                    <div class="col-6 col-md-4">
                        <label class="form-label">Financial Year</label>
                        <select name="fy" class="form-select">
                            <?php foreach ($years as $fy => $info): ?>
                                <option value="<?= $fy ?>" <?= $fy === $thisFy ? 'selected' : '' ?>>
                                    FY <?= esc(ReportController::fyLabel((int) $fy)) ?><?= $fy === $thisFy ? ' (current)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label"><?= esc($shriLabel) ?> (₹)</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">&#8377;</span>
                            <input type="number" step="0.01" min="-9999999999.99" max="9999999999.99"
                                   name="amount" class="form-control" placeholder="0.00"
                                   inputmode="decimal" required
                                   value="<?= esc(old('amount'), 'attr') ?>">
                            <div class="invalid-feedback">Enter an amount between −999,99,99,999.99 and 999,99,99,999.99.</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-auto">
                        <?php if (can($moduleCode, 'edit')): ?>
                            <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Existing values -->
                <div class="table-responsive">
                    <table class="table tm-table align-middle mb-0">
                        <thead><tr><th>Financial Year</th><th>Period (1 Apr &ndash; 31 Mar)</th><th>Source</th><th class="text-end"><?= esc($shriLabel) ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($years as $fy => $info): ?>
                            <tr class="<?= $fy === $thisFy ? 'table-primary' : '' ?>">
                                <td class="fw-semibold">FY <?= esc(ReportController::fyLabel((int) $fy)) ?><?= $fy === $thisFy ? ' <span class="badge text-bg-primary">current</span>' : '' ?></td>
                                <td class="text-secondary small">01 Apr <?= $fy ?> &ndash; 31 Mar <?= $fy + 1 ?></td>
                                <td>
                                    <?php if ($info['auto']): ?>
                                        <span class="badge text-bg-light border" title="Rolled over from the previous year's closing"><i class="bi bi-arrow-repeat"></i> Auto-carried</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-success"><i class="bi bi-pencil"></i> Manually set</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold tx-closing">&#8377; <?= $fmt($info['value']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
