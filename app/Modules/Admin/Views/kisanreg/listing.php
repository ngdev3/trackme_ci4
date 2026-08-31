<link href="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') ?>" rel="stylesheet">
<div style="padding:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div><h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0;">Kisan Registration</h1>
        <p style="color:#718096;font-size:12px;margin:4px 0 0;">Registered farmers · firm <?= esc(fy()->firm_name ?? '') ?> · FY <?= esc(fy()->FY ?? '') ?></p></div>
        <a href="<?= site_url('admin/Kisanreg/add') ?>" class="btn btn-primary"><i class="fa fa-plus"></i> Register Farmer</a>
    </div>
    <div class="table-responsive" style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <table id="kr-grid" class="table table-striped table-hover" style="width:100%;">
            <thead><tr><th>#</th><th>Farmer</th><th>Farmer ID</th><th>Reg Date</th><th>Quantity</th><th>Left Qty</th><th>Status</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<script src="<?= base_url('assets/global/plugins/datatables/media/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') ?>"></script>
<script>jQuery('#kr-grid').DataTable({processing:true,serverSide:true,order:[],ajax:{url:"<?= site_url('admin/Kisanreg/view_all') ?>",type:'POST'},columnDefs:[{orderable:false,targets:'_all'}]});</script>
