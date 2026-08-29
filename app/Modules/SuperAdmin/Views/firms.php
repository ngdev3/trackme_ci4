<?php
/**
 * Super Admin — all firms. Rendered inside layout.php.
 * Shared list design: cust-* page shell (hero + panel, assets/css/erp-list.css)
 * over the shared ERP data-table (assets/css/erp-table.css) + hover-card engine
 * (assets/js/erp-table.js) — same look as the canonical Customers listing.
 */
$total = isset($pager) ? (int) $pager->getTotal() : count($rows ?? []);
?>
<div class="cust-page">

    <!-- Hero -->
    <section class="cust-hero">
        <div>
            <h4 class="cust-title">Firms / Companies</h4>
            <p class="cust-subtitle">Every firm across all customer accounts — owners, states and financial years.</p>
        </div>
        <div class="cust-hero-actions">
            <form class="cust-search" method="get" role="search">
                <i class="bi bi-search cust-search-ic"></i>
                <input type="search" name="q" value="<?= esc($search) ?>" placeholder="Search firm or owner…" autocomplete="off">
                <?php if ($search !== ''): ?><a href="<?= site_url('admin/firms') ?>" class="cust-search-clear" title="Clear"><i class="bi bi-x-lg"></i></a><?php endif; ?>
            </form>
        </div>
    </section>

    <!-- Table panel -->
    <section class="cust-panel cust-table-panel">
        <div class="cust-toolbar">
            <div>
                <h5 class="cust-table-title">Firm Records</h5>
                <p class="cust-table-note">Firms are owner-managed; toggle a firm's active status from here.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($search !== ''): ?><span class="cust-search-tag"><i class="bi bi-search"></i> “<?= esc($search) ?>”</span><?php endif; ?>
                <span class="cust-total-tag"><i class="bi bi-building"></i> <?= number_format($total) ?> total</span>
            </div>
        </div>

        <div class="erp-tbl-wrap">
            <table class="erp-tbl">
                <thead>
                    <tr>
                        <th class="text-start" style="width:96px">ID</th>
                        <th class="text-start" style="width:260px">Firm</th>
                        <th class="text-start" style="width:240px">Owner</th>
                        <th class="text-start" style="width:130px">State</th>
                        <th class="text-center" style="width:90px">Users</th>
                        <th class="text-start" style="width:130px">FY From</th>
                        <th class="text-center" style="width:120px">Status</th>
                        <th class="text-end" style="width:150px">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="erp-empty"><i class="bi bi-inbox"></i><div>No firms found<?= $search !== '' ? ' for “' . esc($search) . '”' : '' ?>.</div></td></tr>
                <?php else: foreach ($rows as $r):
                    $active   = (int) $r['status'] === 1;
                    $users    = (int) $r['user_count'];
                    $fyFrom   = ! empty($r['financial_year_from']) ? date('d M Y', strtotime((string) $r['financial_year_from'])) : '';
                    $ownerNm  = (string) ($r['owner_name'] ?? '');
                    $ownerEml = (string) ($r['owner_email'] ?? '');

                    // Rich hover-card payload (generic shape rendered by erp-table.js).
                    $tip = [
                        'type'   => 'Firm',
                        'icon'   => 'building',
                        'name'   => (string) $r['name'],
                        'accent' => $active ? 'green' : 'gray',
                        'chips'  => array_values(array_filter([
                            ['t' => $active ? 'Active' : 'Inactive', 'ic' => $active ? 'check-circle-fill' : 'pause-circle-fill', 'ok' => $active],
                            $r['state'] ? ['t' => (string) $r['state'], 'ic' => 'geo-alt-fill'] : null,
                        ])),
                        'stats'  => [
                            ['v' => (string) $users, 'l' => 'Users'],
                            ['v' => $r['state'] ?: '—', 'l' => 'State'],
                            ['v' => $fyFrom ?: '—', 'l' => 'FY From'],
                        ],
                        'rows'   => array_values(array_filter([
                            $ownerNm  ? ['ic' => 'person-badge', 'l' => 'Owner', 'v' => $ownerNm] : null,
                            $ownerEml ? ['ic' => 'envelope', 'l' => 'Owner email', 'v' => $ownerEml] : null,
                            $fyFrom   ? ['ic' => 'calendar-range', 'l' => 'Financial year from', 'v' => $fyFrom] : null,
                        ])),
                        'foot'   => 'Firm #' . $r['id'],
                    ];
                    $tipJson = json_encode($tip, JSON_UNESCAPED_UNICODE);
                ?>
                    <tr>
                        <td class="text-start"><span class="erp-idchip">FRM-<?= str_pad((string) $r['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
                        <td class="text-start">
                            <div class="erp-cellname">
                                <span class="erp-avatar<?= $active ? ' green' : '' ?>"><?= esc(strtoupper(mb_substr((string) $r['name'], 0, 1) ?: '?')) ?></span>
                                <span class="erp-name-txt erp-hover" data-tip="<?= esc($tipJson, 'attr') ?>"><?= esc($r['name']) ?></span>
                            </div>
                        </td>
                        <td class="text-start">
                            <span class="erp-truncate" title="<?= esc($ownerNm . ($ownerEml ? ' · ' . $ownerEml : ''), 'attr') ?>">
                                <?= esc($ownerNm ?: '—') ?>
                                <?php if ($ownerEml): ?><br><small class="erp-muted"><?= esc($ownerEml) ?></small><?php endif; ?>
                            </span>
                        </td>
                        <td class="text-start"><span class="erp-muted"><?= esc($r['state'] ?: '—') ?></span></td>
                        <td class="text-center"><span class="erp-badge"><i class="bi bi-people"></i><?= $users ?></span></td>
                        <td class="text-start"><span class="erp-muted"><?= esc($fyFrom ?: '—') ?></span></td>
                        <td class="text-center">
                            <a href="<?= site_url('admin/firms/toggle/' . $r['id']) ?>" class="text-decoration-none" title="<?= $active ? 'Active — click to deactivate' : 'Inactive — click to activate' ?>">
                                <span class="erp-status <?= $active ? 'active' : 'inactive' ?>"><?= $active ? 'Active' : 'Inactive' ?></span>
                            </a>
                        </td>
                        <td class="text-end"><span class="erp-muted small">Owner-managed</span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($pager)): ?><div class="cust-pager-bar"><?= $pager->only(['q'])->links('default', 'modern') ?></div><?php endif; ?>
    </section>
</div>
