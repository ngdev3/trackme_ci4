<?php

namespace App\Models;

use CodeIgniter\Model;

class NoteHistoryModel extends Model
{
    protected $table         = 'note_history';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;
    protected $updatedField   = '';   // history rows are immutable

    protected $allowedFields = ['note_id', 'user_id', 'company_id', 'title', 'content', 'tags'];

    /** Snapshot the current state of a note before it is overwritten. */
    public function snapshot(array $note): void
    {
        $this->insert([
            'note_id'    => (int) $note['id'],
            'user_id'    => (int) $note['user_id'],
            'company_id' => $note['company_id'] ?? null,
            'title'      => $note['title'] ?? null,
            'content'    => $note['content'] ?? null,
            'tags'       => $note['tags'] ?? null,
        ]);
    }

    /** Prior versions of a note (within the company), newest first. */
    public function forNote(int $noteId, ?int $companyId): array
    {
        return $this->where('note_id', $noteId)->where('company_id', $companyId)
            ->orderBy('id', 'DESC')->findAll();
    }
}
