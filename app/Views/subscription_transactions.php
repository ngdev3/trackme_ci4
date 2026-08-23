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
                <?php $statusCls = ['paid' => 'active', 'created' => 'inactive', 'failed' => 'delete', 'refunded' => 'inactive']; ?>
                <div class="erp-tbl-wrap">
                    <table class="erp-tbl auto">
                        <thead>
                            <tr>
                                <th class="text-start">Date</th>
                                <th class="text-start">Invoice / Order</th>
                                <th class="text-start">Plan</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="6" class="erp-empty">
                                <i class="bi bi-inbox"></i><div>No payments yet.</div>
                                <div class="mt-2"><a href="<?= site_url('subscription') ?>" class="btn btn-sm btn-primary">Choose a plan</a></div>
                            </td></tr>
                        <?php else: foreach ($orders as $o): ?>
                            <?php [$label, $color] = $statusMeta[$o['status']] ?? ['—', 'secondary']; $scls = $statusCls[$o['status']] ?? 'inactive'; ?>
                            <tr>
                                <td class="text-start"><span class="erp-muted"><?= esc(date('d M Y, H:i', strtotime($o['invoice_date'] ?: $o['created_at']))) ?></span></td>
                                <td class="text-start">
                                    <?php if (! empty($o['invoice_no'])): ?>
                                        <strong><?= esc($o['invoice_no']) ?></strong><br>
                                    <?php endif; ?>
                                    <span class="erp-muted small"><?= esc($o['order_id']) ?></span>
                                </td>
                                <td class="text-start">
                                    <?= esc($o['plan_name'] ?? '—') ?>
                                    <?php if (! empty($o['coupon_code'])): ?>
                                        <br><span class="erp-pill green" title="Coupon applied"><i class="bi bi-ticket-perforated"></i> <?= esc($o['coupon_code']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold">
                                    &#8377;<?= esc(number_format((float) $o['amount'], 2)) ?>
                                    <?php if (! empty($o['discount']) && (float) $o['discount'] > 0): ?>
                                        <br><span class="text-success small">&#8722;&#8377;<?= esc(number_format((float) $o['discount'], 2)) ?> off</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="erp-status <?= $scls ?>"><?= esc($label) ?></span>
                                    <?php if ((int) ($o['refunded'] ?? 0) === 1): ?><span class="erp-status inactive">Refunded</span><?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ((int) $o['activated'] === 1): ?>
                                        <a href="<?= site_url('subscription/receipt/' . $o['order_id']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-download me-1"></i>Tax receipt
                                        </a>
                                    <?php elseif ($o['status'] === 'failed'): ?>
                                        <a href="<?= site_url('subscription') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Retry</a>
                                    <?php else: ?>
                                        <span class="erp-muted small">—</span>
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
