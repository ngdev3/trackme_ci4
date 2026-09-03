<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RbacFilter — replaces MY_Controller step 3 (erp_require_current_permission()).
 * Gates `admin/*` on the per-user/role permission for the module named by the
 * 2nd URI segment (CI3 uri_segment(2)), with the action derived from the 3rd
 * segment (the method). Super admin bypasses. Denied -> permission_denied.
 */
class RbacFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('permission');

        if (erp_is_super_admin()) {
            return;
        }

        $uri    = service('uri');
        $module = $uri->getSegment(2, '');   // admin/<module>/<method>
        $method = $uri->getSegment(3, 'index');

        if ($module === '') {
            return;
        }

        // Firm / financial-year switching is core navigation for EVERY logged-in
        // user (including view-only): it only changes which firm is being viewed,
        // never mutates business data, and the switcher already lists every firm.
        // Exempt it so a read-only user can still move between firms to view data.
        if ($module === 'setting' && in_array($method, ['change_fy_id', 'add_fy', 'change_fy'], true)) {
            return;
        }

        // Only gate keys that exist in the registry (unknown segments pass).
        $registry = erp_module_registry();
        if (! isset($registry[$module])) {
            return;
        }

        $action = erp_permission_action_from_method($method);
        $uid    = erp_current_user_id();
        if (! erp_current_user_can($module, $action)) {
            // Read-only mode is not a "no access" denial — tell the user the right thing.
            $viewOnly = ($uid && $action !== 'view' && erp_user_is_view_only($uid));
            return $this->deny($request, $viewOnly);
        }

        // GLOBAL view-only hardening: even when the method name classifies as a
        // 'view' (save_entry, quick_update, store, …), block it for a view-only
        // user whenever it arrives over a write HTTP verb and is not a known read.
        if ($action === 'view' && $uid) {
            $vo = erp_user_is_view_only($uid);
            $http = strtoupper($request->getMethod());
            @file_put_contents(WRITEPATH . 'logs/rbac_debug.log', date('H:i:s') . " mod=$module method=$method action=$action uid=$uid vo=" . var_export($vo, true) . " http=$http read=" . var_export(erp_method_is_read_endpoint($method), true) . "\n", FILE_APPEND);
            if ($vo && in_array($http, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && ! erp_method_is_read_endpoint($method)) {
                return $this->deny($request, true);
            }
        }
    }

    /** AJAX → JSON 403; normal request → permission_denied page. Friendly for view-only. */
    private function deny(RequestInterface $request, bool $viewOnly = false)
    {
        // Never reveal the permission/read-only restriction to the user — present
        // a neutral "temporary technical glitch" for every blocked request.
        $title   = 'Something Went Wrong';
        $message = 'We hit a temporary technical glitch while processing your request. Please refresh the page and try again in a little while. If it keeps happening, contact support.';

        if ($request->isAJAX()) {
            return service('response')->setStatusCode(403)->setJSON([
                'status'        => 'denied',
                'access_denied' => true,
                'view_only'     => $viewOnly,
                'error_msg'     => $title,
                'message'       => $message,
            ]);
        }
        return redirect()->to(site_url('permission_denied') . ($viewOnly ? '?vo=1' : ''));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
