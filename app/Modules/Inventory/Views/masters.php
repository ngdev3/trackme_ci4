<?php /** Inventory setup — products, godowns, parties. Owner/admin only. */ ?>
<div class="inv-masters">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h2 class="mb-0"><i class="bi bi-gear me-2"></i>Inventory Setup</h2>
        <a href="<?= site_url('inventory') ?>" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
    <p class="text-secondary">Set these up once. Workers then just pick from the lists — no typing needed.</p>

    <div class="row g-3">
        <!-- Products -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-box me-1"></i>Products</h3></div>
                <div class="card-body">
                    <form action="<?= site_url('inventory/masters/product') ?>" method="post" class="row g-2 mb-3">
                        <?= csrf_field() ?>
                        <div class="col-12"><input name="name" class="form-control" placeholder="Product name (e.g. Potato)" required></div>
                        <div class="col-6"><input name="avg_weight" type="number" step="0.01" min="0" class="form-control" placeholder="Kg/bag"></div>
                        <div class="col-6"><input name="low_stock" type="number" min="0" class="form-control" placeholder="Low-stock bags"></div>
                        <div class="col-12"><button class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Add Product</button></div>
                    </form>
                    <?php if (empty($products)): ?><p class="text-muted small mb-0">No products yet.</p>
                    <?php else: ?><ul class="inv-master-list">
                        <?php foreach ($products as $p): ?>
                            <li><span><?= esc($p['name']) ?><small><?= $p['avg_weight'] > 0 ? esc($p['avg_weight']) . ' kg/bag' : '' ?></small></span>
                                <?php if (! empty($canDelete)): ?><?= view('Modules\Inventory\Views\_del', ['type' => 'product', 'id' => $p['id']]) ?><?php endif; ?></li>
                        <?php endforeach; ?></ul><?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Godowns -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-buildings me-1"></i>Godowns / Warehouses</h3></div>
                <div class="card-body">
                    <form action="<?= site_url('inventory/masters/warehouse') ?>" method="post" class="row g-2 mb-3">
                        <?= csrf_field() ?>
                        <div class="col-12"><input name="name" class="form-control" placeholder="Godown name (e.g. Main Godown)" required></div>
                        <div class="col-7"><input name="location" class="form-control" placeholder="Location"></div>
                        <div class="col-5"><input name="capacity" type="number" min="0" class="form-control" placeholder="Capacity"></div>
                        <div class="col-12"><button class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Add Godown</button></div>
                    </form>
                    <?php if (empty($warehouses)): ?><p class="text-muted small mb-0">No godowns yet.</p>
                    <?php else: ?><ul class="inv-master-list">
                        <?php foreach ($warehouses as $w): ?>
                            <li><span><?= esc($w['name']) ?><small><?= esc($w['location'] ?? '') ?></small></span>
                                <?php if (! empty($canDelete)): ?><?= view('Modules\Inventory\Views\_del', ['type' => 'warehouse', 'id' => $w['id']]) ?><?php endif; ?></li>
                        <?php endforeach; ?></ul><?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Parties -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-people me-1"></i>Parties</h3></div>
                <div class="card-body">
                    <form action="<?= site_url('inventory/masters/party') ?>" method="post" class="row g-2 mb-3">
                        <?= csrf_field() ?>
                        <div class="col-12"><input name="name" class="form-control" placeholder="Party name (e.g. Sharma Traders)" required></div>
                        <div class="col-6"><select name="type" class="form-select"><option value="both">Supplier &amp; Customer</option><option value="supplier">Supplier / Farmer</option><option value="customer">Customer</option></select></div>
                        <div class="col-6"><input name="phone" class="form-control" placeholder="Phone"></div>
                        <div class="col-12"><button class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Add Party</button></div>
                    </form>
                    <?php if (empty($parties)): ?><p class="text-muted small mb-0">No parties yet.</p>
                    <?php else: ?><ul class="inv-master-list">
                        <?php foreach ($parties as $pt): ?>
                            <li><span><?= esc($pt['name']) ?><small><?= esc(ucfirst($pt['type'])) ?></small></span>
                                <?php if (! empty($canDelete)): ?><?= view('Modules\Inventory\Views\_del', ['type' => 'party', 'id' => $pt['id']]) ?><?php endif; ?></li>
                        <?php endforeach; ?></ul><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
