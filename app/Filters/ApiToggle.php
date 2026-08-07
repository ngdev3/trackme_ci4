<?php

namespace App\Filters;

use App\Models\ApiEndpointModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Enforces the Super-Admin API Monitor's active/inactive switch on the live
 * mobile API. If an endpoint has been toggled off, the app receives a clean
 * 503 instead of the real handler running.
 *
 * Matching is by HTTP method + the request's friendly path (numeric/segment
 * URL parts normalised to {id}/{param}, mirroring how ApiRegistry stores them),
 * so a disabled `POST transactions/update/{id}` blocks every id.
 */
class ApiToggle implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Never let a registry lookup failure take the whole API down.
        try {
            $method = strtoupper($request->getMethod());
            $uri    = ltrim($request->getUri()->getPath(), '/');

            // Strip any base directory (e.g. /ERP/) so we start at api/v1/.
            $pos = strpos($uri, 'api/v1/');
            if ($pos === false) {
                return; // not an api/v1 request
            }
            $rel  = substr($uri, $pos + strlen('api/v1/'));
            $path = $this->friendly($rel);

            if ((new ApiEndpointModel())->isDisabled($method, $path)) {
                return service('response')
                    ->setStatusCode(503)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'This endpoint is temporarily disabled by the administrator.',
                    ]);
            }
        } catch (\Throwable $e) {
            // fail open — a monitoring feature must not break production traffic
            log_message('error', 'ApiToggle filter error: {msg}', ['msg' => $e->getMessage()]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    /**
     * Normalise a concrete request path to the registry's friendly form.
     * Numeric segments → {id}. The only two string-placeholder routes in the
     * api/v1 collection are google-play/rtdn/(:segment) and
     * inventory/reports/(:alpha); their value segment → {param}. Every other
     * segment is a fixed literal and is left as-is.
     */
    private function friendly(string $rel): string
    {
        $rel = trim($rel, '/');

        // Fixed {param} routes.
        if (preg_match('~^google-play/rtdn/[^/]+$~', $rel)) {
            return 'google-play/rtdn/{param}';
        }
        if (preg_match('~^inventory/reports/[^/]+$~', $rel)) {
            return 'inventory/reports/{param}';
        }

        // Numeric id segments.
        $parts = explode('/', $rel);
        foreach ($parts as $i => $seg) {
            if ($seg !== '' && ctype_digit($seg)) {
                $parts[$i] = '{id}';
            }
        }
        return implode('/', $parts);
    }
}
