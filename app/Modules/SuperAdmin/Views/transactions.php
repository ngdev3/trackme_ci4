<?php
/** Super Admin — all payment transactions. Rendered inside layout.php. */
$statusMeta = [
    'paid'     => ['Paid', 'success'],
    'created'  => ['Pending', 'secondary'],
    'failed'   => ['Failed', 'danger'],
    'refunded' => ['Refunded', 'warning'],
];
$filters = ['' => 'All', 'paid' => 'Paid', 'created' => 'Pending', 'failed' => 'Failed', 'refunded' => 'Refunded'];
?>
<div class="row g-3">
    <!-- Stat tiles -->
    <div class="col-12">
        <div class="row g-2">
            <?php
            $tiles = [
                ['Total', number_format((int) ($stats['total'] ?? 0)), 'bi-collection', 'primary'],
                ['Revenue (paid)', '&#8377;' . number_format((float) ($stats['revenue'] ?? 0), 2), 'bi-cash-stack', 'success'],
                ['Paid', number_format((int) ($stats['paid'] ?? 0)), 'bi-check-circle', 'success'],
                ['Failed', number_format((int) ($stats['failed'] ?? 0)), 'bi-x-circle', 'danger'],
                ['Refunded', number_format((int) ($stats['refunded'] ?? 0)), 'bi-arrow-counterclockwise', 'warning'],
            ];
            foreach ($tiles as [$label, $val, $icon, $color]): ?>
                <div class="col-6 col-md">
                    <div class="card h-100"><div class="card-body py-2 px-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge text-bg-<?= $color ?>"><i class="bi <?= $icon ?>"></i></span>
                            <div><div class="small text-muted"><?= $label ?></div><div class="fw-bold fs-6"><?= $val ?></div></div>
                        </div>
                    </div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="bi bi-receipt me-1"></i> Transactions</h3>
                <form class="d-flex gap-2" method="get">
                    <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        <?php foreach ($filters as $k => $v): ?>
                            <option value="<?= esc($k, 'attr') ?>" <?= $status === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="search" name="q" value="<?= esc($q, 'attr') ?>" class="form-control form-control-sm" placeholder="Order / invoice / customer" style="width:220px">
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr>
                            <th>Date</th><th>Customer</th><th>Plan</th><th class="text-end">Amount</th>
                            <th>Status</th><th>Invoice</th><th class="text-end">Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="7" class="text-center text-secondary py-4">No transactions found.</td></tr>
                        <?php else: foreach ($rows as $r): ?>
                            <?php [$label, $color] = $statusMeta[$r['status']] ?? ['—', 'secondary']; ?>
                            <tr>
                                <td class="text-nowrap"><?= esc(date('d M Y, H:i', strtotime($r['invoice_date'] ?: $r['created_at']))) ?></td>
                                <td>
                                    <a href="<?= site_url('admin/customers/subscription/' . $r['customer_id']) ?>" class="text-decoration-none" title="View subscription & full flow">
                                        <strong><?= esc($r['customer_name'] ?? ('#' . $r['customer_id'])) ?></strong>
                                    </a>
                                    <div class="small text-muted"><?= esc($r['customer_email'] ?? '') ?></div>
                                    <div class="small text-muted"><?= esc($r['order_id']) ?></div>
                                </td>
                                <td>
                                    <?= esc($r['plan_name'] ?? '—') ?>
                                    <?php $gw = $r['gateway'] ?? 'cashfree'; ?>
                                    <span class="badge rounded-pill text-bg-light border ms-1" title="Payment gateway">
                                        <i class="bi <?= $gw === 'googleplay' ? 'bi-google-play' : 'bi-credit-card' ?> me-1"></i><?= $gw === 'googleplay' ? 'Play' : 'Cashfree' ?>
                                    </span>
                                </td>
                                <td class="text-end">&#8377;<?= esc(number_format((float) $r['amount'], 2)) ?></td>
                                <td><span class="badge text-bg-<?= esc($color) ?>"><?= esc($label) ?></span></td>
                                <td><?= esc($r['invoice_no'] ?: '—') ?></td>
                                <td class="text-end text-nowrap">
                                    <?php if ((int) $r['activated'] === 1): ?>
                                        <a href="<?= site_url('subscription/receipt/' . $r['order_id']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Tax receipt"><i class="bi bi-download"></i></a>
                                    <?php endif; ?>
                                    <?php if ($r['status'] === 'paid'): ?>
                                        <?php $refundWhere = ($r['gateway'] ?? 'cashfree') === 'googleplay' ? 'Google Play Console' : 'Cashfree dashboard'; ?>
                                        <form action="<?= site_url('admin/transactions/refund/' . $r['id']) ?>" method="post" class="d-inline" data-no-validate data-confirm="Refund the money in the <?= $refundWhere ?> separately." data-confirm-title="Mark as refunded?" data-confirm-btn="Yes, mark refunded" data-confirm-icon="warning">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-warning" title="Mark refunded"><i class="bi bi-arrow-counterclockwise"></i></button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="<?= site_url('admin/customers/cancel/' . $r['customer_id']) ?>" method="post" class="d-inline" data-no-validate data-confirm="They drop to Basic restrictions. Their data is preserved." data-confirm-title="Cancel subscription?" data-confirm-btn="Yes, cancel" data-confirm-icon="warning">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger" title="Cancel subscription"><i class="bi bi-slash-circle"></i></button>
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
