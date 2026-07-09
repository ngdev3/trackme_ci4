<?php
/**
 * Entry detail — the single record of one stock movement and all of its proof
 * files. Every attachment is auto-linked to this entry, its product, lot, party,
 * the user who added it and the date/time. Workers can add proof; only
 * owner/admin (delete permission) can remove it.
 */
$type = $row['movement_type'] ?? 'inward';
$tone = $type === 'outward' ? 'out' : ($type === 'adjustment' ? 'chk' : 'in');
$title = ['inward' => 'Stock Inward', 'outward' => 'Stock Outward', 'adjustment' => 'Stock Adjustment'][$type] ?? 'Entry';
$srcLabel = ['web' => 'Web', 'mobile' => 'Mobile App', 'voice' => 'Voice'][$row['source'] ?? 'web'] ?? ucfirst((string) ($row['source'] ?? ''));
?>
<div class="inv-form-wrap wide">
    <div class="inv-form-card">
        <div class="inv-form-head <?= $tone ?>">
            <a href="<?= site_url('inventory') ?>" class="inv-back"><i class="bi bi-arrow-left"></i></a>
            <div><h2><i class="bi bi-receipt me-2"></i><?= esc($row['entry_no']) ?></h2><p><?= esc($title) ?> · auto-linked proof &amp; full audit trail</p></div>
        </div>

        <div class="inv-form" style="gap:1.25rem;">
            <!-- Entry facts (the auto-link keys) -->
            <div class="inv-receipt-grid">
                <div><small>Entry No</small><strong><?= esc($row['entry_no']) ?></strong></div>
                <?php if (! empty($row['lot_no'])): ?><div><small>Lot No</small><strong><?= esc($row['lot_no']) ?></strong></div><?php endif; ?>
                <div><small>Product</small><strong><?= esc($row['product_name']) ?></strong></div>
                <div><small>Bags</small><strong><?= number_format((float) $row['bags'], 0) ?></strong></div>
                <div><small>Weight</small><strong><?= number_format((float) $row['weight'], 2) ?> kg</strong></div>
                <div><small>Godown</small><strong><?= esc($row['warehouse_name']) ?></strong></div>
                <?php if (! empty($row['party_name'])): ?><div><small>Party</small><strong><?= esc($row['party_name']) ?></strong></div><?php endif; ?>
                <?php if (! empty($row['rack'])): ?><div><small>Rack</small><strong><?= esc($row['rack']) ?></strong></div><?php endif; ?>
                <?php if (! empty($row['vehicle_no'])): ?><div><small>Vehicle</small><strong><?= esc($row['vehicle_no']) ?></strong></div><?php endif; ?>
                <div><small>Entered By</small><strong><?= esc($creatorName ?: '—') ?></strong></div>
                <div><small>Source</small><strong><?= esc($srcLabel) ?></strong></div>
                <div><small>Date &amp; Time</small><strong><?= esc(date('d M Y, H:i', strtotime($row['created_at']))) ?></strong></div>
            </div>

            <?php if (! empty($row['notes'])): ?>
                <div class="inv-att-notes"><i class="bi bi-sticky me-1"></i><?= esc($row['notes']) ?></div>
            <?php endif; ?>

            <!-- Proof files -->
            <div>
                <div class="inv-panel-head px-0"><h3 class="mb-2"><i class="bi bi-paperclip me-1"></i>Proof &amp; Documents (<?= count($attachments) ?>)</h3></div>
                <?= view('Modules\Inventory\Views\_attachments', ['attachments' => $attachments, 'canDelete' => $canDelete]) ?>
            </div>

            <!-- Add more proof -->
            <?php if (! empty($canAdd)): ?>
                <form action="<?= site_url('inventory/entry/' . $row['id'] . '/attach') ?>" method="post" enctype="multipart/form-data" class="inv-att-add">
                    <?= csrf_field() ?>
                    <label class="inv-photo">
                        <input type="file" name="attachments[]" accept="image/*,application/pdf,video/*,audio/*" capture="environment" multiple hidden id="entryAtt">
                        <i class="bi bi-plus-circle"></i><span id="entryAttLabel">Add photos, bills, challans, videos or voice notes</span>
                    </label>
                    <div class="inv-att-add-actions">
                        <span class="text-secondary small">Images, videos, voice notes &amp; PDFs up to <?= (int) $maxMb ?> MB each.</span>
                        <button type="submit" class="btn inv-btn-in"><i class="bi bi-upload me-1"></i>Upload</button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= site_url('inventory') ?>" class="btn btn-light border"><i class="bi bi-house me-1"></i>Inventory Home</a>
                <a href="<?= site_url('inventory/search?all=1') ?>" class="btn btn-light border"><i class="bi bi-search me-1"></i>Stock</a>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var inp = document.getElementById('entryAtt');
    if (inp) inp.addEventListener('change', function () {
        var n = inp.files.length;
        document.getElementById('entryAttLabel').textContent = n
            ? (n + ' file' + (n > 1 ? 's' : '') + ' selected — tap Upload')
            : 'Add photos, bills, challans, videos or voice notes';
    });
})();
</script>
