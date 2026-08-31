<?php
$totalCredited = 0;
foreach ($logs as $l) {
    if (strtolower($l->status) === 'success') {
        $totalCredited += (float) $l->salary_amount;
    }
}
?>
<link href="<?= base_url('assets/global/css/components-rounded.css') ?>" id="style_components" rel="stylesheet" type="text/css"/>

<main class="main-content bgc-grey-100">
    <div id="mainContent">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="bgc-white bd bdrs-3 p-20 mB-20">

                        <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
                        <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>

                        <div class="peers ai-c jc-sb fxw-nw mB-20" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                            <div>
                                <h5 class="c-grey-900" style="margin:0 0 4px;">Salary Credit History</h5>
                                <small class="c-grey-600">
                                    <?= count($logs); ?> entries &middot; Total credited (success):
                                    <strong>&#8377; <?= number_format($totalCredited, 2); ?></strong>
                                </small>
                            </div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <a href="<?= site_url('admin/salary_module/listing') ?>" class="btn btn-secondary btn-sm" style="background:#6c757d;color:#fff;"><i class="fa fa-arrow-left"></i> Back to Salaries</a>
                                <a href="<?= site_url('admin/salary_module/run_now') ?>" class="btn btn-success btn-sm"
                                    onclick="return crConfirmNav(this, 'Credit this month\'s salary now for all active employees? Already-credited employees are skipped automatically.');">
                                    <i class="fa fa-money"></i> Run Salary Now
                                </a>
                            </div>
                        </div>

                        <table class="table table-striped table-bordered" style="text-align:center;">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Month</th>
                                    <th>Credit Date</th>
                                    <th class="ta-l">Employee</th>
                                    <th class="ta-l">Credited Account</th>
                                    <th class="ta-l">Firm / Template</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Logged At</th>
                                    <th class="ta-l">Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)) { ?>
                                    <tr><td colspan="10" class="c-grey-600" style="font-style:italic;">No salary has been credited yet.</td></tr>
                                <?php } else { $i = 0; foreach ($logs as $l) { $i++;
                                    $isOk = strtolower($l->status) === 'success';
                                    $emp = trim(($l->first_name ?? '') . ' ' . ($l->last_name ?? ''));
                                    if ($emp === '') { $emp = 'User #' . $l->user_id; }
                                ?>
                                    <tr>
                                        <td><?= $i; ?></td>
                                        <td><?= esc($l->execution_month_year); ?></td>
                                        <td><?= esc($l->execution_date); ?></td>
                                        <td style="text-align:left;"><?= esc($emp); ?> <small class="c-grey-600">(#<?= (int) $l->user_id; ?>)</small></td>
                                        <td style="text-align:left;"><?= esc($l->account_name ?? ''); ?> <small class="c-grey-600">(#<?= (int) $l->account_id; ?>)</small></td>
                                        <td style="text-align:left;"><?= esc($l->template_name ?? ''); ?></td>
                                        <td style="text-align:right;">&#8377; <?= number_format((float) $l->salary_amount, 2); ?></td>
                                        <td>
                                            <span class="badge" style="padding:4px 9px;border-radius:999px;font-weight:700;color:#fff;background:<?= $isOk ? '#21a366' : '#d33'; ?>;">
                                                <?= esc($l->status); ?>
                                            </span>
                                        </td>
                                        <td><?= esc($l->created_at); ?></td>
                                        <td style="text-align:left;"><small class="c-grey-600"><?= esc($l->message ?? ''); ?></small></td>
                                    </tr>
                                <?php } } ?>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
