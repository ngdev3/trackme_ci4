<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:760px;margin:0 auto;">
  <h3 style="font-weight:900;">Upload Android Build</h3>
  <?php if (! empty($error)): ?><div class="alert alert-danger"><?= esc($error); ?></div><?php endif; ?>
  <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:20px;">
    <form method="post" action="<?= current_url(); ?>" enctype="multipart/form-data">
      <div class="form-group"><label>Version Name *</label><input class="form-control" name="version_name" placeholder="e.g. 2.4.1" required></div>
      <div class="form-group"><label>Version Code * (integer, unique)</label><input class="form-control" type="number" name="version_code" min="1" required></div>
      <div class="form-group"><label>Release Notes</label><textarea class="form-control" name="release_notes" rows="4"></textarea></div>
      <div class="form-group"><label>APK File * (max <?= esc($max_mb ?? '150'); ?> MB)</label><input class="form-control" type="file" name="apk_file" accept=".apk" required></div>
      <div class="checkbox"><label><input type="checkbox" name="mark_latest" value="1"> Mark this build as the latest</label></div>
      <button class="btn btn-primary" type="submit"><i class="fa fa-upload"></i> Upload</button>
      <a class="btn btn-default" href="<?= base_url('admin/app_update/listing'); ?>">Cancel</a>
    </form>
  </div>
</div></div></main>
