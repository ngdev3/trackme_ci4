<?php /** Voucher detail. Rendered inside layout.php. */ ?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="bi bi-receipt me-1"></i> <span class="text-capitalize"><?= esc($voucher['voucher_type']) ?></span> Voucher</h3>
                <a href="<?= site_url('accounting/vouchers') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Day Book</a>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4"><small class="text-muted">Voucher No.</small><div class="fw-semibold"><?= esc($voucher['voucher_no']) ?></div></div>
                    <div class="col-md-4"><small class="text-muted">Date</small><div class="fw-semibold"><?= esc(date('d-m-Y', strtotime($voucher['date']))) ?></div></div>
                    <div class="col-md-4"><small class="text-muted">Amount</small><div class="fw-semibold"><?= esc(number_format((float) $voucher['amount'], 2)) ?></div></div>
                </div>
                <?php if (! empty($voucher['narration'])): ?>
                    <p class="text-muted mb-3"><i class="bi bi-chat-left-text me-1"></i><?= esc($voucher['narration']) ?></p>
                <?php endif; ?>
                <table class="table table-bordered align-middle">
                    <thead class="table-light"><tr><th>Ledger</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
                    <tbody>
                    <?php foreach ($entries as $e): ?>
                        <tr>
                            <td><?= esc($e['ledger_name'] ?? '—') ?></td>
                            <td class="text-end"><?= (float) $e['dr_amount'] > 0 ? esc(number_format((float) $e['dr_amount'], 2)) : '' ?></td>
                            <td class="text-end"><?= (float) $e['cr_amount'] > 0 ? esc(number_format((float) $e['cr_amount'], 2)) : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="fw-bold"><tr>
                        <td class="text-end">Total</td>
                        <td class="text-end"><?= esc(number_format(array_sum(array_column($entries, 'dr_amount')), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format(array_sum(array_column($entries, 'cr_amount')), 2)) ?></td>
                    </tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
