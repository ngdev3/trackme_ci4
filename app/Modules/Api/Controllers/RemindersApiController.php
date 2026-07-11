<?php

namespace Modules\Api\Controllers;

use App\Models\ReminderModel;

/**
 * Reminders API for the mobile app. Company-scoped (shared across members;
 * `user_id` records the author). Gated by the `reminder` feature (Plus plan
 * and up).
 *
 *   GET    reminders                  list (optional ?status=pending|completed)
 *   POST   reminders                  create
 *   PUT    reminders/(:num)           update
 *   POST   reminders/(:num)/complete  mark done
 *   DELETE reminders/(:num)           soft-delete
 */
class RemindersApiController extends BaseApiController
{
    protected $helpers = ['settings', 'subscription'];

    private const FEATURE = 'reminder';

    private function guard(): array
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return ['error' => $this->failUnauthorized('Invalid or missing token.')];
        }
        if (! $this->apiHasFeature($user, self::FEATURE)) {
            return ['error' => $this->failForbidden('Your plan does not include Reminders.')];
        }
        return ['user' => $user, 'companyId' => $this->resolveCompanyId($user)];
    }

    public function index()
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $model  = new ReminderModel();
        $status = (string) ($this->request->getGet('status') ?? '');
        $b      = $model->scoped($g['companyId']);
        if (in_array($status, ['pending', 'completed'], true)) {
            $b->where('status', $status);
        }
        // Order by effective due time (a snooze overrides the original time).
        $rows = $b->orderBy('COALESCE(snoozed_until, remind_at)', 'ASC', false)->findAll();

        return $this->respond(['status' => 'ok', 'reminders' => $rows]);
    }

    public function create()
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $data = $this->collect($g['companyId'], (int) $g['user']['id']);
        if ($data['title'] === '' || $data['remind_at'] === null) {
            return $this->failValidationErrors('Title and a remind-at date/time are required.');
        }
        $model = new ReminderModel();
        $id    = $model->insert($data, true);
        if (! $id) {
            return $this->failValidationErrors($model->errors() ?: 'Could not save reminder.');
        }
        return $this->respondCreated(['status' => 'ok', 'id' => (int) $id]);
    }

    public function update($id = null)
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $model = new ReminderModel();
        $row   = $this->findScoped($model, (int) $id, $g['companyId']);
        if (! $row) {
            return $this->failNotFound('Reminder not found.');
        }
        $data = $this->collect($g['companyId'], (int) $row['user_id']);
        if ($data['title'] === '' || $data['remind_at'] === null) {
            return $this->failValidationErrors('Title and a remind-at date/time are required.');
        }
        if (! $model->update((int) $id, $data)) {
            return $this->failValidationErrors($model->errors() ?: 'Could not update reminder.');
        }
        return $this->respond(['status' => 'ok', 'id' => (int) $id]);
    }

    public function complete($id = null)
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $model = new ReminderModel();
        $row   = $this->findScoped($model, (int) $id, $g['companyId']);
        if (! $row) {
            return $this->failNotFound('Reminder not found.');
        }
        $model->update((int) $id, ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);
        return $this->respond(['status' => 'ok', 'id' => (int) $id]);
    }

    public function delete($id = null)
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $model = new ReminderModel();
        $row   = $this->findScoped($model, (int) $id, $g['companyId']);
        if (! $row) {
            return $this->failNotFound('Reminder not found.');
        }
        $model->delete((int) $id);
        return $this->respondDeleted(['status' => 'ok', 'id' => (int) $id]);
    }

    private function findScoped(ReminderModel $model, int $id, ?int $companyId): ?array
    {
        $row = $model->find($id);
        if (! $row || ($companyId !== null && (int) $row['company_id'] !== $companyId)) {
            return null;
        }
        return $row;
    }

    private function collect(?int $companyId, int $userId): array
    {
        $priority = (string) ($this->input('priority') ?? 'medium');
        $repeat   = (string) ($this->input('repeat_type') ?? 'none');
        $remindAt = trim((string) ($this->input('remind_at') ?? ''));

        return [
            'user_id'     => $userId,
            'company_id'  => $companyId,
            'title'       => trim((string) ($this->input('title') ?? '')),
            'description' => trim((string) ($this->input('description') ?? '')) ?: null,
            'remind_at'   => $remindAt !== '' ? date('Y-m-d H:i:s', strtotime($remindAt)) : null,
            'priority'    => in_array($priority, ReminderModel::PRIORITIES, true) ? $priority : 'medium',
            'repeat_type' => in_array($repeat, ReminderModel::REPEATS, true) ? $repeat : 'none',
        ];
    }
}
