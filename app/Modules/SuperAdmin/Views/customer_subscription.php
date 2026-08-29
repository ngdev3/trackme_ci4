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

<div class="cust-page">
<section class="cust-hero">
    <div>
        <h4 class="cust-title"><?= esc($user['name']) ?></h4>
        <p class="cust-subtitle"><?= esc($user['email']) ?> · Customer #<?= (int) $user['id'] ?> · subscription and billing controls.</p>
    </div>
    <div class="cust-hero-actions">
        <a href="<?= site_url('admin/customers') ?>" class="cust-btn cust-btn-ghost"><i class="bi bi-arrow-left"></i> Customers</a>
        <a href="<?= site_url('admin/impersonate/' . (int) $user['id']) ?>" class="cust-btn cust-btn-primary"
           data-confirm="You can return to Super Admin anytime." data-confirm-title="Sign in as <?= esc($user['name'], 'attr') ?>?" data-confirm-btn="Sign in" data-confirm-icon="info"><i class="bi bi-box-arrow-in-right"></i> Access</a>
    </div>
</section>

<div class="row g-3">
    <!-- Customer header -->
    <div class="col-12 d-none">
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
        <section class="cust-panel h-100">
            <div class="cust-toolbar">
                <div><h5 class="cust-table-title">Current Plan</h5><p class="cust-table-note">Activate, correct expiry, or deactivate the customer subscription.</p></div>
                <span class="erp-status <?= $stateColor === 'success' || $stateColor === 'info' ? 'active' : ($stateColor === 'danger' ? 'delete' : 'inactive') ?>"><?= esc($stateLabel) ?></span>
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

                <!-- Activate / change plan. Guard against accidental double/quadruple
                     clicks: each activation extends the expiry by one billing cycle,
                     so the submit button disables itself on the first press. -->
                <form action="<?= site_url('admin/customers/subscription/' . (int) $user['id'] . '/activate') ?>" method="post" class="mb-2"
                      onsubmit="var b=this.querySelector('button[type=submit]'); if(b){setTimeout(function(){b.disabled=true;b.innerHTML='<span class=&quot;spinner-border spinner-border-sm me-1&quot;></span>Activating…';},0);}">
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
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Activate</button>
                    </div>
                    <div class="form-text"><i class="bi bi-info-circle me-1"></i>Each activation adds one billing cycle to the expiry. Clicked it too many times? Use “Correct expiry” below.</div>
                    <?php if (empty($plans)): ?>
                        <div class="form-text text-warning">No active paid plans. Create one on <a href="<?= site_url('admin/plans') ?>">Plans</a>.</div>
                    <?php endif; ?>
                </form>

                <!-- Correct expiry — fix an accidental over-extension (e.g. Activate
                     tapped several times stacked extra billing cycles). -->
                <?php if ($sub !== null): ?>
                    <form action="<?= site_url('admin/customers/subscription/' . (int) $user['id'] . '/set-expiry') ?>" method="post" class="mb-2"
                          data-no-validate data-confirm="Set this subscription's expiry to the chosen date? Use this to undo accidental repeat activations." data-confirm-title="Correct expiry date?" data-confirm-btn="Set date" data-confirm-icon="warning">
                        <?= csrf_field() ?>
                        <label class="form-label small fw-semibold mb-1">Correct expiry date</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                            <input type="date" name="expires_at" class="form-control" required
                                   value="<?= esc($expTs ? date('Y-m-d', $expTs) : date('Y-m-d')) ?>">
                            <button type="submit" class="btn btn-outline-warning"><i class="bi bi-pencil-square me-1"></i> Set</button>
                        </div>
                        <div class="form-text">Current expiry: <strong><?= esc($expTs ? date('d M Y', $expTs) : '—') ?></strong>. Setting a date won't add a cycle — it replaces the expiry exactly.</div>
                    </form>
                <?php endif; ?>

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
        </section>
    </div>

    <!-- Payment chain / flow -->
    <div class="col-lg-7">
        <section class="cust-panel h-100">
            <div class="cust-toolbar"><div><h5 class="cust-table-title">Payment Chain &amp; Flow</h5><p class="cust-table-note">Payment order status and activation path.</p></div></div>
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
        </section>
    </div>

    <!-- Subscription activity log -->
    <div class="col-12">
        <section class="cust-panel cust-table-panel">
            <div class="cust-toolbar">
                <div>
                    <h5 class="cust-table-title">Subscription Activity Log</h5>
                    <p class="cust-table-note">Admin actions and subscription changes for this customer.</p>
                </div>
            </div>
                <div class="cust-table-wrap">
                    <table class="cust-table">
                        <thead><tr><th class="text-start">When</th><th class="text-start">By</th><th class="text-start">Module</th><th class="text-start">Action</th><th class="text-start">Detail</th></tr></thead>
                        <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5" class="erp-empty"><i class="bi bi-clock-history"></i><div>No subscription activity recorded yet.</div></td></tr>
                        <?php else: foreach ($logs as $l): ?>
                            <tr>
                                <td class="text-start"><span class="erp-muted"><?= esc($dt($l['created_at'] ?? null)) ?></span></td>
                                <td class="text-start"><?php $lby = (string) ($l['user_name'] ?? ($l['user_id'] ? '#' . $l['user_id'] : 'System')); ?><?= erp_cell_name($lby, [
                                    'type' => 'Activity', 'icon' => 'clock-history',
                                    'chips' => array_values(array_filter([
                                        ! empty($l['module']) ? ['t' => (string) $l['module'], 'ic' => 'folder2'] : null,
                                        ! empty($l['action']) ? ['t' => (string) $l['action'], 'ic' => 'lightning-charge-fill', 'ok' => true] : null,
                                    ])),
                                    'rows' => array_values(array_filter([
                                        ! empty($l['description']) ? ['ic' => 'card-text', 'l' => 'Detail', 'v' => (string) $l['description']] : null,
                                        ['ic' => 'clock', 'l' => 'When', 'v' => (string) $dt($l['created_at'] ?? null)],
                                    ])),
                                ]) ?></td>
                                <td class="text-start"><span class="erp-badge"><?= esc($l['module'] ?? '—') ?></span></td>
                                <td class="text-start"><span class="erp-pill"><?= esc($l['action'] ?? '—') ?></span></td>
                                <td class="text-start"><span class="erp-muted"><?= esc($l['description'] ?? '') ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
        </section>
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
