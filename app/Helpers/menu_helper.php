<?php

use App\Models\ModuleModel;
use Config\Services;

if (! function_exists('sidebar_modules')) {
    /**
     * Build the permission-filtered module tree for the sidebar.
     *
     * Returns a list of top-level nodes, each with a 'children' array.
     * A leaf is included only if the user can view it; a parent is
     * included only if at least one visible child (or its own URL) remains.
     *
     * @return list<array>
     */
    function sidebar_modules(): array
    {
        $acl     = Services::acl();
        $all     = (new ModuleModel())->activeOrdered();
        $viewable = array_flip($acl->viewableModuleCodes());
        $superOnly = [
            'logs' => true,
            'activity_logs' => true,
            'login_logs' => true,
            'roles' => true,
            'user_types' => true,
            'permissions' => true,
            'push_notifications' => true,
            'api_monitor' => true, // Mobile API Monitor — Super Admin only
        ];

        $isVisible = static function (array $m) use ($acl, $viewable, $superOnly): bool {
            if ($acl->isSuperAdmin()) {
                return true;
            }
            if (isset($superOnly[$m['code']])) {
                return false;
            }
            return isset($viewable[$m['code']]);
        };

        // Split into parents and children.
        $tree = [];
        foreach ($all as $m) {
            if ($m['parent_id'] === null) {
                if (! $acl->isSuperAdmin() && isset($superOnly[$m['code']])) {
                    continue;
                }
                $tree[(int) $m['id']] = $m + ['children' => []];
            }
        }
        foreach ($all as $m) {
            if ($m['parent_id'] !== null && isset($tree[(int) $m['parent_id']])) {
                if (! $isVisible($m)) {
                    continue; // hide leaf the user cannot view
                }
                $tree[(int) $m['parent_id']]['children'][] = $m;
            }
        }

        // Drop parents that ended up with no visible children and have no URL of their own.
        $result = [];
        foreach ($tree as $node) {
            if (! empty($node['url'])) {
                if ($isVisible($node)) {
                    $result[] = $node;
                }
                continue;
            }
            if ($node['children'] !== []) {
                $result[] = $node;
            }
        }

        // Enforce fixed placements regardless of stored sort order or newly added
        // modules: Dashboard first, "Hisaab Kitaab Vahi" (transactions) directly
        // after it, and Settings always last. Everything else keeps its order.
        $rank = static function (array $node): int {
            switch ($node['code']) {
                case 'dashboard':    return -2;
                case 'transactions': return -1;   // Hisaab Kitaab Vahi — right after Dashboard
                case 'settings':     return PHP_INT_MAX; // always last
                default:             return 0;
            }
        };
        usort($result, static function (array $a, array $b) use ($rank): int {
            $ra = $rank($a);
            $rb = $rank($b);
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }
            // Preserve the existing relative order for everything else.
            return ((int) ($a['sort_order'] ?? 0) <=> (int) ($b['sort_order'] ?? 0))
                ?: ((int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0));
        });

        return $result;
    }
}

if (! function_exists('render_firm_sidebar')) {
    /**
     * Firm-side navigation for customers and firm users: a fixed menu of firm
     * modules filtered by the user's firm role via firm_can(). Only built
     * modules are listed, so there are no dead links.
     */
    function render_firm_sidebar(): string
    {
        helper(['company', 'auth', 'subscription']);

        // No active company (e.g. brand-new user, or just deleted their last
        // firm): lock the workspace down to the essentials until they create one.
        if (! company_id()) {
            $mini = [
                ['company/create', 'Create Company', 'bi bi-plus-circle'],
                ['settings', 'Settings', 'bi bi-gear'],
                ['help', 'Help & Support', 'bi bi-life-preserver'],
            ];
            $html = '';
            foreach ($mini as [$url, $label, $icon]) {
                if ($url === 'settings' && function_exists('can') && ! can('settings', 'view')) {
                    continue;
                }
                $active = is_menu_active($url) ? ' active' : '';
                $html .= '<li class="nav-item"><a href="' . site_url($url) . '" class="nav-link' . $active . '">'
                    . '<i class="nav-icon ' . esc($icon) . '"></i><p>' . esc($label) . '</p></a></li>';
            }
            return $html;
        }

        // [module-code, label, icon, url, children[]]
        // Mirrors the canonical module tree (see `modules` table) minus the
        // super-admin-only sections, so a firm owner/admin sees the same core
        // app menu — with "Hissab-Kitaab Vahi" (transactions) right after the
        // dashboard, exactly like the admin sidebar.
        // Accounting and Firm Users are intentionally NOT here — they are
        // reserved for the Super Admin only (see request). "Opening Balance"
        // is the renamed Shri Rokad Nagad screen. "Trash" holds soft-deleted
        // companies and is owner/admin-only (via firm_can).
        $menu = [
            ['dashboard', 'Dashboard', 'bi bi-speedometer2', 'dashboard', []],
            ['rokad', brand_name() . ' Vahi', 'bi bi-journal-text', null, [
                ['Rokadh Parcha', 'transactions'],
                ['Add Deposit (Jama)', 'transactions/add/jama'],
                ['Add Expense (Naam)', 'transactions/add/naam'],
                ['Rokad Vahi', 'transactions/list'],
                ['Party Accounts', 'transactions/parties'],
                ['Account Statement', 'transactions/statement'],
                ['Report', 'transactions/report/breakdown'],
                ['Opening Balance', 'transactions/opening'],
            ]],
            ['notes', 'Notes', 'bi bi-sticky', 'notes', []],
            ['reminders', 'Reminders', 'bi bi-alarm', 'reminders', []],
            ['passwords', 'Password Manager', 'bi bi-shield-lock', null, [
                ['Password List', 'passwords/list'],
                ['Add Password', 'passwords/add'],
            ]],
            ['calculator', 'Calculator', 'bi bi-calculator-fill', 'calculator', []],
            ['inventory', 'Stock / Inventory', 'bi bi-boxes', null, [
                ['Inventory Dashboard', 'inventory'],
                ['Product Master', 'inventory/products'],
                ['Stock In / Out', 'inventory/stock'],
                ['Report', 'inventory/reports'],
            ]],
            ['invoices', 'Sales & Purchase', 'bi bi-receipt', null, [
                ['All Bills', 'invoices'],
                ['New Sale', 'invoices/new/sale'],
                ['New Purchase', 'invoices/new/purchase'],
            ]],
            ['upi_qr', 'Receive Payment', 'bi bi-qr-code', 'upi-qr', []],
            ['subscription', 'Subscription', 'bi bi-gem', null, [
                ['My Plan', 'subscription'],
                ['Payment History', 'subscription/transactions'],
            ]],
            ['firm_users', 'Trash', 'bi bi-trash', 'company/trash', []],
            ['settings', 'Settings', 'bi bi-gear', 'settings', []],
            ['help', 'Help & Support', 'bi bi-life-preserver', null, [
                ['Help & FAQ', 'help'],
                ['Support Chat', 'help/support'],
            ]],
        ];

        $html = '';
        foreach ($menu as [$code, $label, $icon, $url, $children]) {
            // Help & Support and Subscription are open to every signed-in user.
            if ($code !== 'help' && $code !== 'subscription' && ! firm_can($code)) {
                continue;
            }
            // Package feature gate — modules the active subscription package does
            // NOT include are still SHOWN (never hidden), but flagged as locked.
            // Clicking one hits the 'feature' route filter, which redirects to the
            // Subscription page with an "upgrade required" message (same hasFeature()
            // rule the routes/views enforce). "Trash" is the company recycle bin.
            $featMap = [
                'notes'      => 'notes',
                'reminders'  => 'reminder',
                'passwords'  => 'password_manager',
                'calculator' => 'calculator',
            ];
            $feat   = $featMap[$code] ?? ($url === 'company/trash' ? 'trash' : null);
            $locked = $feat !== null && ! hasFeature($feat);
            $lockBadge = $locked ? ' <i class="nav-lock bi bi-lock-fill" title="Upgrade your plan to unlock"></i>' : '';
            // Password Manager access is governed by app RBAC (its routes use a
            // permission filter), so mirror that here to avoid a dead menu link.
            if ($code === 'passwords' && function_exists('can') && ! can('passwords', 'view')) {
                continue;
            }
            // Settings route is permission-gated too — only show if reachable.
            if ($code === 'settings' && function_exists('can') && ! can('settings', 'view')) {
                continue;
            }
            if ($code === 'passwords' && function_exists('can') && ! can('passwords', 'add')) {
                $children = array_values(array_filter($children, static function ($child) {
                    return (isset($child[1]) ? $child[1] : '') !== 'passwords/add';
                }));
            }

            if ($children === []) {
                $active = is_menu_active($url) ? ' active' : '';
                $html .= '<li class="nav-item"><a href="' . site_url($url) . '" class="nav-link' . $active . ($locked ? ' is-locked' : '') . '">'
                    . '<i class="nav-icon ' . esc($icon) . '"></i><p>' . esc($label) . $lockBadge . '</p></a></li>';
                continue;
            }

            $bestIdx = -1;
            $bestLen = -1;
            foreach ($children as $i => [$clabel, $curl]) {
                $len = menu_match_len($curl);
                if ($len > $bestLen) {
                    $bestLen = $len;
                    $bestIdx = $i;
                }
            }
            if ($code === 'passwords' && $bestLen < 0 && is_menu_active('passwords')) {
                $bestIdx = 0;
                $bestLen = strlen('passwords');
            }
            $childActive = $bestLen >= 0;
            $childHtml   = '';
            foreach ($children as $i => [$clabel, $curl]) {
                $ca = ($i === $bestIdx);
                $childHtml .= '<li class="nav-item"><a href="' . site_url($curl) . '" class="nav-link' . ($ca ? ' active' : '') . '">'
                    . '<i class="nav-icon bi bi-dot"></i><p>' . esc($clabel) . '</p></a></li>';
            }
            $html .= '<li class="nav-item' . ($childActive ? ' menu-open' : '') . '">'
                . '<a href="#" class="nav-link' . ($childActive ? ' active' : '') . ($locked ? ' is-locked' : '') . '">'
                . '<i class="nav-icon ' . esc($icon) . '"></i><p>' . esc($label) . $lockBadge . '<i class="nav-arrow bi bi-chevron-right"></i></p></a>'
                . '<ul class="nav nav-treeview">' . $childHtml . '</ul></li>';
        }

        return $html;
    }
}

if (! function_exists('is_menu_active')) {
    /**
     * Whether a module URL matches the current request (first path segment).
     */
    function is_menu_active(?string $url): bool
    {
        return menu_match_len($url) >= 0;
    }
}

if (! function_exists('menu_match_len')) {
    /**
     * How well a menu URL matches the current request: the length of the URL
     * when it is an exact match or a prefix of the current path (so sub-pages
     * like `users/edit/5` keep the `users` item active), or -1 when it does not
     * match. Longer = more specific, which lets callers pick the best child.
     *
     * Uses uri_string() (app-relative) so it works under a sub-directory install
     * such as /ERP/ — getUri()->getPath() would wrongly include "ERP".
     */
    function menu_match_len(?string $url): int
    {
        if (empty($url)) {
            return -1;
        }
        helper('url');
        $current = trim(uri_string(), '/');
        $url     = trim($url, '/');
        if ($url === '') {
            return -1;
        }
        if ($current === $url || str_starts_with($current, $url . '/')) {
            return strlen($url);
        }
        return -1;
    }
}

if (! function_exists('render_sidebar')) {
    /**
     * Render the AdminLTE 4 sidebar navigation from the module tree.
     */
    function render_sidebar(): string
    {
        $nodes = sidebar_modules();
        $html  = '';

        foreach ($nodes as $node) {
            $icon = esc($node['icon'] ?: 'bi bi-circle');
            $name = esc($node['name']);

            if (empty($node['children'])) {
                $active = is_menu_active($node['url']) ? ' active' : '';
                $href   = site_url($node['url']);
                $html .= '<li class="nav-item">'
                    . '<a href="' . $href . '" class="nav-link' . $active . '">'
                    . '<i class="nav-icon ' . $icon . '"></i><p>' . $name . '</p></a></li>';
                continue;
            }

            // Parent with children — highlight only the most specific match.
            $bestIdx = -1;
            $bestLen = -1;
            foreach ($node['children'] as $i => $child) {
                $len = menu_match_len($child['url']);
                if ($len > $bestLen) {
                    $bestLen = $len;
                    $bestIdx = $i;
                }
            }
            $childActive = $bestLen >= 0;
            $childHtml   = '';
            foreach ($node['children'] as $i => $child) {
                $ca = ($i === $bestIdx);
                $childHtml .= '<li class="nav-item">'
                    . '<a href="' . site_url($child['url']) . '" class="nav-link' . ($ca ? ' active' : '') . '">'
                    . '<i class="nav-icon ' . esc($child['icon'] ?: 'bi bi-circle') . '"></i><p>' . esc($child['name']) . '</p></a></li>';
            }

            $openClass  = $childActive ? ' menu-open' : '';
            $linkActive = $childActive ? ' active' : '';
            $html .= '<li class="nav-item' . $openClass . '">'
                . '<a href="#" class="nav-link' . $linkActive . '">'
                . '<i class="nav-icon ' . $icon . '"></i><p>' . $name
                . '<i class="nav-arrow bi bi-chevron-right"></i></p></a>'
                . '<ul class="nav nav-treeview">' . $childHtml . '</ul></li>';
        }

        // Super Admin management pages — not DB modules, so grouped into logical
        // sub-menus explicitly (one collapsible menu per area).
        if (function_exists('is_super_admin_account') && is_super_admin_account()) {
            $adminGroups = [
                ['Customers', 'bi bi-people', [
                    ['All Customers', 'admin/customers', 'bi bi-person-lines-fill'],
                    ['Deleted (Trash)', 'admin/customers/trash', 'bi bi-trash3'],
                    ['Firms / Companies', 'admin/firms', 'bi bi-building'],
                    ['Mobile User Locations', 'admin/locations', 'bi bi-geo-alt'],
                ]],
                ['Subscription & Billing', 'bi bi-gem', [
                    ['Activate Plan', 'admin/activate', 'bi bi-patch-check'],
                    ['Plans & Pricing', 'admin/plans', 'bi bi-card-checklist'],
                    ['Coupons', 'admin/coupons', 'bi bi-ticket-perforated'],
                    ['Payments', 'admin/transactions', 'bi bi-receipt'],
                ]],
                ['Support', 'bi bi-headset', [
                    ['Inquiries', 'admin/inquiries', 'bi bi-chat-left-text'],
                    ['Deletion Requests', 'admin/deletion-requests', 'bi bi-person-x'],
                ]],
                ['Developer Tools', 'bi bi-tools', [
                    ['App Usage (Menu Taps)', 'admin/app-events', 'bi bi-activity'],
                    ['Load Test Console', 'loadtest', 'bi bi-speedometer'],
                    ['Health Check', 'health', 'bi bi-heart-pulse'],
                ]],
            ];

            // Render one collapsible menu, opening + highlighting the active child.
            $renderGroup = static function (string $label, string $groupIcon, array $children): string {
                $bestIdx = -1;
                $bestLen = -1;
                foreach ($children as $i => $c) {
                    $len = menu_match_len($c[1]);
                    if ($len > $bestLen) {
                        $bestLen = $len;
                        $bestIdx = $i;
                    }
                }
                $open  = $bestLen >= 0;
                $inner = '';
                foreach ($children as $i => $c) {
                    $inner .= '<li class="nav-item"><a href="' . site_url($c[1]) . '" class="nav-link' . ($i === $bestIdx ? ' active' : '') . '">'
                        . '<i class="nav-icon ' . esc($c[2]) . '"></i><p>' . esc($c[0]) . '</p></a></li>';
                }
                return '<li class="nav-item' . ($open ? ' menu-open' : '') . '">'
                    . '<a href="#" class="nav-link' . ($open ? ' active' : '') . '">'
                    . '<i class="nav-icon ' . esc($groupIcon) . '"></i><p>' . esc($label) . '<i class="nav-arrow bi bi-chevron-right"></i></p></a>'
                    . '<ul class="nav nav-treeview">' . $inner . '</ul></li>';
            };

            foreach ($adminGroups as $g) {
                $html .= $renderGroup($g[0], $g[1], $g[2]);
            }
        }

        return $html;
    }
}
