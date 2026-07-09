<?php /** Ledger statement with running balance. Rendered inside layout.php. */ ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="card-title mb-0"><i class="bi bi-file-text me-1"></i> <?= esc($ledger['name']) ?></h3>
            <small class="text-muted"><?= esc($ledger['group_name'] ?? '') ?></small>
        </div>
        <a href="<?= site_url('accounting/ledgers') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Ledgers</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr>
                    <th>Date</th><th>Voucher</th><th>Narration</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th>
                </tr></thead>
                <tbody>
                    <tr class="table-light">
                        <td colspan="5" class="fw-semibold">Opening Balance</td>
                        <td class="text-end fw-semibold"><?= esc(number_format((float) $ledger['opening_balance'], 2)) ?> <?= esc($ledger['opening_type']) ?></td>
                    </tr>
                    <?php if (empty($entries)): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-3">No transactions.</td></tr>
                    <?php else: foreach ($entries as $e): ?>
                        <tr>
                            <td><small><?= esc(date('d-m-Y', strtotime($e['date']))) ?></small></td>
                            <td><small><span class="badge text-bg-light border text-capitalize"><?= esc($e['voucher_type']) ?></span> <?= esc($e['voucher_no']) ?></small></td>
                            <td><small><?= esc($e['narration'] ?: '—') ?></small></td>
                            <td class="text-end"><?= (float) $e['dr_amount'] > 0 ? esc(number_format((float) $e['dr_amount'], 2)) : '' ?></td>
                            <td class="text-end"><?= (float) $e['cr_amount'] > 0 ? esc(number_format((float) $e['cr_amount'], 2)) : '' ?></td>
                            <td class="text-end"><?= esc(number_format(abs($e['balance']), 2)) ?> <?= $e['balance'] >= 0 ? 'Dr' : 'Cr' ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="5" class="text-end">Closing Balance</td>
                        <td class="text-end"><?= esc(number_format(abs($closing), 2)) ?> <?= $closing >= 0 ? 'Dr' : 'Cr' ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
