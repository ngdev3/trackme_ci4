<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Email-verification one-time codes. Codes are stored hashed and are single-use.
 */
class EmailOtpModel extends Model
{
    protected $table         = 'email_otps';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['email', 'purpose', 'code_hash', 'attempts', 'consumed_at', 'expires_at'];

    public const MAX_ATTEMPTS = 5;

    /** Hash a code the same way it is stored. */
    public static function hash(string $code): string
    {
        return hash('sha256', $code);
    }

    /**
     * Issue a fresh numeric code for an email/purpose, invalidating any prior
     * unconsumed codes. Returns the plaintext code (to be emailed, never stored).
     */
    public function issue(string $email, string $purpose = 'email_verify', int $ttlMinutes = 10): string
    {
        // Retire any outstanding codes for this email/purpose so only one is live.
        $this->where('email', $email)
            ->where('purpose', $purpose)
            ->where('consumed_at', null)
            ->set(['consumed_at' => date('Y-m-d H:i:s')])
            ->update();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->insert([
            'email'      => $email,
            'purpose'    => $purpose,
            'code_hash'  => self::hash($code),
            'attempts'   => 0,
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlMinutes * 60),
        ]);
        return $code;
    }

    /** The most recent live (unconsumed, unexpired) code row, or null. */
    public function latestLive(string $email, string $purpose = 'email_verify'): ?array
    {
        $row = $this->where('email', $email)
            ->where('purpose', $purpose)
            ->where('consumed_at', null)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->orderBy('id', 'DESC')
            ->first();
        return $row ?: null;
    }

    /**
     * Seconds since the last code was issued for this email/purpose (any state),
     * or null if none exists. Used to throttle re-sends.
     */
    public function secondsSinceLast(string $email, string $purpose = 'email_verify'): ?int
    {
        $row = $this->where('email', $email)
            ->where('purpose', $purpose)
            ->orderBy('id', 'DESC')
            ->first();
        if (! $row || empty($row['created_at'])) {
            return null;
        }
        return time() - strtotime((string) $row['created_at']);
    }

    /** Mark a code row consumed (single-use). */
    public function consume(int $id): void
    {
        $this->update($id, ['consumed_at' => date('Y-m-d H:i:s')]);
    }

    /** Record a failed guess. */
    public function bumpAttempts(int $id, int $current): void
    {
        $this->update($id, ['attempts' => $current + 1]);
    }
}
