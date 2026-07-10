<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Gate paid-only ("pro") features. Applied to the routes listed under the 'pro'
 * alias in Config\Filters. Free/expired customers are bounced to the upgrade
 * page (or get a 402 JSON for AJAX/download callers).
 */
class ProFeature implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('subscription');
        if (sub_is_pro()) {
            return;
        }

        if ($request->isAJAX()) {
            return service('response')->setStatusCode(402)->setJSON([
                'ok'      => false,
                'upgrade' => site_url('subscription'),
                'message' => 'This feature is available on the paid plan.',
            ]);
        }

        return redirect()->to(site_url('subscription'))
            ->with('error', 'This feature is available on the paid plan. Your free plan does not include it.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
