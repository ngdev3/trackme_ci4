<?php /** Super Admin — all customers. Rendered inside layout.php. */ ?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-people me-1"></i> Customers</h3>
        <form class="d-flex gap-2" method="get">
            <input type="search" name="q" value="<?= esc($search) ?>" class="form-control form-control-sm" placeholder="Search name or email...">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>
                    <th>#</th><th>Name</th><th>Email</th><th>Firms</th><th>Subscription</th><th>Payment</th><th>Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-secondary py-4">No customers found.</td></tr>
                <?php else: foreach ($rows as $r): $sub = $r['subscription'] ?? null; ?>
                    <tr>
                        <td><?= esc($r['id']) ?></td>
                        <td class="fw-semibold"><?= esc($r['name']) ?></td>
                        <td><?= esc($r['email']) ?></td>
                        <td><span class="badge text-bg-light border"><?= (int) $r['firm_count'] ?></span></td>
                        <td>
                            <a href="<?= site_url('admin/customers/subscription/' . $r['id']) ?>" class="text-decoration-none" title="Manage subscription">
                                <small><?= esc($sub['plan_name'] ?? '—') ?> <span class="text-muted"><?= esc($sub['status'] ?? '') ?></span></small>
                            </a>
                        </td>
                        <td>
                            <form action="<?= site_url('admin/customers/payment/' . $r['id']) ?>" method="post" class="d-flex gap-1">
                                <?= csrf_field() ?>
                                <select name="payment_status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                                    <?php foreach (['trial', 'paid', 'unpaid'] as $ps): ?>
                                        <option value="<?= $ps ?>" <?= ($sub['payment_status'] ?? 'trial') === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <a href="<?= site_url('admin/customers/toggle/' . $r['id']) ?>">
                                <?= (int) $r['status'] === 1 ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?>
                            </a>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="<?= site_url('admin/customers/subscription/' . $r['id']) ?>" class="btn btn-sm btn-outline-info" title="Manage subscription"><i class="bi bi-gem"></i></a>
                            <a href="<?= site_url('admin/impersonate/' . $r['id']) ?>" class="btn btn-sm btn-outline-primary" title="Access this account"
                               data-confirm="You can return to Super Admin anytime." data-confirm-title="Sign in as <?= esc($r['name'], 'attr') ?>?" data-confirm-btn="Sign in" data-confirm-icon="info">
                                <i class="bi bi-box-arrow-in-right"></i>
                            </a>
                            <form action="<?= site_url('admin/customers/reset/' . $r['id']) ?>" method="post" class="d-inline" data-no-validate data-confirm="This customer will be forced to reset their password on next login." data-confirm-title="Reset access?" data-confirm-btn="Yes, reset" data-confirm-icon="warning">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-warning" title="Reset access"><i class="bi bi-key"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (isset($pager)): ?><div class="card-footer d-flex justify-content-end"><?= $pager->links() ?></div><?php endif; ?>
</div>
