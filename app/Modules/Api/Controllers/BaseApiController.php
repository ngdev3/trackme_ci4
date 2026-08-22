<?php

namespace Modules\Api\Controllers;

use App\Models\ApiTokenModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Shared behaviour for the stateless JSON API: input parsing (JSON body or
 * form fields) and bearer-token authentication.
 */
abstract class BaseApiController extends Controller
{
    use ResponseTrait;

    // 'dashcache' is loaded API-wide so dashboard endpoints can dash_remember()
    // their aggregates AND every write path's dash_bust() actually fires (it is
    // guarded by function_exists) — keeping the cached API + web dashboards in
    // step with each other and with the DB.
    protected $helpers = ['settings', 'dashcache'];

    /** Cached authenticated user for this request. */
    protected ?array $apiUser = null;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    /**
     * Rate-limit a sensitive action. Returns true when the caller has exceeded
     * `$capacity` attempts within `$seconds` (a leaky bucket, per key). Callers
     * should respond 429 when this is true. Backed by CI4's Throttler (cache),
     * so it works without a WAF and survives distributed/slow brute-force that
     * a per-IP host firewall misses. Never throws — a throttler/cache error
     * fails OPEN (allows the request) so auth can't be locked out by cache loss.
     */
    protected function tooManyAttempts(string $key, int $capacity, int $seconds): bool
    {
        try {
            $throttler = \Config\Services::throttler();
            return $throttler->check($key, $capacity, $seconds) === false;
        } catch (\Throwable $e) {
            log_message('error', '[Api] throttler failed: ' . $e->getMessage());
            return false;
        }
    }

    /** A stable, low-cardinality throttle key component for the caller's IP. */
    protected function clientIpKey(): string
    {
        return preg_replace('/[^a-z0-9]+/i', '-', (string) $this->request->getIPAddress());
    }

    /**
     * Read an input value from the JSON body first, then form/POST.
     */
    protected function input(string $key, $default = null)
    {
        // Only parse the body as JSON when it actually is JSON. A multipart /
        // form-encoded request (e.g. a file upload) has no JSON body, and
        // getJSON() would throw "Failed to parse JSON string" on it.
        if (str_contains($this->request->getHeaderLine('Content-Type'), 'application/json')) {
            $json = $this->request->getJSON(true);
            if (is_array($json) && array_key_exists($key, $json)) {
                return $json[$key];
            }
        }
        $post = $this->request->getPost($key);
        return $post !== null ? $post : $default;
    }

    /**
     * Resolve the current user from the Authorization: Bearer <token> header.
     */
    protected function currentApiUser(): ?array
    {
        if ($this->apiUser !== null) {
            return $this->apiUser;
        }
        $header = (string) ($this->request->getHeaderLine('Authorization') ?? '');
        if (! preg_match('/Bearer\s+([a-f0-9]{64})/i', $header, $m)) {
            return null;
        }
        $tokens = new ApiTokenModel();
        $row    = $tokens->findValid($m[1]);
        if (! $row) {
            return null;
        }
        $tokens->touch((int) $row['id']);
        $user = (new UserModel())->find((int) $row['user_id']);
        if (! $user || (int) $user['status'] !== 1) {
            return null;
        }
        return $this->apiUser = $user;
    }

    /**
     * Resolve + authorise the active company for the API caller. A `company_id`
     * may be supplied (JSON/POST/GET) but is always validated against the user's
     * memberships, so one firm's data can never be reached by another. Falls back
     * to the user's first company. Shared by every company-scoped module API.
     */
    protected function resolveCompanyId(array $user): ?int
    {
        $members   = new \App\Models\CompanyUserModel();
        $requested = (int) ($this->input('company_id') ?? $this->request->getGet('company_id') ?? 0);
        if ($requested > 0 && $members->isMember($requested, (int) $user['id'])) {
            return $requested;
        }
        $companies = (new \App\Models\CompanyModel())->forUser((int) $user['id']);
        return $companies !== [] ? (int) $companies[0]['id'] : null;
    }

    /**
     * The customer id that owns the caller's active company (the subscription
     * holder). Falls back to the user's own id. Mirrors the resolution used by
     * the subscription / billing controllers so feature checks line up with /me.
     */
    protected function ownerId(array $user): int
    {
        $cid = $this->resolveCompanyId($user);
        if ($cid) {
            $company = (new \App\Models\CompanyModel())->find($cid);
            if ($company && ! empty($company['owner_id'])) {
                return (int) $company['owner_id'];
            }
        }
        return (int) $user['id'];
    }

    /**
     * Does the caller's package include $feature? Enforces the same plan gating
     * server-side that the app applies to the menu, so a gated endpoint can't be
     * reached just by calling it directly.
     */
    protected function apiHasFeature(array $user, string $feature): bool
    {
        helper('subscription');
        if (! function_exists('customer_has_feature')) {
            return false;
        }
        return customer_has_feature($this->ownerId($user), $feature);
    }

    /**
     * Standard shape for a user returned to the client.
     */
    protected function publicUser(array $user): array
    {
        return [
            'id'                   => (int) $user['id'],
            'name'                 => $user['name'],
            'email'                => $user['email'],
            'username'             => $user['username'],
            'mobile'               => $user['mobile'] ?? null,
            'must_change_password' => (int) ($user['must_change_password'] ?? 0) === 1,
        ];
    }
}
