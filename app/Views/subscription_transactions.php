<?php
/** Customer payment history. Rendered inside layout.php. */
$statusMeta = [
    'paid'     => ['Paid', 'success'],
    'created'  => ['Pending', 'secondary'],
    'failed'   => ['Failed', 'danger'],
    'refunded' => ['Refunded', 'warning'],
];
$state = $state ?? ['reason' => 'default'];
$sub   = $sub ?? null;
$reason = (string) ($state['reason'] ?? '');
$planName = $sub['plan_name'] ?? null;
$reasonMeta = [
    'paid'          => ['Paid plan active', 'success', 'bi-patch-check-fill'],
    'trial'         => ['Trial access', 'info', 'bi-stars'],
    'trial_expired' => ['Trial ended', 'warning', 'bi-hourglass-bottom'],
    'expired'       => ['Payment expired', 'danger', 'bi-exclamation-triangle-fill'],
    'free'          => ['Free plan', 'secondary', 'bi-lock-fill'],
    'none'          => ['Free plan', 'secondary', 'bi-lock-fill'],
    'superadmin'    => ['Super Admin access', 'primary', 'bi-shield-check'],
    'default'       => ['Active access', 'success', 'bi-check-circle-fill'],
];
$rm = $reasonMeta[$reason] ?? $reasonMeta['default'];
$expTs = ! empty($sub['expires_at']) ? strtotime((string) $sub['expires_at']) : null;
$daysLeft = $expTs ? (int) ceil(($expTs - time()) / 86400) : null;
?>
<div class="row mb-3">
    <div class="col-12">
        <div class="card border-<?= esc($rm[1]) ?>">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <span class="badge text-bg-<?= esc($rm[1]) ?> fs-6 py-2 px-3"><i class="bi <?= esc($rm[2]) ?> me-1"></i><?= esc($rm[0]) ?></span>
                <div class="flex-grow-1">
                    <div class="fw-bold fs-5"><?= esc($planName ?: 'Free plan') ?></div>
                    <div class="text-muted small">
                        <?php if ($reason === 'paid' && $expTs): ?>
                            Valid until <strong><?= esc(date('d M Y', $expTs)) ?></strong><?= $daysLeft !== null ? ' · ' . max(0, $daysLeft) . ' day' . ($daysLeft === 1 ? '' : 's') . ' left' : '' ?>
                        <?php elseif ($reason === 'trial' && $expTs): ?>
                            Trial ends <strong><?= esc(date('d M Y', $expTs)) ?></strong><?= $daysLeft !== null ? ' · ' . max(0, $daysLeft) . ' day' . ($daysLeft === 1 ? '' : 's') . ' left' : '' ?>
                        <?php else: ?>
                            No active paid subscription. Upgrade to unlock documents, exports and reports.
                        <?php endif; ?>
                    </div>
                </div>
                <a href="<?= site_url('subscription') ?>" class="btn btn-primary"><i class="bi bi-gem me-1"></i> Manage plan</a>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="bi bi-receipt me-1"></i> Payment History</h3>
                <a href="<?= site_url('subscription') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-gem me-1"></i> Plans</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice / Order</th>
                                <th>Plan</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th class="text-end">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">
                                <i class="bi bi-inbox fs-4 d-block mb-1"></i>No payments yet.
                                <div class="mt-2"><a href="<?= site_url('subscription') ?>" class="btn btn-sm btn-primary">Choose a plan</a></div>
                            </td></tr>
                        <?php else: foreach ($orders as $o): ?>
                            <?php [$label, $color] = $statusMeta[$o['status']] ?? ['—', 'secondary']; ?>
                            <tr>
                                <td><?= esc(date('d M Y, H:i', strtotime($o['invoice_date'] ?: $o['created_at']))) ?></td>
                                <td>
                                    <?php if (! empty($o['invoice_no'])): ?>
                                        <strong><?= esc($o['invoice_no']) ?></strong><br>
                                    <?php endif; ?>
                                    <span class="text-muted small"><?= esc($o['order_id']) ?></span>
                                </td>
                                <td><?= esc($o['plan_name'] ?? '—') ?></td>
                                <td class="text-end">&#8377;<?= esc(number_format((float) $o['amount'], 2)) ?></td>
                                <td>
                                    <span class="badge text-bg-<?= esc($color) ?>"><?= esc($label) ?></span>
                                    <?php if ((int) ($o['refunded'] ?? 0) === 1): ?><span class="badge text-bg-warning">Refunded</span><?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ((int) $o['activated'] === 1): ?>
                                        <a href="<?= site_url('subscription/receipt/' . $o['order_id']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-download me-1"></i>Tax receipt
                                        </a>
                                    <?php elseif ($o['status'] === 'failed'): ?>
                                        <a href="<?= site_url('subscription') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Retry</a>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
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
