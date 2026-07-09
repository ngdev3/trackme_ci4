<?php /** Super Admin — all firms. Rendered inside layout.php. */ ?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-building me-1"></i> Firms / Companies</h3>
        <form class="d-flex gap-2" method="get">
            <input type="search" name="q" value="<?= esc($search) ?>" class="form-control form-control-sm" placeholder="Search firm or owner...">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>
                    <th>#</th><th>Firm</th><th>Owner</th><th>State</th><th>Users</th><th>FY From</th><th>Status</th><th class="text-end">Action</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-secondary py-4">No firms found.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= esc($r['id']) ?></td>
                        <td class="fw-semibold"><?= esc($r['name']) ?></td>
                        <td><?= esc($r['owner_name'] ?? '—') ?><br><small class="text-muted"><?= esc($r['owner_email'] ?? '') ?></small></td>
                        <td><small><?= esc($r['state']) ?></small></td>
                        <td><span class="badge text-bg-light border"><?= (int) $r['user_count'] ?></span></td>
                        <td><small><?= esc(date('d M Y', strtotime($r['financial_year_from']))) ?></small></td>
                        <td>
                            <a href="<?= site_url('admin/firms/toggle/' . $r['id']) ?>">
                                <?= (int) $r['status'] === 1 ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?>
                            </a>
                        </td>
                        <td class="text-end"><span class="text-muted small">Owner-managed</span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (isset($pager)): ?><div class="card-footer d-flex justify-content-end"><?= $pager->links() ?></div><?php endif; ?>
</div>
