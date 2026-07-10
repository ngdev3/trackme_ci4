<?php /** Super Admin — activate a paid plan for a customer. Rendered inside layout.php. */ ?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-gem me-1"></i> Activate a Plan</h3></div>
            <div class="card-body">
                <p class="text-secondary">Pick a customer and a package, then activate. This grants full access and extends the paid period by the package's billing cycle.</p>

                <?php if (empty($plans)): ?>
                    <div class="alert alert-warning mb-0">
                        No paid packages exist yet. Create one on the
                        <a href="<?= site_url('admin/plans') ?>">Plans &amp; Pricing</a> page first.
                    </div>
                <?php elseif (empty($customers)): ?>
                    <div class="alert alert-info mb-0">No customers found yet.</div>
                <?php else: ?>
                    <form action="<?= site_url('admin/activate') ?>" method="post" class="row g-3">
                        <?= csrf_field() ?>
                        <div class="col-12">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">— Choose a customer —</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>" <?= (int) ($preselect ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                                        <?= esc($c['name']) ?> (<?= esc($c['email']) ?>)
                                        — <?= esc(ucfirst((string) $c['sub_status'])) ?><?= ! empty($c['expires_at']) ? ', till ' . esc(date('d M Y', strtotime($c['expires_at']))) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Package</label>
                            <select name="plan_id" class="form-select" required>
                                <?php foreach ($plans as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>">
                                        <?= esc($p['name']) ?> — &#8377;<?= esc(number_format((float) $p['price'], 0)) ?> / <?= esc($p['billing_cycle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i> Activate plan</button>
                            <a href="<?= site_url('admin/customers') ?>" class="btn btn-outline-secondary">All customers</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
