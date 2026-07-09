<?php

namespace App\Libraries;

use App\Models\SettingModel;

/**
 * Self-contained Web Push (RFC 8291 "aes128gcm" content encoding + RFC 8292
 * VAPID) sender. No external Composer dependency — uses ext-openssl and
 * ext-gmp/bcmath-free EC via openssl.
 *
 * Delivery to a push service (FCM/Mozilla/…) requires a real browser
 * subscription, so end-to-end delivery can only be exercised from a browser;
 * key generation and payload assembly are pure and unit-testable.
 */
class WebPush
{
    /**
     * Return the app's VAPID keys, generating and storing them on first use.
     *
     * @return array{publicKey:string,privateKey:string,subject:string}
     */
    public static function ensureVapidKeys(): array
    {
        $settings = new SettingModel();
        $public = (string) $settings->get('vapid_public_key', 0, '');
        $private = (string) $settings->get('vapid_private_key', 0, '');

        if ($public === '' || $private === '') {
            [$public, $private] = self::generateVapidKeys();
            $settings->put('vapid_public_key', $public, 0);
            $settings->put('vapid_private_key', $private, 0);
        }

        return [
            'publicKey' => $public,
            'privateKey' => $private,
            'subject' => self::defaultSubject(),
        ];
    }

    /**
     * Generate a VAPID key pair.
     *
     * @return array{0:string,1:string} [publicKey(base64url, 65-byte point), privateKey(base64url, 32-byte)]
     */
    public static function generateVapidKeys(): array
    {
        $res = openssl_pkey_new(self::ecKeyArgs());
        if ($res === false) {
            throw new \RuntimeException('OpenSSL EC key generation failed. Ensure the openssl extension supports prime256v1.');
        }
        $details = openssl_pkey_get_details($res);
        $x = str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
        $d = str_pad($details['ec']['d'], 32, "\0", STR_PAD_LEFT);

        $public  = "\x04" . $x . $y;      // uncompressed EC point
        return [self::b64u($public), self::b64u($d)];
    }

    /**
     * Send a push message to one subscription.
     *
     * @param array  $subscription  ['endpoint'=>..., 'p256dh'=>..., 'auth'=>...]
     * @param string $payload       message body (typically JSON)
     * @param array  $vapid         ['publicKey'=>b64u, 'privateKey'=>b64u, 'subject'=>'mailto:..']
     *
     * @return array{success:bool, status:int, error:?string}
     */
    public static function send(array $subscription, string $payload, array $vapid): array
    {
        try {
            $encrypted = self::encrypt($payload, $subscription['p256dh'], $subscription['auth']);
            $jwt       = self::vapidHeader($subscription['endpoint'], $vapid);

            $headers = [
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'TTL: 2419200',
                'Authorization: vapid t=' . $jwt['token'] . ', k=' . $vapid['publicKey'],
            ];

            $ch = curl_init($subscription['endpoint']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $encrypted,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HEADER         => false,
            ]);
            curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err    = curl_error($ch) ?: null;
            curl_close($ch);

            // 201 Created = accepted; 404/410 = subscription gone.
            return ['success' => $status >= 200 && $status < 300, 'status' => $status, 'error' => $err];
        } catch (\Throwable $e) {
            return ['success' => false, 'status' => 0, 'error' => $e->getMessage()];
        }
    }

    // -----------------------------------------------------------------
    // RFC 8291 aes128gcm payload encryption
    // -----------------------------------------------------------------
    private static function encrypt(string $payload, string $p256dhB64, string $authB64): string
    {
        $uaPublic = self::b64uDecode($p256dhB64);      // 65 bytes
        $authSecret = self::b64uDecode($authB64);      // 16 bytes

        // Ephemeral server key pair.
        $server = openssl_pkey_new(self::ecKeyArgs());
        if ($server === false) {
            throw new \RuntimeException('OpenSSL EC key generation failed.');
        }
        $sd     = openssl_pkey_get_details($server);
        $serverPublic = "\x04"
            . str_pad($sd['ec']['x'], 32, "\0", STR_PAD_LEFT)
            . str_pad($sd['ec']['y'], 32, "\0", STR_PAD_LEFT);

        // ECDH shared secret between server private and UA public.
        $sharedSecret = self::ecdh($sd['ec']['d'], $uaPublic);

        $salt = random_bytes(16);

        // key_info = "WebPush: info" || 0x00 || ua_public || server_public
        $keyInfo = 'WebPush: info' . "\0" . $uaPublic . $serverPublic;
        $ikm     = self::hkdf($authSecret, $sharedSecret, $keyInfo, 32);

        $cek   = self::hkdf($salt, $ikm, "Content-Encoding: aes128gcm\0", 16);
        $nonce = self::hkdf($salt, $ikm, "Content-Encoding: nonce\0", 12);

        // Content: payload || 0x02 (last-record delimiter), then AES-128-GCM.
        $plaintext = $payload . "\x02";
        $tag = '';
        $cipher = openssl_encrypt($plaintext, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('aes-128-gcm encryption failed.');
        }

        // Header: salt(16) || rs(4, uint32 BE) || idlen(1) || keyid(server public)
        $rs     = pack('N', 4096);
        $idlen  = chr(strlen($serverPublic));
        $header = $salt . $rs . $idlen . $serverPublic;

        return $header . $cipher . $tag;
    }

    /**
     * ECDH: derive the shared secret X coordinate from our private scalar and
     * the peer's uncompressed public point, using a throwaway OpenSSL PKey.
     */
    private static function ecdh(string $privateScalar, string $peerPublicPoint): string
    {
        $peerPem = self::ecPublicPem($peerPublicPoint);
        $ourPem  = self::ecPrivatePem($privateScalar);

        $peer = openssl_pkey_get_public($peerPem);
        $ours = openssl_pkey_get_private($ourPem);
        if ($peer === false || $ours === false) {
            throw new \RuntimeException('Failed to load EC keys for ECDH.');
        }
        $secret = openssl_pkey_derive($peer, $ours, 32);
        if ($secret === false) {
            throw new \RuntimeException('openssl_pkey_derive (ECDH) failed.');
        }
        return str_pad($secret, 32, "\0", STR_PAD_LEFT);
    }

    // -----------------------------------------------------------------
    // RFC 8292 VAPID JWT (ES256)
    // -----------------------------------------------------------------
    private static function vapidHeader(string $endpoint, array $vapid): array
    {
        $parts = parse_url($endpoint);
        $aud   = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

        $header  = self::b64u(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims  = self::b64u(json_encode([
            'aud' => $aud,
            'exp' => time() + 12 * 3600,
            'sub' => $vapid['subject'] ?: 'mailto:admin@example.com',
        ]));
        $signingInput = $header . '.' . $claims;

        $privatePem = self::ecPrivatePem(self::b64uDecode($vapid['privateKey']));
        $pkey = openssl_pkey_get_private($privatePem);
        openssl_sign($signingInput, $derSig, $pkey, OPENSSL_ALGO_SHA256);
        $sig = self::derToJose($derSig);

        return ['token' => $signingInput . '.' . self::b64u($sig)];
    }

    // -----------------------------------------------------------------
    // Crypto helpers
    // -----------------------------------------------------------------
    private static function hkdf(string $salt, string $ikm, string $info, int $length): string
    {
        return hash_hkdf('sha256', $ikm, $length, $info, $salt);
    }

    /** Convert an ECDSA DER signature to the fixed 64-byte JOSE (r||s) form. */
    private static function derToJose(string $der): string
    {
        $offset = 0;
        if ($der[$offset++] !== "\x30") {
            throw new \RuntimeException('Invalid DER signature.');
        }
        if (ord($der[$offset]) & 0x80) {
            $offset += (ord($der[$offset]) & 0x7f);
        }
        $offset++; // seq length byte
        $read = static function () use ($der, &$offset): string {
            $offset++; // 0x02
            $len = ord($der[$offset++]);
            $val = substr($der, $offset, $len);
            $offset += $len;
            return ltrim($val, "\0");
        };
        $r = $read();
        $s = $read();
        return str_pad($r, 32, "\0", STR_PAD_LEFT) . str_pad($s, 32, "\0", STR_PAD_LEFT);
    }

    /** Build a DER SubjectPublicKeyInfo PEM for a P-256 uncompressed point. */
    private static function ecPublicPem(string $point): string
    {
        // prime256v1 SPKI prefix for an uncompressed EC point.
        $der = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"
             . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00" . $point;
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    /** Build a DER EC private-key PEM (SEC1) from a 32-byte scalar. */
    private static function ecPrivatePem(string $scalar): string
    {
        $scalar = str_pad($scalar, 32, "\0", STR_PAD_LEFT);
        // SEC1 ECPrivateKey with prime256v1 (1.2.840.10045.3.1.7) parameters;
        // public key omitted (optional). SEQUENCE length 0x31 = 49 bytes.
        $der = "\x30\x31\x02\x01\x01\x04\x20" . $scalar
             . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
        return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
    }

    /**
     * OpenSSL args for a prime256v1 key, adding an explicit `config` path when
     * one can be located (some Windows/CLI setups lack a default openssl.cnf).
     */
    private static function ecKeyArgs(): array
    {
        $args = ['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'];
        if ($cnf = self::opensslConfig()) {
            $args['config'] = $cnf;
        }
        return $args;
    }

    private static function opensslConfig(): ?string
    {
        foreach ([getenv('OPENSSL_CONF'), ini_get('openssl.conf')] as $c) {
            if ($c && is_file($c)) {
                return $c;
            }
        }
        // Candidate base directories that commonly ship an openssl.cnf. Under
        // mod_php PHP_BINARY points at the web server, so also derive the PHP
        // directory from the loaded php.ini and PHP_BINDIR.
        $dirs = array_filter([
            defined('PHP_BINARY') ? dirname(PHP_BINARY) : null,
            defined('PHP_BINDIR') ? PHP_BINDIR : null,
            ($ini = php_ini_loaded_file()) ? dirname($ini) : null,
        ]);
        foreach ($dirs as $dir) {
            foreach (['/extras/ssl/openssl.cnf', '/openssl.cnf', '/ssl/openssl.cnf'] as $rel) {
                if (is_file($dir . $rel)) {
                    return $dir . $rel;
                }
            }
        }
        return null;
    }

    private static function defaultSubject(): string
    {
        try {
            $host = parse_url(site_url(), PHP_URL_HOST) ?: 'localhost';
            return 'mailto:admin@' . $host;
        } catch (\Throwable $e) {
            return 'mailto:admin@example.com';
        }
    }

    // -----------------------------------------------------------------
    // base64url
    // -----------------------------------------------------------------
    private static function b64u(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private static function b64uDecode(string $s): string
    {
        return base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4));
    }
}
