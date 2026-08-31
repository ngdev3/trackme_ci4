<?php $g = $settings ?? []; $val = fn ($k, $d = '') => esc($g[$k] ?? $d); ?>
<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:760px;margin:0 auto;">
  <h3 style="font-weight:900;">APK Settings</h3>
  <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
  <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:20px;">
    <form method="post" action="<?= current_url(); ?>">
      <div class="form-group"><label>App Name</label><input class="form-control" name="app_name" value="<?= $val('app_name', 'C R Industries ERP'); ?>"></div>
      <div class="form-group"><label>Play Store URL</label><input class="form-control" name="play_store_url" value="<?= $val('play_store_url'); ?>"></div>
      <div class="form-group"><label>Keep newest N APK files</label><input class="form-control" type="number" name="keep_apk_files" value="<?= $val('keep_apk_files', '5'); ?>"></div>
      <div class="form-group"><label>Max APK size (MB)</label><input class="form-control" type="number" name="max_apk_mb" value="<?= $val('max_apk_mb', '150'); ?>"></div>
      <div class="checkbox"><label><input type="checkbox" name="website_section_enabled" value="1" <?= ($g['website_section_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>> Show download section on website</label></div>
      <div class="checkbox"><label><input type="checkbox" name="public_download_enabled" value="1" <?= ($g['public_download_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>> Allow public download</label></div>
      <button class="btn btn-primary" type="submit">Save</button>
      <a class="btn btn-default" href="<?= base_url('admin/app_update/listing'); ?>">Back</a>
    </form>
  </div>
</div></div></main>
