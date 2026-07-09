<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Company onboarding gate. A self-service (social sign-up) user who has not yet
 * created or joined a company is redirected to the company-creation form on
 * every request until they have one — implementing the flow:
 *
 *   Gmail sign-up → (no company) → Create Company Form → Save → Dashboard.
 *
 * Existing local/admin accounts (auth_provider = 'local') are exempt, so the
 * pre-seeded admin app is never forced through onboarding.
 */
class RequireCompany implements FilterInterface
{
    /** Paths that stay reachable while onboarding is pending. */
    private const ALLOW = ['company', 'logout', 'account/change-password', 'impersonate'];

    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = Services::auth();
        if (! $auth->check()) {
            return; // guests handled by auth/permission filters
        }

        helper('url');
        $path = trim(uri_string(), '/');

        if (str_starts_with($path, 'api/')) {
            return;
        }
        foreach (self::ALLOW as $allowed) {
            if ($path === $allowed || str_starts_with($path, $allowed . '/')) {
                return;
            }
        }

        // Only tenant CUSTOMERS go through firm onboarding. Super admins run the
        // SaaS panel; firm users are assigned to a firm by their customer.
        if (Services::session()->get('account_type') !== 'customer') {
            return;
        }

        if (Services::company()->activeId() === null) {
            Services::session()->setFlashdata('info', 'Create your firm to get started.');
            return redirect()->to(site_url('company/create'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
