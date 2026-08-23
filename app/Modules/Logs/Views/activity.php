<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-list-check me-1"></i> Activity Logs</h3>
        <form class="d-flex gap-2" method="get">
            <input type="search" name="q" value="<?= esc($search) ?>" class="form-control form-control-sm" placeholder="Search user, module, action...">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
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
    </div>
    <?php if (isset($pager)): ?><div class="card-footer d-flex justify-content-end"><?= $pager->links() ?></div><?php endif; ?>
</div>

