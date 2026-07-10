<?php
/** GST tax receipt rendered to PDF via dompdf. Self-contained HTML. */
$m = fn ($n) => '&#8377; ' . number_format((float) $n, 2);
$cur = $inv['currency'] ?? 'INR';
$dateStr = $inv['invoice_date'] ? date('d M Y', strtotime($inv['invoice_date'])) : date('d M Y');
$title = $inv['is_tax_invoice'] ? 'TAX INVOICE' : 'PAYMENT RECEIPT';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    body { color: #1f2a3d; font-size: 12px; margin: 0; }
    .wrap { padding: 24px 28px; }
    .head { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .head td { vertical-align: top; }
    .brand { font-size: 20px; font-weight: bold; color: #0f766e; }
    .muted { color: #66748c; }
    .doc-title { font-size: 18px; font-weight: bold; letter-spacing: 1px; text-align: right; color: #1f2a3d; }
    .meta { text-align: right; font-size: 11px; color: #66748c; }
    .status-pill { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
    .paid { background: #dcfce7; color: #166534; }
    .refunded { background: #fee2e2; color: #991b1b; }
    .party { width: 100%; border-collapse: collapse; margin: 8px 0 14px; }
    .party td { width: 50%; vertical-align: top; padding: 10px 12px; border: 1px solid #e4e9f2; }
    .party .label { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: #66748c; margin-bottom: 3px; }
    .party .name { font-weight: bold; font-size: 13px; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.items th { background: #0f766e; color: #fff; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; padding: 7px 8px; text-align: left; }
    table.items td { padding: 8px; border-bottom: 1px solid #e4e9f2; }
    table.items td.r, table.items th.r { text-align: right; }
    .totals { width: 46%; border-collapse: collapse; float: right; margin-top: 4px; }
    .totals td { padding: 5px 8px; }
    .totals td.r { text-align: right; }
    .totals .grand td { border-top: 2px solid #0f766e; font-weight: bold; font-size: 14px; color: #0f766e; }
    .foot { clear: both; padding-top: 60px; font-size: 10px; color: #66748c; }
    .foot .note { border-top: 1px solid #e4e9f2; padding-top: 8px; }
</style>
</head>
<body>
<div class="wrap">
    <table class="head">
        <tr>
            <td>
                <div class="brand"><?= esc($inv['seller']['name']) ?></div>
                <?php if ($inv['seller']['address']): ?><div class="muted"><?= nl2br(esc($inv['seller']['address'])) ?></div><?php endif; ?>
                <?php if ($inv['seller']['state']): ?><div class="muted">State: <?= esc($inv['seller']['state']) ?></div><?php endif; ?>
                <?php if ($inv['seller']['gstin']): ?><div class="muted">GSTIN: <strong><?= esc($inv['seller']['gstin']) ?></strong></div><?php endif; ?>
                <?php if ($inv['seller']['email']): ?><div class="muted"><?= esc($inv['seller']['email']) ?></div><?php endif; ?>
            </td>
            <td>
                <div class="doc-title"><?= $title ?></div>
                <div class="meta">
                    <strong>No:</strong> <?= esc($inv['invoice_no'] ?: '—') ?><br>
                    <strong>Date:</strong> <?= esc($dateStr) ?><br>
                    <strong>Order:</strong> <?= esc($inv['order_id']) ?><br>
                    <?php if ($inv['cf_payment_id']): ?><strong>Txn:</strong> <?= esc($inv['cf_payment_id']) ?><br><?php endif; ?>
                    <span class="status-pill <?= $inv['refunded'] ? 'refunded' : 'paid' ?>"><?= $inv['refunded'] ? 'REFUNDED' : 'PAID' ?></span>
                </div>
            </td>
        </tr>
    </table>

    <table class="party">
        <tr>
            <td>
                <div class="label">Billed To</div>
                <div class="name"><?= esc($inv['buyer']['name']) ?></div>
                <?php if ($inv['buyer']['address']): ?><div class="muted"><?= esc($inv['buyer']['address']) ?></div><?php endif; ?>
                <?php if ($inv['buyer']['state']): ?><div class="muted">State: <?= esc($inv['buyer']['state']) ?></div><?php endif; ?>
                <?php if ($inv['buyer']['gstin']): ?><div class="muted">GSTIN: <?= esc($inv['buyer']['gstin']) ?></div><?php endif; ?>
                <?php if ($inv['buyer']['email']): ?><div class="muted"><?= esc($inv['buyer']['email']) ?></div><?php endif; ?>
            </td>
            <td>
                <div class="label">Supply</div>
                <div class="muted">Place of supply: <?= esc($inv['buyer']['state'] ?: '—') ?></div>
                <div class="muted">Type: <?= $inv['intra'] ? 'Intra-state (CGST + SGST)' : 'Inter-state (IGST)' ?></div>
                <div class="muted">Payment: Cashfree (online)</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th>SAC</th>
                <th class="r">Taxable Value</th>
                <th class="r">GST %</th>
                <th class="r">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td><?= esc($inv['item']['name']) ?> <span class="muted">(<?= esc($inv['item']['cycle']) ?>)</span></td>
                <td><?= esc($inv['item']['hsn']) ?></td>
                <td class="r"><?= $m($inv['taxable']) ?></td>
                <td class="r"><?= esc(rtrim(rtrim(number_format($inv['rate'], 2), '0'), '.')) ?>%</td>
                <td class="r"><?= $m($inv['amount']) ?></td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Taxable Value</td><td class="r"><?= $m($inv['taxable']) ?></td></tr>
        <?php if ($inv['intra']): ?>
            <tr><td>CGST (<?= esc($inv['rate'] / 2) ?>%)</td><td class="r"><?= $m($inv['cgst']) ?></td></tr>
            <tr><td>SGST (<?= esc($inv['rate'] / 2) ?>%)</td><td class="r"><?= $m($inv['sgst']) ?></td></tr>
        <?php else: ?>
            <tr><td>IGST (<?= esc($inv['rate']) ?>%)</td><td class="r"><?= $m($inv['igst']) ?></td></tr>
        <?php endif; ?>
        <tr><td>Total GST</td><td class="r"><?= $m($inv['tax_total']) ?></td></tr>
        <tr class="grand"><td>Total (<?= esc($cur) ?>)</td><td class="r"><?= $m($inv['amount']) ?></td></tr>
    </table>

    <div class="foot">
        <div class="note">
            <?php if ($inv['is_tax_invoice']): ?>
                This is a computer-generated tax invoice and does not require a signature. GST is calculated as inclusive of the amount charged.
            <?php else: ?>
                This is a computer-generated payment receipt and does not require a signature.
            <?php endif; ?>
            <br>Thank you for your subscription.
        </div>
    </div>
</div>
</body>
</html>
