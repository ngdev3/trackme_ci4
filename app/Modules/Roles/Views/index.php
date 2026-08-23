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
        <div class="erp-tbl-wrap">
            <table class="erp-tbl">
                <thead><tr>
                    <th class="text-start" style="width:90px">ID</th><th class="text-start">Name</th><th class="text-start" style="width:180px">Code</th><th class="text-start" style="width:150px">Type</th><th class="text-center" style="width:120px">Status</th>
                    <th class="text-end" style="width:130px">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="erp-empty"><i class="bi bi-inbox"></i><div>No records found.</div></td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-start"><span class="erp-idchip"><?= esc($r['id']) ?></span></td>
                        <td class="text-start"><?= erp_cell_name((string) $r['name'], [
                            'type' => 'Role', 'icon' => 'person-gear',
                            'chips' => [(int) $r['is_superadmin'] === 1 ? ['t' => 'Super Admin', 'ic' => 'shield-shaded', 'ok' => true] : ['t' => 'Standard', 'ic' => 'person']],
                            'rows' => [
                                ['ic' => 'upc-scan', 'l' => 'Code', 'v' => (string) $r['code']],
                                ['ic' => (int) $r['status'] === 1 ? 'check-circle' : 'pause-circle', 'l' => 'Status', 'v' => (int) $r['status'] === 1 ? 'Active' : 'Inactive'],
                            ],
                            'foot' => 'Role #' . $r['id'],
                        ], ['green' => (int) $r['status'] === 1]) ?></td>
                        <td class="text-start"><code><?= esc($r['code']) ?></code></td>
                        <td class="text-start"><?= (int) $r['is_superadmin'] === 1 ? '<span class="erp-pill gray">Super Admin</span>' : '<span class="erp-pill">Standard</span>' ?></td>
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

