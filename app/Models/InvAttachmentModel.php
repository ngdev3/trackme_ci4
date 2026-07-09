<?php

namespace App\Models;

use CodeIgniter\Model;

/** Proof files (photo / video / voice / pdf) linked to an inventory movement. */
class InvAttachmentModel extends Model
{
    protected $table         = 'inv_attachments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'company_id', 'movement_id', 'product_id', 'lot_id', 'party_id',
        'kind', 'original_name', 'stored_name', 'mime', 'size', 'created_by', 'created_at',
    ];

    /** Classify a file into a coarse kind from its mime / extension. */
    public static function kindFor(string $mime, string $ext): string
    {
        $mime = strtolower($mime);
        $ext  = strtolower($ext);
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/') || in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'webm'], true)) {
            return 'audio';
        }
        if ($mime === 'application/pdf' || $ext === 'pdf') {
            return 'pdf';
        }
        return 'doc';
    }

    public function forMovement(int $movementId): array
    {
        return $this->where('movement_id', $movementId)->orderBy('id', 'DESC')->findAll();
    }
}
