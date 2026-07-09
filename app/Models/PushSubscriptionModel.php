<?php

namespace App\Models;

use CodeIgniter\Model;

class PushSubscriptionModel extends Model
{
    protected $table         = 'push_subscriptions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['user_id', 'email', 'endpoint', 'endpoint_hash', 'p256dh', 'auth', 'user_agent'];

    /**
     * Store (or refresh) a subscription for a user's browser. Idempotent on the
     * endpoint hash, so re-subscribing the same browser updates in place.
     */
    public function store(int $userId, string $email, string $endpoint, string $p256dh, string $auth, ?string $ua = null): void
    {
        $hash = hash('sha256', $endpoint);
        $data = [
            'user_id'       => $userId,
            'email'         => $email,
            'endpoint'      => $endpoint,
            'endpoint_hash' => $hash,
            'p256dh'        => $p256dh,
            'auth'          => $auth,
            'user_agent'    => $ua ? substr($ua, 0, 255) : null,
        ];
        $existing = $this->where('endpoint_hash', $hash)->first();
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }
    }

    public function forUser(int $userId): array
    {
        return $this->where('user_id', $userId)->findAll();
    }

    public function deleteByEndpoint(string $endpoint): void
    {
        $this->where('endpoint_hash', hash('sha256', $endpoint))->delete();
    }
}
