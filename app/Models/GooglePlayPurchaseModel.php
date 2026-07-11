<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Server record of a Google Play subscription purchase (keyed by purchase token).
 * Verified against the Play Developer API before it is trusted; updated by RTDN.
 */
class GooglePlayPurchaseModel extends Model
{
    protected $table         = 'google_play_purchases';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'customer_id', 'user_id', 'plan_id', 'product_id', 'base_plan_id',
        'purchase_token', 'order_id', 'linked_purchase_token',
        'purchase_time', 'expiry_time', 'status', 'auto_renewing',
        'acknowledged', 'activated', 'last_notification_type', 'raw',
    ];

    /** Find the record for a purchase token. */
    public function findByToken(string $token): ?array
    {
        return $this->where('purchase_token', $token)->first();
    }

    /**
     * Insert or update the record for a purchase token (token is unique). Returns
     * the stored row.
     */
    public function upsertByToken(string $token, array $data): array
    {
        $existing = $this->findByToken($token);
        if ($existing) {
            $this->update($existing['id'], $data);
            return $this->find($existing['id']);
        }
        $id = $this->insert($data + ['purchase_token' => $token], true);
        return $this->find($id);
    }
}
