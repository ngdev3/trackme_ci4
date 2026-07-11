<?php

namespace Modules\Api\Controllers;

use App\Models\NoteModel;

/**
 * Notes API for the mobile app. Company-scoped (shared across a company's
 * members; `user_id` records the author). Gated by the `notes` feature
 * (Premium plan).
 *
 *   GET    notes               list
 *   POST   notes               create
 *   PUT    notes/(:num)        update
 *   POST   notes/(:num)/pin    toggle pinned
 *   DELETE notes/(:num)        soft-delete
 */
class NotesApiController extends BaseApiController
{
    protected $helpers = ['settings', 'subscription'];

    private const FEATURE = 'notes';

    private function guard(): array
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return ['error' => $this->failUnauthorized('Invalid or missing token.')];
        }
        if (! $this->apiHasFeature($user, self::FEATURE)) {
            return ['error' => $this->failForbidden('Your plan does not include Notes.')];
        }
        return ['user' => $user, 'companyId' => $this->resolveCompanyId($user)];
    }

    public function index()
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        // Pinned first, then most-recently updated.
        $rows = (new NoteModel())
            ->where('notes.company_id', $g['companyId'])
            ->orderBy('is_pinned', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        return $this->respond(['status' => 'ok', 'notes' => $rows]);
    }

    public function create()
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $data = $this->collect($g['companyId'], (int) $g['user']['id']);
        if ($data['title'] === '' && (string) $data['content'] === '') {
            return $this->failValidationErrors('A title or some content is required.');
        }
        if ($data['title'] === '') {
            $data['title'] = mb_substr(trim((string) $data['content']), 0, 60);
        }
        $model = new NoteModel();
        $id    = $model->insert($data, true);
        if (! $id) {
            return $this->failValidationErrors($model->errors() ?: 'Could not save note.');
        }
        return $this->respondCreated(['status' => 'ok', 'id' => (int) $id]);
    }

    public function update($id = null)
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $model = new NoteModel();
        $row   = $this->findScoped($model, (int) $id, $g['companyId']);
        if (! $row) {
            return $this->failNotFound('Note not found.');
        }
        $data = $this->collect($g['companyId'], (int) $row['user_id']);
        if (! $model->update((int) $id, $data)) {
            return $this->failValidationErrors($model->errors() ?: 'Could not update note.');
        }
        return $this->respond(['status' => 'ok', 'id' => (int) $id]);
    }

    public function togglePin($id = null)
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $model = new NoteModel();
        $row   = $this->findScoped($model, (int) $id, $g['companyId']);
        if (! $row) {
            return $this->failNotFound('Note not found.');
        }
        $pinned = (int) ($row['is_pinned'] ?? 0) === 1 ? 0 : 1;
        $model->update((int) $id, ['is_pinned' => $pinned]);
        return $this->respond(['status' => 'ok', 'id' => (int) $id, 'is_pinned' => $pinned]);
    }

    public function delete($id = null)
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $model = new NoteModel();
        $row   = $this->findScoped($model, (int) $id, $g['companyId']);
        if (! $row) {
            return $this->failNotFound('Note not found.');
        }
        $model->delete((int) $id);
        return $this->respondDeleted(['status' => 'ok', 'id' => (int) $id]);
    }

    private function findScoped(NoteModel $model, int $id, ?int $companyId): ?array
    {
        $row = $model->find($id);
        if (! $row || ($companyId !== null && (int) $row['company_id'] !== $companyId)) {
            return null;
        }
        return $row;
    }

    private function collect(?int $companyId, int $userId): array
    {
        $catId = $this->input('category_id');
        return [
            'user_id'      => $userId,
            'company_id'   => $companyId,
            'category_id'  => $catId !== null && $catId !== '' ? (int) $catId : null,
            'title'        => trim((string) ($this->input('title') ?? '')),
            'content'      => (string) ($this->input('content') ?? ''),
            'tags'         => trim((string) ($this->input('tags') ?? '')) ?: null,
            'color'        => trim((string) ($this->input('color') ?? '')) ?: null,
            'is_important' => (int) ((bool) $this->input('is_important')),
        ];
    }
}
