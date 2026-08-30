<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ApiAuthFilter — token auth for the mobile REST API (`api_services/*`),
 * replacing the CI3 webservices REST_Controller token check. POST-only,
 * always HTTP 200 with the status in the body (mobile contract), bypasses the
 * admin session + RBAC entirely.
 *
 * Applied 'before' to `api_services/*` in Config\Filters.
 */
class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // TODO(P7): validate api_key + per-user token exactly as the CI3
        // webservices module did. Preserve the always-200 envelope:
        //   return service('response')->setStatusCode(200)
        //       ->setJSON(['status' => false, 'message' => 'Invalid token']);
        // Do NOT redirect and do NOT touch the admin session here.
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
