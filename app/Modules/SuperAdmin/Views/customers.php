<?php /** Super Admin — all customers. Rendered inside layout.php. */ ?>

<?php if ($np = session()->getFlashdata('new_password')): ?>
    <div class="alert alert-success alert-dismissible d-flex flex-wrap align-items-center gap-2 shadow-sm" role="alert">
        <i class="bi bi-shield-lock-fill fs-5"></i>
        <div>
            New password for <strong><?= esc(session()->getFlashdata('new_password_for')) ?></strong>:
            <code id="npValue" class="fs-6 user-select-all"><?= esc($np) ?></code>
            <button type="button" class="btn btn-sm btn-outline-success ms-1" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('npValue').innerText)">
                <i class="bi bi-clipboard"></i> Copy
            </button>
            <div class="small mt-1">
                <?= session()->getFlashdata('new_password_emailed') === '1'
                    ? '<i class="bi bi-envelope-check text-success"></i> Emailed to the customer. '
                    : '' ?>
                Share it privately — it is shown only once and the customer must change it on next login.
            </div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($rl = session()->getFlashdata('reset_link')): ?>
    <div class="alert alert-warning alert-dismissible d-flex flex-wrap align-items-center gap-2 shadow-sm" role="alert">
        <i class="bi bi-link-45deg fs-5"></i>
        <div>
            Reset link for <strong><?= esc(session()->getFlashdata('reset_link_for')) ?></strong>:
            <code id="rlValue" class="user-select-all"><?= esc($rl) ?></code>
            <button type="button" class="btn btn-sm btn-outline-warning ms-1" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('rlValue').innerText)">
                <i class="bi bi-clipboard"></i> Copy
            </button>
            <div class="small mt-1">Share privately — it expires in 1 hour.</div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

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
                            <button type="button" class="btn btn-sm btn-outline-success" title="Set a new password"
                                    data-bs-toggle="modal" data-bs-target="#setPwdModal"
                                    data-id="<?= esc($r['id'], 'attr') ?>" data-name="<?= esc($r['name'], 'attr') ?>">
                                <i class="bi bi-shield-lock"></i>
                            </button>
                            <form action="<?= site_url('admin/customers/send-reset/' . $r['id']) ?>" method="post" class="d-inline" data-no-validate data-confirm="Email a one-click password-reset link to this customer?" data-confirm-title="Send reset link?" data-confirm-btn="Send link" data-confirm-icon="info">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-primary" title="Email a reset link"><i class="bi bi-envelope-lock"></i></button>
                            </form>
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

<!-- Set-password modal (shared; populated by the clicked row's button) -->
<div class="modal fade" id="setPwdModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="setPwdForm" method="post" data-no-validate>
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-lock me-1"></i> Set password for <span id="setPwdName">customer</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary small mb-3">
                    Existing passwords can't be shown — they're stored one-way (bcrypt) and are unrecoverable.
                    Set a new one instead. Leave it blank to auto-generate a strong password. The customer will be
                    asked to change it on their next login.
                </p>
                <label class="form-label">New password <span class="text-muted">(optional — blank = generate)</span></label>
                <div class="input-group">
                    <input type="text" name="new_password" id="setPwdInput" class="form-control" autocomplete="off"
                           minlength="8" placeholder="Leave blank to auto-generate">
                    <button class="btn btn-outline-secondary" type="button" id="setPwdGen" title="Generate"><i class="bi bi-magic"></i></button>
                </div>
                <div class="form-text">At least 8 characters if you type your own.</div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" value="1" name="email_customer" id="setPwdEmail" checked>
                    <label class="form-check-label" for="setPwdEmail">Email the new password to the customer</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check2 me-1"></i> Set password</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('setPwdModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (ev) {
        var btn = ev.relatedTarget;
        if (!btn) return;
        var id = btn.getAttribute('data-id');
        document.getElementById('setPwdName').textContent = btn.getAttribute('data-name') || ('#' + id);
        document.getElementById('setPwdForm').setAttribute('action', '<?= site_url('admin/customers/set-password') ?>/' + id);
        document.getElementById('setPwdInput').value = '';
    });
    // Client-side generator for the "type your own" box (server also generates when blank).
    document.getElementById('setPwdGen').addEventListener('click', function () {
        var lower = 'abcdefghijkmnpqrstuvwxyz', upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ', digits = '23456789', sym = '@#%&*!?';
        var all = lower + upper + digits + sym, out = [
            lower[Math.floor(Math.random() * lower.length)],
            upper[Math.floor(Math.random() * upper.length)],
            digits[Math.floor(Math.random() * digits.length)],
            sym[Math.floor(Math.random() * sym.length)]
        ];
        while (out.length < 12) out.push(all[Math.floor(Math.random() * all.length)]);
        for (var i = out.length - 1; i > 0; i--) { var j = Math.floor(Math.random() * (i + 1)); var t = out[i]; out[i] = out[j]; out[j] = t; }
        document.getElementById('setPwdInput').value = out.join('');
    });
})();
</script>
