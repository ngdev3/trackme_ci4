<?php
/**
 * Sidebar menu — CI4 port, mirroring the CI3 super-admin left_menu.php
 * (application/views/elements/left_menu.php). Same top-level modules, order,
 * labels and sub-links. Each group is RBAC-gated: a super admin sees everything;
 * a regular user sees a group only if erp_current_user_can(<rbac>, 'view').
 * Groups whose rbac key is null are super-admin-only admin tools.
 *
 * As controllers are ported their links resolve; until then a link may 404 —
 * the structure + names + RBAC gating are what this mirrors.
 */
helper(['url', 'permission']);

$seg2  = strtolower(service('uri')->getSegment(2, ''));
$isSA  = function_exists('erp_is_super_admin') && erp_is_super_admin();
$can   = function ($rbac) use ($isSA) {
    if ($isSA) { return true; }
    return $rbac && function_exists('erp_current_user_can') && erp_current_user_can($rbac, 'view');
};
$u = fn($p) => base_url('admin/' . $p);

/**
 * Controllers already ported to CI4 → show a "NEW" badge so it's obvious which
 * modules are live in the new project. Add keys here as more controllers land.
 */
$ready = ['dashboard', 'notification', 'users', 'role_permissions', 'user_permissions', 'invoice', 'seo', 'hsn', 'profile', 'help', 'attachments', 'app_setting', 'entry_trace', 'menu_order'];
$ctrlOf = fn($uri) => strtolower(explode('/', $uri)[0]);
$isNew  = fn($ctrl) => in_array(strtolower($ctrl), $ready, true);
$NEW    = '<span class="menu-new">NEW</span>';

/**
 * Each entry: 'label','icon','ctrl' (segment for active state),'rbac' (module key
 * or null = super-admin only), and either 'href' (single link) or 'items' => [label => uri].
 */
$menu = [
    ['label' => 'Dashboard', 'icon' => 'fa-tachometer', 'ctrl' => 'dashboard', 'rbac' => 'dashboard', 'href' => $u('dashboard')],
    ['label' => 'Help & FAQ', 'icon' => 'fa-question-circle', 'ctrl' => 'help', 'rbac' => null, 'href' => $u('help')],
    ['label' => 'Documents Renewal', 'icon' => 'fa-refresh', 'ctrl' => 'document', 'rbac' => 'document', 'items' => ['Add Document' => 'document/add', 'Document List' => 'document/listing']],
    ['label' => 'Letter Pad', 'icon' => 'fa-envelope-o', 'ctrl' => 'letter_pad', 'rbac' => 'letter_pad', 'items' => ['Create Letter' => 'letter_pad/add', 'Letters' => 'letter_pad/listing']],
    ['label' => 'APK Manager', 'icon' => 'fa-android', 'ctrl' => 'app_update', 'rbac' => 'app_update', 'items' => ['Versions' => 'app_update/listing', 'Upload Build' => 'app_update/upload', 'Employee Portal' => 'app_update/portal', 'Download Logs' => 'app_update/logs', 'Settings' => 'app_update/settings']],
    ['label' => 'Notification', 'icon' => 'fa-bell', 'ctrl' => 'notification', 'rbac' => 'notification', 'href' => $u('notification/listing')],
    ['label' => 'Device Management', 'icon' => 'fa-mobile', 'ctrl' => 'device', 'rbac' => null, 'items' => ['Devices' => 'device/listing', 'Send Push' => 'device/send_push']],
    ['label' => 'Tasks', 'icon' => 'fa-tasks', 'ctrl' => 'task', 'rbac' => null, 'href' => base_url('task')],
    ['label' => 'Activity & Audit Monitor', 'icon' => 'fa-desktop', 'ctrl' => 'monitor', 'rbac' => null, 'href' => $u('monitor')],
    ['label' => 'Password Manager', 'icon' => 'fa-key', 'ctrl' => 'bank_password', 'rbac' => 'bank_password', 'items' => ['Vault' => 'bank_password/listing', 'Add Credential' => 'bank_password/add', 'History' => 'bank_password/history']],
    ['label' => 'Account Name', 'icon' => 'fa-address-book-o', 'ctrl' => 'account_name', 'rbac' => 'account_name', 'items' => ['Accounts' => 'account_name/listing', 'Add Account' => 'account_name/add']],
    ['label' => 'FCI Driver', 'icon' => 'fa-id-card-o', 'ctrl' => 'driver_module', 'rbac' => 'driver_module', 'items' => ['Drivers' => 'driver_module/listing', 'Add Driver' => 'driver_module/add']],
    ['label' => 'FCI Truck', 'icon' => 'fa-truck', 'ctrl' => 'truck_module', 'rbac' => 'truck_module', 'items' => ['Trucks' => 'truck_module/listing', 'Add Truck' => 'truck_module/add']],
    ['label' => 'Attendance', 'icon' => 'fa-calendar-check-o', 'ctrl' => 'attendance', 'rbac' => 'attendance', 'items' => ['Employees' => 'attendance/employee_listing', 'Add Employee' => 'attendance/employee_add', 'Attendance' => 'attendance/listing', 'Mark Attendance' => 'attendance/add', 'Report' => 'attendance/report']],
    ['label' => 'Jama Naam Voucher', 'icon' => 'fa-exchange', 'ctrl' => 'account', 'rbac' => 'account', 'href' => $u('account/entry')],
    ['label' => 'Cold Lot System', 'icon' => 'fa-snowflake-o', 'ctrl' => 'cold_lot_system', 'rbac' => 'cold_lot_system', 'items' => ['Cold Lots' => 'cold_lot_system/listing', 'Add Lot' => 'cold_lot_system/add', 'Kisan' => 'cold_lot_system/kisan_listing', 'Employee' => 'cold_lot_system/employee_listing', 'Kisan Inventory Bill' => 'cold_lot_system/billing/kisan_inventory_bill/listing', 'Reports' => 'cold_lot_system/reports', 'Bank Statement' => 'cold_lot_system/bank_statement', 'Saved Statements' => 'cold_lot_system/bank_statement_listing', 'Statement Setting' => 'cold_lot_system/bank_statement_setting']],
    ['label' => 'Cold Storage Inventory', 'icon' => 'fa-cube', 'ctrl' => 'cold_inventory', 'rbac' => 'cold_lot_system', 'items' => ['Overview' => 'cold_inventory/overview', 'By Variety' => 'cold_inventory/report/variety', 'Movement' => 'cold_inventory/report/movement']],
    ['label' => 'Rice Mill Website', 'icon' => 'fa-leaf', 'ctrl' => 'kisan_vahi', 'rbac' => 'accountMapping', 'items' => ['Rice Mill Inquiry' => 'ricemill_inquiry/listing', 'Kisan Vahi Register' => 'kisan_vahi/register', 'Kisan Registration' => 'Kisanreg/listing', 'Kisan Reg Report' => 'Kisanreg/report', 'Kisan Vahi Entry' => 'kisan_vahi/entry', 'Account Mapping' => 'accountMapping/account_mapping', 'Thumb Figure' => 'accountMapping/thumb_figure', 'Farmer Captures' => 'accountMapping/captures', 'Kisan Vahi Report' => 'kisan_vahi/report', 'Extension Key' => 'kisan_vahi/extension']],
    ['label' => 'Reports', 'icon' => 'fa-bar-chart', 'ctrl' => 'report', 'rbac' => 'report', 'items' => ['Search' => 'report/search', 'By Account' => 'report/byaccount_name', 'Ledger' => 'report/ledger', 'Rokad Parcha' => 'report/rokad_parcha', 'Deleted Entries' => 'report/deleted_entries', 'Attachments' => 'attachments']],
    ['label' => 'Accounting Reports', 'icon' => 'fa-line-chart', 'ctrl' => 'accounts_report', 'rbac' => 'report', 'href' => $u('accounts_report')],
    ['label' => 'Stock Records', 'icon' => 'fa-cubes', 'ctrl' => 'stock', 'rbac' => 'stock', 'items' => ['Add Stock' => 'stock/add', 'Stock List' => 'stock/listing', 'Position' => 'stock/position', 'Statement' => 'stock/statement', 'HSN Master' => 'hsn/listing', 'Item Master' => 'item_master/listing']],
    ['label' => 'Bill Of Supply', 'icon' => 'fa-file-text-o', 'ctrl' => 'invoice', 'rbac' => 'invoice', 'items' => ['Bills' => 'invoice/listing', 'Add Bill' => 'invoice/add', 'Verify Logs' => 'invoice/verify_logs', 'PDF Theme' => 'pdf_theme/listing']],
    ['label' => 'Credit/Debit Note', 'icon' => 'fa-exchange', 'ctrl' => 'cd_note', 'rbac' => 'cd_note', 'items' => ['CD Notes' => 'cd_note/listing', 'Add CD Note' => 'cd_note/add']],
    ['label' => 'Unregistered BOS', 'icon' => 'fa-file-o', 'ctrl' => 'uninvoice', 'rbac' => 'uninvoice', 'items' => ['Bills' => 'uninvoice/listing', 'Add Bill' => 'uninvoice/add']],
    ['label' => 'Delivery Challan', 'icon' => 'fa-truck', 'ctrl' => 'delivery_challan', 'rbac' => 'delivery_challan', 'items' => ['Challans' => 'delivery_challan/listing', 'Add Challan' => 'delivery_challan/add']],
    ['label' => 'Billing Register', 'icon' => 'fa-book', 'ctrl' => 'billing_register', 'rbac' => 'report', 'items' => ['Register' => 'billing_register/listing', 'Add' => 'billing_register/add', 'Statement' => 'billing_register/statement']],
    ['label' => 'E-Tax Invoice', 'icon' => 'fa-file-text', 'ctrl' => 'taxinvoice', 'rbac' => 'taxinvoice', 'items' => ['E-Invoice Add' => 'taxinvoice/e_invoice_add', 'E-Invoice List' => 'taxinvoice/einvoice_listing', 'GST Setting' => 'gst_setting']],
    ['label' => 'Purchase From Farmers', 'icon' => 'fa-inr', 'ctrl' => 'payment_receipt', 'rbac' => 'payment_receipt', 'items' => ['Add Receipt' => 'payment_receipt/add', 'Receipts' => 'payment_receipt/listing']],
    ['label' => 'Purchase Module', 'icon' => 'fa-shopping-cart', 'ctrl' => 'purchase_module', 'rbac' => 'purchase_module', 'items' => ['Add Purchase' => 'purchase_module/add', 'Purchases' => 'purchase_module/listing']],
    ['label' => 'Sales Register', 'icon' => 'fa-tags', 'ctrl' => 'sale_module', 'rbac' => 'sale_module', 'href' => $u('sale_module/listing')],
    ['label' => 'Lot System', 'icon' => 'fa-th-large', 'ctrl' => 'lot_system', 'rbac' => 'lot_system', 'items' => ['Lots' => 'lot_system/listing', 'Add Lot' => 'lot_system/add']],
    ['label' => 'Paddy Center Challan', 'icon' => 'fa-leaf', 'ctrl' => 'paddylotsystem', 'rbac' => 'PaddyLotsystem', 'items' => ['Paddy Lots' => 'PaddyLotsystem/listing', 'Add Paddy Lot' => 'PaddyLotsystem/add']],
    ['label' => 'Salary Module', 'icon' => 'fa-money', 'ctrl' => 'salary_module', 'rbac' => 'salary_module', 'items' => ['Salaries' => 'Salary_Module/listing', 'Add' => 'Salary_Module/add', 'History' => 'Salary_Module/history']],
    ['label' => 'Users', 'icon' => 'fa-user-plus', 'ctrl' => 'users', 'rbac' => 'users', 'items' => ['Users' => 'users/listing', 'Add User' => 'users/add', 'Role Permissions' => 'role_permissions', 'User Permissions' => 'user_permissions']],
    ['label' => 'Setting', 'icon' => 'fa-cog', 'ctrl' => 'setting', 'rbac' => 'setting', 'items' => ['Setting Hub' => 'setting/hub', 'Templates' => 'setting/listing', 'Add Template' => 'setting/add', 'Add Firm' => 'setting/add_firm', 'Change FY' => 'setting/change_fy', 'TDS/TCS' => 'setting/tds_tcs', 'MSP' => 'setting/msp', 'GSTIN' => 'gstin', 'Opening Balance' => 'opening_balance']],
    ['label' => 'SEO & Search', 'icon' => 'fa-search', 'ctrl' => 'seo', 'rbac' => 'seo', 'items' => ['SEO Settings' => 'seo', 'Generate Files' => 'seo/generate']],
    ['label' => 'App Settings', 'icon' => 'fa-sliders', 'ctrl' => 'app_setting', 'rbac' => null, 'href' => $u('app_setting')],
];
?>
<style>
    .menu-new { display: inline-block; margin-left: 7px; padding: 1px 6px; border-radius: 9px;
        background: #16a34a; color: #fff; font-size: 9px; font-weight: 800; letter-spacing: .04em;
        vertical-align: middle; line-height: 1.5; text-transform: uppercase; }
    .dropdown-menu .menu-new { background: #22c55e; }
</style>
<ul class="sidebar-menu scrollable pos-r">
    <?php foreach ($menu as $m): ?>
        <?php if (! $can($m['rbac'])) { continue; } ?>
        <?php $active = ($seg2 !== '' && $seg2 === $m['ctrl']); ?>
        <?php
        // A group is "ready" if its own controller is ported, or any sub-item's is.
        $groupNew = $isNew($m['ctrl']);
        if (! $groupNew && ! empty($m['items'])) {
            foreach ($m['items'] as $uri) {
                if ($isNew($ctrlOf($uri))) { $groupNew = true; break; }
            }
        }
        ?>
        <?php if (! empty($m['href'])): ?>
            <li class="nav-item <?= $active ? 'btn_active' : '' ?>">
                <a href="<?= $m['href'] ?>"><span class="icon-holder"><i class="c-red-500 fa <?= esc($m['icon']) ?>"></i></span><span class="title"><?= esc($m['label']) ?><?= $groupNew ? $NEW : '' ?></span></a>
            </li>
        <?php else: ?>
            <li class="nav-item dropdown <?= $active ? 'open' : '' ?>">
                <a class="dropdown-toggle" href="javascript:void(0);">
                    <span class="icon-holder"><i class="c-red-500 fa <?= esc($m['icon']) ?>"></i></span>
                    <span class="title"><?= esc($m['label']) ?><?= $groupNew ? $NEW : '' ?></span>
                    <span class="arrow"><i class="fa fa-angle-right"></i></span>
                </a>
                <ul class="dropdown-menu">
                    <?php foreach ($m['items'] as $label => $uri): ?>
                        <li><a class="sidebar-link" href="<?= $u($uri) ?>"><?= esc($label) ?><?= $isNew($ctrlOf($uri)) ? $NEW : '' ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>
