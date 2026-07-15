<?php /** Super Admin — subscription plans + pricing. Rendered inside layout.php. */ ?>
<div class="row g-3">
    <!-- Free-trial length -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-hourglass-split me-1"></i> Free Trial</h3></div>
            <div class="card-body">
                <form action="<?= site_url('admin/plans/trial') ?>" method="post" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label class="form-label">Trial length (days)</label>
                        <input type="number" name="trial_days" class="form-control" min="0" max="3650" value="<?= (int) ($trialDays ?? 30) ?>">
                        <div class="form-text">New customers get full access for this many days. 0 = no trial.</div>
                    </div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save trial</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- UPI payment details (shown as a QR to customers on /subscription) -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-qr-code me-1"></i> Payment (UPI)</h3></div>
            <div class="card-body">
                <form action="<?= site_url('admin/plans/payment') ?>" method="post" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label class="form-label">UPI ID</label>
                        <input type="text" name="upi_id" class="form-control" maxlength="120" value="<?= esc($upiId ?? '', 'attr') ?>" placeholder="name@bank">
                        <div class="form-text">Customers scan a QR that pays this UPI ID.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Payee name</label>
                        <input type="text" name="upi_name" class="form-control" maxlength="120" value="<?= esc($upiName ?? '', 'attr') ?>" placeholder="Your business name">
                    </div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save payment</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Invoice / GST seller details (printed on tax receipts) -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-receipt-cutoff me-1"></i> Tax Invoice / GST details</h3></div>
            <div class="card-body">
                <p class="text-muted small mb-3">These appear on the tax receipts customers download. Set your GSTIN to issue proper <strong>Tax Invoices</strong> (otherwise a plain payment receipt is issued). GST is treated as inclusive of the plan price.</p>
                <form action="<?= site_url('admin/plans/invoice') ?>" method="post" class="row g-2">
                    <?= csrf_field() ?>
                    <div class="col-md-4">
                        <label class="form-label">Seller / business name</label>
                        <input type="text" name="invoice_seller_name" class="form-control" maxlength="150" value="<?= esc($invoice['name'] ?? '', 'attr') ?>" placeholder="Your company name">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">GSTIN</label>
                        <input type="text" name="invoice_seller_gstin" class="form-control text-uppercase" maxlength="15" value="<?= esc($invoice['gstin'] ?? '', 'attr') ?>" placeholder="27ABCDE1234F1Z5">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">State (place of supply)</label>
                        <input type="text" name="invoice_seller_state" class="form-control" maxlength="100" value="<?= esc($invoice['state'] ?? '', 'attr') ?>" placeholder="Maharashtra">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">GST rate %</label>
                        <input type="number" step="0.01" min="0" max="100" name="invoice_tax_rate" class="form-control" value="<?= esc($invoice['rate'] ?? '18', 'attr') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input type="text" name="invoice_seller_address" class="form-control" maxlength="255" value="<?= esc($invoice['address'] ?? '', 'attr') ?>" placeholder="Street, City, PIN">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Billing email</label>
                        <input type="email" name="invoice_seller_email" class="form-control" maxlength="150" value="<?= esc($invoice['email'] ?? '', 'attr') ?>" placeholder="billing@company.com">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Invoice prefix</label>
                        <input type="text" name="invoice_prefix" class="form-control" maxlength="10" value="<?= esc($invoice['prefix'] ?? 'INV', 'attr') ?>">
                    </div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save invoice details</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create / edit a plan -->
    <div class="col-12">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-plus-square me-1"></i> <span data-plan-form-title>Add Package</span></h3></div>
            <div class="card-body">
                <form action="<?= site_url('admin/plans/save') ?>" method="post" class="row g-2" id="planForm">
                    <?= csrf_field() ?>
                    <div class="col-md-5">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" maxlength="60" required placeholder="e.g. Yearly">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Price (₹)</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required placeholder="299">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Billing cycle</label>
                        <select name="billing_cycle" class="form-select">
                            <option value="yearly">Yearly</option>
                            <option value="monthly">Monthly</option>
                            <option value="lifetime">Lifetime</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Max firms</label>
                        <input type="number" name="max_firms" class="form-control" min="0" placeholder="Unlimited">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Max users</label>
                        <input type="number" name="max_users" class="form-control" min="0" placeholder="Unlimited">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Features (short description)</label>
                        <input type="text" name="features" class="form-control" maxlength="255" placeholder="Optional marketing note">
                    </div>
                    <?php
                    // The seven gated features. Rokadh Parcha, PDF/print, reports,
                    // attachments, statement download and opening balance are baseline
                    // (in every package) and are not toggleable here.
                    $planFeatureLabels = [
                        'calculator'       => 'Calculator',
                        'password_manager' => 'Password Manager',
                        'calendar'         => 'Calendar',
                        'reminder'         => 'Reminder',
                        'trash'            => 'Trash',
                        'notes'            => 'Notes',
                        'inventory'        => 'Inventory',
                    ];
                    ?>
                    <div class="col-12">
                        <label class="form-label mb-1">Package features</label>
                        <div class="form-text mt-0 mb-2">Tick the modules this package unlocks. Rokadh Parcha, PDF/print, reports, attachments, statement download and opening balance are included in every package.</div>
                        <div class="row row-cols-2 row-cols-md-4 g-2">
                            <?php foreach ($planFeatureLabels as $key => $label): ?>
                                <div class="col">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="feat[<?= esc($key, 'attr') ?>]" id="feat_<?= esc($key, 'attr') ?>" value="1" data-feat="<?= esc($key, 'attr') ?>">
                                        <label class="form-check-label" for="feat_<?= esc($key, 'attr') ?>"><?= esc($label) ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="status" id="planStatus" value="1" checked>
                            <label class="form-check-label" for="planStatus">Active (visible to customers)</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save package</button>
                        <button type="button" class="btn btn-outline-secondary d-none" data-plan-cancel>Cancel edit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Existing plans -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-card-checklist me-1"></i> Packages &amp; Prices</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr>
                            <th>Plan</th><th>Code</th><th>Price</th><th>Cycle</th><th>Max Firms</th><th>Max Users</th><th>Extra features</th><th>Status</th><th class="text-end">Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="9" class="text-center text-secondary py-4">No plans defined.</td></tr>
                        <?php else: foreach ($rows as $p): ?>
                            <?php
                            $planFlags = [];
                            foreach ($planFeatureLabels as $fk => $fl) {
                                $planFlags[$fk] = (int) ($p['feat_' . $fk] ?? 0);
                            }
                            $enabled = array_keys(array_filter($planFlags));
                            ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($p['name']) ?></td>
                                <td><code><?= esc($p['code']) ?></code></td>
                                <td>&#8377;<?= esc(number_format((float) $p['price'], 2)) ?></td>
                                <td class="text-capitalize"><?= esc($p['billing_cycle']) ?></td>
                                <td><?= $p['max_firms'] === null ? 'Unlimited' : (int) $p['max_firms'] ?></td>
                                <td><?= $p['max_users'] === null ? 'Unlimited' : (int) $p['max_users'] ?></td>
                                <td>
                                    <?php if ($enabled === []): ?>
                                        <span class="text-secondary small">Baseline only</span>
                                    <?php else: foreach ($enabled as $fk): ?>
                                        <span class="badge text-bg-light border me-1 mb-1"><?= esc($planFeatureLabels[$fk]) ?></span>
                                    <?php endforeach; endif; ?>
                                </td>
                                <td><?= (int) $p['status'] === 1 ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Off</span>' ?></td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-plan-edit
                                            data-plan='<?= esc(json_encode([
                                                'id' => $p['id'], 'name' => $p['name'], 'price' => $p['price'],
                                                'billing_cycle' => $p['billing_cycle'], 'max_firms' => $p['max_firms'],
                                                'max_users' => $p['max_users'], 'features' => $p['features'], 'status' => $p['status'],
                                                'feat' => $planFlags,
                                            ]), 'attr') ?>'><i class="bi bi-pencil"></i></button>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/plans/toggle/' . $p['id']) ?>" title="Toggle active"><i class="bi bi-toggle-<?= (int) $p['status'] === 1 ? 'on' : 'off' ?>"></i></a>
                                    <form action="<?= site_url('admin/plans/delete/' . $p['id']) ?>" method="post" class="d-inline" data-no-validate data-confirm="Existing customer subscriptions are not affected." data-confirm-title="Delete plan?" data-confirm-btn="Yes, delete">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('planForm');
    if (!form) { return; }
    var title  = document.querySelector('[data-plan-form-title]');
    var cancel = document.querySelector('[data-plan-cancel]');
    var baseAction = form.getAttribute('action');

    document.querySelectorAll('[data-plan-edit]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var p = JSON.parse(btn.getAttribute('data-plan'));
            form.action = baseAction + '/' + p.id;
            form.name.value = p.name || '';
            form.price.value = p.price || 0;
            form.billing_cycle.value = p.billing_cycle || 'yearly';
            form.max_firms.value = p.max_firms == null ? '' : p.max_firms;
            form.max_users.value = p.max_users == null ? '' : p.max_users;
            form.features.value = p.features || '';
            form.status.checked = String(p.status) === '1';
            var flags = p.feat || {};
            form.querySelectorAll('[data-feat]').forEach(function (cb) {
                cb.checked = String(flags[cb.getAttribute('data-feat')]) === '1';
            });
            title.textContent = 'Edit: ' + p.name;
            cancel.classList.remove('d-none');
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            form.name.focus();
        });
    });
    if (cancel) {
        cancel.addEventListener('click', function () {
            form.reset(); form.action = baseAction;
            title.textContent = 'Add Package'; cancel.classList.add('d-none');
        });
    }
})();
</script>
