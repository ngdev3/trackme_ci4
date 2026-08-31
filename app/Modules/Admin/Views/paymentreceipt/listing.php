<?php
$selected_hsn = service('request')->getGet('hsn_code') ?? 'none';
$from = service('request')->getGet('from_billing_date') ?? '';
$to   = service('request')->getGet('to_billing_date') ?? '';
?>
<link href="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') ?>" rel="stylesheet">

<div style="padding:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0;">Payment Receipt</h1>
            <p style="color:#718096;font-size:12px;margin:4px 0 0;">Purchase from Farmer · firm <?= esc(fy()->firm_name ?? '') ?> · FY <?= esc(fy()->FY ?? '') ?></p>
        </div>
        <a href="<?= site_url('admin/payment_receipt/add') ?>" class="btn btn-primary"><i class="fa fa-plus"></i> Add Receipt</a>
    </div>

    <form method="get" action="<?= site_url('admin/payment_receipt/listing') ?>" style="background:#fff;border-radius:10px;padding:16px;margin-bottom:16px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <div class="row">
            <div class="col-sm-3"><label style="font-size:11px;font-weight:800;color:#516174;">From Date</label><input type="date" name="from_billing_date" class="form-control" value="<?= esc($from) ?>"></div>
            <div class="col-sm-3"><label style="font-size:11px;font-weight:800;color:#516174;">To Date</label><input type="date" name="to_billing_date" class="form-control" value="<?= esc($to) ?>"></div>
            <div class="col-sm-3"><label style="font-size:11px;font-weight:800;color:#516174;">Product (HSN)</label>
                <select name="hsn_code" class="form-control"><option value="none">All Products</option>
                    <?php foreach ($hsn_list as $h): ?><option value="<?= esc($h->hsn_code) ?>" <?= ($selected_hsn == $h->hsn_code) ? 'selected' : '' ?>><?= esc($h->product_name ?? $h->hsn_code) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3" style="display:flex;align-items:flex-end;gap:8px;">
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                <a href="<?= site_url('admin/payment_receipt/listing') ?>" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</a>
            </div>
        </div>
    </form>

    <div style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <table id="pr-grid" class="table table-striped table-hover" style="width:100%;">
            <thead><tr><th>#</th><th>Receipt</th><th>Date</th><th>Party</th><th>Product</th><th>Rate</th><th>Qty</th><th>Total</th><th>Status</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script src="<?= base_url('assets/global/plugins/datatables/media/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') ?>"></script>
<script>
    (function () {
        jQuery('#pr-grid').DataTable({
            processing: true, serverSide: true, order: [],
            ajax: { url: "<?= site_url('admin/payment_receipt/view_all') ?>" + window.location.search, type: 'POST' },
            columnDefs: [{ orderable: false, targets: [0, 3, 4, 5, 6, 7, 8] }]
        });
    })();
</script>
