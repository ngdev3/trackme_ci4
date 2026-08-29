<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<?php $total = isset($pager) ? (int) $pager->getTotal() : count($rows ?? []); ?>
<div class="cust-page">
    <section class="cust-hero">
        <div>
            <h4 class="cust-title">User Types</h4>
            <p class="cust-subtitle">Top-level account categories that group roles and permissions.</p>
        </div>
        <div class="cust-hero-actions">
            <form class="cust-search" method="get" role="search">
                <i class="bi bi-search cust-search-ic"></i>
                <input type="search" name="q" value="<?= esc($search) ?>" placeholder="Search user types…" autocomplete="off">
                <?php if ($search !== ''): ?><a href="<?= site_url($baseRoute) ?>" class="cust-search-clear" title="Clear"><i class="bi bi-x-lg"></i></a><?php endif; ?>
            </form>
            <?php if (can($moduleCode, 'add')): ?>
                <a href="<?= site_url($baseRoute . '/create') ?>" class="cust-btn cust-btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> Add User Type</a>
            <?php endif; ?>
        </div>
    </section>
    <section class="cust-panel cust-table-panel">
        <div class="cust-toolbar">
            <div>
                <h5 class="cust-table-title">User Type Records</h5>
                <p class="cust-table-note">Toggle a type's status, edit it, or add a new one.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($search !== ''): ?><span class="cust-search-tag"><i class="bi bi-search"></i> “<?= esc($search) ?>”</span><?php endif; ?>
                <span class="cust-total-tag"><i class="bi bi-person-badge"></i> <?= number_format($total) ?> total</span>
            </div>
        </div>
        <div class="erp-tbl-wrap">
            <table class="erp-tbl">
                <thead>
                    <tr>
                        <th class="text-start" style="width:90px">ID</th>
                        <th class="text-start" style="width:200px">Name</th>
                        <th class="text-start" style="width:180px">Code</th>
                        <th class="text-start">Description</th>
                        <th class="text-center" style="width:120px">Status</th>
                        <th class="text-end" style="width:130px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="erp-empty"><i class="bi bi-inbox"></i><div>No records found.</div></td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-start"><span class="erp-idchip"><?= esc($r['id']) ?></span></td>
                        <td class="text-start"><?= erp_cell_name((string) $r['name'], [
                            'type' => 'User Type', 'icon' => 'person-badge',
                            'chips' => [['t' => (string) $r['code'], 'ic' => 'upc-scan']],
                            'rows' => array_values(array_filter([
                                ! empty($r['description']) ? ['ic' => 'card-text', 'l' => 'Description', 'v' => (string) $r['description']] : null,
                                ['ic' => (int) $r['status'] === 1 ? 'check-circle' : 'pause-circle', 'l' => 'Status', 'v' => (int) $r['status'] === 1 ? 'Active' : 'Inactive'],
                            ])),
                            'foot' => 'User type #' . $r['id'],
                        ], ['green' => (int) $r['status'] === 1]) ?></td>
                        <td class="text-start"><code><?= esc($r['code']) ?></code></td>
                        <td class="text-start"><span class="erp-muted"><?= esc($r['description']) ?></span></td>
                        <td class="text-center">
                            <?php if (can($moduleCode, 'edit')): ?>
                                <a href="<?= site_url($baseRoute . '/toggle/' . $r['id']) ?>" class="text-decoration-none"><?= status_badge($r['status']) ?></a>
                            <?php else: ?>
                                <?= status_badge($r['status']) ?>
                            <?php endif; ?>
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
