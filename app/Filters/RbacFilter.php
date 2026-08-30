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

        // Only gate keys that exist in the registry (unknown segments pass).
        $registry = erp_module_registry();
        if (! isset($registry[$module])) {
            return;
        }

        $action = erp_permission_action_from_method($method);
        if (! erp_current_user_can($module, $action)) {
            return redirect()->to(site_url('permission_denied'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
