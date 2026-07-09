<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Files attached to a transaction. The physical file lives under
 * writable/uploads/transactions/<user_id>/; this row holds the metadata.
 */
class TransactionAttachmentModel extends Model
{
    protected $table          = 'transaction_attachments';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'transaction_id', 'user_id', 'company_id', 'original_name',
        'stored_name', 'mime', 'kind', 'size', 'created_by',
    ];

    /** All (non-deleted) attachments for a transaction, oldest first. */
    public function forTransaction(int $transactionId): array
    {
        return $this->where('transaction_id', $transactionId)
            ->orderBy('id', 'ASC')->findAll();
    }

    /** Count attachments per transaction id, for a set of ids. */
    public function countsFor(array $transactionIds): array
    {
        $transactionIds = array_values(array_unique(array_filter(array_map('intval', $transactionIds))));
        if ($transactionIds === []) {
            return [];
        }
        $rows = $this->builder()
            ->select('transaction_id, COUNT(*) AS c')
            ->where('deleted_at', null)
            ->whereIn('transaction_id', $transactionIds)
            ->groupBy('transaction_id')
            ->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['transaction_id']] = (int) $r['c'];
        }
        return $out;
    }

    /**
     * Bucket a mime type / extension into a coarse kind used to pick the preview
     * widget: image | audio | video | pdf | doc | sheet | file.
     */
    public static function kindFor(string $mime, string $ext): string
    {
        $mime = strtolower($mime);
        $ext  = strtolower($ext);

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if ($mime === 'application/pdf' || $ext === 'pdf') {
            return 'pdf';
        }
        if (in_array($ext, ['doc', 'docx', 'odt', 'rtf', 'txt'], true)) {
            return 'doc';
        }
        if (in_array($ext, ['xls', 'xlsx', 'csv', 'ods'], true)) {
            return 'sheet';
        }
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic'], true)) {
            return 'image';
        }
        if (in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'aac'], true)) {
            return 'audio';
        }
        if (in_array($ext, ['mp4', 'webm', 'mov', 'mkv', 'avi'], true)) {
            return 'video';
        }
        return 'file';
    }
}
