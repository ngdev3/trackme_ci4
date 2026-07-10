<?php

namespace App\Filters;

use App\Models\ApiTokenModel;
use App\Models\CompanyModel;
use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Package feature gate for the bearer-token API. The web hasFeature() reads the
 * session, which the stateless API does not have, so this resolves the caller's
 * owning customer from their token and checks that customer's package directly
 * (customer_has_feature). Blocked calls get the same {status:false,message:...}
 * shape the app uses.
 *
 * The feature is inferred from the request path (currently only Inventory is a
 * gated API surface). If no token is present we do nothing and let the
 * controller's own auth return 401.
 */
class ApiFeature implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('subscription');

        $feature = $arguments[0] ?? $this->resolveFeature();
        if (! $feature) {
            return;
        }

        $header = (string) ($request->getHeaderLine('Authorization') ?? '');
        if (! preg_match('/Bearer\s+([a-f0-9]{64})/i', $header, $m)) {
            return; // unauthenticated — controller handles 401
        }
        $token = (new ApiTokenModel())->findValid($m[1]);
        if (! $token) {
            return;
        }
        $user = (new UserModel())->find((int) $token['user_id']);
        if (! $user) {
            return;
        }

        // The owning customer governs the package for all of a user's firms.
        $companies = (new CompanyModel())->forUser((int) $user['id']);
        $customerId = ! empty($companies) ? (int) $companies[0]['owner_id'] : (int) $user['id'];

        if (customer_has_feature($customerId, $feature)) {
            return;
        }

        return service('response')->setStatusCode(403)->setJSON([
            'status'  => false,
            'message' => feature_lock_message($feature),
            'feature' => $feature,
        ]);
    }

    private function resolveFeature(): ?string
    {
        $path = trim(uri_string(), '/');
        if (str_contains($path, 'api/v1/inventory')) {
            return 'inventory';
        }
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
