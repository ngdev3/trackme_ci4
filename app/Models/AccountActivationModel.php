<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Activation tokens for self-service email/password signups. The raw token is
 * emailed as a one-click link; only its sha256 hash is stored here.
 */
class AccountActivationModel extends Model
{
    protected $table         = 'account_activations';
    protected $primaryKey    = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = false;

    protected $allowedFields = ['email', 'token', 'expires_at', 'created_at'];

    /** Hash a raw token the way it is stored. */
    public static function hash(string $raw): string
    {
        return hash('sha256', $raw);
    }

    /**
     * Issue a fresh activation token for an email (retiring any previous ones),
     * returning the RAW token to embed in the link. Valid for $ttlHours.
     */
    public function issue(string $email, int $ttlHours = 48): string
    {
        $this->where('email', $email)->delete();
        $raw = bin2hex(random_bytes(32));
        $this->insert([
            'email'      => $email,
            'token'      => self::hash($raw),
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlHours * 3600),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $raw;
    }

    /** The live (unexpired) row for a raw token, or null. */
    public function findLive(string $raw): ?array
    {
        $row = $this->where('token', self::hash($raw))
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->first();
        return $row ?: null;
    }

    /** Delete all activation rows for an email (after a successful activation). */
    public function clearFor(string $email): void
    {
        $this->where('email', $email)->delete();
    }
}
