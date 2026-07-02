<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-person-gear me-1"></i> Roles</h3>
        <form class="d-flex gap-2" method="get">
            <input type="search" name="q" value="<?= esc($search) ?>" class="form-control form-control-sm" placeholder="Search...">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
            <?php if (can($moduleCode, 'add')): ?>
                <a href="<?= site_url($baseRoute . '/create') ?>" class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> Add</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>
                    <th style="width:60px">#</th><th>Name</th><th>Code</th><th>Type</th><th>Status</th>
                    <th class="col-actions text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-4">No records found.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= esc($r['id']) ?></td>
                        <td class="fw-semibold"><?= esc($r['name']) ?></td>
                        <td><code><?= esc($r['code']) ?></code></td>
                        <td><?= (int) $r['is_superadmin'] === 1 ? '<span class="badge text-bg-dark">Super Admin</span>' : '<span class="badge text-bg-light border">Standard</span>' ?></td>
                        <td>
                            <?php if (can($moduleCode, 'edit')): ?>
                                <a href="<?= site_url($baseRoute . '/toggle/' . $r['id']) ?>" class="text-decoration-none"><?= status_badge($r['status']) ?></a>
                            <?php else: ?><?= status_badge($r['status']) ?><?php endif; ?>
                        </td>
                        <td class="text-end"><?= action_buttons($moduleCode, $baseRoute, (int) $r['id']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (isset($pager)): ?><div class="card-footer d-flex justify-content-end"><?= $pager->links() ?></div><?php endif; ?>
</div>

