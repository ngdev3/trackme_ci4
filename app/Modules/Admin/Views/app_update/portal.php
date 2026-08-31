<?php $l = $latest ?? null; ?>
<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:640px;margin:0 auto;text-align:center;">
  <h3 style="font-weight:900;">Install the App</h3>
  <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:30px;margin-top:16px;">
    <?php if ($l): ?>
      <p style="font-size:18px;font-weight:800;">Version <?= esc($l->version_name); ?> (code <?= (int) $l->version_code; ?>)</p>
      <p><?= nl2br(esc($l->release_notes ?? '')); ?></p>
      <a class="btn btn-lg btn-success" href="<?= base_url('admin/app_update/download/' . (int) $l->id); ?>"><i class="fa fa-android"></i> Download APK</a>
    <?php else: ?>
      <p class="text-muted">No build published yet.</p>
    <?php endif; ?>
  </div>
</div></div></main>
