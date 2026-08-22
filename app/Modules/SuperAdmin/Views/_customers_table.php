<?php
/**
 * Customers table fragment — the sortable table + pager. Rendered inside the
 * full customers page on first load, and returned on its own by
 * SuperAdminController::customersData() for live (AJAX) search / page-size /
 * sort / pagination. Self-contained: it defines its own sort-header helper.
 *
 * @var array       $rows
 * @var string|int  $per
 * @var string      $sort
 * @var string      $dir
 * @var string      $search
 * @var int         $offset
 * @var \CodeIgniter\Pager\Pager $pager
 */
$per  = $per  ?? 25;
$sort = $sort ?? 'id';
$dir  = $dir  ?? 'desc';

// Params carried through every sort/pager link so search + page-size compose.
$carry = [];
if ($search !== '')  { $carry['q'] = $search; }
if ($per !== 25)     { $carry['per'] = $per; }

/** Render a sortable <th>: clicking toggles asc/desc, keeps q + per. */
$sortTh = static function (string $key, string $label, string $align = 'text-start') use ($sort, $dir, $carry) {
    $nextDir = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
    $url = site_url('admin/customers') . '?' . http_build_query(array_merge($carry, ['sort' => $key, 'dir' => $nextDir]));
    if ($sort === $key) {
        $icon = $dir === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill';
        $cls  = 'is-sorted';
    } else {
        $icon = 'bi-chevron-expand';
        $cls  = '';
    }
    return '<th class="' . $align . ' cust-th-sort"><a href="' . $url . '" class="cust-sort ' . $cls . '">'
        . '<span>' . esc($label) . '</span><i class="bi ' . $icon . '"></i></a></th>';
};
?>
<div class="cust-table-wrap">
    <table class="cust-table">
        <thead>
            <tr>
                <th class="col-sno text-center">S.No</th>
                <?= $sortTh('id', 'ID', 'col-id text-center') ?>
                <?= $sortTh('name', 'Name', 'text-start') ?>
                <?= $sortTh('email', 'Email', 'text-start') ?>
                <?= $sortTh('firms', 'Firms', 'text-center') ?>
                <?= $sortTh('subscription', 'Subscription', 'text-start') ?>
                <?= $sortTh('payment', 'Payment', 'text-center') ?>
                <?= $sortTh('status', 'Status', 'text-center') ?>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="9" class="cust-empty"><i class="bi bi-inbox"></i><div>No customers found<?= $search !== '' ? ' for “' . esc($search) . '”' : '' ?>.</div></td></tr>
        <?php else: $sno = (int) ($offset ?? 0); foreach ($rows as $r): $sub = $r['subscription'] ?? null; $sno++; ?>
            <tr>
                <td class="col-sno text-center"><?= $sno ?></td>
                <td class="col-id text-center"><span class="cust-idchip">CUS-<?= str_pad((string) $r['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
                <td class="text-start">
                    <div class="cust-name">
                        <span class="cust-avatar"><?= esc(strtoupper(mb_substr((string) $r['name'], 0, 1) ?: '?')) ?></span>
                        <span class="fw-semibold"><?= esc($r['name']) ?></span>
                    </div>
                </td>
                <td class="text-start cust-muted"><?= esc($r['email']) ?></td>
                <td class="text-center"><span class="cust-pill"><?= (int) $r['firm_count'] ?></span></td>
                <td class="text-start">
                    <a href="<?= site_url('admin/customers/subscription/' . $r['id']) ?>" class="cust-sub-link" title="Manage subscription">
                        <span class="cust-sub-plan"><?= esc($sub['plan_name'] ?? '—') ?></span>
                        <?php if (! empty($sub['status'])): ?><span class="cust-sub-status"><?= esc($sub['status']) ?></span><?php endif; ?>
                    </a>
                </td>
                <td class="text-center">
                    <form action="<?= site_url('admin/customers/payment/' . $r['id']) ?>" method="post" class="m-0">
                        <?= csrf_field() ?>
                        <?php $pstat = $sub['payment_status'] ?? 'trial'; ?>
                        <select name="payment_status" class="cust-select pay-<?= esc($pstat, 'attr') ?>" onchange="this.form.submit()">
                            <?php foreach (['trial', 'paid', 'unpaid'] as $ps): ?>
                                <option value="<?= $ps ?>" <?= $pstat === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td class="text-center">
                    <a href="<?= site_url('admin/customers/toggle/' . $r['id']) ?>" class="text-decoration-none" title="Toggle active">
                        <?= (int) $r['status'] === 1
                            ? '<span class="cust-badge is-active"><i class="bi bi-dot"></i>Active</span>'
                            : '<span class="cust-badge is-inactive"><i class="bi bi-dot"></i>Inactive</span>' ?>
                    </a>
                </td>
                <td class="text-center">
                    <div class="cust-row-actions">
                        <a href="<?= site_url('admin/customers/subscription/' . $r['id']) ?>" class="cust-act act-sub" title="Manage subscription"><i class="bi bi-gem"></i></a>
                        <a href="<?= site_url('admin/impersonate/' . $r['id']) ?>" class="cust-act act-login" title="Sign in as this customer"
                           data-confirm="You can return to Super Admin anytime." data-confirm-title="Sign in as <?= esc($r['name'], 'attr') ?>?" data-confirm-btn="Sign in" data-confirm-icon="info">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </a>
                        <button type="button" class="cust-act act-pwd" title="Set a new password"
                                data-bs-toggle="modal" data-bs-target="#setPwdModal"
                                data-id="<?= esc($r['id'], 'attr') ?>" data-name="<?= esc($r['name'], 'attr') ?>">
                            <i class="bi bi-shield-lock"></i>
                        </button>
                        <form action="<?= site_url('admin/customers/send-reset/' . $r['id']) ?>" method="post" class="d-inline m-0" data-no-validate data-confirm="Email a one-click password-reset link to this customer?" data-confirm-title="Send reset link?" data-confirm-btn="Send link" data-confirm-icon="info">
                            <?= csrf_field() ?>
                            <button class="cust-act act-mail" title="Email a reset link"><i class="bi bi-envelope-arrow-up"></i></button>
                        </form>
                        <form action="<?= site_url('admin/customers/reset/' . $r['id']) ?>" method="post" class="d-inline m-0" data-no-validate data-confirm="This customer will be forced to reset their password on next login." data-confirm-title="Reset access?" data-confirm-btn="Yes, reset" data-confirm-icon="warning">
                            <?= csrf_field() ?>
                            <button class="cust-act act-reset" title="Force password reset"><i class="bi bi-key"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($pager)): ?><div class="cust-pager-bar"><?= $pager->only(['q', 'per', 'sort', 'dir'])->links('default', 'modern') ?></div><?php endif; ?>
