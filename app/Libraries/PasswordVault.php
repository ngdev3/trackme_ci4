<?php

namespace App\Libraries;

use Config\Services;

/**
 * Symmetric encryption for stored credentials. Wraps CI4's Encryption service
 * (OpenSSL/AES) so the Password Manager never persists a plaintext password:
 * values are encrypted on save and only decrypted on an explicit, permission-
 * checked reveal. The ciphertext is base64-wrapped for safe storage in TEXT.
 *
 * Requires `encryption.key` to be set (see .env / Config\Encryption).
 */
class PasswordVault
{
    private $enc;

    public function __construct()
    {
        $this->enc = Services::encrypter();
    }

    /** Encrypt a plaintext secret into a storable, base64-wrapped string. */
    public function encrypt(string $plain): string
    {
        return base64_encode($this->enc->encrypt($plain));
    }

    /**
     * Decrypt a stored value back to plaintext. Returns '' when the input is
     * empty or cannot be decrypted (e.g. corrupt data or a rotated key without
     * a fallback), so a failure never surfaces raw ciphertext to the UI.
     */
    public function decrypt(?string $stored): string
    {
        $stored = (string) $stored;
        if ($stored === '') {
            return '';
        }
        try {
            $raw = base64_decode($stored, true);
            if ($raw === false) {
                return '';
            }
            return $this->enc->decrypt($raw);
        } catch (\Throwable $e) {
            log_message('error', 'PasswordVault decrypt failed: ' . $e->getMessage());
            return '';
        }
    }
}
