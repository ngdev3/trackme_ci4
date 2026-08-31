<?php

/**
 * Task notification helper — CI4 port of application/helpers/task_notify_helper.
 *
 * Shared by the web admin Task module and the mobile webservice APIs so the
 * discussion notification behaviour stays identical on both surfaces.
 *
 *   - Resolve the participants of a task (author + assignee + additional
 *     assignees + super/admin users).
 *   - Insert in-app rows into aa_task_notification for everyone except the actor.
 *   - Push an FCM v1 notification to those recipients (users.fcm_token + any
 *     registered device push_token). Best-effort: no-op when FCM is unconfigured.
 */

use Config\Database;

if (! function_exists('task_notify_db')) {
    function task_notify_db()
    {
        return Database::connect();
    }
}

if (! function_exists('task_participant_ids')) {

    /** Distinct user ids to notify about a task, optionally excluding the actor. */
    function task_participant_ids($task_id, $exclude_user_id = 0): array
    {
        $db  = task_notify_db();
        $ids = [];

        $task = $db->table('aa_task')->select('created_by, assigned_to')
            ->where('task_id', (int) $task_id)->get()->getRow();
        if ($task) {
            if (! empty($task->created_by)) {
                $ids[] = (int) $task->created_by;
            }
            if (! empty($task->assigned_to)) {
                $ids[] = (int) $task->assigned_to;
            }
        }

        if ($db->tableExists('aa_task_assignee')) {
            foreach ($db->table('aa_task_assignee')->select('user_id')
                ->where('task_id', (int) $task_id)->get()->getResult() as $a) {
                $ids[] = (int) $a->user_id;
            }
        }

        // Super admins / admins always have visibility into the discussion.
        foreach ($db->table('users')->select('id')
            ->whereIn('user_type', ['1', '2'])->where('status', 'Active')
            ->get()->getResult() as $u) {
            $ids[] = (int) $u->id;
        }

        $ids = array_values(array_unique(array_filter($ids)));
        if (! empty($exclude_user_id)) {
            $ids = array_values(array_diff($ids, [(int) $exclude_user_id]));
        }
        return $ids;
    }

    /**
     * Notify a task's participants of an event (comment / reply / assigned).
     * Writes in-app notifications and fires a best-effort push.
     */
    function task_notify_participants($task_id, $actor_id, $type, $message, $comment_id = 0, $extra_exclude = []): array
    {
        $db         = task_notify_db();
        $recipients = task_participant_ids($task_id, $actor_id);

        // The assigned user always hears about their own task.
        $task = $db->table('aa_task')->select('assigned_to')->where('task_id', (int) $task_id)->get()->getRow();
        if ($task && ! empty($task->assigned_to)) {
            $aid = (int) $task->assigned_to;
            if (! in_array($aid, $recipients, true)) {
                $recipients[] = $aid;
            }
        }

        $extra_exclude = array_filter(array_map('intval', (array) $extra_exclude));
        if (! empty($extra_exclude)) {
            $recipients = array_values(array_diff($recipients, $extra_exclude));
        }

        if (empty($recipients)) {
            return ['recipients' => 0, 'push' => ['success' => 0, 'response' => 'No recipients']];
        }

        foreach ($recipients as $uid) {
            $db->table('aa_task_notification')->insert([
                'user_id'    => $uid,
                'task_id'    => (int) $task_id,
                'comment_id' => (int) $comment_id,
                'actor_id'   => (int) $actor_id,
                'type'       => $type,
                'message'    => mb_substr($message, 0, 250),
                'is_read'    => 0,
                'added_date' => date('Y-m-d H:i:s'),
            ]);
        }

        $tokens = [];
        foreach ($db->table('users')->select('fcm_token')
            ->whereIn('id', $recipients)
            ->where('fcm_token IS NOT NULL', null, false)
            ->where("fcm_token != ''", null, false)
            ->get()->getResult() as $u) {
            $tokens[] = $u->fcm_token;
        }
        if ($db->tableExists('aa_whitelist_device')) {
            foreach ($db->table('aa_whitelist_device')->select('push_token')
                ->whereIn('user_id', $recipients)->where('status', 'Active')
                ->where('push_token IS NOT NULL', null, false)
                ->where("push_token != ''", null, false)
                ->get()->getResult() as $d) {
                $tokens[] = $d->push_token;
            }
        }
        $tokens = array_values(array_unique(array_filter($tokens)));

        $title    = 'Task #' . (int) $task_id;
        $task_url = site_url('task/task/view/' . (function_exists('ID_encode') ? ID_encode($task_id) : (int) $task_id));
        $push     = task_fcm_send($tokens, $title, $message, [
            'title'    => $title,
            'body'     => $message,
            'category' => 'task_' . $type,
            'task_id'  => (string) (int) $task_id,
            'url'      => $task_url,
        ]);

        return ['recipients' => count($recipients), 'push' => $push];
    }

    /**
     * Notify the user a task was assigned to (priority/due/snippet). Writes an
     * in-app row + best-effort push. No-op when there is no assignee.
     */
    function task_notify_assignment($task_id, $actor_id, $assignee_id): array
    {
        $assignee_id = (int) $assignee_id;
        if (! $assignee_id) {
            return ['recipients' => 0, 'push' => ['success' => 0, 'response' => 'No assignee to notify']];
        }

        $db   = task_notify_db();
        $task = $db->table('aa_task')->select('task_id, title, description, priority, due_date')
            ->where('task_id', (int) $task_id)->get()->getRow();
        if (! $task) {
            return ['recipients' => 0, 'push' => ['success' => 0, 'response' => 'Task not found']];
        }

        $actor      = $db->table('users')->select('first_name, last_name')->where('id', (int) $actor_id)->get()->getRow();
        $actor_name = $actor ? trim($actor->first_name . ' ' . $actor->last_name) : 'Someone';

        $bits = ['Priority: ' . ucfirst($task->priority)];
        if (! empty($task->due_date) && $task->due_date !== '0000-00-00') {
            $bits[] = 'Due: ' . date('d M Y', strtotime($task->due_date));
        }
        $desc = trim((string) $task->description);
        if ($desc !== '') {
            $bits[] = mb_substr($desc, 0, 120);
        }

        $title   = 'New task assigned: ' . $task->title;
        $message = mb_substr($actor_name . ' assigned you "' . $task->title . '". ' . implode(' · ', $bits), 0, 250);

        $db->table('aa_task_notification')->insert([
            'user_id'    => $assignee_id,
            'task_id'    => (int) $task_id,
            'comment_id' => 0,
            'actor_id'   => (int) $actor_id,
            'type'       => 'assigned',
            'message'    => $message,
            'is_read'    => 0,
            'added_date' => date('Y-m-d H:i:s'),
        ]);

        $tokens = [];
        foreach ($db->table('users')->select('fcm_token')
            ->where('id', $assignee_id)
            ->where('fcm_token IS NOT NULL', null, false)
            ->where("fcm_token != ''", null, false)
            ->get()->getResult() as $u) {
            $tokens[] = $u->fcm_token;
        }
        if ($db->tableExists('aa_whitelist_device')) {
            foreach ($db->table('aa_whitelist_device')->select('push_token')
                ->where('user_id', $assignee_id)->where('status', 'Active')
                ->where('push_token IS NOT NULL', null, false)
                ->where("push_token != ''", null, false)
                ->get()->getResult() as $d) {
                $tokens[] = $d->push_token;
            }
        }
        $tokens = array_values(array_unique(array_filter($tokens)));

        $task_url = site_url('task/task/view/' . (function_exists('ID_encode') ? ID_encode($task_id) : (int) $task_id));
        $push     = task_fcm_send($tokens, $title, $message, [
            'title'    => $title,
            'body'     => $message,
            'category' => 'task_assigned',
            'task_id'  => (string) (int) $task_id,
            'url'      => $task_url,
        ]);

        return ['recipients' => 1, 'push' => $push];
    }

    /**
     * Send an FCM v1 notification to the given tokens. Best effort: when the
     * service account is not configured it is a no-op (logged only).
     */
    function task_fcm_send($tokens, $title, $body, $data = []): array
    {
        if (empty($tokens)) {
            return ['success' => 0, 'response' => 'No tokens'];
        }
        if (! defined('FCM_SERVICE_ACCOUNT') || ! defined('FCM_PROJECT_ID') || ! file_exists(FCM_SERVICE_ACCOUNT)) {
            return ['success' => 0, 'response' => 'FCM service account not configured (logged only).'];
        }

        $auth = task_fcm_access_token();
        if (empty($auth['access_token'])) {
            return ['success' => 0, 'response' => 'FCM auth failed'];
        }

        $url     = 'https://fcm.googleapis.com/v1/projects/' . FCM_PROJECT_ID . '/messages:send';
        $success = 0;
        $details = [];
        $pruned  = [];
        foreach ($tokens as $device_token) {
            $payload_data = array_merge(['title' => $title, 'body' => $body, 'category' => 'task_comment'], (array) $data);
            $payload      = ['message' => [
                'token'        => $device_token,
                'notification' => ['title' => $title, 'body' => $body],
                'android'      => ['priority' => 'high', 'notification' => ['sound' => 'default']],
                'webpush'      => ['fcm_options' => ['link' => $payload_data['url'] ?? site_url('task/task')]],
                'data'         => $payload_data,
            ]];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $auth['access_token'],
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            $resp = curl_exec($ch);
            $err  = curl_error($ch);
            $code = ($resp !== false) ? curl_getinfo($ch, CURLINFO_HTTP_CODE) : 0;
            curl_close($ch);
            if ($code == 200) {
                $success++;
            } else {
                log_message('error', 'task_fcm_send token=' . substr($device_token, 0, 12)
                    . '... code=' . $code . ' curl=' . $err . ' resp=' . (string) $resp);
                if (task_fcm_is_dead_token($code, (string) $resp)) {
                    task_fcm_prune_token($device_token);
                    $pruned[] = substr($device_token, 0, 12) . '...';
                }
            }
            $details[] = ['code' => $code, 'curl' => $err, 'resp' => is_string($resp) ? $resp : ''];
        }
        return [
            'success'  => $success,
            'response' => $success . '/' . count($tokens) . ' delivered',
            'pruned'   => $pruned,
            'details'  => $details,
        ];
    }

    /** Whether an FCM v1 error means the token is permanently dead (remove it). */
    function task_fcm_is_dead_token($code, $resp): bool
    {
        if ((int) $code === 404) {
            return true;
        }
        $r = strtoupper((string) $resp);
        if (strpos($r, 'UNREGISTERED') !== false) {
            return true;
        }
        if (strpos($r, 'REGISTRATION-TOKEN-NOT-REGISTERED') !== false) {
            return true;
        }
        if ((int) $code === 400 && strpos($r, 'INVALID_ARGUMENT') !== false && strpos($r, 'TOKEN') !== false) {
            return true;
        }
        return false;
    }

    /** Remove a dead push token everywhere it is stored. */
    function task_fcm_prune_token($token): void
    {
        $db    = task_notify_db();
        $token = (string) $token;
        if ($token === '') {
            return;
        }
        if ($db->tableExists('aa_whitelist_device')) {
            $db->table('aa_whitelist_device')->where('push_token', $token)
                ->update(['status' => 'Delete', 'updated_at' => date('Y-m-d')]);
        }
        $db->table('users')->where('fcm_token', $token)->update(['fcm_token' => null]);
        log_message('error', 'task_fcm_prune_token removed dead token ' . substr($token, 0, 12) . '...');
    }

    /** Mint a short-lived OAuth2 access token for FCM from the service account. */
    function task_fcm_access_token(): array
    {
        $sa = json_decode(file_get_contents(FCM_SERVICE_ACCOUNT), true);
        if (empty($sa['client_email']) || empty($sa['private_key'])) {
            return ['error' => 'invalid_service_account'];
        }

        $b64url = static fn ($data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

        $now    = time();
        $header = $b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim  = $b64url(json_encode([
            'iss'   => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $signature = '';
        openssl_sign($header . '.' . $claim, $signature, $sa['private_key'], 'sha256WithRSAEncryption');
        $jwt = $header . '.' . $claim . '.' . $b64url($signature);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]));
        $resp = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($resp, true);
        return is_array($decoded) ? $decoded : ['error' => 'token_request_failed'];
    }
}
