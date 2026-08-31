<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:1200px;margin:0 auto;">
  <h3 style="font-weight:900;">Download Logs</h3>
  <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:16px;">
    <table class="table table-striped table-bordered">
      <thead><tr><th>#</th><th>Version</th><th>User</th><th>Source</th><th>IP</th><th>When</th></tr></thead>
      <tbody>
      <?php $i = 0; foreach (($logs ?? []) as $l): $i++; ?>
        <tr>
          <td><?= $i; ?></td>
          <td><?= esc($l->version_name ?? ''); ?> (<?= (int) ($l->version_code ?? 0); ?>)</td>
          <td><?= esc(trim($l->user_name ?? '') ?: 'Guest'); ?></td>
          <td><?= esc($l->source ?? ''); ?></td>
          <td><?= esc($l->ip_address ?? ''); ?></td>
          <td><?= ! empty($l->downloaded_at) ? date('d M Y H:i', strtotime($l->downloaded_at)) : '-'; ?></td>
        </tr>
      <?php endforeach; if ($i === 0): ?>
        <tr><td colspan="6" class="text-center text-muted">No downloads yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div></div></main>
