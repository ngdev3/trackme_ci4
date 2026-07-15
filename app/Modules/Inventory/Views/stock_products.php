<?php
/** Products master — add in seconds. Name only is enough; unit & rate optional. */
$old = $old ?? [];
$err = static fn ($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . esc($errors[$k]) . '</div>' : '';
?>
<div class="sinv">
    <?= view('Modules\Inventory\Views\_nav', ['active' => 'products']) ?>

    <div class="sinv-grid">
        <!-- Add product -->
        <div class="sinv-card">
            <h3 class="sinv-h"><i class="bi bi-plus-circle me-1"></i>Add a Product</h3>
            <form action="<?= site_url('inventory/products') ?>" method="post" class="sinv-form" autocomplete="off">
                <?= csrf_field() ?>
                <div class="sinv-field">
                    <label>Product name <span class="req">*</span></label>
                    <input name="name" class="form-control form-control-lg<?= isset($errors['name']) ? ' is-invalid' : '' ?>" placeholder="e.g. Potato" maxlength="150" required value="<?= esc($old['name'] ?? '', 'attr') ?>" autofocus>
                    <?= $err('name') ?>
                </div>
                <div class="sinv-row">
                    <div class="sinv-field">
                        <label>Unit <span class="opt">optional</span></label>
                        <input name="unit" class="form-control" placeholder="quintal" value="<?= esc($old['unit'] ?? 'quintal', 'attr') ?>" list="sinvUnits">
                        <datalist id="sinvUnits"><option value="quintal"><option value="bag"><option value="kg"><option value="box"><option value="piece"></datalist>
                    </div>
                    <div class="sinv-field">
                        <label>Rate (₹ per Qtl) <span class="opt">optional</span></label>
                        <input name="rate" type="number" min="0" step="0.01" class="form-control<?= isset($errors['rate']) ? ' is-invalid' : '' ?>" placeholder="0" value="<?= esc($old['rate'] ?? '', 'attr') ?>">
                        <?= $err('rate') ?>
                    </div>
                </div>
                <button type="submit" class="sinv-save in"><i class="bi bi-check2-circle me-1"></i>Add Product</button>
            </form>
        </div>

        <!-- List -->
        <div class="sinv-card">
            <h3 class="sinv-h"><i class="bi bi-box-seam me-1"></i>Your Products <span class="sinv-count"><?= count($products) ?></span></h3>
            <?php if (empty($products)): ?>
                <div class="inv-empty-mini"><i class="bi bi-inbox"></i>No products yet. Add your first on the left.</div>
            <?php else: ?>
                <ul class="sinv-plist">
                    <?php foreach ($products as $p): ?>
                        <li>
                            <span class="nm"><?= esc($p['name']) ?><small><?= esc($p['unit'] ?: 'quintal') ?><?= ! empty($p['rate']) && $p['rate'] > 0 ? ' · ₹' . esc(number_format((float) $p['rate'], 0)) : '' ?></small></span>
                            <?php if (! empty($canDelete)): ?>
                                <form action="<?= site_url('inventory/products/delete/' . $p['id']) ?>" method="post" data-no-validate data-confirm="&ldquo;<?= esc($p['name'], 'attr') ?>&rdquo; will be removed." data-confirm-title="Remove product?" data-confirm-btn="Yes, remove">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="sinv-del" title="Remove"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
