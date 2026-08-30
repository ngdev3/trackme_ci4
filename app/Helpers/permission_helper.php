<?php

/**
 * RBAC permission helper — CI4 port of application/helpers/permission_helper.php.
 * Same registry + resolution order (super admin -> per-user grants -> role
 * defaults). Session/DB access goes through FyContext + Config\Database.
 *
 * Super Admin is defined ONLY by isSuperAdmin==1 (NOT user_type) — almost every
 * account is user_type 1, so treating that as admin would bypass RBAC entirely.
 */

use Config\Database;

if (! function_exists('erp_module_registry')) {
    /** Canonical module_key => label map (must match controller/URL segment). */
    function erp_module_registry(): array
    {
        return [
            'dashboard' => 'Dashboard', 'task' => 'Tasks', 'document' => 'Documents Renewal',
            'notification' => 'Notification', 'chat' => 'Employee Chat', 'device' => 'Device Management',
            'monitor' => 'Activity & Audit Monitor', 'letter_pad' => 'Letter Pad', 'invoice' => 'Bill of Supply',
            'uninvoice' => 'Unregistered Bill of Supply', 'taxinvoice' => 'Tax Invoice',
            'purchase_module' => 'Purchase Module', 'sale_module' => 'Sales Register',
            'purchase_gst_summary' => 'Purchase: GST Commodity Summary', 'sale_gst_summary' => 'Sales: GST Commodity Summary',
            'cd_note' => 'Credit/Debit Note', 'delivery_challan' => 'Delivery Challan',
            'billing_register' => 'Billing Register', 'billing' => 'Billing', 'service_charge' => 'Service Charge',
            'payment_receipt' => 'Payment Receipt', 'bank_password' => 'Password Manager', 'account_name' => 'Account Name',
            'awak_jawak' => 'Inward-Outward Register', 'driver_module' => 'FCI Driver', 'truck_module' => 'FCI Truck',
            'attendance' => 'Attendance', 'account' => 'Jama Naam Voucher', 'cold_lot_system' => 'Cold Lot System',
            'cold_inventory' => 'Cold Storage Inventory', 'lot_system' => 'Lot System', 'PaddyLotsystem' => 'Paddy Center Challan',
            'Kisanreg' => 'KV Registration', 'report' => 'Reports', 'attachments' => 'Attachments Gallery',
            'accounts_report' => 'Accounting Reports', 'ricemill_inquiry' => 'Rice Mill Website', 'accountMapping' => 'Mapping KV',
            'kisan_vahi' => 'Kisan Vahi (Unified)', 'stock' => 'Stock', 'hsn' => 'HSN Code Master', 'item_master' => 'Item Master',
            'opening_balance' => 'Opening Balance', 'gstin' => 'GSTIN Analysis', 'app_update' => 'APK Manager / App Updates',
            'city' => 'Master: City', 'state' => 'Master: State', 'tax' => 'Master: Tax', 'quality' => 'Master: Quality',
            'seller' => 'Master: Seller', 'site' => 'Master: Site', 'reason' => 'Master: Reason', 'purchaser' => 'Master: Purchaser',
            'setting' => 'Setting', 'backup_restore' => 'Backup & Restore', 'seo' => 'SEO & Search', 'users' => 'Users',
            'salary_module' => 'Salary Module', 'role_permissions' => 'Role Permissions', 'user_permissions' => 'User Permissions',
        ];
    }
}

if (! function_exists('erp_is_super_admin')) {
    function erp_is_super_admin(): bool
    {
        return service('fyContext')->isSuperAdmin();
    }
}

if (! function_exists('erp_current_user_id')) {
    function erp_current_user_id(): int
    {
        return (int) (service('fyContext')->userId() ?? 0);
    }
}

if (! function_exists('erp_user_permission_map')) {
    /** Per-user module permission rows keyed by lower-cased module_key (cached). */
    function erp_user_permission_map($userId): array
    {
        static $cache = [];
        $userId = (int) $userId;
        if (isset($cache[$userId])) {
            return $cache[$userId];
        }
        $map = [];
        $db  = Database::connect();
        if ($userId && $db->tableExists('erp_user_module_permissions')) {
            foreach ($db->table('erp_user_module_permissions')->where('user_id', $userId)->get()->getResult() as $row) {
                $map[strtolower($row->module_key)] = $row;
            }
        }
        return $cache[$userId] = $map;
    }
}

if (! function_exists('erp_user_has_custom_permissions')) {
    function erp_user_has_custom_permissions($userId): bool
    {
        return count(erp_user_permission_map($userId)) > 0;
    }
}

if (! function_exists('erp_permission_action_from_method')) {
    function erp_permission_action_from_method($method): string
    {
        $method = strtolower((string) $method);
        if (in_array($method, ['add', 'insert', 'create', 'save', 'send', 'deposite', 'expenditure', 'upload'], true) || preg_match('/(^|_)add$/', $method)) {
            return 'add';
        }
        if (in_array($method, ['edit', 'update', 'updateuserstatus', 'change_status', 'status', 'renew_session', 'toggle', 'toggle_status', 'mark_latest', 'settings'], true) || strpos($method, 'update') === 0 || strpos($method, 'toggle') === 0) {
            return 'edit';
        }
        if (in_array($method, ['delete', 'remove', 'destroy'], true) || strpos($method, 'delete') === 0) {
            return 'delete';
        }
        return 'view';
    }
}

if (! function_exists('erp_current_user_can')) {
    /**
     * Resolution: super admin -> per-user grants (default-deny once configured)
     * -> role (user_type) defaults (backward-compatible fallback).
     */
    function erp_current_user_can($moduleKey, $action = 'view'): bool
    {
        // No admin session (public) -> do not restrict.
        if (! session()->get('user_type')) {
            return true;
        }
        if (erp_is_super_admin()) {
            return true;
        }

        $uid = erp_current_user_id();

        // Per-user permissions take precedence once configured.
        if ($uid && erp_user_has_custom_permissions($uid)) {
            $map = erp_user_permission_map($uid);
            $key = strtolower($moduleKey);
            if (! isset($map[$key])) {
                return false; // configured user, module not granted => deny
            }
            $row    = $map[$key];
            $column = 'can_' . $action;
            if (! isset($row->{$column})) {
                $column = 'can_view';
            }
            return (int) $row->{$column} === 1;
        }

        // Fallback: legacy role (user_type) based permissions.
        $userType = (int) session()->get('user_type');
        $db       = Database::connect();
        if (! $db->tableExists('erp_role_module_permissions')) {
            return true;
        }
        $row = $db->table('erp_role_module_permissions')
            ->where('user_type', $userType)
            ->where('module_key', $moduleKey)
            ->get()->getRow();

        if (! $row) {
            return true; // no role row configured -> allow (legacy default)
        }
        $column = 'can_' . $action;
        if (! isset($row->{$column})) {
            $column = 'can_view';
        }
        return (int) $row->{$column} === 1;
    }
}
