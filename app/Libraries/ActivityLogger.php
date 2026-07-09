<?php

namespace App\Libraries;

use App\Models\ActivityLogModel;
use Config\Services;

/**
 * Records important user actions to the activity_logs table.
 */
class ActivityLogger
{
    protected ActivityLogModel $model;

    public function __construct()
    {
        $this->model = new ActivityLogModel();
    }

    public function log(string $module, string $action, string $description = ''): void
    {
        $req     = service('request');
        $session = Services::session();

        $impersonatorId = $session->get('impersonator_id');
        if ($impersonatorId) {
            $description = trim($description . ' [Impersonated by user #' . $impersonatorId . ']');
        }

        $this->model->insert([
            'user_id'     => $session->get('user_id'),
            'module'      => $module,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => $req->getIPAddress(),
            'user_agent'  => substr((string) $req->getUserAgent(), 0, 255),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
