<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<?php $total = isset($pager) ? (int) $pager->getTotal() : count($rows ?? []); ?>
<div class="cust-page">
    <section class="cust-hero">
        <div>
            <h4 class="cust-title">Activity Logs</h4>
            <p class="cust-subtitle">Audit trail of every user action across the application.</p>
        </div>
        <div class="cust-hero-actions">
            <form class="cust-search" method="get" role="search">
                <i class="bi bi-search cust-search-ic"></i>
                <input type="search" name="q" value="<?= esc($search) ?>" placeholder="User, module, action…" autocomplete="off">
                <?php if ($search !== ''): ?><a href="<?= site_url('activity-logs') ?>" class="cust-search-clear" title="Clear"><i class="bi bi-x-lg"></i></a><?php endif; ?>
            </form>
        </div>
    </section>
    <section class="cust-panel cust-table-panel">
        <div class="cust-toolbar">
            <div>
                <h5 class="cust-table-title">Activity Records</h5>
                <p class="cust-table-note">Newest first. Hover a user to preview the full log entry.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($search !== ''): ?><span class="cust-search-tag"><i class="bi bi-search"></i> “<?= esc($search) ?>”</span><?php endif; ?>
                <span class="cust-total-tag"><i class="bi bi-list-check"></i> <?= number_format($total) ?> total</span>
            </div>
        </div>
        <div class="erp-tbl-wrap">
            <table class="erp-tbl auto">
                <thead><tr>
                    <th class="text-start">ID</th><th class="text-start">User</th><th class="text-start">Module</th><th class="text-start">Action</th><th class="text-start">Description</th><th class="text-start">IP</th><th class="text-start">Browser</th><th class="text-start">When</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="erp-empty"><i class="bi bi-inbox"></i><div>No activity recorded.</div></td></tr>
                <?php else: foreach ($rows as $r):
                    $uname = (string) ($r['user_name'] ?? 'System');
                    // Rich hover-card payload (same generic shape as the customers card).
                    $tip = [
                        'type'   => 'Activity',
                        'icon'   => 'list-check',
                        'name'   => $uname,
                        'accent' => 'blue',
                        'chips'  => array_values(array_filter([
                            ! empty($r['module']) ? ['t' => (string) $r['module'], 'ic' => 'folder2'] : null,
                            ! empty($r['action']) ? ['t' => (string) $r['action'], 'ic' => 'lightning-charge-fill', 'ok' => true] : null,
                        ])),
                        'rows'   => array_values(array_filter([
                            ! empty($r['description']) ? ['ic' => 'card-text', 'l' => 'Description', 'v' => (string) $r['description']] : null,
                            ! empty($r['ip_address']) ? ['ic' => 'hdd-network', 'l' => 'IP address', 'v' => (string) $r['ip_address']] : null,
                            ! empty($r['user_agent']) ? ['ic' => 'window', 'l' => 'Browser', 'v' => (string) $r['user_agent']] : null,
                        ])),
                        'foot'   => 'Log #' . $r['id'] . ' · ' . date('d M Y, H:i', strtotime($r['created_at'])),
                    ];
                    $tipJson = json_encode($tip, JSON_UNESCAPED_UNICODE);
                ?>
                    <tr>
                        <td><span class="erp-idchip"><?= esc($r['id']) ?></span></td>
                        <td>
                            <div class="erp-cellname">
                                <span class="erp-avatar"><?= esc(strtoupper(mb_substr($uname, 0, 1) ?: '?')) ?></span>
                                <span class="erp-name-txt erp-hover" data-tip="<?= esc($tipJson, 'attr') ?>"><?= esc($uname) ?></span>
                            </div>
                        </td>
                        <td><span class="erp-badge"><?= esc($r['module']) ?></span></td>
                        <td><span class="erp-pill"><?= esc($r['action']) ?></span></td>
                        <td style="white-space:normal;max-width:320px"><span class="erp-muted"><?= esc($r['description']) ?></span></td>
                        <td><span class="erp-muted"><?= esc($r['ip_address']) ?></span></td>
                        <td><span class="erp-truncate erp-muted" style="max-width:180px" title="<?= esc($r['user_agent']) ?>"><?= esc($r['user_agent']) ?></span></td>
                        <td><span class="erp-muted"><?= esc(date('d M Y, H:i', strtotime($r['created_at']))) ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (isset($pager)): ?><div class="cust-pager-bar"><?= $pager->links() ?></div><?php endif; ?>
    </section>
</div>

