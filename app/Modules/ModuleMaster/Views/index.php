<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-grid-3x3-gap me-1"></i> Modules (Sidebar Menu)</h3>
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
                    <th style="width:60px">#</th><th>Icon</th><th>Name</th><th>Code</th><th>URL</th>
                    <th>Parent</th><th>Sort</th><th>Status</th><th class="col-actions text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="9" class="text-center text-secondary py-4">No records found.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= esc($r['id']) ?></td>
                        <td><i class="<?= esc($r['icon']) ?>"></i></td>
                        <td class="fw-semibold"><?= esc($r['name']) ?></td>
                        <td><code><?= esc($r['code']) ?></code></td>
                        <td><?= $r['url'] ? '<span class="badge text-bg-light border">' . esc($r['url']) . '</span>' : '<span class="text-secondary">—</span>' ?></td>
                        <td><?= esc($r['parent_name'] ?? '—') ?></td>
                        <td><?= esc($r['sort_order']) ?></td>
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

