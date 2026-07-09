<?php

use Config\Services;

if (! function_exists('company_id')) {
    /** Active company id for the current user, or null (needs onboarding). */
    function company_id(): ?int
    {
        return Services::company()->activeId();
    }
}

if (! function_exists('current_company')) {
    /** The active company record, or null. */
    function current_company(): ?array
    {
        return Services::company()->current();
    }
}

if (! function_exists('company_role')) {
    /** The current user's role in the active company (owner|admin|staff). */
    function company_role(): ?string
    {
        return Services::company()->role();
    }
}

if (! function_exists('post_login_url')) {
    /**
     * Where to send a user right after login, per account type:
     *   super_admin → Super Admin dashboard
     *   customer    → active firm dashboard, or Create Firm if they have none
     *   firm_user   → their assigned firm dashboard (logged out if none assigned)
     */
    function post_login_url(): string
    {
        switch (account_type()) {
            case 'super_admin':
                // Everyone lands on the dashboard by default; super admins reach
                // the SaaS panel via the "Super Admin Panel" menu item.
                return site_url('dashboard');
            case 'firm_user':
                return Services::company()->activeId()
                    ? site_url('dashboard')
                    : site_url('logout'); // a firm user with no firm cannot proceed
            default: // customer
                return Services::company()->activeId()
                    ? site_url('dashboard')
                    : site_url('company/create');
        }
    }
}

if (! function_exists('indian_states')) {
    /** @return list<string> States + union territories (for the company form). */
    function indian_states(): array
    {
        return [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa',
            'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala',
            'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland',
            'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
            'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
            'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
            'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry',
        ];
    }
}

if (! function_exists('company_countries')) {
    /** @return list<string> */
    function company_countries(): array
    {
        return ['India', 'United States', 'United Kingdom', 'United Arab Emirates', 'Singapore', 'Australia', 'Canada', 'Other'];
    }
}

if (! function_exists('gst_registration_types')) {
    /** @return list<string> */
    function gst_registration_types(): array
    {
        return ['Regular', 'Composition', 'Unregistered', 'Consumer', 'SEZ', 'Overseas'];
    }
}

if (! function_exists('firm_roles')) {
    /**
     * Roles a customer can assign to a firm user. 'owner' is the customer and is
     * not assignable here. @return array<string,string> code => label
     */
    function firm_roles(): array
    {
        return [
            'admin'      => 'Admin',
            'accountant' => 'Accountant',
            'sales'      => 'Sales User',
            'purchase'   => 'Purchase User',
            'inventory'  => 'Inventory User',
            'viewer'     => 'Viewer',
        ];
    }
}

if (! function_exists('firm_role_modules')) {
    /**
     * Which firm module codes a role may access. @return list<string>
     */
    function firm_role_modules(string $role): array
    {
        $all = ['dashboard', 'accounting', 'rokad', 'sales', 'purchase', 'inventory', 'gst', 'reports', 'notes', 'reminders', 'firm_users', 'firm_settings'];
        switch ($role) {
            case 'owner':
            case 'admin':      return $all;
            case 'accountant': return ['dashboard', 'accounting', 'rokad', 'gst', 'reports', 'notes', 'reminders'];
            case 'sales':      return ['dashboard', 'sales', 'reports', 'notes', 'reminders'];
            case 'purchase':   return ['dashboard', 'purchase', 'reports', 'notes', 'reminders'];
            case 'inventory':  return ['dashboard', 'inventory', 'reports', 'notes', 'reminders'];
            case 'viewer':     return ['dashboard', 'reports'];
            default:           return ['dashboard'];
        }
    }
}

if (! function_exists('firm_can')) {
    /**
     * Firm-level permission check for the current user in the active firm.
     * Owner/admin can do everything; a viewer is read-only; other roles get the
     * modules mapped to their role, unless a per-user override list is set.
     */
    function firm_can(string $module, string $action = 'view'): bool
    {
        if (is_super_admin_account()) {
            return true; // SaaS operator side is governed by app RBAC, not firm roles
        }
        $role = company_role();
        if (! $role) {
            return false;
        }
        if (in_array($role, ['owner', 'admin'], true)) {
            return true;
        }
        if ($role === 'viewer' && $action !== 'view') {
            return false;
        }

        $overrides = Services::session()->get('company_permissions');
        $allowed   = is_array($overrides) && $overrides !== [] ? $overrides : firm_role_modules($role);

        return in_array($module, $allowed, true);
    }
}

if (! function_exists('business_types')) {
    /** @return list<string> */
    function business_types(): array
    {
        return ['Proprietorship', 'Partnership', 'Private Limited', 'Public Limited', 'LLP', 'HUF', 'Trust / NGO', 'Other'];
    }
}
