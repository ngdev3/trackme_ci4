<?php /** Super Admin SaaS dashboard. Rendered inside layout.php. */ ?>
<div class="row g-3 mb-3">
    <?php
    $cards = [
        ['Customers', $stats['customers'], 'bi-people', 'primary', site_url('admin/customers')],
        ['Firms', $stats['firms'], 'bi-building', 'success', site_url('admin/firms')],
        ['Firm Users', $stats['firm_users'], 'bi-person-badge', 'info', null],
        ['Active Plans', $stats['plans'], 'bi-card-checklist', 'warning', site_url('admin/plans')],
    ];
    foreach ($cards as [$label, $value, $icon, $color, $link]): ?>
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-start border-4 border-<?= $color ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small"><?= esc($label) ?></span>
                        <i class="bi <?= $icon ?> text-<?= $color ?> fs-5"></i>
                    </div>
                    <div class="fs-3 fw-bold"><?= (int) $value ?></div>
                    <?php if ($link): ?><a href="<?= $link ?>" class="small stretched-link">Manage →</a><?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-toggles me-1"></i> Accounts</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1 border-bottom"><span>Active customers</span><strong class="text-success"><?= (int) $stats['customers_active'] ?></strong></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span>Inactive customers</span><strong class="text-secondary"><?= (int) $stats['customers_inactive'] ?></strong></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span>Active firms</span><strong class="text-success"><?= (int) $stats['firms_active'] ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span>Inactive firms</span><strong class="text-secondary"><?= (int) $stats['firms_inactive'] ?></strong></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-credit-card me-1"></i> Payments</h3></div>
            <div class="card-body">
                <?php foreach (['paid' => 'success', 'unpaid' => 'danger', 'trial' => 'info'] as $st => $col): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-capitalize"><?= $st ?></span>
                        <strong class="text-<?= $col ?>"><?= (int) ($payments[$st] ?? 0) ?></strong>
                    </div>
                <?php endforeach; ?>
                <a href="<?= site_url('admin/customers') ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">Manage subscriptions</a>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-activity me-1"></i> Recent Activity</h3></div>
            <ul class="list-group list-group-flush">
                <?php if (empty($recent)): ?>
                    <li class="list-group-item text-secondary small">No recent activity.</li>
                <?php else: foreach ($recent as $a): ?>
                    <li class="list-group-item small">
                        <strong><?= esc($a['user_name'] ?? 'System') ?></strong> — <?= esc($a['module']) ?> · <?= esc($a['action']) ?>
                        <div class="text-muted"><?= esc(date('d M, H:i', strtotime($a['created_at']))) ?></div>
                    </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>
</div>
