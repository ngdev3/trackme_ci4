<?php

namespace Modules\Api\Controllers;

use App\Models\LoginLogModel;

/**
 * Read-only account logs for the mobile app. Only the caller's OWN login
 * history is exposed (mirrors the web `my-login-history`, which is the sole
 * non-superadmin log). Activity logs + all-user login logs stay admin-only.
 *
 *   GET /api/v1/logs/logins[?limit=]   the caller's recent sign-ins
 */
class LogsApiController extends BaseApiController
{
    public function logins()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $uid   = (int) $user['id'];
        $limit = (int) ($this->request->getGet('limit') ?? 50);
        $limit = max(1, min($limit, 200));

        $rows = (new LoginLogModel())
            ->where('user_id', $uid)
            ->orderBy('id', 'DESC')
            ->findAll($limit);

        $out = array_map(static function (array $r): array {
            return [
                'id'               => (int) $r['id'],
                'status'           => $r['status'] ?? 'success',
                'device'           => $r['device_type'] ?? null,
                'browser'          => $r['browser'] ?? null,
                'os'               => $r['operating_system'] ?? null,
                'ip'               => $r['ip_address'] ?? null,
                'suspicious'       => (int) ($r['is_suspicious'] ?? 0) === 1,
                'suspicious_reason' => $r['suspicious_reason'] ?? null,
                'failure_reason'   => $r['failure_reason'] ?? null,
                'session_seconds'  => isset($r['session_duration']) ? (int) $r['session_duration'] : null,
                'at'               => $r['login_at'] ?? $r['created_at'] ?? null,
            ];
        }, $rows);

        return $this->respond(['status' => 'ok', 'logins' => $out]);
    }
}
