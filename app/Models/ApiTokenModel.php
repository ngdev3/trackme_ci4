<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiTokenModel extends Model
{
    protected $table         = 'api_tokens';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['user_id', 'token', 'name', 'last_used_at', 'expires_at', 'created_at'];

    /**
     * Sliding session window (days). A token that is USED stays alive (touch()
     * pushes expires_at forward on every request), so an active mobile user is
     * never silently signed out — but an abandoned OR stolen token dies after
     * this many days of inactivity. Far safer than the old fixed 10-year TTL.
     */
    public const TTL_DAYS = 180;

    /**
     * Only the SHA-256 of a bearer token is stored. The plaintext is shown to the
     * client exactly once (at issue) and never persisted, so a DB dump / backup
     * leak yields hashes an attacker can't present as a Bearer credential.
     */
    public static function hashToken(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * Issue a fresh opaque bearer token for a user. Returns the PLAINTEXT token
     * (the only time it exists in the clear); the DB stores its hash.
     */
    public function issue(int $userId, string $name = 'mobile', int $ttlDays = self::TTL_DAYS): string
    {
        $token = bin2hex(random_bytes(32));
        $this->insert([
            'user_id'    => $userId,
            'token'      => self::hashToken($token),
            'name'       => $name,
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlDays * 86400),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $token;
    }

    /**
     * Resolve a valid (non-expired) token to its row, or null. Looks up by hash;
     * transparently upgrades any legacy plaintext row (issued before hashing) to
     * a hash on first use, so existing sessions keep working without a re-login.
     */
    public function findValid(string $token): ?array
    {
        $hash = self::hashToken($token);
        $row  = $this->where('token', $hash)->first();
        if (! $row) {
            // Legacy plaintext token — match once, then rewrite it as a hash.
            $row = $this->where('token', $token)->first();
            if ($row) {
                $this->update((int) $row['id'], ['token' => $hash]);
                $row['token'] = $hash;
            }
        }
        if (! $row) {
            return null;
        }
        if ($row['expires_at'] !== null && strtotime($row['expires_at']) < time()) {
            return null;
        }
        return $row;
    }

    /** Mark used AND slide the expiry forward so active sessions never lapse. */
    public function touch(int $id): void
    {
        $this->update($id, [
            'last_used_at' => date('Y-m-d H:i:s'),
            'expires_at'   => date('Y-m-d H:i:s', time() + self::TTL_DAYS * 86400),
        ]);
    }

    /** Revoke a single token given its plaintext (handles legacy + hashed rows). */
    public function revoke(string $plain): void
    {
        $this->groupStart()
            ->where('token', self::hashToken($plain))
            ->orWhere('token', $plain)
            ->groupEnd()
            ->delete();
    }

    public function revokeAllForUser(int $userId): void
    {
        $this->where('user_id', $userId)->delete();
    }

    /**
     * Revoke every token for a user EXCEPT the one presented (kept alive so the
     * caller stays signed in). Used after a password change to boot other devices.
     */
    public function revokeAllForUserExcept(int $userId, string $keepPlain): void
    {
        $this->where('user_id', $userId)
            ->where('token !=', self::hashToken($keepPlain))
            ->delete();
    }
}
