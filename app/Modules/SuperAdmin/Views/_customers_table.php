<?php
/**
 * Customers table fragment — the sortable table + pager. Rendered inside the
 * full customers page on first load, and returned on its own by
 * SuperAdminController::customersData() for live (AJAX) search / page-size /
 * sort / pagination. Self-contained: it defines its own sort-header helper.
 *
 * Compact layout: the Subscription column is narrowed to a single plan pill and
 * the standalone "Firms" column is folded into a badge on the customer name, so
 * the primary columns (name, email, payment, status) get more room. Truncated
 * cells carry a native title tooltip with the full / detailed value.
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

/** Compact relative time ("2 mo ago") for the hover-card footer / last-seen. */
$ago = static function ($ts): string {
    if (! $ts) { return ''; }
    $d = time() - strtotime((string) $ts);
    if ($d < 60)       { return 'just now'; }
    if ($d < 3600)     { return floor($d / 60) . 'm ago'; }
    if ($d < 86400)    { return floor($d / 3600) . 'h ago'; }
    if ($d < 2592000)  { return floor($d / 86400) . 'd ago'; }
    if ($d < 31536000) { return floor($d / 2592000) . ' mo ago'; }
    return round($d / 31536000, 1) . ' yr ago';
};
?>
<div class="cust-table-wrap">
    <table class="cust-table">
        <thead>
            <tr>
                <?= $sortTh('id', 'ID', 'col-id text-start') ?>
                <?= $sortTh('name', 'Name', 'text-start') ?>
                <?= $sortTh('email', 'Email', 'text-start') ?>
                <?= $sortTh('subscription', 'Plan', 'col-sub text-start') ?>
                <?= $sortTh('payment', 'Payment', 'text-center') ?>
                <?= $sortTh('status', 'Status', 'text-center') ?>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="cust-empty"><i class="bi bi-inbox"></i><div>No customers found<?= $search !== '' ? ' for “' . esc($search) . '”' : '' ?>.</div></td></tr>
        <?php else: foreach ($rows as $r): $sub = $r['subscription'] ?? null;
            $firmCount = (int) $r['firm_count'];

            // Detailed tooltips for values that may be clipped in the compact view.
            $nameTip = (string) $r['name'];
            if ($firmCount > 0) { $nameTip .= ' • Owns ' . $firmCount . ' firm' . ($firmCount === 1 ? '' : 's'); }

            $subParts = ['Plan: ' . ($sub['plan_name'] ?? '—')];
            if (! empty($sub['status']))         { $subParts[] = 'Status: ' . ucfirst((string) $sub['status']); }
            if (! empty($sub['payment_status'])) { $subParts[] = 'Payment: ' . ucfirst((string) $sub['payment_status']); }
            if (! empty($sub['started_at']))     { $subParts[] = 'Started: ' . date('d M Y', strtotime((string) $sub['started_at'])); }
            if (! empty($sub['expires_at']))     { $subParts[] = 'Expires: ' . date('d M Y', strtotime((string) $sub['expires_at'])); }
            $subTip = implode(' • ', $subParts);

            // Rich hover-card payload (mirrors the TrackMe account preview, mapped
            // to SaaS-customer fields). Read by the delegated JS in customers.php.
            $tip = [
                'id'          => (int) $r['id'],
                'name'        => (string) $r['name'],
                'email'       => (string) ($r['email'] ?? ''),
                'mobile'      => (string) ($r['mobile'] ?? ''),
                'status'      => ((int) ($r['status'] ?? 0) === 1) ? 'active' : 'inactive',
                'source'      => ! empty($r['auth_provider']) ? ucfirst((string) $r['auth_provider']) : 'Web',
                'firms'       => $firmCount,
                'plan'        => (string) ($sub['plan_name'] ?? ''),
                'plan_status' => (string) ($sub['status'] ?? ''),
                'payment'     => (string) ($sub['payment_status'] ?? ''),
                'started'     => ! empty($sub['started_at']) ? date('d M Y', strtotime((string) $sub['started_at'])) : '',
                'expires'     => ! empty($sub['expires_at']) ? date('d M Y', strtotime((string) $sub['expires_at'])) : '',
                'last_ago'    => ! empty($r['last_login_at']) ? $ago($r['last_login_at']) : '',
                'created'     => ! empty($r['created_at']) ? date('d M Y', strtotime((string) $r['created_at'])) : '',
                'created_ago' => ! empty($r['created_at']) ? $ago($r['created_at']) : '',
            ];
            if (! empty($sub['started_at']) && ! empty($sub['expires_at'])) {
                $s = strtotime((string) $sub['started_at']);
                $e = strtotime((string) $sub['expires_at']);
                $now = time();
                $tip['valid_pct'] = $e > $s ? max(0, min(100, (int) round(($now - $s) / ($e - $s) * 100))) : 100;
                $tip['days_left'] = (int) floor(($e - $now) / 86400);
            }
            $tipJson = json_encode($tip, JSON_UNESCAPED_UNICODE);
        ?>
            <tr>
                <td class="col-id text-start"><span class="cust-idchip" title="Customer ID <?= esc($r['id'], 'attr') ?>">CUS-<?= str_pad((string) $r['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
                <td class="text-start">
                    <div class="cust-name">
                        <span class="cust-avatar"><?= esc(strtoupper(mb_substr((string) $r['name'], 0, 1) ?: '?')) ?></span>
                        <span class="cust-name-txt cust-hover-name fw-semibold" data-tip="<?= esc($tipJson, 'attr') ?>"><?= esc($r['name']) ?></span>
                        <?php if ($firmCount > 0): ?>
                            <span class="cust-firmbadge" title="Owns <?= $firmCount ?> firm<?= $firmCount === 1 ? '' : 's' ?>"><i class="bi bi-building"></i><?= $firmCount ?></span>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="text-start cust-email"><span class="cust-muted" title="<?= esc($r['email'], 'attr') ?>"><?= esc($r['email']) ?></span></td>
                <td class="text-start col-sub">
                    <a href="<?= site_url('admin/customers/subscription/' . $r['id']) ?>" class="cust-planpill" title="<?= esc($subTip, 'attr') ?>">
                        <?= esc($sub['plan_name'] ?? '—') ?>
                    </a>
                </td>
                <td class="text-center">
                    <form action="<?= site_url('admin/customers/payment/' . $r['id']) ?>" method="post" class="m-0">
                        <?= csrf_field() ?>
                        <?php $pstat = $sub['payment_status'] ?? 'trial'; ?>
                        <select name="payment_status" class="cust-select pay-<?= esc($pstat, 'attr') ?>" title="Payment status — change to update" onchange="this.form.submit()">
                            <?php foreach (['trial', 'paid', 'unpaid'] as $ps): ?>
                                <option value="<?= $ps ?>" <?= $pstat === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td class="text-center">
                    <a href="<?= site_url('admin/customers/toggle/' . $r['id']) ?>" class="text-decoration-none" title="<?= (int) $r['status'] === 1 ? 'Active — click to deactivate' : 'Inactive — click to activate' ?>">
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
                        <form action="<?= site_url('admin/customers/delete/' . $r['id']) ?>" method="post" class="d-inline m-0" data-no-validate
                              data-confirm="This moves the customer and all their data to Trash. Nothing is erased — you can restore it anytime from Customers › Trash."
                              data-confirm-title="Move “<?= esc($r['name'], 'attr') ?>” to Trash?" data-confirm-btn="Move to Trash" data-confirm-icon="warning">
                            <?= csrf_field() ?>
                            <button class="cust-act act-purge" title="Move to Trash"><i class="bi bi-trash3"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($pager)): ?><div class="cust-pager-bar"><?= $pager->only(['q', 'per', 'sort', 'dir'])->links('default', 'modern') ?></div><?php endif; ?>
