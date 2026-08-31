<link rel="stylesheet" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css"/>
<?php $cur = $_GET['status'] ?? ''; ?>
<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:1320px;margin:0 auto;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
    <h3 style="font-weight:900;margin:0;">Tasks</h3>
    <a href="<?= base_url('task/task/add'); ?>" class="btn btn-primary"><i class="fa fa-plus"></i> Add Task</a>
  </div>
  <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>
  <div style="margin-bottom:12px;">
    <?php foreach (['' => 'All', 'open' => 'Open', 'in_progress' => 'In Progress', 'done' => 'Done', 'closed' => 'Closed'] as $k => $lbl): ?>
      <a href="<?= base_url('task/task' . ($k ? '?status=' . $k : '')); ?>" class="btn btn-sm <?= $cur === $k ? 'btn-primary' : 'btn-default'; ?>"><?= esc($lbl); ?></a>
    <?php endforeach; ?>
  </div>
  <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:16px;">
    <table class="table table-striped table-bordered" id="employee-grid-buyer" style="width:100%">
      <thead><tr><th>#</th><th>Title</th><th>Status</th><th>Priority</th><th>Assignee</th><th>Comments</th><th>Created</th><th>Action</th></tr></thead>
    </table>
  </div>
</div></div></main>
<script src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script>
var BASE = "<?= base_url(); ?>", QS = "<?= esc($_SERVER['QUERY_STRING'] ?? '', 'js'); ?>";
$('#employee-grid-buyer').dataTable({bStateSave:false,processing:true,serverSide:true,responsive:true,pageLength:25,pagingType:"bootstrap_full_number",
 ajax:{url:BASE+"task/task/view_all?"+QS,type:"post",error:function(){$("#employee-grid_processing").hide();}},order:[]});
$(document).on('click','.tsk-del',function(){if(!confirm('Delete this task?'))return;
 $.post(BASE+"task/task/delete",{id:$(this).data('id')},function(){location.reload();},'json');});
</script>
