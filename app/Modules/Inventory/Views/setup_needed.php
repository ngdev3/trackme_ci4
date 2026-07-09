<?php /** Shown when no products/warehouses exist yet — inward needs at least one of each. */ ?>
<div class="inv-form-wrap">
    <div class="inv-form-card text-center p-4">
        <i class="bi bi-box-seam" style="font-size:2.5rem;color:var(--bs-primary)"></i>
        <h2 class="mt-2">Quick setup needed</h2>
        <p class="text-secondary">Add at least one <?= $needProduct ? 'product' : '' ?><?= ($needProduct && $needWarehouse) ? ' and one ' : '' ?><?= $needWarehouse ? 'godown' : '' ?> before recording stock inward.</p>
        <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
            <a href="<?= site_url('inventory/masters') ?>" class="btn btn-primary btn-lg"><i class="bi bi-gear me-1"></i>Set up Products &amp; Godowns</a>
            <a href="<?= site_url('inventory') ?>" class="btn btn-light border btn-lg">Back</a>
        </div>
        <p class="text-muted small mt-3">Owners/admins set these up once. Workers then only pick from the list.</p>
    </div>
</div>
