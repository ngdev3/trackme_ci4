<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<?php $total = isset($pager) ? (int) $pager->getTotal() : count($rows ?? []); ?>
<div class="cust-page">
    <section class="cust-hero">
        <div>
            <h4 class="cust-title">Modules</h4>
            <p class="cust-subtitle">Sidebar menu items and their routes, icons, hierarchy and order.</p>
        </div>
        <div class="cust-hero-actions">
            <form class="cust-search" method="get" role="search">
                <i class="bi bi-search cust-search-ic"></i>
                <input type="search" name="q" value="<?= esc($search) ?>" placeholder="Search modules…" autocomplete="off">
                <?php if ($search !== ''): ?><a href="<?= site_url($baseRoute) ?>" class="cust-search-clear" title="Clear"><i class="bi bi-x-lg"></i></a><?php endif; ?>
            </form>
            <?php if (can($moduleCode, 'add')): ?>
                <a href="<?= site_url($baseRoute . '/create') ?>" class="cust-btn cust-btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> Add Module</a>
            <?php endif; ?>
        </div>
    </section>
    <section class="cust-panel cust-table-panel">
        <div class="cust-toolbar">
            <div>
                <h5 class="cust-table-title">Module Records</h5>
                <p class="cust-table-note">Sidebar menu structure — toggle visibility, edit, or add a menu item.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($search !== ''): ?><span class="cust-search-tag"><i class="bi bi-search"></i> “<?= esc($search) ?>”</span><?php endif; ?>
                <span class="cust-total-tag"><i class="bi bi-grid-3x3-gap"></i> <?= number_format($total) ?> total</span>
            </div>
        </div>
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
        <?php if (isset($pager)): ?><div class="cust-pager-bar"><?= $pager->links() ?></div><?php endif; ?>
    </section>
</div>

