<?php

namespace App\Models;

use CodeIgniter\Model;

class NoteModel extends Model
{
    protected $table          = 'notes';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'user_id', 'company_id', 'category_id', 'title', 'content', 'tags', 'color',
        'is_pinned', 'is_important', 'attach_module', 'attach_ref',
    ];

    protected $validationRules = [
        'title' => 'required|min_length[1]|max_length[191]',
    ];

    /**
     * A company-scoped query builder joined to the category name. Every listing
     * MUST start here so one company can never read another company's notes.
     * Notes are shared across all members of a company; `user_id` records the
     * author only.
     */
    public function scoped(?int $companyId)
    {
        return $this->select('notes.*, note_categories.name AS category_name, note_categories.color AS category_color')
            ->join('note_categories', 'note_categories.id = notes.category_id', 'left')
            ->where('notes.company_id', $companyId);
    }

    /** Fetch one note that belongs to the company, or null. */
    public function findForCompany(int $id, ?int $companyId): ?array
    {
        return $this->where('company_id', $companyId)->find($id) ?: null;
    }

    /** Important, non-deleted notes for the dashboard. */
    public function important(?int $companyId, int $limit = 5): array
    {
        return $this->where('company_id', $companyId)->where('is_important', 1)
            ->orderBy('updated_at', 'DESC')->findAll($limit);
    }

    /** Recently updated notes for the dashboard. */
    public function recent(?int $companyId, int $limit = 5): array
    {
        return $this->where('company_id', $companyId)
            ->orderBy('updated_at', 'DESC')->findAll($limit);
    }

    /** Soft-deleted notes (the recycle bin) for a company. */
    public function recycleBin(?int $companyId): array
    {
        return $this->onlyDeleted()->where('company_id', $companyId)
            ->orderBy('deleted_at', 'DESC')->findAll();
    }

    /**
     * Restore a soft-deleted note. Uses the query builder directly because
     * `deleted_at` is (deliberately) not an allowed mass-assignment field.
     */
    public function restore(int $id, ?int $companyId): bool
    {
        return $this->builder()
            ->where('id', $id)->where('company_id', $companyId)
            ->update(['deleted_at' => null]);
    }
}
