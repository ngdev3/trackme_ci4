<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AdminAuthFilter — replaces CI3 `is_adminprotected(); validate_admin_login();`
 * that MY_Controller ran on every admin request. Bounces unauthenticated
 * visitors to the login page.
 *
 * Applied 'before' to the `admin/*` route group in Config\Filters.
 */
class AdminAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // The login/logout routes themselves must stay public.
        $uri = service('uri');
        if ($uri->getSegment(2, '') === 'auth') {
            return;
        }
        // Web-push config + service worker are loaded by the browser (CI3 left
        // these two public; save/delete token stay gated below via the session).
        if ($uri->getSegment(2, '') === 'web_push'
            && in_array($uri->getSegment(3, ''), ['config', 'service_worker'], true)) {
            return;
        }

        $fy = service('fyContext');

        // Session expiry (CI3 session_expires_at).
        $expiresAt = (int) session()->get('session_expires_at');
        if ($fy->isLoggedIn() && $expiresAt > 0 && $expiresAt <= time()) {
            session()->destroy();
            return redirect()->to(site_url('admin/auth') . '?timeout=1')
                ->with('error', 'Your session has expired. Please sign in again.');
        }

        if (! $fy->isLoggedIn()) {
            // Route-relative path (e.g. "admin/dashboard") — NOT getUri()->getPath(),
            // which includes the base subfolder (/trackme_ci4/public/…) and would be
            // double-prefixed by site_url() after login.
            $redirect = ltrim($request->getPath(), '/');
            return redirect()->to(site_url('admin/auth') . '?redirect=' . rawurlencode($redirect))
                ->with('error', 'Please sign in to continue.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
