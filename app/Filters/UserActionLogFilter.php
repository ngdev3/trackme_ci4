<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class UserActionLogFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $session = service('session');
        if (! $session->get('user_id')) {
            return;
        }

        $method = strtoupper($request->getMethod());
        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $path = trim($request->getUri()->getPath(), '/');
        if ($path === '' || str_starts_with($path, 'api/') || str_contains($path, 'activity-logs')) {
            return;
        }

        Services::activityLogger()->log('User Action', $method, $path);
    }
}
