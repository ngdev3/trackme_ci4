<link rel="stylesheet" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css"/>
<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:1320px;margin:0 auto;">
    <h3 style="font-weight:900;">Page Traffic</h3>
    <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:16px;margin-top:12px;">
        <table class="table table-striped table-bordered" id="employee-grid-buyer" style="width:100%">
            <thead><tr><th>#</th><th>User</th><th>URL</th><th>Date</th><th>IP</th></tr></thead>
        </table>
    </div>
</div></div></main>
<script src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script>
var BASE="<?= base_url(); ?>", QS="<?= esc($_SERVER['QUERY_STRING'] ?? '', 'js'); ?>";
$('#employee-grid-buyer').dataTable({bStateSave:false,processing:true,serverSide:true,responsive:true,pageLength:25,pagingType:"bootstrap_full_number",
 ajax:{url:BASE+"admin/traffic/view_all?"+QS,type:"post",error:function(){$("#employee-grid_processing").hide();}},order:[]});
</script>
