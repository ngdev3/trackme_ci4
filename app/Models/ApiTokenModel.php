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
     * Issue a fresh opaque bearer token for a user. Returns the plaintext token.
     *
     * Mobile sessions are meant to stay signed in until the user explicitly logs
     * out (which deletes the token server-side), so the default TTL is very long
     * (10 years) rather than a short expiry that would silently sign them out.
     */
    public function issue(int $userId, string $name = 'mobile', int $ttlDays = 3650): string
    {
        $token = bin2hex(random_bytes(32));
        $this->insert([
            'user_id'    => $userId,
            'token'      => $token,
            'name'       => $name,
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlDays * 86400),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $token;
    }

    /**
     * Resolve a valid (non-expired) token to its row, or null.
     */
    public function findValid(string $token): ?array
    {
        $row = $this->where('token', $token)->first();
        if (! $row) {
            return null;
        }
        if ($row['expires_at'] !== null && strtotime($row['expires_at']) < time()) {
            return null;
        }
        return $row;
    }

    public function touch(int $id): void
    {
        $this->update($id, ['last_used_at' => date('Y-m-d H:i:s')]);
    }

    public function revokeAllForUser(int $userId): void
    {
        $this->where('user_id', $userId)->delete();
    }
}
