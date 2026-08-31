<link href="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') ?>" rel="stylesheet">
<div style="padding:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div><h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0;">Purchase</h1>
        <p style="color:#718096;font-size:12px;margin:4px 0 0;">Purchase bills · firm <?= esc(fy()->firm_name ?? '') ?></p></div>
        <a href="<?= site_url('admin/purchase_module/add') ?>" class="btn btn-primary"><i class="fa fa-plus"></i> Add Purchase</a>
    </div>
    <div class="table-responsive" style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <table id="pu-grid" class="table table-striped table-hover" style="width:100%;">
            <thead><tr><th>#</th><th>Party</th><th>Invoice</th><th>Date</th><th>Product</th><th>HSN</th><th>Weight</th><th>Rate</th><th>Amount</th><th>Vehicle</th><th>Status</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<script src="<?= base_url('assets/global/plugins/datatables/media/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') ?>"></script>
<script>jQuery('#pu-grid').DataTable({processing:true,serverSide:true,order:[],ajax:{url:"<?= site_url('admin/purchase_module/view_all') ?>",type:'POST'},columnDefs:[{orderable:false,targets:'_all'}]});</script>
