<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-box-arrow-in-right me-1"></i> Login Logs</h3>
        <form class="d-flex gap-2" method="get">
            <input type="search" name="q" value="<?= esc($search) ?>" class="form-control form-control-sm" placeholder="Search username, IP, status...">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead><tr>
                    <th>#</th><th>User</th><th>Username</th><th>Status</th><th>Message</th><th>IP</th><th>Browser</th><th>When</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-secondary py-4">No login history.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= esc($r['id']) ?></td>
                        <td><?= esc($r['user_name'] ?? '—') ?></td>
                        <td><code><?= esc($r['username']) ?></code></td>
                        <td>
                            <?php if ($r['status'] === 'success'): ?>
                                <span class="badge text-bg-success">success</span>
                            <?php else: ?>
                                <span class="badge text-bg-danger">failed</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?= esc($r['message']) ?></small></td>
                        <td><small><?= esc($r['ip_address']) ?></small></td>
                        <td><small class="text-truncate d-inline-block" style="max-width:180px" title="<?= esc($r['user_agent']) ?>"><?= esc($r['user_agent']) ?></small></td>
                        <td><small><?= esc(date('d M Y, H:i', strtotime($r['created_at']))) ?></small></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (isset($pager)): ?><div class="card-footer d-flex justify-content-end"><?= $pager->links() ?></div><?php endif; ?>
</div>

