<?php
/** Sales & Purchase — bill list. In layout.php. */
$rows = $rows ?? [];
$money = static fn ($v): string => '₹' . number_format((float) $v, 2);
?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-receipt me-1"></i> Sales &amp; Purchase <span class="erp-pill gray ms-1"><?= count($rows) ?></span></h3>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-success" href="<?= site_url('invoices/new/sale') ?>"><i class="bi bi-cart-plus me-1"></i>New Sale</a>
            <a class="btn btn-sm btn-primary" href="<?= site_url('invoices/new/purchase') ?>"><i class="bi bi-bag-plus me-1"></i>New Purchase</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($rows)): ?>
            <div class="erp-empty py-5 text-center">
                <i class="bi bi-receipt" style="font-size:38px;opacity:.5"></i>
                <div class="mt-2">No bills yet. Create a sale or purchase — it adjusts stock and posts the matching cash-book entry automatically.</div>
                <a class="btn btn-success btn-sm mt-3" href="<?= site_url('invoices/new/sale') ?>"><i class="bi bi-cart-plus me-1"></i>New Sale</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle erp-table mb-0">
                    <thead>
                        <tr>
                            <th>Bill No.</th><th>Date</th><th>Type</th><th>Party</th>
                            <th class="text-end">Total</th><th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): $sale = $r['type'] === 'sale'; ?>
                        <tr>
                            <td class="fw-bold"><?= esc($r['invoice_no']) ?></td>
                            <td><?= esc($r['invoice_date']) ?></td>
                            <td>
                                <span class="badge <?= $sale ? 'bg-success' : 'bg-primary' ?>">
                                    <?= $sale ? 'Sale' : 'Purchase' ?>
                                </span>
                            </td>
                            <td><?= esc($r['party_name'] ?: ($sale ? 'Cash Sale' : 'Cash Purchase')) ?></td>
                            <td class="text-end fw-bold"><?= $money($r['total']) ?></td>
                            <td class="text-center">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('invoices/view/' . (int) $r['id']) ?>">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
