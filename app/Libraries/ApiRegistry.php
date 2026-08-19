<?php

namespace App\Libraries;

use App\Models\ApiEndpointModel;
use Config\Services;

/**
 * Discovers the mobile-app REST endpoints (api/v1/*) from the route collection
 * and keeps the `api_endpoints` registry in sync, plus performs live health
 * checks. Used by the Super-Admin-only API Monitor module.
 *
 * Health check safety: pings are sent WITHOUT a bearer token. Every mutating
 * endpoint authenticates first (BaseApiController::currentApiUser()), so an
 * unauthenticated request returns 401 before any action runs — no side effects.
 * The few public endpoints (login/register/otp) are hit with an empty body and
 * fail validation harmlessly.
 */
class ApiRegistry
{
    /** Endpoints reachable without a bearer token. Everything else = 'bearer'. */
    private const PUBLIC_PATHS = [
        'auth/login', 'auth/google', 'auth/truecaller', 'auth/forgot-password',
        'auth/register', 'auth/request-email-otp', 'auth/verify-email-otp',
        'app/version', 'google-play/rtdn/{param}',
    ];

    /**
     * Curated request parameters, keyed by "METHOD path". Path uses {id}/{param}
     * for URL placeholders. Missing entries fall back to path params only.
     *
     * @var array<string, array{path?:list<string>, query?:list<string>, body?:list<string>}>
     */
    private const PARAMS = [
        'POST auth/login'              => ['body' => ['login (email|username|mobile)', 'password', 'device_name?']],
        'POST auth/google'            => ['body' => ['id_token']],
        'POST auth/truecaller'        => ['body' => ['payload', 'signature', 'access_token?']],
        'POST auth/forgot-password'   => ['body' => ['email']],
        'POST auth/change-password'   => ['body' => ['current_password', 'new_password']],
        'POST auth/register'          => ['body' => ['name', 'email', 'password', 'mobile?']],
        'POST auth/request-email-otp' => ['body' => ['email']],
        'POST auth/verify-email-otp'  => ['body' => ['email', 'otp']],
        'GET me'                      => [],
        'PUT me'                      => ['body' => ['name?', 'email?', 'mobile?', 'photo?']],
        'POST company/switch'         => ['body' => ['company_id']],
        'POST companies'              => ['body' => ['name', 'phone?', 'address?', 'opening_balance?']],
        'PUT companies/{id}'          => ['path' => ['id'], 'body' => ['name?', 'phone?', 'address?']],
        'GET transactions/list'       => ['query' => ['from?', 'to?', 'party?', 'mode?', 'type?', 'page?', 'per?']],
        'GET transactions/changes'    => ['query' => ['since (timestamp)', 'company_id?']],
        'POST transactions/sync'      => ['body' => ['entries[] (batch upsert)']],
        'GET transactions/report'     => ['query' => ['from?', 'to?', 'group?']],
        'GET transactions/statement'  => ['query' => ['party', 'from?', 'to?']],
        'POST transactions/store'     => ['body' => ['date', 'amount', 'type (jama|naam)', 'party?', 'payment_mode?', 'note?']],
        'POST transactions/update/{id}' => ['path' => ['id'], 'body' => ['date?', 'amount?', 'type?', 'party?', 'note?']],
        'POST transactions/opening'   => ['body' => ['amount', 'as_of_date?']],
        'GET calendar'                => ['query' => ['month (YYYY-MM)']],
        'POST push/subscribe'         => ['body' => ['fcm_token | subscription (web-push)']],
        'POST subscription/subscribe' => ['body' => ['plan_id', 'payment_ref?']],
        'POST google-play/verify-purchase' => ['body' => ['purchase_token', 'product_id']],
    ];

    private ApiEndpointModel $model;

    public function __construct()
    {
        $this->model = new ApiEndpointModel();
    }

    /**
     * Parse the route collection and upsert every api/v1 endpoint. Preserves the
     * operator's is_active toggle and last health result on existing rows.
     *
     * @return array{added:int, updated:int, total:int}
     */
    public function sync(): array
    {
        $routes  = Services::routes();
        $routes->loadRoutes();
        // getRoutes() keys routes by UPPERCASE verb (Router::HTTP_METHODS);
        // passing lowercase returns nothing.
        $verbs   = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];
        $seen    = [];
        $added   = 0;
        $updated = 0;
        $now     = date('Y-m-d H:i:s');

        foreach ($verbs as $verb) {
            foreach ($routes->getRoutes($verb) as $pattern => $handler) {
                $pattern = ltrim($pattern, '/');
                if (! str_starts_with($pattern, 'api/v1/')) {
                    continue;
                }
                $rel      = substr($pattern, strlen('api/v1/'));
                [$display] = $this->friendly($rel);
                $method   = strtoupper($verb);
                $key      = $method . ' ' . $display;

                $handlerStr = is_string($handler) ? $handler : '(closure)';
                $row = [
                    'http_method' => $method,
                    'path'        => $display,
                    'handler'     => $this->shortHandler($handlerStr),
                    'grp'         => $this->groupOf($display),
                    'title'       => $this->titleOf($method, $display),
                    'auth'        => in_array($display, self::PUBLIC_PATHS, true) ? 'public' : 'bearer',
                    'params'      => json_encode($this->paramsFor($method, $display, $rel)),
                ];

                $existing = $this->model->where('http_method', $method)->where('path', $display)->first();
                if ($existing) {
                    $this->model->update($existing['id'], $row);
                    $updated++;
                } else {
                    $this->model->insert($row + ['is_active' => 1, 'created_at' => $now]);
                    $added++;
                }
                $seen[$key] = true;
            }
        }

        return ['added' => $added, 'updated' => $updated, 'total' => count($seen)];
    }

    /**
     * Health-check one endpoint by pinging it (no auth). Records http_status,
     * health, response_ms, last_checked. Returns the updated row.
     */
    public function checkOne(array $row): array
    {
        $ping   = $this->pingPath($row['path']);
        $url    = rtrim(base_url(), '/') . '/api/v1/' . $ping;
        $method = strtoupper($row['http_method']);

        $start = microtime(true);
        $code  = $this->hit($url, $method);
        $ms    = (int) round((microtime(true) - $start) * 1000);

        $health = $this->classify($code);
        $patch  = [
            'http_status' => $code ?: null,
            'health'      => $health,
            'response_ms' => $ms,
            'last_checked'=> date('Y-m-d H:i:s'),
        ];
        $this->model->update($row['id'], $patch);
        return array_merge($row, $patch);
    }

    /** Ping every registered endpoint. Returns counts by health bucket. */
    public function checkAll(): array
    {
        $counts = ['online' => 0, 'down' => 0, 'error' => 0, 'missing' => 0];
        foreach ($this->model->findAll() as $row) {
            $res = $this->checkOne($row);
            $counts[$res['health']] = ($counts[$res['health']] ?? 0) + 1;
        }
        return $counts;
    }

    // ----------------------------------------------------------------------
    // Internals
    // ----------------------------------------------------------------------

    /** Fire a request and return the HTTP status (0 on connection failure). */
    private function hit(string $url, string $method): int
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_NOBODY         => $method === 'HEAD',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER     => ['Accept: application/json', 'X-Health-Check: 1'],
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;
    }

    /** Map an HTTP status to a health bucket. */
    private function classify(int $code): string
    {
        if ($code === 0)    return 'down';
        if ($code >= 500)   return 'error';
        if ($code === 404)  return 'missing';
        return 'online'; // 2xx/3xx/401/403/400/405 — the route responds
    }

    /**
     * Convert a compiled route pattern to [friendlyDisplay, pingPath]. Regex
     * placeholder groups become {id}/{param} for display and a concrete value
     * for pinging.
     */
    private function friendly(string $rel): array
    {
        $display = preg_replace_callback('~\(([^)]*)\)~', static function ($m) {
            $inner = $m[1];
            $isNum = (bool) preg_match('~0-9|\\\\d~', $inner) && ! preg_match('~a-z~i', $inner);
            return $isNum ? '{id}' : '{param}';
        }, $rel);

        return [$display, $this->pingPath($display)];
    }

    /** Substitute {id}→1, {param}→test so the path resolves for a real request. */
    private function pingPath(string $display): string
    {
        return str_replace(['{id}', '{param}'], ['1', 'test'], $display);
    }

    /** First path segment as the display group (auth, transactions, inventory…). */
    private function groupOf(string $display): string
    {
        $seg = explode('/', $display)[0] ?? 'other';
        return $seg ?: 'other';
    }

    private function shortHandler(string $handler): string
    {
        return preg_replace('~^Modules\\\\Api\\\\Controllers\\\\~', '', $handler);
    }

    /** Human title from the handler method / path tail. */
    private function titleOf(string $method, string $display): string
    {
        $tail = str_replace(['{id}', '{param}'], '', $display);
        $tail = trim(preg_replace('~/+~', ' ', $tail));
        return ucwords(str_replace(['-', '_'], ' ', $tail ?: $display));
    }

    /**
     * Assemble the params descriptor: curated body/query params (if any) merged
     * with auto-detected path params.
     */
    private function paramsFor(string $method, string $display, string $rel): array
    {
        $key    = $method . ' ' . $display;
        $params = self::PARAMS[$key] ?? [];

        // Auto path params from placeholders, unless the catalog already named them.
        if (empty($params['path'])) {
            $path = [];
            if (str_contains($display, '{id}'))    $path[] = 'id (numeric)';
            if (str_contains($display, '{param}')) $path[] = 'param (string)';
            if ($path !== []) {
                $params['path'] = $path;
            }
        }

        return $params;
    }
}
