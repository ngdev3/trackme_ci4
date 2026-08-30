<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css"/>
<?php
$s = $summary ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'with_gst' => 0, 'farmers' => 0];
$cur = $_GET['status'] ?? '';
?>
<style>
    .acc-shell { max-width: 1480px; margin: 0 auto; }
    .acc-hero { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; padding:20px 24px; margin-bottom:14px; border-radius:14px; color:#fff; background:linear-gradient(125deg,#0f2748,#1d4ed8 58%,#0c7a48); box-shadow:0 18px 42px rgba(16,32,72,.25); }
    .acc-hero h1 { margin:0; font-size:22px; font-weight:900; } .acc-hero small { display:block; font-size:12px; font-weight:700; color:rgba(235,242,255,.85); margin-top:3px; }
    .acc-kpis { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; margin-bottom:14px; }
    .acc-kpi { padding:14px 15px; border:1px solid #e3e9f2; border-radius:13px; background:#fff; box-shadow:0 10px 26px rgba(24,36,60,.06); }
    .acc-kpi span { display:block; font-size:10.5px; font-weight:800; text-transform:uppercase; color:#7a8aa0; }
    .acc-kpi strong { display:block; margin-top:3px; font-size:20px; font-weight:900; color:#18243c; }
    .acc-card { border:1px solid #e3e9f2; border-radius:14px; background:#fff; box-shadow:0 12px 30px rgba(24,36,60,.06); padding:16px 18px; }
    table#employee-grid-buyer thead th { font-size:11px; text-transform:uppercase; color:#64748b; font-weight:800; background:#f8fbff; }
    @media(max-width:1100px){ .acc-kpis{ grid-template-columns:repeat(2,1fr);} }
</style>

<main class="main-content">
    <div id="mainContent"><div class="container-fluid acc-shell">
        <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>

        <section class="acc-hero">
            <div><h1>Account Master <small>Trade parties &amp; farmers — the ledger accounts</small></h1></div>
            <a href="<?= base_url('admin/account_name/add'); ?>" class="btn btn-lg" style="background:#fff;color:#1740b5;font-weight:800;"><i class="fa fa-plus"></i> Add Account</a>
        </section>

        <div class="acc-kpis">
            <div class="acc-kpi"><span>Total</span><strong><?= number_format((int)$s['total']); ?></strong></div>
            <div class="acc-kpi"><span>Active</span><strong><?= number_format((int)$s['active']); ?></strong></div>
            <div class="acc-kpi"><span>Inactive</span><strong><?= number_format((int)$s['inactive']); ?></strong></div>
            <div class="acc-kpi"><span>With GST</span><strong><?= number_format((int)$s['with_gst']); ?></strong></div>
            <div class="acc-kpi"><span>Farmers (Kisan)</span><strong><?= number_format((int)$s['farmers']); ?></strong></div>
        </div>

        <div class="acc-card">
            <table class="table table-striped table-bordered" id="employee-grid-buyer" style="width:100%">
                <thead><tr>
                    <th>#</th><th>ID</th><th>Name</th><th>Contact</th><th>GST</th><th>Source</th><th>Status</th><th>Action</th>
                </tr></thead>
            </table>
        </div>
    </div></div>
</main>

<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script>
    var BASE = "<?= base_url(); ?>", QS = "<?= esc($_SERVER['QUERY_STRING'] ?? '', 'js'); ?>";
    $('#employee-grid-buyer').dataTable({
        bStateSave:false, processing:true, serverSide:true, responsive:true,
        columns:[{orderable:false},{orderable:true},{orderable:true},{orderable:false},{orderable:false},{orderable:false},{orderable:false},{orderable:false}],
        lengthMenu:[[25,50,-1],[25,50,"All"]], pageLength:25, pagingType:"bootstrap_full_number",
        language:{search:"Search:",lengthMenu:"_MENU_ Records",paginate:{previous:"Prev",next:"Next",last:"Last",first:"First"}},
        ajax:{ url: BASE+"admin/account_name/view_all?"+QS, type:"post", error:function(){ $("#employee-grid_processing").hide(); } },
        order:[]
    });
    $(document).on('click','.acc-toggle',function(){
        var id=$(this).data('id'), st=$(this).data('status');
        $.post(BASE+"admin/account_name/updateStatus",{id:id,status:st},function(){location.reload();},'json');
    });
    $(document).on('click','.acc-del',function(){
        if(!confirm('Delete this account? (only allowed if it has no cash-book entries)')) return;
        var id=$(this).data('id');
        $.post(BASE+"admin/account_name/soft_delete",{id:id},function(r){ if(r&&r.status==='success'){location.reload();} else {alert((r&&r.message)||'Delete failed');} },'json');
    });
</script>
