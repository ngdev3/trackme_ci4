<link rel="stylesheet" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css"/>
<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:1320px;margin:0 auto;">
    <h3 style="font-weight:900;">Device Management <small style="color:#888;"><?= number_format((int)($count ?? 0)); ?> devices</small></h3>
    <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:16px;margin-top:12px;">
        <table class="table table-striped table-bordered" id="employee-grid-buyer" style="width:100%">
            <thead><tr><th>#</th><th>Device</th><th>User</th><th>Platform</th><th>Status</th><th>Last Seen</th><th>Action</th></tr></thead>
        </table>
    </div>
</div></div></main>
<script src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script>
var BASE="<?= base_url(); ?>";
$('#employee-grid-buyer').dataTable({bStateSave:false,processing:true,serverSide:true,responsive:true,pageLength:25,pagingType:"bootstrap_full_number",
 ajax:{url:BASE+"admin/device/view_all",type:"post",error:function(){$("#employee-grid_processing").hide();}},order:[]});
$(document).on('click','.dv-toggle',function(){$.post(BASE+"admin/device/update_status",{id:$(this).data('id'),status:$(this).data('status')},function(){location.reload();},'json');});
$(document).on('click','.dv-del',function(){if(!confirm('Delete this device?'))return;$.post(BASE+"admin/device/delete",{id:$(this).data('id')},function(){location.reload();},'json');});
</script>
