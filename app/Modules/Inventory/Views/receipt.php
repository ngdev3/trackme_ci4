<?php
/** Entry saved — big confirmation with the auto-generated lot & entry numbers. */
$isIn = ($row['movement_type'] ?? '') === 'inward';
?>
<div class="inv-form-wrap">
    <div class="inv-receipt">
        <div class="inv-receipt-tick <?= $isIn ? 'in' : 'out' ?>"><i class="bi bi-check-lg"></i></div>
        <h2><?= $isIn ? 'Inward' : 'Outward' ?> Saved</h2>
        <p class="text-secondary mb-3">Inventory has been updated automatically.</p>

        <div class="inv-receipt-grid">
            <div><small>Entry No</small><strong><?= esc($row['entry_no']) ?></strong></div>
            <?php if (! empty($row['lot_id'])): ?><div><small>Lot No</small><strong><?= esc($row['lot_no'] ?? '') ?: 'LOT' ?></strong></div><?php endif; ?>
            <div><small>Product</small><strong><?= esc($row['product_name']) ?></strong></div>
            <div><small>Bags</small><strong><?= number_format((float) $row['bags'], 0) ?></strong></div>
            <div><small>Weight</small><strong><?= number_format((float) $row['weight'], 2) ?> kg</strong></div>
            <div><small>Godown</small><strong><?= esc($row['warehouse_name']) ?></strong></div>
            <?php if (! empty($row['party_name'])): ?><div><small>Party</small><strong><?= esc($row['party_name']) ?></strong></div><?php endif; ?>
            <div><small>Date &amp; Time</small><strong><?= esc(date('d M Y, H:i', strtotime($row['created_at']))) ?></strong></div>
        </div>

        <?php if (! empty($attachments)): ?>
            <div class="inv-receipt-photos">
                <?php foreach ($attachments as $a): if ($a['kind'] === 'image'): ?>
                    <img src="<?= site_url('inventory/attachment/' . $a['id']) ?>" alt="proof">
                <?php endif; endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-2 mt-4 justify-content-center flex-wrap">
            <a href="<?= site_url('inventory/inward') ?>" class="btn btn-primary btn-lg"><i class="bi bi-plus-circle me-1"></i>New Inward</a>
            <a href="<?= site_url('inventory') ?>" class="btn btn-light border btn-lg"><i class="bi bi-house me-1"></i>Inventory Home</a>
        </div>
    </div>
</div>
