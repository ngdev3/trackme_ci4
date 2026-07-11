<?php

namespace Modules\Api\Controllers;

use App\Models\NoteModel;
use App\Models\PasswordModel;
use App\Models\ReminderModel;

/**
 * Trash API for the mobile app — soft-deleted rows across the company-scoped
 * feature modules, with restore. Gated by the `trash` feature (Plus plan and
 * up).
 *
 *   GET  trash            list recoverable items across modules
 *   POST trash/restore    { module, id } → un-delete
 */
class TrashApiController extends BaseApiController
{
    protected $helpers = ['settings', 'subscription'];

    private const FEATURE = 'trash';

    /** module key → [model class, label field]. */
    private const SOURCES = [
        'notes'     => [NoteModel::class, 'title'],
        'reminders' => [ReminderModel::class, 'title'],
        'passwords' => [PasswordModel::class, 'title'],
    ];

    private function guard(): array
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return ['error' => $this->failUnauthorized('Invalid or missing token.')];
        }
        if (! $this->apiHasFeature($user, self::FEATURE)) {
            return ['error' => $this->failForbidden('Your plan does not include Trash.')];
        }
        return ['user' => $user, 'companyId' => $this->resolveCompanyId($user)];
    }

    public function index()
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $items = [];
        foreach (self::SOURCES as $module => [$class, $labelField]) {
            $model = new $class();
            $rows  = $model->onlyDeleted()
                ->where('company_id', $g['companyId'])
                ->orderBy('deleted_at', 'DESC')
                ->findAll();
            foreach ($rows as $r) {
                $items[] = [
                    'module'     => $module,
                    'id'         => (int) $r['id'],
                    'label'      => (string) ($r[$labelField] ?? ('#' . $r['id'])),
                    'deleted_at' => $r['deleted_at'] ?? null,
                ];
            }
        }
        // Most-recently deleted first, across all modules.
        usort($items, static fn ($a, $b) => strcmp((string) $b['deleted_at'], (string) $a['deleted_at']));

        return $this->respond(['status' => 'ok', 'items' => $items]);
    }

    public function restore()
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $module = (string) ($this->input('module') ?? '');
        $id     = (int) ($this->input('id') ?? 0);
        if (! isset(self::SOURCES[$module]) || $id <= 0) {
            return $this->failValidationErrors('A valid module and id are required.');
        }
        [$class] = self::SOURCES[$module];
        $model   = new $class();

        // Confirm the soft-deleted row belongs to this company before restoring.
        $row = $model->onlyDeleted()->where('company_id', $g['companyId'])->where('id', $id)->first();
        if (! $row) {
            return $this->failNotFound('Item not found in trash.');
        }
        $model->builder()->where('id', $id)->update(['deleted_at' => null]);

        return $this->respond(['status' => 'ok', 'module' => $module, 'id' => $id]);
    }
}
