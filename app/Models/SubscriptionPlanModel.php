<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionPlanModel extends Model
{
    protected $table         = 'subscription_plans';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'name', 'code', 'price', 'billing_cycle', 'max_firms', 'max_users', 'features', 'status',
        'feat_calculator', 'feat_password_manager', 'feat_calendar', 'feat_reminder',
        'feat_trash', 'feat_notes', 'feat_inventory',
    ];

    /** The gated feature columns this plan controls (feature key ⇄ feat_<key>). */
    public const FEATURE_COLUMNS = [
        'calculator', 'password_manager', 'calendar', 'reminder', 'trash', 'notes', 'inventory',
    ];

    public function active(): array
    {
        return $this->where('status', 1)->orderBy('price', 'ASC')->findAll();
    }
}
