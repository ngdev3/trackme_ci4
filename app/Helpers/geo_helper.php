<?php

/**
 * Geolocation helpers for the login-logs "where did they sign in from" feature.
 *
 * ip_geolocate() resolves a public IP to an approximate location using the free
 * ip-api.com endpoint (no key, HTTP). It is intentionally best-effort: a short
 * timeout, private/reserved IPs are skipped, and any failure returns null so it
 * can NEVER slow down or break a login. Precise coordinates instead come from
 * the device GPS via POST /api/v1/location when the user grants access.
 */

if (! function_exists('is_public_ip')) {
    /** True only for a routable, non-private, non-reserved IP (v4 or v6). */
    function is_public_ip(?string $ip): bool
    {
        if (! $ip) {
            return false;
        }
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}

if (! function_exists('ip_geolocate')) {
    /**
     * @return array{latitude:float,longitude:float,label:string,city:string,region:string,country:string}|null
     */
    function ip_geolocate(?string $ip): ?array
    {
        if (! is_public_ip($ip)) {
            return null; // localhost / LAN / reserved — nothing to resolve.
        }

        try {
            $url = 'http://ip-api.com/json/' . rawurlencode($ip)
                . '?fields=status,country,regionName,city,lat,lon';

            $client = \Config\Services::curlrequest([
                'timeout'         => 2,
                'connect_timeout' => 2,
                'http_errors'     => false,
            ]);
            $res  = $client->get($url);
            $data = json_decode((string) $res->getBody(), true);

            if (! is_array($data) || ($data['status'] ?? '') !== 'success') {
                return null;
            }

            $city    = (string) ($data['city'] ?? '');
            $region  = (string) ($data['regionName'] ?? '');
            $country = (string) ($data['country'] ?? '');
            $label   = implode(', ', array_filter([$city, $region, $country]));

            return [
                'latitude'  => (float) ($data['lat'] ?? 0),
                'longitude' => (float) ($data['lon'] ?? 0),
                'label'     => $label,
                'city'      => $city,
                'region'    => $region,
                'country'   => $country,
            ];
        } catch (\Throwable $e) {
            log_message('warning', '[geo] ip_geolocate failed: ' . $e->getMessage());
            return null;
        }
    }
}
