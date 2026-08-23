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
        <div class="erp-tbl-wrap">
            <table class="erp-tbl auto">
                <thead><tr>
                    <th class="text-start">ID</th><th class="text-center">Icon</th><th class="text-start">Name</th><th class="text-start">Code</th><th class="text-start">URL</th>
                    <th class="text-start">Parent</th><th class="text-center">Sort</th><th class="text-center">Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="9" class="erp-empty"><i class="bi bi-inbox"></i><div>No records found.</div></td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-start"><span class="erp-idchip"><?= esc($r['id']) ?></span></td>
                        <td class="text-center"><i class="<?= esc($r['icon']) ?>"></i></td>
                        <td class="text-start"><?= erp_cell_name((string) $r['name'], [
                            'type' => 'Module', 'icon' => 'grid-3x3-gap',
                            'chips' => array_values(array_filter([
                                ['t' => (string) $r['code'], 'ic' => 'upc-scan'],
                                ! empty($r['parent_name']) ? ['t' => (string) $r['parent_name'], 'ic' => 'diagram-2'] : null,
                            ])),
                            'rows' => array_values(array_filter([
                                ! empty($r['url']) ? ['ic' => 'link-45deg', 'l' => 'URL', 'v' => (string) $r['url']] : null,
                                ['ic' => 'sort-numeric-down', 'l' => 'Sort order', 'v' => (string) $r['sort_order']],
                                ['ic' => (int) $r['status'] === 1 ? 'check-circle' : 'pause-circle', 'l' => 'Status', 'v' => (int) $r['status'] === 1 ? 'Active' : 'Inactive'],
                            ])),
                            'foot' => 'Module #' . $r['id'],
                        ], ['green' => (int) $r['status'] === 1]) ?></td>
                        <td class="text-start"><code><?= esc($r['code']) ?></code></td>
                        <td class="text-start"><?= $r['url'] ? '<span class="erp-badge">' . esc($r['url']) . '</span>' : '<span class="erp-muted">—</span>' ?></td>
                        <td class="text-start"><span class="erp-muted"><?= esc($r['parent_name'] ?? '—') ?></span></td>
                        <td class="text-center"><span class="erp-muted"><?= esc($r['sort_order']) ?></span></td>
                        <td class="text-center">
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

