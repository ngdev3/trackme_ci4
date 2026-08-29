<?php
/**
 * Users table + grid fragment (AJAX sort / search / paginate / page-size).
 */
$showRoleType = ! empty($showRoleType);
$tableCols = $showRoleType ? 8 : 6;

$avatarGradient = static function (string $name): string {
    $palettes = [
        ['#6366f1', '#8b5cf6'], ['#0ea5e9', '#22d3ee'], ['#22c55e', '#4ade80'],
        ['#f59e0b', '#fbbf24'], ['#ef4444', '#fb7185'], ['#14b8a6', '#2dd4bf'],
    ];
    $i = ord(strtoupper($name[0] ?? 'A')) % count($palettes);
    return 'linear-gradient(135deg,' . $palettes[$i][0] . ',' . $palettes[$i][1] . ')';
};

$roleBadge = static function (string $roles): string {
    if ($roles === '' || $roles === '-') {
        return '<span class="text-muted small">-</span>';
    }
    $out = '';
    foreach (array_filter(array_map('trim', explode(',', $roles))) as $role) {
        $out .= '<span class="role-badge">' . esc($role) . '</span>';
    }
    return $out;
};

$acctBadge = static function (?string $type): string {
    $map = [
        'super_admin' => ['Super Admin', 'acct-super', 'bi-shield-shaded'],
        'customer'    => ['Customer', 'acct-customer', 'bi-person-badge'],
        'firm_user'   => ['Firm User', 'acct-firm', 'bi-person-workspace'],
    ];
    [$label, $cls, $icon] = $map[$type] ?? ['Local', 'acct-local', 'bi-person'];
    return '<span class="acct-badge ' . $cls . '"><i class="bi ' . $icon . '"></i>' . esc($label) . '</span>';
};

$billingBadge = static function (array $r): string {
    if (($r['account_type'] ?? '') !== 'customer') {
        return '<span class="text-muted small">-</span>';
    }
    $sub = $r['subscription'] ?? null;
    if (! $sub) {
        return '<span class="bill-badge bill-none"><i class="bi bi-dash-circle"></i>No plan</span>';
    }
    if (($sub['plan_code'] ?? '') === 'free') {
        return '<span class="bill-badge bill-free" title="Free plan"><i class="bi bi-gift"></i>Free</span>';
    }
    $map = [
        'paid'   => ['Paid', 'bill-paid', 'bi-check-circle'],
        'unpaid' => ['Unpaid', 'bill-unpaid', 'bi-exclamation-circle'],
        'trial'  => ['Trial', 'bill-trial', 'bi-hourglass-split'],
    ];
    [$label, $cls, $icon] = $map[$sub['payment_status'] ?? 'trial'] ?? ['Trial', 'bill-trial', 'bi-hourglass-split'];
    $plan = ! empty($sub['plan_name']) ? ' <small class="text-muted">' . esc($sub['plan_name']) . '</small>' : '';
    return '<span class="bill-badge ' . $cls . '"><i class="bi ' . $icon . '"></i>' . $label . '</span>' . $plan;
};

$sortHead = static function (string $key, string $label) use ($sort, $dir, $search, $per) {
    $active  = $sort === $key;
    $nextDir = ($active && $dir === 'asc') ? 'desc' : 'asc';
    $qs = http_build_query(array_filter(['q' => $search, 'sort' => $key, 'dir' => $nextDir, 'per' => $per], static fn ($v) => $v !== '' && $v !== null));
    $icon = ! $active ? 'bi-arrow-down-up opacity-25' : ($dir === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill');
    return '<a href="' . site_url('users') . '?' . $qs . '" class="cust-sort' . ($active ? ' is-sorted' : '') . '">'
        . esc($label) . ' <i class="bi ' . $icon . '"></i></a>';
};

$pageInfo = 'Showing ' . count($rows) . ' user' . (count($rows) === 1 ? '' : 's');
if (isset($pager)) {
    $d = $pager->getDetails();
    if (($d['total'] ?? 0) > 0) {
        $from = (($d['currentPage'] - 1) * $d['perPage']) + 1;
        $to = min($d['currentPage'] * $d['perPage'], $d['total']);
        $pageInfo = 'Showing <strong>' . number_format($from) . '-' . number_format($to) . '</strong> of <strong>' . number_format($d['total']) . '</strong>';
    }
}

$actions = static function (array $r) use ($moduleCode, $baseRoute) {
    $id = (int) $r['id'];
    $html = '<div class="cust-row-actions">';
    if (session('is_superadmin') && $id !== (int) session('user_id')) {
        $html .= '<a href="' . site_url('admin/impersonate/' . $id) . '" class="cust-act act-login" title="Access account"'
            . ' data-confirm="You can return to Super Admin anytime." data-confirm-title="Sign in as ' . esc($r['name'], 'attr') . '?" data-confirm-btn="Sign in" data-confirm-icon="info"><i class="bi bi-box-arrow-in-right"></i></a>';
    }
    if (can($moduleCode, 'edit')) {
        $html .= '<a href="' . site_url($baseRoute . '/edit/' . $id) . '" class="cust-act act-edit" title="Edit"><i class="bi bi-pencil"></i></a>';
    }
    if (can($moduleCode, 'delete')) {
        $html .= '<form action="' . site_url($baseRoute . '/delete/' . $id) . '" method="post" class="d-inline" data-no-validate data-confirm="This user will be deleted." data-confirm-title="Delete user?" data-confirm-btn="Yes, delete">'
            . csrf_field()
            . '<button type="submit" class="cust-act act-del" title="Delete"><i class="bi bi-trash"></i></button></form>';
    }
    return $html . '</div>';
};
?>
<section class="cust-panel cust-table-panel fade-up">
    <div class="cust-toolbar">
        <div>
            <h5 class="cust-table-title"><?= esc($scopeLabel ?? 'All Users') ?></h5>
            <p class="cust-table-note">Search, sort, edit, or manage access for users.</p>
        </div>
        <span class="cust-total-tag"><i class="bi bi-people"></i> <?= isset($d['total']) ? number_format($d['total']) : count($rows) ?> total</span>
    </div>
    <div class="cust-tabletools">
            <label class="cust-len">
                <span>Show</span>
                <select id="perSelect" class="cust-len-select">
                    <?php foreach (['25' => '25', '35' => '35', '50' => '50', '100' => '100', '1000' => '1000', 'all' => 'All'] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= (string) $per === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
                <span>Records</span>
            </label>
            <div class="view-toggle btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active" data-view="table" title="Table view"><i class="bi bi-list-ul"></i></button>
                <button type="button" class="btn btn-outline-secondary" data-view="grid" title="Grid view"><i class="bi bi-grid-3x3-gap"></i></button>
            </div>
    </div>

    <div class="card-body p-0" data-view-panel="table">
        <div class="cust-table-wrap">
            <table class="cust-table">
                <thead><tr>
                    <th class="cust-th-sort"><?= $sortHead('name', 'User') ?></th>
                    <th class="cust-th-sort"><?= $sortHead('username', 'Username') ?></th>
                    <?php if ($showRoleType): ?><th class="cust-th-sort"><?= $sortHead('type', 'User Type') ?></th><?php endif; ?>
                    <th>Account</th>
                    <th>Billing</th>
                    <?php if ($showRoleType): ?><th>Roles</th><?php endif; ?>
                    <th class="cust-th-sort"><?= $sortHead('status', 'Status') ?></th>
                    <th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= $tableCols ?>" class="cust-empty"><i class="bi bi-people"></i>No users found.</td></tr>
                <?php else: foreach ($rows as $i => $r): ?>
                    <tr style="animation-delay:<?= min($i, 20) * 25 ?>ms">
                        <td>
                            <div class="user-cell">
                                <?php if (! empty($r['profile_image'])): ?>
                                    <img src="<?= base_url('uploads/users/' . $r['profile_image']) ?>" class="user-avatar" alt="">
                                <?php else: ?>
                                    <span class="user-avatar user-avatar-fallback" style="background:<?= $avatarGradient($r['name']) ?>"><?= esc(strtoupper(substr($r['name'], 0, 1))) ?></span>
                                <?php endif; ?>
                                <div class="user-meta">
                                    <span class="user-name"><?= esc($r['name']) ?></span>
                                    <span class="user-email"><?= esc($r['email']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td><code><?= esc($r['username']) ?></code></td>
                        <?php if ($showRoleType): ?>
                            <td><?php $t = $r['user_type_name'] ?? ''; echo $t !== '' ? '<span class="type-badge">' . esc($t) . '</span>' : '<span class="text-muted small">-</span>'; ?></td>
                        <?php endif; ?>
                        <td><?= $acctBadge($r['account_type'] ?? null) ?></td>
                        <td><?= $billingBadge($r) ?></td>
                        <?php if ($showRoleType): ?><td class="roles-cell"><?= $roleBadge((string) ($r['role_names'] ?? '')) ?></td><?php endif; ?>
                        <td>
                            <?php if (can($moduleCode, 'edit')): ?>
                                <a href="<?= site_url($baseRoute . '/toggle/' . $r['id']) ?>" class="status-toggle <?= (int) $r['status'] === 1 ? 'is-on' : 'is-off' ?>" title="Click to toggle">
                                    <span class="status-dot"></span><?= (int) $r['status'] === 1 ? 'Active' : 'Inactive' ?>
                                </a>
                            <?php else: ?>
                                <span class="status-toggle <?= (int) $r['status'] === 1 ? 'is-on' : 'is-off' ?>"><span class="status-dot"></span><?= (int) $r['status'] === 1 ? 'Active' : 'Inactive' ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= $actions($r) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-body d-none" data-view-panel="grid">
        <div class="row g-3">
            <?php if (empty($rows)): ?>
                <div class="col-12 text-center text-secondary py-5">No users found.</div>
            <?php else: foreach ($rows as $i => $r): ?>
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="user-grid-card fade-up" style="animation-delay:<?= min($i, 20) * 25 ?>ms">
                        <?php if (! empty($r['profile_image'])): ?>
                            <img src="<?= base_url('uploads/users/' . $r['profile_image']) ?>" class="user-avatar-lg" alt="">
                        <?php else: ?>
                            <span class="user-avatar-lg user-avatar-fallback" style="background:<?= $avatarGradient($r['name']) ?>"><?= esc(strtoupper(substr($r['name'], 0, 1))) ?></span>
                        <?php endif; ?>
                        <div class="user-grid-name"><?= esc($r['name']) ?></div>
                        <div class="user-grid-email text-truncate"><?= esc($r['email']) ?></div>
                        <?php if ($showRoleType && ! empty($r['user_type_name'])): ?><div class="mt-2"><span class="type-badge"><?= esc($r['user_type_name']) ?></span></div><?php endif; ?>
                        <div class="my-2 d-flex gap-1 justify-content-center flex-wrap">
                            <?= $acctBadge($r['account_type'] ?? null) ?>
                            <?= $billingBadge($r) ?>
                            <span class="status-toggle <?= (int) $r['status'] === 1 ? 'is-on' : 'is-off' ?>"><span class="status-dot"></span><?= (int) $r['status'] === 1 ? 'Active' : 'Inactive' ?></span>
                        </div>
                        <div class="d-flex justify-content-center"><?= $actions($r) ?></div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div class="cust-pager-bar">
        <span class="erp-pager__info"><?= $pageInfo ?></span>
        <?php if (isset($pager)): ?><div><?= $pager->only(['q', 'sort', 'dir', 'per'])->links('default', 'erp') ?></div><?php endif; ?>
    </div>
</section>
