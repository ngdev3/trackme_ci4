<?php

namespace Modules\Notes\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ReminderService;
use App\Models\NoteCategoryModel;
use App\Models\NoteHistoryModel;
use App\Models\NoteModel;
use App\Models\ReminderModel;

/**
 * Notes — company-shared CRUD with pin/important flags, categories/tags, module
 * attachments, autosave, edit history and a recycle bin (soft delete). The
 * index doubles as the small Notes & Reminders dashboard.
 *
 * Every query is scoped to the active company, so notes are shared across a
 * company's members but never leak to another company. `user_id` records the
 * author only.
 */
class NoteController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'notes', 'company'];

    protected string $moduleCode = 'notes';
    protected string $baseRoute  = 'notes';

    protected NoteModel $notes;
    protected NoteCategoryModel $categories;
    protected NoteHistoryModel $history;

    public function __construct()
    {
        $this->notes      = new NoteModel();
        $this->categories = new NoteCategoryModel();
        $this->history    = new NoteHistoryModel();
    }

    private function uid(): int
    {
        return (int) user_id();
    }

    /** The active company id (all note data is scoped to it). */
    private function cid(): ?int
    {
        return company_id();
    }

    // ---------------------------------------------------------------
    // Listing + dashboard
    // ---------------------------------------------------------------
    public function index()
    {
        $uid = $this->uid();
        $cid = $this->cid();

        // Fire any newly-due reminder notifications for this company (cheap).
        (new ReminderService())->fireDueForCompany($cid);

        $search   = trim((string) $this->request->getGet('q'));
        $category = (int) $this->request->getGet('category');
        $filter   = (string) $this->request->getGet('filter'); // '', pinned, important

        $builder = $this->notes->scoped($cid)
            ->orderBy('notes.is_pinned', 'DESC')
            ->orderBy('notes.updated_at', 'DESC');

        if ($search !== '') {
            $builder->groupStart()
                ->like('notes.title', $search)
                ->orLike('notes.content', $search)
                ->orLike('notes.tags', $search)
                ->groupEnd();
        }
        if ($category > 0) {
            $builder->where('notes.category_id', $category);
        }
        if ($filter === 'pinned') {
            $builder->where('notes.is_pinned', 1);
        } elseif ($filter === 'important') {
            $builder->where('notes.is_important', 1);
        }

        $reminders = new ReminderModel();

        return $this->render('index', [
            'title'      => 'Notes',
            'breadcrumb' => [['label' => 'Notes & Reminders'], ['label' => 'Notes']],
            'rows'       => $builder->paginate(12),
            'pager'      => $this->notes->pager,
            'search'     => $search,
            'category'   => $category,
            'filter'     => $filter,
            'categories' => $this->categories->forCompany($cid),
            'binCount'   => count($this->notes->recycleBin($cid)),
            // Dashboard widgets
            'dueToday'      => $reminders->today($cid),
            'upcoming'      => $reminders->upcoming($cid, 5),
            'overdue'       => $reminders->overdue($cid),
            'importantNotes'=> $this->notes->important($cid, 5),
            'recentNotes'   => $this->notes->recent($cid, 5),
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
        ]);
    }

    // ---------------------------------------------------------------
    // Create / edit forms
    // ---------------------------------------------------------------
    public function create()
    {
        return $this->render('form', $this->formData(null, 'create'));
    }

    public function edit($id = null)
    {
        $row = $this->notes->findForCompany((int) $id, $this->cid());
        if (! $row) {
            return redirect()->to(site_url('notes'))->with('error', 'Note not found.');
        }
        return $this->render('form', $this->formData($row, 'edit'));
    }

    private function formData(?array $row, string $mode): array
    {
        return [
            'title'      => $mode === 'edit' ? 'Edit Note' : 'Add Note',
            'breadcrumb' => [
                ['label' => 'Notes', 'url' => site_url('notes')],
                ['label' => $mode === 'edit' ? 'Edit' : 'Add'],
            ],
            'row'        => $row,
            'mode'       => $mode,
            'errors'     => session()->getFlashdata('errors') ?? [],
            'categories' => $this->categories->forCompany($this->cid()),
            'history'    => $row ? $this->history->forNote((int) $row['id'], $this->cid()) : [],
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
        ];
    }

    // ---------------------------------------------------------------
    // Persist (Save button) + autosave (AJAX)
    // ---------------------------------------------------------------
    public function save()
    {
        $id = (int) $this->request->getPost('id');

        if (trim((string) $this->request->getPost('title')) === '') {
            return redirect()->back()->withInput()->with('errors', ['title' => 'A title is required.']);
        }

        [$id] = $this->upsert($this->input(), $id ?: null, true);
        return redirect()->to(site_url('notes'))->with('success', 'Note saved.');
    }

    public function autosave()
    {
        $id = (int) $this->request->getPost('id');
        [$id, $isNew] = $this->upsert($this->input(), $id ?: null, false);

        return $this->json(200, [
            'status' => 'success',
            'id'     => $id,
            'isNew'  => $isNew,
            'savedAt'=> date('H:i:s'),
        ]);
    }

    /**
     * @return array{0:int,1:bool}  [noteId, wasCreated]
     */
    private function upsert(array $input, ?int $id, bool $recordHistory): array
    {
        $uid = $this->uid();
        $cid = $this->cid();

        // Editing an existing note — must belong to the active company.
        if ($id) {
            $existing = $this->notes->findForCompany($id, $cid);
            if (! $existing) {
                return [0, false];
            }
            if ($recordHistory && $this->changed($existing, $input)) {
                $this->history->snapshot($existing);
            }
            $this->notes->update($id, $input);
            activity_log('Notes', 'Edit', "Note #{$id} updated");
            return [$id, false];
        }

        // New note.
        $input['user_id']    = $uid;
        $input['company_id'] = $cid;
        if (($input['title'] ?? '') === '') {
            $input['title'] = 'Untitled note';
        }
        $newId = (int) $this->notes->insert($input, true);
        activity_log('Notes', 'Add', "Note #{$newId} created");
        return [$newId, true];
    }

    /** Collected, sanitised note fields from the request. */
    private function input(): array
    {
        return [
            'title'         => trim((string) $this->request->getPost('title')),
            'content'       => (string) $this->request->getPost('content'),
            'tags'          => trim((string) $this->request->getPost('tags')) ?: null,
            'category_id'   => ((int) $this->request->getPost('category_id')) ?: null,
            'is_important'  => (int) (bool) $this->request->getPost('is_important'),
            'attach_module' => (string) $this->request->getPost('attach_module') ?: null,
            'attach_ref'    => trim((string) $this->request->getPost('attach_ref')) ?: null,
        ];
    }

    private function changed(array $old, array $new): bool
    {
        return ($old['title'] ?? '') !== ($new['title'] ?? '')
            || ($old['content'] ?? '') !== ($new['content'] ?? '')
            || ($old['tags'] ?? '') !== ($new['tags'] ?? '');
    }

    // ---------------------------------------------------------------
    // Quick toggles
    // ---------------------------------------------------------------
    public function togglePin($id = null)
    {
        return $this->toggleFlag((int) $id, 'is_pinned', 'Pin');
    }

    public function toggleImportant($id = null)
    {
        return $this->toggleFlag((int) $id, 'is_important', 'Important');
    }

    private function toggleFlag(int $id, string $field, string $label)
    {
        $row = $this->notes->findForCompany($id, $this->cid());
        if (! $row) {
            return redirect()->to(site_url('notes'))->with('error', 'Note not found.');
        }
        $new = (int) $row[$field] === 1 ? 0 : 1;
        $this->notes->update($id, [$field => $new]);
        activity_log('Notes', 'Edit', "Note #{$id} {$label} toggled");
        return redirect()->back()->with('success', $label . ($new ? ' enabled.' : ' removed.'));
    }

    // ---------------------------------------------------------------
    // Recycle bin (soft delete → restore / permanent delete)
    // ---------------------------------------------------------------
    public function delete($id = null)
    {
        $id  = (int) $id;
        $row = $this->notes->findForCompany($id, $this->cid());
        if (! $row) {
            return redirect()->to(site_url('notes'))->with('error', 'Note not found.');
        }
        $this->notes->delete($id); // soft delete
        activity_log('Notes', 'Delete', "Note #{$id} moved to recycle bin");
        return redirect()->to(site_url('notes'))->with('success', 'Note moved to recycle bin.');
    }

    public function recycleBin()
    {
        return $this->render('recycle_bin', [
            'title'      => 'Notes Recycle Bin',
            'breadcrumb' => [
                ['label' => 'Notes', 'url' => site_url('notes')],
                ['label' => 'Recycle Bin'],
            ],
            'rows'       => $this->notes->recycleBin($this->cid()),
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
        ]);
    }

    public function restore($id = null)
    {
        $id  = (int) $id;
        $row = $this->notes->onlyDeleted()->where('company_id', $this->cid())->find($id);
        if (! $row) {
            return redirect()->to(site_url('notes/recycle-bin'))->with('error', 'Note not found.');
        }
        $this->notes->restore($id, $this->cid());
        activity_log('Notes', 'Edit', "Note #{$id} restored");
        return redirect()->to(site_url('notes/recycle-bin'))->with('success', 'Note restored.');
    }

    public function forceDelete($id = null)
    {
        $id  = (int) $id;
        $row = $this->notes->onlyDeleted()->where('company_id', $this->cid())->find($id);
        if (! $row) {
            return redirect()->to(site_url('notes/recycle-bin'))->with('error', 'Note not found.');
        }
        $this->notes->delete($id, true); // hard delete
        $this->history->where('note_id', $id)->delete();
        activity_log('Notes', 'Delete', "Note #{$id} permanently deleted");
        return redirect()->to(site_url('notes/recycle-bin'))->with('success', 'Note permanently deleted.');
    }

    // ---------------------------------------------------------------
    // Categories (per-user)
    // ---------------------------------------------------------------
    public function storeCategory()
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->with('error', 'Category name is required.');
        }
        $this->categories->insert([
            'user_id'    => $this->uid(),
            'company_id' => $this->cid(),
            'name'       => $name,
            'color'      => (string) $this->request->getPost('color') ?: '#6c757d',
        ]);
        return redirect()->back()->with('success', 'Category added.');
    }

    public function deleteCategory($id = null)
    {
        $id  = (int) $id;
        $row = $this->categories->where('company_id', $this->cid())->find($id);
        if ($row) {
            $this->categories->delete($id);
            // Detach notes that referenced it.
            $this->notes->where('company_id', $this->cid())->where('category_id', $id)->set('category_id', null)->update();
        }
        return redirect()->back()->with('success', 'Category removed.');
    }

    // ---------------------------------------------------------------
    private function json(int $status, array $body)
    {
        $body['csrf'] = csrf_hash();
        return $this->response->setStatusCode($status)->setJSON($body);
    }
}
