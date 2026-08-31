<link rel="stylesheet" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css"/>
<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:1480px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <h3 style="font-weight:900;margin:0;">Letter Pad</h3>
        <a href="<?= base_url('admin/letter_pad/add'); ?>" class="btn btn-primary"><i class="fa fa-plus"></i> Create Letter</a>
    </div>
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>
    <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:16px;">
        <table class="table table-striped table-bordered" id="employee-grid-buyer" style="width:100%">
            <thead><tr><th>#</th><th>Letter No</th><th>Title / Subject</th><th>Firm</th><th>Date</th><th>By</th><th>Action</th></tr></thead>
        </table>
    </div>
</div></div></main>
<script src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script>
var BASE="<?= base_url(); ?>";
$('#employee-grid-buyer').dataTable({bStateSave:false,processing:true,serverSide:true,responsive:true,pageLength:25,pagingType:"bootstrap_full_number",
 ajax:{url:BASE+"admin/letter_pad/listing_data",type:"post",error:function(){$("#employee-grid_processing").hide();}},order:[]});
$(document).on('click','.lp-del',function(){if(!confirm('Delete this letter?'))return;
 $.post(BASE+"admin/letter_pad/delete",{id:$(this).data('id')},function(){location.reload();},'json');});
</script>
