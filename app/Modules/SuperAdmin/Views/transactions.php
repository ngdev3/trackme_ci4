<?php
/** Super Admin — all payment transactions. Rendered inside layout.php.
 * Shared list design (cust-* — canonical Customers look). */
$statusMeta = [
    'paid'     => ['Paid', 'success'],
    'created'  => ['Pending', 'secondary'],
    'failed'   => ['Failed', 'danger'],
    'refunded' => ['Refunded', 'warning'],
];
$filters = ['' => 'All', 'paid' => 'Paid', 'created' => 'Pending', 'failed' => 'Failed', 'refunded' => 'Refunded'];
?>
<div class="cust-page">

    <!-- Hero -->
    <section class="cust-hero">
        <div>
            <h4 class="cust-title">Transactions</h4>
            <p class="cust-subtitle">Every subscription payment across all customers — revenue, status and receipts.</p>
        </div>
    </section>

    <!-- Snapshot stat cards -->
    <section class="cust-snap-grid">
        <div class="cust-snap"><span class="cust-snap-ic ic-blue"><i class="bi bi-collection-fill"></i></span>
            <div><p class="cust-snap-label">Total</p><p class="cust-snap-value"><?= number_format((int) ($stats['total'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-green"><i class="bi bi-cash-stack"></i></span>
            <div><p class="cust-snap-label">Revenue (paid)</p><p class="cust-snap-value">&#8377;<?= number_format((float) ($stats['revenue'] ?? 0), 2) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-green"><i class="bi bi-check-circle-fill"></i></span>
            <div><p class="cust-snap-label">Paid</p><p class="cust-snap-value"><?= number_format((int) ($stats['paid'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-red"><i class="bi bi-x-circle-fill"></i></span>
            <div><p class="cust-snap-label">Failed</p><p class="cust-snap-value"><?= number_format((int) ($stats['failed'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-amber"><i class="bi bi-arrow-counterclockwise"></i></span>
            <div><p class="cust-snap-label">Refunded</p><p class="cust-snap-value"><?= number_format((int) ($stats['refunded'] ?? 0)) ?></p></div></div>
    </section>

    <!-- Table panel -->
    <section class="cust-panel cust-table-panel">
        <div class="cust-toolbar">
            <div>
                <h5 class="cust-table-title">Payment Records</h5>
                <p class="cust-table-note">Download tax receipts, mark refunds, or cancel a subscription.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if (($q ?? '') !== ''): ?><span class="cust-search-tag"><i class="bi bi-search"></i> “<?= esc($q) ?>”</span><?php endif; ?>
                <span class="cust-total-tag"><i class="bi bi-receipt"></i> <?= number_format((int) ($stats['total'] ?? 0)) ?> total</span>
            </div>
        </div>

        <div class="cust-tabletools">
            <form method="get" class="cust-len">
                <?php if (($q ?? '') !== ''): ?><input type="hidden" name="q" value="<?= esc($q, 'attr') ?>"><?php endif; ?>
                <label for="txStatus">Status</label>
                <select name="status" id="txStatus" class="cust-len-select" data-autosubmit>
                    <?php foreach ($filters as $k => $v): ?>
                        <option value="<?= esc($k, 'attr') ?>" <?= $status === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <form method="get" class="cust-find" role="search">
                <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= esc($status, 'attr') ?>"><?php endif; ?>
                <label for="txSearch">Search:</label>
                <div class="cust-find-box">
                    <i class="bi bi-search"></i>
                    <input type="search" id="txSearch" name="q" value="<?= esc($q, 'attr') ?>" placeholder="Order / invoice / customer…" autocomplete="off">
                    <?php if (($q ?? '') !== ''): ?><a href="<?= site_url('admin/transactions' . ($status !== '' ? '?status=' . urlencode($status) : '')) ?>" class="cust-find-clear" title="Clear"><i class="bi bi-x-lg"></i></a><?php endif; ?>
                </div>
            </form>
        </div>

        <div class="cust-table-wrap">
            <table class="cust-table">
                <thead><tr>
                    <th class="text-start" style="width:150px">Date</th>
                    <th class="text-start" style="width:230px">Customer</th>
                    <th class="text-start" style="width:150px">Plan</th>
                    <th class="text-end" style="width:120px">Amount</th>
                    <th class="text-center" style="width:110px">Status</th>
                    <th class="text-start" style="width:130px">Invoice</th>
                    <th class="text-end" style="width:140px">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="cust-empty"><i class="bi bi-receipt"></i><div>No transactions found<?= ($q ?? '') !== '' ? ' for “' . esc($q) . '”' : '' ?>.</div></td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <?php [$label, $color] = $statusMeta[$r['status']] ?? ['—', 'secondary']; ?>
                    <tr>
                        <td class="text-start"><span class="cust-muted text-nowrap"><?= esc(date('d M Y, H:i', strtotime($r['invoice_date'] ?: $r['created_at']))) ?></span></td>
                        <td class="text-start">
                            <a href="<?= site_url('admin/customers/subscription/' . $r['customer_id']) ?>" class="text-decoration-none" title="View subscription & full flow">
                                <strong><?= esc($r['customer_name'] ?? ('#' . $r['customer_id'])) ?></strong>
                            </a>
                            <div class="small cust-muted"><?= esc($r['customer_email'] ?? '') ?></div>
                            <div class="small cust-muted"><?= esc($r['order_id']) ?></div>
                        </td>
                        <td class="text-start">
                            <?= esc($r['plan_name'] ?? '—') ?>
                            <?php $gw = $r['gateway'] ?? 'cashfree'; ?>
                            <span class="badge rounded-pill text-bg-light border ms-1" title="Payment gateway">
                                <i class="bi <?= $gw === 'googleplay' ? 'bi-google-play' : 'bi-credit-card' ?> me-1"></i><?= $gw === 'googleplay' ? 'Play' : 'Cashfree' ?>
                            </span>
                        </td>
                        <td class="text-end fw-bold">&#8377;<?= esc(number_format((float) $r['amount'], 2)) ?></td>
                        <td class="text-center"><span class="badge text-bg-<?= esc($color) ?>"><?= esc($label) ?></span></td>
                        <td class="text-start"><span class="cust-muted"><?= esc($r['invoice_no'] ?: '—') ?></span></td>
                        <td class="text-end">
                            <div class="cust-row-actions">
                                <?php if ((int) $r['activated'] === 1): ?>
                                    <a href="<?= site_url('subscription/receipt/' . $r['order_id']) ?>" target="_blank" rel="noopener" class="cust-act act-mail" title="Tax receipt"><i class="bi bi-download"></i></a>
                                <?php endif; ?>
                                <?php if ($r['status'] === 'paid'): ?>
                                    <?php $refundWhere = ($r['gateway'] ?? 'cashfree') === 'googleplay' ? 'Google Play Console' : 'Cashfree dashboard'; ?>
                                    <form action="<?= site_url('admin/transactions/refund/' . $r['id']) ?>" method="post" data-no-validate data-confirm="Refund the money in the <?= $refundWhere ?> separately." data-confirm-title="Mark as refunded?" data-confirm-btn="Yes, mark refunded" data-confirm-icon="warning">
                                        <?= csrf_field() ?>
                                        <button class="cust-act act-reset" title="Mark refunded"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form action="<?= site_url('admin/customers/cancel/' . $r['customer_id']) ?>" method="post" data-no-validate data-confirm="They drop to Basic restrictions. Their data is preserved." data-confirm-title="Cancel subscription?" data-confirm-btn="Yes, cancel" data-confirm-icon="warning">
                                    <?= csrf_field() ?>
                                    <button class="cust-act act-purge" title="Cancel subscription"><i class="bi bi-slash-circle"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
