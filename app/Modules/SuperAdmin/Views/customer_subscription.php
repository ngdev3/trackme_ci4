<?php
/** Super Admin — one customer's subscription: current plan, activate/deactivate,
 *  full payment chain and the subscription activity log. Rendered inside layout.php. */
$sub    = $sub ?? null;
$orders = $orders ?? [];
$plans  = $plans ?? [];
$logs   = $logs ?? [];

// ---- Current plan status -------------------------------------------------
$payStatus = strtolower((string) ($sub['payment_status'] ?? 'none'));
$status    = strtolower((string) ($sub['status'] ?? ''));
$expTs     = ! empty($sub['expires_at']) ? strtotime((string) $sub['expires_at']) : null;
$startTs   = ! empty($sub['started_at']) ? strtotime((string) $sub['started_at']) : null;
$daysLeft  = $expTs ? (int) ceil(($expTs - time()) / 86400) : null;
$expired   = $expTs !== null && $expTs < time();

if ($sub === null) {
    $stateLabel = 'No subscription';
    $stateColor = 'secondary';
    $stateIcon  = 'bi-dash-circle';
} elseif ($status === 'cancelled' || $payStatus === 'unpaid') {
    $stateLabel = 'Deactivated';
    $stateColor = 'danger';
    $stateIcon  = 'bi-slash-circle';
} elseif ($payStatus === 'paid' && ! $expired) {
    $stateLabel = 'Active (Paid)';
    $stateColor = 'success';
    $stateIcon  = 'bi-patch-check-fill';
} elseif ($payStatus === 'paid' && $expired) {
    $stateLabel = 'Expired';
    $stateColor = 'warning';
    $stateIcon  = 'bi-hourglass-bottom';
} elseif ($payStatus === 'trial') {
    $stateLabel = $expired ? 'Trial ended' : 'Trial';
    $stateColor = $expired ? 'warning' : 'info';
    $stateIcon  = $expired ? 'bi-hourglass-bottom' : 'bi-stars';
} else {
    $stateLabel = ucfirst($payStatus ?: 'Free');
    $stateColor = 'secondary';
    $stateIcon  = 'bi-lock-fill';
}

$orderMeta = [
    'paid'     => ['Paid', 'success'],
    'created'  => ['Pending', 'secondary'],
    'failed'   => ['Failed', 'danger'],
    'refunded' => ['Refunded', 'warning'],
];
$dt = static fn ($v) => $v ? date('d M Y, H:i', strtotime((string) $v)) : '—';
?>

<div class="row g-3">
    <!-- Customer header -->
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <span class="badge text-bg-primary rounded-circle p-3 fs-5"><i class="bi bi-person-fill"></i></span>
                <div class="flex-grow-1">
                    <h3 class="mb-0"><?= esc($user['name']) ?></h3>
                    <div class="text-muted"><?= esc($user['email']) ?> · Customer #<?= (int) $user['id'] ?></div>
                </div>
                <a href="<?= site_url('admin/customers') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Customers</a>
                <a href="<?= site_url('admin/impersonate/' . (int) $user['id']) ?>" class="btn btn-outline-primary"
                   data-confirm="You can return to Super Admin anytime." data-confirm-title="Sign in as <?= esc($user['name'], 'attr') ?>?" data-confirm-btn="Sign in" data-confirm-icon="info"><i class="bi bi-box-arrow-in-right me-1"></i> Access</a>
            </div>
        </div>
    </div>

    <!-- Current plan + controls -->
    <div class="col-lg-5">
        <div class="card h-100 border-<?= esc($stateColor) ?>">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="bi bi-gem me-1"></i> Current Plan</h3>
                <span class="badge text-bg-<?= esc($stateColor) ?>"><i class="bi <?= esc($stateIcon) ?> me-1"></i><?= esc($stateLabel) ?></span>
            </div>
            <div class="card-body">
                <div class="fs-4 fw-bold mb-1"><?= esc($sub['plan_name'] ?? 'No plan') ?></div>
                <dl class="row mb-3 small">
                    <dt class="col-5 text-muted">Status</dt><dd class="col-7"><?= esc(ucfirst($status ?: '—')) ?></dd>
                    <dt class="col-5 text-muted">Payment</dt><dd class="col-7"><?= esc(ucfirst($payStatus ?: '—')) ?></dd>
                    <dt class="col-5 text-muted">Started</dt><dd class="col-7"><?= esc($dt($sub['started_at'] ?? null)) ?></dd>
                    <dt class="col-5 text-muted">Expires</dt>
                    <dd class="col-7">
                        <?= esc($expTs ? date('d M Y', $expTs) : '—') ?>
                        <?php if ($daysLeft !== null && ! $expired): ?>
                            <span class="text-muted">(<?= max(0, $daysLeft) ?> day<?= $daysLeft === 1 ? '' : 's' ?> left)</span>
                        <?php elseif ($expired): ?>
                            <span class="text-danger">(expired)</span>
                        <?php endif; ?>
                    </dd>
                </dl>

                <!-- Activate / change plan -->
                <form action="<?= site_url('admin/customers/subscription/' . (int) $user['id'] . '/activate') ?>" method="post" class="mb-2">
                    <?= csrf_field() ?>
                    <label class="form-label small fw-semibold mb-1">Activate / change plan</label>
                    <div class="input-group">
                        <select name="plan_id" class="form-select" required>
                            <option value="">Select a package…</option>
                            <?php foreach ($plans as $p): ?>
                                <option value="<?= (int) $p['id'] ?>" <?= (int) ($sub['plan_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>>
                                    <?= esc($p['name']) ?> — &#8377;<?= esc(number_format((float) $p['price'], 0)) ?> / <?= esc($p['billing_cycle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Activate</button>
                    </div>
                    <?php if (empty($plans)): ?>
                        <div class="form-text text-warning">No active paid plans. Create one on <a href="<?= site_url('admin/plans') ?>">Plans</a>.</div>
                    <?php endif; ?>
                </form>

                <!-- Deactivate -->
                <?php if ($sub !== null && $status !== 'cancelled' && $payStatus !== 'unpaid'): ?>
                    <form action="<?= site_url('admin/customers/subscription/' . (int) $user['id'] . '/deactivate') ?>" method="post"
                          data-no-validate data-confirm="The customer drops to Basic restrictions. Their data is preserved." data-confirm-title="Deactivate subscription?" data-confirm-btn="Yes, deactivate" data-confirm-icon="warning">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-danger w-100"><i class="bi bi-slash-circle me-1"></i> Deactivate subscription</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-light border small mb-0"><i class="bi bi-info-circle me-1"></i> This customer has no active subscription. Activate a plan above to grant access.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payment chain / flow -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-diagram-3 me-1"></i> Payment Chain &amp; Flow</h3></div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="text-center text-secondary py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i>No payment orders yet for this customer.</div>
                <?php else: foreach ($orders as $o):
                    [$oLabel, $oColor] = $orderMeta[$o['status']] ?? ['—', 'secondary'];
                    // Ordered stages of a single payment order.
                    $steps = [
                        ['Order created', $dt($o['created_at'] ?? null), true, 'bi-plus-circle'],
                        ['Checkout started', ! empty($o['payment_session_id']) ? 'Session issued' : 'Not started', ! empty($o['payment_session_id']), 'bi-box-arrow-up-right'],
                    ];
                    if ($o['status'] === 'failed') {
                        $steps[] = ['Payment failed', $dt($o['updated_at'] ?? null), true, 'bi-x-circle', 'danger'];
                    } else {
                        $steps[] = ['Paid', ! empty($o['cf_payment_id']) ? ('Ref ' . $o['cf_payment_id']) : ($o['status'] === 'paid' ? 'Confirmed' : 'Awaiting'), ($o['status'] === 'paid' || (int) $o['activated'] === 1), 'bi-cash-coin'];
                        $steps[] = ['Activated', (int) $o['activated'] === 1 ? $dt($o['invoice_date'] ?? null) : 'No', (int) $o['activated'] === 1, 'bi-unlock-fill'];
                    }
                    if ((int) ($o['refunded'] ?? 0) === 1) {
                        $steps[] = ['Refunded', 'Marked refunded', true, 'bi-arrow-counterclockwise', 'warning'];
                    }
                ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="badge text-bg-<?= esc($oColor) ?>"><?= esc($oLabel) ?></span>
                            <strong><?= esc($o['plan_name'] ?? '—') ?></strong>
                            <span class="text-muted">&#8377;<?= esc(number_format((float) $o['amount'], 2)) ?></span>
                            <span class="ms-auto small text-muted font-monospace"><?= esc($o['order_id']) ?></span>
                            <?php if (! empty($o['invoice_no'])): ?>
                                <a href="<?= site_url('subscription/receipt/' . $o['order_id']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Tax receipt"><i class="bi bi-download"></i> <?= esc($o['invoice_no']) ?></a>
                            <?php endif; ?>
                        </div>
                        <ol class="sub-flow">
                            <?php foreach ($steps as $s):
                                $done  = (bool) ($s[2] ?? false);
                                $tone  = $s[4] ?? ($done ? 'success' : 'muted');
                            ?>
                                <li class="sub-flow-step is-<?= esc($tone) ?>">
                                    <span class="sub-flow-dot"><i class="bi <?= esc($s[3] ?? 'bi-circle') ?>"></i></span>
                                    <span class="sub-flow-body">
                                        <strong><?= esc($s[0]) ?></strong>
                                        <small><?= esc($s[1]) ?></small>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Subscription activity log -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-clock-history me-1"></i> Subscription Activity Log</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>When</th><th>By</th><th>Module</th><th>Action</th><th>Detail</th></tr></thead>
                        <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">No subscription activity recorded yet.</td></tr>
                        <?php else: foreach ($logs as $l): ?>
                            <tr>
                                <td class="text-nowrap small"><?= esc($dt($l['created_at'] ?? null)) ?></td>
                                <td class="small"><?= esc($l['user_name'] ?? ($l['user_id'] ? '#' . $l['user_id'] : 'System')) ?></td>
                                <td><span class="badge text-bg-light border"><?= esc($l['module'] ?? '—') ?></span></td>
                                <td class="small"><?= esc($l['action'] ?? '—') ?></td>
                                <td class="small"><?= esc($l['description'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.sub-flow { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 6px; }
.sub-flow-step { display: flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 999px; background: var(--bs-body-bg); border: 1px solid var(--erp-border, #e4e9f2); }
.sub-flow-step .sub-flow-dot { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; background: rgba(100,116,139,.15); color: #64748b; flex: 0 0 auto; }
.sub-flow-step.is-success .sub-flow-dot { background: rgba(22,163,74,.15); color: #16a34a; }
.sub-flow-step.is-danger  .sub-flow-dot { background: rgba(220,38,38,.15); color: #dc2626; }
.sub-flow-step.is-warning .sub-flow-dot { background: rgba(245,158,11,.18); color: #b45309; }
.sub-flow-body { display: flex; flex-direction: column; line-height: 1.15; }
.sub-flow-body strong { font-size: .82rem; }
.sub-flow-body small { color: var(--erp-muted, #66748c); font-size: .72rem; }
.sub-flow-step.is-muted { opacity: .6; }
</style>
