<?php
/**
 * Sidebar menu — CI4 port that mirrors the CI3 SUPER-ADMIN left_menu.php
 * (application/views/elements/left_menu.php) EXACTLY: same top-level items, the
 * same order, the same labels and the same sub-links. A super admin sees every
 * group; a regular user sees a group only if erp_current_user_can(<rbac>,'view')
 * (rbac null = super-admin-only admin tool). Cold-storage groups appear only for
 * product_type 3 firms, exactly as CI3.
 *
 * A small red "NEW" badge marks links whose CI4 route is live (migration aid);
 * it never changes the list itself.
 */
helper(['url', 'permission']);

$seg1 = strtolower(service('uri')->getSegment(1, ''));
$seg2 = strtolower(service('uri')->getSegment(2, ''));
$isSA = function_exists('erp_is_super_admin') && erp_is_super_admin();
$can  = function ($rbac) use ($isSA) {
    if ($isSA) { return true; }
    return $rbac && function_exists('erp_current_user_can') && erp_current_user_can($rbac, 'view');
};
$pt   = (int) (function_exists('fy') && fy() ? (fy()->product_type ?? 0) : 0);

// URL builder: '/xxx' → root-relative, 'http…' → as-is, else admin/-prefixed.
$mkUrl = function ($uri) {
    if ($uri === '' || $uri === '#' || $uri === '#soon' || $uri === '---') { return 'javascript:void(0);'; }
    if (strpos($uri, 'http') === 0) { return $uri; }
    if ($uri[0] === '/') { return base_url(ltrim($uri, '/')); }
    return base_url('admin/' . $uri);
};

/* Controllers/links already live on CI4 → show a red "NEW" badge. Precise URI
   whitelist so the badge never marks an unported sub-method that would 404. */
$readyUris = [
    // Verified LIVE on CI4 by an authenticated sweep of every menu link (the rest
    // render the "being migrated" page). Keep this in sync as controllers are ported.
    'dashboard', 'help',
    'document/add', 'document/listing', 'letter_pad/add', 'letter_pad/listing',
    'app_update/listing', 'app_update/upload', 'app_update/portal', 'app_update/logs', 'app_update/settings',
    'notification/listing', 'device/listing',
    'bank_password/listing', 'account_name/listing',
    'driver_module/listing', 'driver_module/add', 'truck_module/listing', 'truck_module/add',
    'attendance/listing', 'attendance/add', 'account/entry',
    'cold_lot_system/listing', 'ricemill_inquiry/listing', 'kisanreg/listing',
    'report/search', 'report/byaccount_name', 'report/ledger', 'report/rokad_parcha', 'report/deleted_entries', 'attachments',
    'accounts_report', 'accounts_report/trial_balance', 'accounts_report/balance_sheet', 'accounts_report/profit_loss',
    'accounts_report/outstanding', 'accounts_report/debtors', 'accounts_report/creditors', 'accounts_report/ageing', 'accounts_report/inter_firm',
    'stock/listing', 'hsn/listing', 'item_master/listing',
    'invoice/listing', 'invoice/add', 'cd_note/listing', 'uninvoice/listing', 'delivery_challan/listing',
    'billing_register/listing', 'gst_setting', 'payment_receipt/listing', 'purchase_module/listing',
    'lot_system/listing', 'paddylotsystem/listing',
    'users/listing', 'users/add', 'role_permissions', 'user_permissions',
    'salary_module/listing', 'salary_module/add',
];
$uriNew = function ($uri) use ($readyUris) {
    $u = strtolower(trim((string) $uri, '/'));
    $u = explode('?', $u)[0];                 // drop query (e.g. account/entry?type=…)
    return in_array($u, $readyUris, true);
};
$NEW = '<span class="menu-new">NEW</span>';

/**
 * EXACT CI3 super-admin menu. Each entry:
 *   'label','icon','ctrl' (active-state segment),'rbac' (module key | null),
 *   optional 'firm3'=>true (only product_type 3), optional 'badge' (forced label),
 *   and either 'href' (single link, full URL) or 'items' => [label => uri].
 * Sub-item uris are admin/-relative unless they start with '/' (root) or 'http'.
 * A label containing '↗' opens in a new tab; uri '#soon' renders a disabled item;
 * uri '---' renders a separator line.
 */
$menu = [
    ['label' => 'Help & FAQ', 'icon' => 'fa-question-circle', 'ctrl' => 'help', 'rbac' => null, 'href' => base_url('admin/help')],
    ['label' => 'Documents Renewal', 'icon' => 'fa-refresh', 'ctrl' => 'document', 'rbac' => 'document', 'items' => ['Add Docs' => 'document/add', 'Listing' => 'document/listing']],
    ['label' => 'Letter Pad', 'icon' => 'fa-envelope-o', 'ctrl' => 'letter_pad', 'rbac' => 'letter_pad', 'items' => ['Create Letter' => 'letter_pad/add', 'Letters' => 'letter_pad/listing']],
    ['label' => 'APK Manager', 'icon' => 'fa-android', 'ctrl' => 'app_update', 'rbac' => 'app_update', 'items' => ['APK Manager' => 'app_update/listing', 'Upload APK' => 'app_update/upload', 'Download Portal' => 'app_update/portal', 'Download Logs' => 'app_update/logs', 'Settings' => 'app_update/settings']],
    ['label' => 'Notification', 'icon' => 'fa-bell', 'ctrl' => 'notification', 'rbac' => 'notification', 'items' => ['Listing' => 'notification/listing']],
    ['label' => 'Device Management', 'icon' => 'fa-mobile', 'ctrl' => 'device', 'rbac' => null, 'items' => ['Registered Devices' => 'device/listing', 'Send Notification' => 'device/send_push']],
    ['label' => 'Tasks', 'icon' => 'fa-clipboard', 'ctrl' => 'task', 'rbac' => null, 'items' => ['All Tasks' => '/task/task', 'Add Task' => '/task/task/add']],
    ['label' => 'Activity & Audit Monitor', 'icon' => 'fa-desktop', 'ctrl' => 'monitor', 'rbac' => null, 'items' => ['Overview' => 'monitor/overview', 'Page Traffic' => 'monitor/traffic', 'Entry Audit' => 'monitor/entries', 'Logins' => 'monitor/logins', 'Activity Timeline' => 'monitor/timeline', 'IP & Location' => 'monitor/ip_intel', 'Anomalies' => 'monitor/anomalies', 'Retention' => 'monitor/retention']],
    ['label' => 'Password Manager', 'icon' => 'fa-lock', 'ctrl' => 'bank_password', 'rbac' => 'bank_password', 'items' => ['Listing' => 'bank_password/listing', 'Add' => 'bank_password/add', 'Export History' => 'bank_password/history']],
    ['label' => 'Account Name', 'icon' => 'fa-address-book-o', 'ctrl' => 'account_name', 'rbac' => 'account_name', 'items' => ['Listing' => 'account_name/listing', 'Generate Account Name' => 'account_name/add']],
    ['label' => 'FCI Driver', 'icon' => 'fa-id-card-o', 'ctrl' => 'driver_module', 'rbac' => 'driver_module', 'items' => ['Driver Listing' => 'driver_module/listing', 'Add Driver' => 'driver_module/add']],
    ['label' => 'FCI Truck', 'icon' => 'fa-truck', 'ctrl' => 'truck_module', 'rbac' => 'truck_module', 'items' => ['Listing' => 'truck_module/listing', 'Add' => 'truck_module/add']],
    ['label' => 'Attendance', 'icon' => 'fa-calendar-check-o', 'ctrl' => 'attendance', 'rbac' => 'attendance', 'items' => ['Employee Listing' => 'attendance/employee_listing', 'Add Employee' => 'attendance/employee_add', 'Attendance Listing' => 'attendance/listing', 'Mark Attendance' => 'attendance/add', 'Report' => 'attendance/report']],
    ['label' => 'Jama Naam Voucher', 'icon' => 'fa-exchange', 'ctrl' => 'account', 'rbac' => 'account', 'items' => ['Jama (जमा)' => 'account/entry?type=deposit', 'Naam (नाम)' => 'account/entry?type=expenses']],
    ['label' => 'Cold Lot System', 'icon' => 'fa-snowflake-o', 'ctrl' => 'cold_lot_system', 'rbac' => 'cold_lot_system', 'firm3' => true, 'items' => ['Cold Lot Listing' => 'cold_lot_system/listing', 'Add Cold Lot' => 'cold_lot_system/add', 'Kisan Accounts' => 'cold_lot_system/kisan_listing', 'Employee Accounts' => 'cold_lot_system/employee_listing', '——' => '---', 'Delivery Order (soon)' => '#soon', 'Billing — Kisan Bills' => 'cold_lot_system/billing/kisan_inventory_bill/listing', 'Inventory Control (soon)' => '#soon', 'Reports' => 'cold_lot_system/reports', 'Bank Stock Statement' => 'cold_lot_system/bank_statement', 'Saved Bank Statements' => 'cold_lot_system/bank_statement_listing', 'Bank Statement Settings' => 'cold_lot_system/bank_statement_setting']],
    ['label' => 'Cold Storage Inventory', 'icon' => 'fa-cube', 'ctrl' => 'cold_inventory', 'rbac' => 'cold_lot_system', 'firm3' => true, 'items' => ['Overview' => 'cold_inventory/overview', 'Stock Position' => 'cold_inventory/report/variety', 'Movement Register' => 'cold_inventory/report/movement']],
    ['label' => 'Rice Mill Website', 'icon' => 'fa-comments', 'ctrl' => 'ricemill_inquiry', 'rbac' => 'ricemill_inquiry', 'items' => ['Website Inquiries' => 'ricemill_inquiry/listing', 'View Website ↗' => '/ricemill']],
    ['label' => 'Kisan Vahi', 'icon' => 'fa-leaf', 'ctrl' => 'kisan_vahi', 'rbac' => 'accountMapping', 'badge' => 'New', 'items' => ['Registration' => 'kisan_vahi/register', 'Registrations List' => 'Kisanreg/listing', 'Reg Report' => 'Kisanreg/report', 'Add Entry' => 'kisan_vahi/entry', 'Khata Naksha' => 'accountMapping/account_mapping', 'Thumb Figure' => 'accountMapping/thumb_figure', 'Farmer Captures' => 'accountMapping/captures', 'Parcha Report' => 'kisan_vahi/report', 'Chrome Extension' => 'kisan_vahi/extension']],
    ['label' => 'Reports', 'icon' => 'fa-file-text-o', 'ctrl' => 'report', 'rbac' => 'report', 'items' => ['Account Report' => 'report/search', 'Account Statement' => 'report/byaccount_name', 'Account Ledger' => 'report/ledger', 'Daily Rokad Parcha' => 'report/rokad_parcha', 'Deleted Rokad Entries' => 'report/deleted_entries', 'Attachments Gallery' => 'attachments']],
    ['label' => 'Accounting Reports', 'icon' => 'fa-bar-chart', 'ctrl' => 'accounts_report', 'rbac' => 'report', 'items' => ['Trial Balance' => 'accounts_report/trial_balance', 'Balance Sheet' => 'accounts_report/balance_sheet', 'Profit & Loss' => 'accounts_report/profit_loss', 'Trading Profit' => 'accounts_report/trading_profit', 'Outstanding' => 'accounts_report/outstanding', 'Debtor Report' => 'accounts_report/debtors', 'Creditor Report' => 'accounts_report/creditors', 'Ageing Report' => 'accounts_report/ageing', 'Sister-Firm Reconciliation' => 'accounts_report/inter_firm']],
    ['label' => 'Stock Records', 'icon' => 'fa-cubes', 'ctrl' => 'stock', 'rbac' => 'stock', 'items' => ['Opening Stocks Details' => 'stock/listing', 'Stock Position' => 'stock/position', 'Stock Statement' => 'stock/statement', 'HSN Code Master' => 'hsn/listing', 'Item Master' => 'item_master/listing']],
    ['label' => 'Bill Of Supply', 'icon' => 'fa-file-text-o', 'ctrl' => 'invoice', 'rbac' => 'invoice', 'items' => ['Listing' => 'invoice/listing', 'Add' => 'invoice/add', 'Verification Log' => 'invoice/verify_logs', 'PDF Theme Manager' => 'pdf_theme/listing']],
    ['label' => 'Credit/Debit Note', 'icon' => 'fa-exchange', 'ctrl' => 'cd_note', 'rbac' => 'cd_note', 'items' => ['Listing' => 'cd_note/listing', 'Add' => 'cd_note/add']],
    ['label' => 'Unregistered BOS', 'icon' => 'fa-file-o', 'ctrl' => 'uninvoice', 'rbac' => 'uninvoice', 'items' => ['Listing' => 'uninvoice/listing', 'Add' => 'uninvoice/add']],
    ['label' => 'Delivery Challan', 'icon' => 'fa-truck', 'ctrl' => 'delivery_challan', 'rbac' => 'delivery_challan', 'items' => ['Listing' => 'delivery_challan/listing', 'Add' => 'delivery_challan/add']],
    ['label' => 'Billing Register', 'icon' => 'fa-book', 'ctrl' => 'billing_register', 'rbac' => 'report', 'items' => ['Listing' => 'billing_register/listing', 'Add' => 'billing_register/add', 'Account Statement' => 'billing_register/statement']],
    ['label' => 'E-Tax Invoice', 'icon' => 'fa-file-text', 'ctrl' => 'taxinvoice', 'rbac' => 'taxinvoice', 'items' => ['Add E-invoice' => 'taxinvoice/e_invoice_add', 'Listing E-Invoice' => 'taxinvoice/einvoice_listing', 'GST Settings' => 'gst_setting']],
    ['label' => 'Purchase From Farmers', 'icon' => 'fa-inr', 'ctrl' => 'payment_receipt', 'rbac' => 'payment_receipt', 'items' => ['Add' => 'payment_receipt/add', 'Listing' => 'payment_receipt/listing']],
    ['label' => 'Purchase Module', 'icon' => 'fa-shopping-cart', 'ctrl' => 'purchase_module', 'rbac' => 'purchase_module', 'items' => ['Add Bill' => 'purchase_module/add', 'Listing' => 'purchase_module/listing']],
    ['label' => 'Sales Register', 'icon' => 'fa-tags', 'ctrl' => 'sale_module', 'rbac' => 'sale_module', 'items' => ['Listing' => 'sale_module/listing']],
    ['label' => 'Lot System', 'icon' => 'fa-th-large', 'ctrl' => 'lot_system', 'rbac' => 'lot_system', 'items' => ['Listing' => 'lot_system/listing', 'Add' => 'lot_system/add']],
    ['label' => 'Paddy Center Challan', 'icon' => 'fa-pencil-square-o', 'ctrl' => 'paddylotsystem', 'rbac' => 'PaddyLotsystem', 'items' => ['Listing' => 'PaddyLotsystem/listing', 'Add' => 'PaddyLotsystem/add']],
    ['label' => 'Setting', 'icon' => 'fa-cog', 'ctrl' => 'setting', 'rbac' => 'setting', 'href' => base_url('admin/setting/hub')],
    ['label' => 'Users', 'icon' => 'fa-users', 'ctrl' => 'users', 'rbac' => 'users', 'items' => ['Listing' => 'users/listing', 'Add' => 'users/add', 'Role Permissions' => 'role_permissions', 'User Permissions' => 'user_permissions']],
    ['label' => 'Salary Module', 'icon' => 'fa-money', 'ctrl' => 'salary_module', 'rbac' => 'salary_module', 'items' => ['Listing' => 'Salary_Module/listing', 'Add' => 'Salary_Module/add', 'Credit History' => 'Salary_Module/history']],
    ['label' => 'SEO & Search', 'icon' => 'fa-search', 'ctrl' => 'seo', 'rbac' => 'seo', 'items' => ['SEO Settings' => 'seo', 'Generate Sitemap' => 'seo/generate']],
];
?>
<style>
    .menu-new { display: inline-block; margin-left: 7px; padding: 1px 6px; border-radius: 9px;
        background: #dc2626; color: #fff; font-size: 9px; font-weight: 800; letter-spacing: .04em;
        vertical-align: middle; line-height: 1.5; text-transform: uppercase;
        box-shadow: 0 0 0 1px rgba(220,38,38,.35); }
    .dropdown-menu .menu-new { background: #ef4444; }
    .menu-badge-new { display:inline-block; margin-left:6px; padding:1px 6px; border-radius:9px;
        background:#16a34a; color:#fff; font-size:9px; font-weight:800; letter-spacing:.04em;
        vertical-align:middle; line-height:1.5; text-transform:uppercase; }
    .sidebar-menu .menu-sep { list-style:none; border-top:1px solid rgba(255,255,255,.10); margin:6px 12px; padding:0; }
    .sidebar-menu .is-soon > a { opacity:.45; cursor:not-allowed; }
</style>
<ul class="sidebar-menu scrollable pos-r">
    <?php foreach ($menu as $m): ?>
        <?php if (! $can($m['rbac'])) { continue; } ?>
        <?php if (! empty($m['firm3']) && $pt !== 3) { continue; } ?>
        <?php $active = ($seg2 !== '' && $seg2 === $m['ctrl']) || ($m['ctrl'] === 'task' && $seg1 === 'task'); ?>
        <?php
        // "NEW" on the group only if its own link OR a sub-link resolves on CI4.
        $groupNew = false;
        if (! empty($m['href'])) {
            $hrefUri = ltrim(str_replace(rtrim(base_url('admin'), '/') . '/', '', $m['href']), '/');
            $groupNew = $uriNew($hrefUri);
        }
        if (! $groupNew && ! empty($m['items'])) {
            foreach ($m['items'] as $uri) {
                if ($uriNew($uri)) { $groupNew = true; break; }
            }
        }
        $badge = ! empty($m['badge']) ? '<span class="menu-badge-new">' . esc($m['badge']) . '</span>' : '';
        ?>
        <?php if (! empty($m['href'])): ?>
            <li class="nav-item <?= $active ? 'btn_active' : '' ?>">
                <a href="<?= $m['href'] ?>"><span class="icon-holder"><i class="c-red-500 fa <?= esc($m['icon']) ?>"></i></span><span class="title"><?= esc($m['label']) ?><?= $badge ?><?= $groupNew ? $NEW : '' ?></span></a>
            </li>
        <?php else: ?>
            <li class="nav-item dropdown <?= $active ? 'open' : '' ?>">
                <a class="dropdown-toggle" href="javascript:void(0);">
                    <span class="icon-holder"><i class="c-red-500 fa <?= esc($m['icon']) ?>"></i></span>
                    <span class="title"><?= esc($m['label']) ?><?= $badge ?><?= $groupNew ? $NEW : '' ?></span>
                    <span class="arrow"><i class="fa fa-angle-right"></i></span>
                </a>
                <ul class="dropdown-menu">
                    <?php foreach ($m['items'] as $label => $uri): ?>
                        <?php if ($uri === '---'): ?>
                            <li class="menu-sep"></li>
                        <?php elseif ($uri === '#soon'): ?>
                            <li class="is-soon"><a class="sidebar-link" href="javascript:void(0);" title="Planned module"><?= esc($label) ?></a></li>
                        <?php else: ?>
                            <?php $newTab = (strpos($label, '↗') !== false) ? ' target="_blank" rel="noopener"' : ''; ?>
                            <li><a class="sidebar-link" href="<?= $mkUrl($uri) ?>"<?= $newTab ?>><?= esc($label) ?><?= $uriNew($uri) ? $NEW : '' ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>
