<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Module + action authorisation.
 *
 * Usage in routes:  ['filter' => 'permission:users,edit']
 *   arg[0] = module code (required)
 *   arg[1] = action code (optional, defaults to "view")
 *
 * Order of checks:
 *   1. Authenticated?          -> login
 *   2. Has module access?      -> access denied
 *   3. Has the action grant?   -> access denied
 */
class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = Services::auth();

        // 1. Logged in (also restores remember-me).
        if (! $auth->check() && ! $auth->loginFromRememberCookie()) {
            Services::session()->setFlashdata('info', 'Please sign in to continue.');
            return redirect()->to(site_url('login'));
        }

        $moduleCode = $arguments[0] ?? null;
        $action     = $arguments[1] ?? 'view';

        if ($moduleCode === null) {
            return; // nothing to check
        }

        $acl = Services::acl();

        // 2 & 3. Module access + action grant (super admin bypasses inside Acl).
        if (! $acl->can($moduleCode, $action)) {
            if ($request->isAJAX()) {
                return Services::response()->setStatusCode(403)->setJSON(['message' => 'Access denied']);
            }
            return Services::response()
                ->setStatusCode(403)
                ->setBody(view('App\Views\errors\access_denied', [
                    'module' => $moduleCode,
                    'action' => $action,
                ]));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
