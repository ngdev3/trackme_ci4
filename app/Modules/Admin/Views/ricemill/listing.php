<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css"/>
<?php $c = $counts ?? ['All' => 0, 'New' => 0, 'Contacted' => 0, 'Converted' => 0, 'Rejected' => 0]; $cur = $_GET['status'] ?? ''; ?>
<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:1320px;margin:0 auto;">
    <h3 style="font-weight:900;">Rice Mill Website Inquiries</h3>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin:12px 0;">
        <?php foreach (['' => 'All', 'New' => 'New', 'Contacted' => 'Contacted', 'Converted' => 'Converted', 'Rejected' => 'Rejected'] as $k => $lbl): $key = $k === '' ? 'All' : $k; ?>
            <a href="<?= base_url('admin/ricemill_inquiry/listing' . ($k ? '?status=' . $k : '')); ?>" class="btn btn-sm <?= $cur === $k ? 'btn-primary' : 'btn-default'; ?>"><?= esc($lbl); ?> <span class="badge"><?= number_format((int) ($c[$key] ?? 0)); ?></span></a>
        <?php endforeach; ?>
    </div>
    <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:16px;">
        <table class="table table-striped table-bordered" id="employee-grid-buyer" style="width:100%">
            <thead><tr><th>#</th><th>Name / Mobile</th><th>Address</th><th>Product</th><th>Status</th><th>When</th><th>Action</th></tr></thead>
        </table>
    </div>
</div></div></main>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script>
    var BASE = "<?= base_url(); ?>", QS = "<?= esc($_SERVER['QUERY_STRING'] ?? '', 'js'); ?>";
    $('#employee-grid-buyer').dataTable({
        bStateSave:false, processing:true, serverSide:true, responsive:true,
        columns:[{orderable:false},{orderable:false},{orderable:false},{orderable:false},{orderable:false},{orderable:false},{orderable:false}],
        pageLength:25, pagingType:"bootstrap_full_number",
        ajax:{ url: BASE+"admin/ricemill_inquiry/view_all?"+QS, type:"post", error:function(){$("#employee-grid_processing").hide();} }, order:[]
    });
    $(document).on('change','.ri-status',function(){ $.post(BASE+"admin/ricemill_inquiry/update_status",{id:$(this).data('id'),status:$(this).val()},function(){},'json'); });
    $(document).on('click','.ri-del',function(){ if(!confirm('Delete this inquiry?'))return; $.post(BASE+"admin/ricemill_inquiry/delete",{id:$(this).data('id')},function(){location.reload();},'json'); });
</script>
