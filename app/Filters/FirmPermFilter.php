<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Firm-scoped permission gate: `firmperm:<module>` ensures the user is signed
 * in, has an ACTIVE firm, and that their firm role grants access to <module>
 * (via firm_can). This is the enforcement point that keeps firm data isolated
 * and honours per-role / per-user firm permissions.
 */
class FirmPermFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $module = $arguments[0] ?? '';

        if (! Services::auth()->check()) {
            return redirect()->to(site_url('login'));
        }

        helper(['auth', 'company']);

        if (! company_id()) {
            // No active firm — customers onboard, everyone else goes home.
            $to = is_customer() ? 'company/create' : 'dashboard';
            return redirect()->to(site_url($to))->with('info', 'Select or create a firm first.');
        }

        if ($module !== '' && ! firm_can($module)) {
            Services::session()->setFlashdata('error', 'You do not have permission to access this section.');
            return redirect()->to(site_url('dashboard'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
