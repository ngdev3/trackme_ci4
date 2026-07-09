<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Restricts the SaaS operator panel to true Super Admins (a super-admin account
 * carrying the is_superadmin role flag). Keeps Super Admin oversight separate
 * from customer/firm data access.
 */
class SuperAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = Services::session();
        if (! Services::auth()->check()) {
            return redirect()->to(site_url('login'));
        }
        if ($session->get('account_type') !== 'super_admin' || ! $session->get('is_superadmin')) {
            $session->setFlashdata('error', 'Super Admin access only.');
            return redirect()->to(site_url('dashboard'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
