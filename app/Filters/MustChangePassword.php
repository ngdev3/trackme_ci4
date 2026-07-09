<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Force a password change. When the signed-in user has must_change_password = 1
 * every web request is redirected to the change-password page, except the
 * change-password endpoints themselves and logout, so they can never navigate
 * away without setting a new password.
 *
 * Registered as a global "before" filter; it no-ops for guests, API requests
 * and static assets.
 */
class MustChangePassword implements FilterInterface
{
    /** Path prefixes that must stay reachable while a change is pending. */
    private const ALLOW = ['account/change-password', 'logout'];

    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = Services::auth();
        if (! $auth->check()) {
            return; // guests handled by auth/permission filters
        }

        // Path relative to the app baseURL (handles /ERP/ sub-directory installs).
        helper('url');
        $path = trim(uri_string(), '/');

        // Never intercept API calls (they carry their own flag in the response).
        if (str_starts_with($path, 'api/')) {
            return;
        }
        foreach (self::ALLOW as $allowed) {
            if ($path === $allowed || str_starts_with($path, $allowed)) {
                return;
            }
        }

        $user = $auth->user();
        if ($user && (int) ($user['must_change_password'] ?? 0) === 1) {
            Services::session()->setFlashdata('info', 'Please set a new password to continue.');
            return redirect()->to(site_url('account/change-password'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
