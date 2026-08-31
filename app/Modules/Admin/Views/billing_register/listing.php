<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css"/>
<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:1480px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
        <h3 style="font-weight:900;margin:0;">Billing Register</h3>
        <div style="background:linear-gradient(135deg,#1f9d70,#0c7048);color:#fff;padding:10px 18px;border-radius:12px;font-weight:900;">
            Total: ₹ <?= number_format((float) ($total_amount ?? 0), 2); ?>
        </div>
    </div>
    <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:16px;">
        <table class="table table-striped table-bordered" id="employee-grid-buyer" style="width:100%">
            <thead><tr><th>#</th><th>Date</th><th>Type</th><th>Account Type</th><th>Party</th><th>Khata #</th><th>Challan #</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
        </table>
    </div>
</div></div></main>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script>
    var BASE = "<?= base_url(); ?>", QS = "<?= esc($_SERVER['QUERY_STRING'] ?? '', 'js'); ?>";
    $('#employee-grid-buyer').dataTable({
        bStateSave:false, processing:true, serverSide:true, responsive:true,
        pageLength:25, pagingType:"bootstrap_full_number",
        ajax:{ url: BASE+"admin/billing_register/listing_data?"+QS, type:"post", error:function(){$("#employee-grid_processing").hide();} }, order:[]
    });
    $(document).on('click','.br-del',function(){ if(!confirm('Delete this billing entry?'))return; $.post(BASE+"admin/billing_register/delete",{id:$(this).data('id')},function(){location.reload();},'json'); });
</script>
