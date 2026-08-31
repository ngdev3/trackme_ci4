<link rel="stylesheet" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css"/>
<?php $s = $stats ?? []; $latest = $s['latest'] ?? null; ?>
<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:1480px;margin:0 auto;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
    <h3 style="font-weight:900;margin:0;">APK Manager</h3>
    <div>
      <a href="<?= base_url('admin/app_update/upload'); ?>" class="btn btn-primary"><i class="fa fa-upload"></i> Upload Build</a>
      <a href="<?= base_url('admin/app_update/settings'); ?>" class="btn btn-default"><i class="fa fa-cog"></i> Settings</a>
      <a href="<?= base_url('admin/app_update/logs'); ?>" class="btn btn-default"><i class="fa fa-list"></i> Logs</a>
    </div>
  </div>
  <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px;">
    <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:14px;"><small>Total Builds</small><h3 style="margin:4px 0 0;font-weight:900;"><?= (int) ($s['total_versions'] ?? 0); ?></h3></div>
    <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:14px;"><small>Total Downloads</small><h3 style="margin:4px 0 0;font-weight:900;"><?= (int) ($s['total_downloads'] ?? 0); ?></h3></div>
    <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:14px;"><small>Latest</small><h3 style="margin:4px 0 0;font-weight:900;"><?= $latest ? esc($latest->version_name) : '—'; ?></h3></div>
    <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:14px;"><small>Force Update</small><h3 style="margin:4px 0 0;font-weight:900;"><?= ! empty($s['force_update']) ? 'ON' : 'off'; ?></h3></div>
  </div>
  <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:16px;">
    <table class="table table-striped table-bordered" id="employee-grid-buyer" style="width:100%">
      <thead><tr><th>#</th><th>Version</th><th>Size</th><th>Notes</th><th>Flags</th><th>Status</th><th>DL</th><th>By</th><th>Action</th></tr></thead>
    </table>
  </div>
</div></div></main>
<script src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script>
var BASE = "<?= base_url(); ?>";
$('#employee-grid-buyer').dataTable({bStateSave:false,processing:true,serverSide:true,responsive:true,pageLength:25,pagingType:"bootstrap_full_number",
 ajax:{url:BASE+"admin/app_update/versions_data",type:"post",error:function(){$("#employee-grid_processing").hide();}},order:[]});
function apkPost(u,d){$.post(BASE+u,d,function(){location.reload();},'json');}
$(document).on('click','.apk-latest',function(){apkPost("admin/app_update/mark_latest",{id:$(this).data('id')});});
$(document).on('click','.apk-toggle',function(){apkPost("admin/app_update/toggle_status",{id:$(this).data('id')});});
$(document).on('click','.apk-flag',function(){apkPost("admin/app_update/flag_toggle",{id:$(this).data('id'),flag:$(this).data('flag')});});
$(document).on('click','.apk-del',function(){if(confirm('Delete this build?'))apkPost("admin/app_update/delete",{id:$(this).data('id')});});
</script>
