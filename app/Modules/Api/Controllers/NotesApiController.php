<?php

namespace Modules\Api\Controllers;

use App\Models\NoteModel;
use App\Models\NoteCategoryModel;
use Config\Database;

/**
 * Notes API for the mobile app. Company-scoped (shared across a company's
 * members; `user_id` records the author). Gated by the `notes` feature
 * (Premium plan).
 *
 *   GET    notes                    list (?category= &filter=pinned|important) + categories
 *   POST   notes                    create
 *   PUT    notes/(:num)             update
 *   POST   notes/(:num)/pin         toggle pinned
 *   POST   notes/(:num)/important   toggle important
 *   DELETE notes/(:num)             soft-delete (to recycle bin)
 *   GET    notes/trash              recycle bin (soft-deleted notes)
 *   POST   notes/(:num)/restore     restore from recycle bin
 *   DELETE notes/(:num)/purge       permanently delete
 *   GET    notes/categories         list categories
 *   POST   notes/categories         create category
 *   DELETE notes/categories/(:num)  delete category (detaches its notes)
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
        $category = (int) ($this->request->getGet('category') ?? 0);
        $filter   = (string) ($this->request->getGet('filter') ?? '');

        // Pinned first, then most-recently updated.
        $b = (new NoteModel())->where('notes.company_id', $g['companyId']);
        if ($category > 0) {
            $b->where('notes.category_id', $category);
        }
        if ($filter === 'pinned') {
            $b->where('notes.is_pinned', 1);
        } elseif ($filter === 'important') {
            $b->where('notes.is_important', 1);
        }
        $rows = $b->orderBy('is_pinned', 'DESC')->orderBy('updated_at', 'DESC')->findAll();

        return $this->respond([
            'status'     => 'ok',
            'notes'      => $rows,
            'categories' => (new NoteCategoryModel())->forCompany($g['companyId']),
        ]);
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

    public function toggleImportant($id = null)
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
        $important = (int) ($row['is_important'] ?? 0) === 1 ? 0 : 1;
        $model->update((int) $id, ['is_important' => $important]);
        return $this->respond(['status' => 'ok', 'id' => (int) $id, 'is_important' => $important]);
    }

    // ---- Recycle bin --------------------------------------------------------

    public function trash()
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        return $this->respond(['status' => 'ok', 'notes' => (new NoteModel())->recycleBin($g['companyId'])]);
    }

    public function restore($id = null)
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $model = new NoteModel();
        $row   = $model->onlyDeleted()->where('company_id', $g['companyId'])->find((int) $id);
        if (! $row) {
            return $this->failNotFound('Note not found.');
        }
        $model->restore((int) $id, $g['companyId']);
        return $this->respond(['status' => 'ok', 'id' => (int) $id, 'message' => 'Note restored.']);
    }

    public function purge($id = null)
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $model = new NoteModel();
        $row   = $model->onlyDeleted()->where('company_id', $g['companyId'])->find((int) $id);
        if (! $row) {
            return $this->failNotFound('Note not found.');
        }
        $model->delete((int) $id, true); // hard delete
        Database::connect()->table('note_history')->where('note_id', (int) $id)->delete();
        return $this->respond(['status' => 'ok', 'id' => (int) $id, 'message' => 'Note permanently deleted.']);
    }

    // ---- Categories ---------------------------------------------------------

    public function categories()
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        return $this->respond(['status' => 'ok', 'categories' => (new NoteCategoryModel())->forCompany($g['companyId'])]);
    }

    public function createCategory()
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $name = trim((string) ($this->input('name') ?? ''));
        if ($name === '' || mb_strlen($name) > 60) {
            return $this->failValidationErrors(['name' => 'A category name (max 60 chars) is required.']);
        }
        $color = trim((string) ($this->input('color') ?? '')) ?: '#6c757d';
        $id    = (new NoteCategoryModel())->insert([
            'user_id'    => (int) $g['user']['id'],
            'company_id' => $g['companyId'],
            'name'       => $name,
            'color'      => $color,
        ]);
        return $this->respondCreated(['status' => 'ok', 'id' => (int) $id, 'message' => 'Category added.']);
    }

    public function deleteCategory($id = null)
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $cats = new NoteCategoryModel();
        $row  = $cats->where('company_id', $g['companyId'])->find((int) $id);
        if (! $row) {
            return $this->failNotFound('Category not found.');
        }
        $cats->delete((int) $id);
        // Detach notes that referenced it (mirrors web).
        (new NoteModel())->builder()
            ->where('company_id', $g['companyId'])
            ->where('category_id', (int) $id)
            ->update(['category_id' => null]);
        return $this->respond(['status' => 'ok', 'message' => 'Category removed.']);
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
