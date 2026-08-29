<?php
/** Super Admin — subscription coupons (discount + redeem codes). In layout.php. */
$rows  = $rows ?? [];
$plans = $plans ?? [];
$money = static fn ($n) => '₹' . number_format((float) $n, 2);
?>
<div class="cust-page">
<section class="cust-hero">
    <div>
        <h4 class="cust-title">Coupons</h4>
        <p class="cust-subtitle">Discount and redeem codes customers apply at checkout — create, edit and track usage.</p>
    </div>
    <div class="cust-hero-actions">
        <a href="<?= site_url('admin/coupons/log') ?>" class="cust-btn cust-btn-ghost"><i class="bi bi-clock-history"></i> Usage log</a>
    </div>
</section>
<div class="row g-3">
    <!-- Create / edit coupon -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="bi bi-ticket-perforated me-1"></i> <span data-form-title>New coupon</span></h3>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-coupon-reset hidden><i class="bi bi-x-lg"></i> Cancel edit</button>
            </div>
            <div class="card-body">
                <form action="<?= site_url('admin/coupons/save') ?>" method="post" class="row g-2" data-coupon-form>
                    <?= csrf_field() ?>

                    <div class="col-7">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control text-uppercase" maxlength="40" required placeholder="WELCOME30" data-f="code">
                    </div>
                    <div class="col-5">
                        <label class="form-label">Type</label>
                        <select name="kind" class="form-select" data-f="kind" data-coupon-kind>
                            <option value="discount">Discount</option>
                            <option value="redeem">Redeem (free time)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description <span class="text-muted">(optional)</span></label>
                        <input type="text" name="description" class="form-control" maxlength="191" placeholder="Diwali offer" data-f="description">
                    </div>

                    <!-- Discount-only fields -->
                    <div class="col-12" data-kind-discount>
                        <div class="row g-2">
                            <div class="col-5">
                                <label class="form-label">Discount</label>
                                <select name="discount_type" class="form-select" data-f="discount_type">
                                    <option value="percent">Percent %</option>
                                    <option value="fixed">Fixed ₹</option>
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label">Value</label>
                                <input type="number" step="0.01" min="0" name="discount_value" class="form-control" placeholder="30" data-f="discount_value">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Max ₹ off</label>
                                <input type="number" step="0.01" min="0" name="max_discount" class="form-control" placeholder="none" data-f="max_discount">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Minimum order ₹</label>
                                <input type="number" step="0.01" min="0" name="min_amount" class="form-control" value="0" data-f="min_amount">
                            </div>
                        </div>
                    </div>

                    <!-- Redeem-only fields -->
                    <div class="col-12" data-kind-redeem hidden>
                        <label class="form-label">Free period (days)</label>
                        <input type="number" min="1" name="free_days" class="form-control" placeholder="30" data-f="free_days">
                        <div class="form-text">Grants this many days of the plan below, stacked on any remaining time.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Plan <span class="text-muted" data-plan-hint>(discount: restrict to one plan — optional)</span></label>
                        <select name="plan_id" class="form-select" data-f="plan_id">
                            <option value="">Any / none</option>
                            <?php foreach ($plans as $pl): ?>
                                <option value="<?= (int) $pl['id'] ?>"><?= esc($pl['name']) ?> (<?= $money($pl['price']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6">
                        <label class="form-label">Total uses <span class="text-muted">(blank = ∞)</span></label>
                        <input type="number" min="0" name="max_redemptions" class="form-control" placeholder="∞" data-f="max_redemptions">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Per customer</label>
                        <input type="number" min="0" name="per_user_limit" class="form-control" value="1" data-f="per_user_limit">
                    </div>

                    <div class="col-6">
                        <label class="form-label">Starts <span class="text-muted">(optional)</span></label>
                        <input type="datetime-local" name="starts_at" class="form-control" data-f="starts_at">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Expires <span class="text-muted">(optional)</span></label>
                        <input type="datetime-local" name="expires_at" class="form-control" data-f="expires_at">
                    </div>

                    <div class="col-12 form-check ms-1">
                        <input type="checkbox" class="form-check-input" name="status" value="1" id="cpn-status" checked data-f="status">
                        <label class="form-check-label" for="cpn-status">Active</label>
                    </div>

                    <div class="col-12"><button class="btn btn-primary w-100"><i class="bi bi-save me-1"></i> Save coupon</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Existing coupons -->
    <div class="col-lg-7">
        <div class="cust-panel cust-table-panel h-100">
            <div class="cust-toolbar">
                <div>
                    <h5 class="cust-table-title">Coupons</h5>
                    <p class="cust-table-note">Edit, pause/resume, or delete a code; click a usage count to see who redeemed it.</p>
                </div>
                <span class="cust-total-tag"><i class="bi bi-ticket-perforated"></i> <?= number_format(count($rows)) ?> total</span>
            </div>
                <div class="erp-tbl-wrap">
                    <table class="erp-tbl auto">
                        <thead>
                            <tr>
                                <th class="text-start">Code</th><th class="text-start">Type</th><th class="text-start">Value</th><th class="text-start">Plan</th><th class="text-start">Used</th><th class="text-start">Window</th><th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="7" class="erp-empty"><i class="bi bi-ticket-perforated"></i><div>No coupons yet. Create one on the left.</div></td></tr>
                        <?php else: foreach ($rows as $c):
                            $isRedeem = ($c['kind'] === 'redeem');
                            $value = $isRedeem
                                ? ((int) $c['free_days'] . ' days')
                                : (($c['discount_type'] === 'fixed') ? $money($c['discount_value']) : ((float) $c['discount_value'] . '%'));
                            $window = trim(
                                (!empty($c['starts_at']) ? date('d M y', strtotime($c['starts_at'])) : '—')
                                . ' → ' .
                                (!empty($c['expires_at']) ? date('d M y', strtotime($c['expires_at'])) : '∞')
                            );
                            $cap = $c['max_redemptions'] !== null ? (int) $c['max_redemptions'] : '∞';
                            $json = esc(json_encode([
                                'id' => (int) $c['id'], 'code' => $c['code'], 'kind' => $c['kind'],
                                'description' => $c['description'], 'discount_type' => $c['discount_type'],
                                'discount_value' => $c['discount_value'], 'max_discount' => $c['max_discount'],
                                'free_days' => $c['free_days'], 'plan_id' => $c['plan_id'], 'min_amount' => $c['min_amount'],
                                'max_redemptions' => $c['max_redemptions'], 'per_user_limit' => $c['per_user_limit'],
                                'starts_at' => $c['starts_at'], 'expires_at' => $c['expires_at'], 'status' => (int) $c['status'],
                            ]), 'attr');
                        ?>
                            <tr class="<?= (int) $c['status'] === 1 ? '' : 'opacity-50' ?>">
                                <td class="text-start"><?= erp_cell_name((string) $c['code'], [
                                    'type' => 'Coupon', 'icon' => 'ticket-perforated',
                                    'accent' => $isRedeem ? 'green' : 'blue',
                                    'chips' => [
                                        ['t' => $isRedeem ? 'Redeem' : 'Discount', 'ic' => $isRedeem ? 'gift' : 'percent', 'ok' => true],
                                        (int) $c['status'] === 1 ? ['t' => 'Active', 'ic' => 'check-circle-fill'] : ['t' => 'Paused', 'ic' => 'pause-circle-fill'],
                                    ],
                                    'rows' => array_values(array_filter([
                                        ['ic' => 'cash-coin', 'l' => 'Value', 'v' => (string) $value],
                                        ['ic' => 'box', 'l' => 'Plan', 'v' => (string) ($c['plan_name'] ?? ($c['plan_id'] ? '#' . $c['plan_id'] : 'Any'))],
                                        ['ic' => 'people', 'l' => 'Used', 'v' => (int) $c['redeemed_count'] . ' / ' . $cap],
                                        ['ic' => 'calendar-range', 'l' => 'Window', 'v' => (string) $window],
                                        ! empty($c['description']) ? ['ic' => 'card-text', 'l' => 'Description', 'v' => (string) $c['description']] : null,
                                    ])),
                                    'foot' => 'Coupon #' . (int) $c['id'],
                                ], ['green' => (int) $c['status'] === 1]) ?></td>
                                <td class="text-start"><span class="erp-pill <?= $isRedeem ? 'green' : '' ?>"><?= $isRedeem ? 'Redeem' : 'Discount' ?></span></td>
                                <td class="text-start fw-semibold"><?= esc($value) ?></td>
                                <td class="text-start"><span class="erp-muted"><?= esc($c['plan_name'] ?? ($c['plan_id'] ? '#' . $c['plan_id'] : 'Any')) ?></span></td>
                                <td class="text-start">
                                    <a href="<?= site_url('admin/coupons/log?coupon_id=' . (int) $c['id']) ?>" class="text-decoration-none fw-semibold" title="View who used this">
                                        <?= (int) $c['redeemed_count'] ?> / <?= esc((string) $cap) ?>
                                    </a>
                                </td>
                                <td class="text-start"><span class="erp-muted"><?= esc($window) ?></span></td>
                                <td class="text-end">
                                    <div class="erp-actions">
                                        <button type="button" class="erp-act slate" title="Edit"
                                                data-coupon-edit data-coupon='<?= $json ?>'><i class="bi bi-pencil"></i></button>
                                        <a href="<?= site_url('admin/coupons/toggle/' . (int) $c['id']) ?>" class="erp-act <?= (int) $c['status'] === 1 ? 'amber' : 'green' ?>" title="Toggle">
                                            <i class="bi bi-<?= (int) $c['status'] === 1 ? 'pause' : 'play' ?>"></i>
                                        </a>
                                        <form action="<?= site_url('admin/coupons/delete/' . (int) $c['id']) ?>" method="post" class="d-inline" data-no-validate data-confirm="Delete coupon <?= esc($c['code'], 'attr') ?>?" data-confirm-title="Delete coupon?" data-confirm-btn="Yes, delete">
                                            <?= csrf_field() ?>
                                            <button class="erp-act red" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
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
    var form = document.querySelector('[data-coupon-form]');
    if (!form) { return; }
    var kindSel = form.querySelector('[data-coupon-kind]');
    var discBox = form.querySelector('[data-kind-discount]');
    var redBox = form.querySelector('[data-kind-redeem]');
    var title = document.querySelector('[data-form-title]');
    var resetBtn = document.querySelector('[data-coupon-reset]');
    var planHint = form.querySelector('[data-plan-hint]');

    function syncKind() {
        var redeem = kindSel.value === 'redeem';
        redBox.hidden = !redeem;
        discBox.hidden = redeem;
        if (planHint) { planHint.textContent = redeem ? '(redeem: the plan the code grants — required)' : '(discount: restrict to one plan — optional)'; }
    }
    kindSel.addEventListener('change', syncKind);
    syncKind();

    function resetForm() {
        form.reset();
        form.setAttribute('action', '<?= site_url('admin/coupons/save') ?>');
        if (title) { title.textContent = 'New coupon'; }
        if (resetBtn) { resetBtn.hidden = true; }
        syncKind();
    }
    if (resetBtn) { resetBtn.addEventListener('click', resetForm); }

    function fld(name) { return form.querySelector('[data-f="' + name + '"]'); }
    function dtLocal(v) { return v ? String(v).replace(' ', 'T').slice(0, 16) : ''; }

    document.querySelectorAll('[data-coupon-edit]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var c;
            try { c = JSON.parse(btn.getAttribute('data-coupon')); } catch (e) { return; }
            form.setAttribute('action', '<?= site_url('admin/coupons/save') ?>/' + c.id);
            if (title) { title.textContent = 'Edit ' + c.code; }
            if (resetBtn) { resetBtn.hidden = false; }
            fld('code').value = c.code || '';
            fld('kind').value = c.kind || 'discount';
            fld('description').value = c.description || '';
            fld('discount_type').value = c.discount_type || 'percent';
            fld('discount_value').value = c.discount_value != null ? c.discount_value : '';
            fld('max_discount').value = c.max_discount != null ? c.max_discount : '';
            fld('free_days').value = c.free_days != null ? c.free_days : '';
            fld('plan_id').value = c.plan_id != null ? c.plan_id : '';
            fld('min_amount').value = c.min_amount != null ? c.min_amount : 0;
            fld('max_redemptions').value = c.max_redemptions != null ? c.max_redemptions : '';
            fld('per_user_limit').value = c.per_user_limit != null ? c.per_user_limit : 1;
            fld('starts_at').value = dtLocal(c.starts_at);
            fld('expires_at').value = dtLocal(c.expires_at);
            fld('status').checked = Number(c.status) === 1;
            syncKind();
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
})();
</script>
