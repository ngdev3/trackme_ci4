<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Ensures the visitor is authenticated. Attempts remember-me restore first.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = Services::auth();

        if (! $auth->check() && ! $auth->loginFromRememberCookie()) {
            if ($request->isAJAX()) {
                return Services::response()->setStatusCode(401)->setJSON(['message' => 'Unauthenticated']);
            }
            Services::session()->setFlashdata('info', 'Please sign in to continue.');
            return redirect()->to(site_url('login'));
        }

        (new \App\Models\LoginLogModel())->markActivity((int) Services::session()->get('login_log_id'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
