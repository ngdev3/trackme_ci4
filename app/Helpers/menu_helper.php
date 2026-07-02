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

        $isVisible = static function (array $m) use ($acl, $viewable): bool {
            if ($acl->isSuperAdmin()) {
                return true;
            }
            return isset($viewable[$m['code']]);
        };

        // Split into parents and children.
        $tree = [];
        foreach ($all as $m) {
            if ($m['parent_id'] === null) {
                $tree[(int) $m['id']] = $m + ['children' => []];
            }
        }
        foreach ($all as $m) {
            if ($m['parent_id'] !== null && isset($tree[(int) $m['parent_id']])) {
                if (! empty($m['url']) && ! $isVisible($m)) {
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
        return $result;
    }
}

if (! function_exists('is_menu_active')) {
    /**
     * Whether a module URL matches the current request (first path segment).
     */
    function is_menu_active(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }
        $current = trim(Services::request()->getUri()->getPath(), '/');
        $first   = explode('/', $current)[0] ?? '';
        return $first === trim($url, '/');
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

            // Parent with children.
            $childActive = false;
            $childHtml   = '';
            foreach ($node['children'] as $child) {
                $ca = is_menu_active($child['url']);
                $childActive = $childActive || $ca;
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

        return $html;
    }
}
