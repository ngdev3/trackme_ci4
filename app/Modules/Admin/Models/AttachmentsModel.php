<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * AttachmentsModel — CI4 port of the two Report_mod methods behind the
 * Attachments Gallery (admin/attachments): the gallery rows (rokad entries that
 * carry an image/voice/video) and the on-disk storage totals. Firm-scoped,
 * soft-delete-aware. Filters (from/to/mtype/q) come from GET, unchanged.
 */
class AttachmentsModel
{
    protected function db()
    {
        return Database::connect();
    }

    public function rokadAttachments(int $limit = 500): array
    {
        $req = service('request');
        $db  = $this->db();

        $b = $db->table('aa_rokad ar')
            ->select("ar.rokad_id, ar.rokad_date, ar.account_name, ar.karch_amount,
                ar.type_of_account, ar.entry_source, ar.remark, ar.added_type, ar.added_by,
                ar.image_path, ar.voice_note_path, ar.video_note_path,
                TRIM(CONCAT(COALESCE(cu.first_name,''),' ',COALESCE(cu.last_name,''))) as added_by_name", false);

        if ($db->tableExists('aa_entry_trace')) {
            $b->select("(SELECT MAX(t.created_at) FROM aa_entry_trace t WHERE t.entry_id = ar.rokad_id AND t.action = 'update') as last_modified", false)
              ->select("(SELECT COUNT(*) FROM aa_entry_trace t WHERE t.entry_id = ar.rokad_id AND t.action = 'update') as modified_count", false)
              ->select("(SELECT TRIM(CONCAT(COALESCE(mu.first_name,''),' ',COALESCE(mu.last_name,''))) FROM aa_entry_trace t LEFT JOIN users mu ON mu.id = t.user_id WHERE t.entry_id = ar.rokad_id AND t.action = 'update' ORDER BY t.id DESC LIMIT 1) as modified_by", false);
        }

        $b->join('users cu', 'cu.id = ar.added_by', 'left')
          ->where('ar.template_id', fy()->template_id)
          ->where('ar.status <>', 'Delete')
          ->where("((ar.image_path IS NOT NULL AND ar.image_path <> '')
            OR (ar.voice_note_path IS NOT NULL AND ar.voice_note_path <> '')
            OR (ar.video_note_path IS NOT NULL AND ar.video_note_path <> ''))", null, false);

        $from = $req->getGet('from_date');
        $to   = $req->getGet('to_date');
        if ($from && $to) {
            $b->where('ar.rokad_date >=', date('Y-m-d', strtotime($from)))->where('ar.rokad_date <=', date('Y-m-d', strtotime($to)));
        }
        $mtype = $req->getGet('mtype');
        if ($mtype === 'image')     { $b->where("(ar.image_path IS NOT NULL AND ar.image_path <> '')", null, false); }
        elseif ($mtype === 'voice') { $b->where("(ar.voice_note_path IS NOT NULL AND ar.voice_note_path <> '')", null, false); }
        elseif ($mtype === 'video') { $b->where("(ar.video_note_path IS NOT NULL AND ar.video_note_path <> '')", null, false); }

        $q = trim((string) $req->getGet('q'));
        if ($q !== '') {
            $b->groupStart()->like('ar.account_name', $q)->orLike('ar.rokad_id', $q)->orLike('ar.remark', $q)->groupEnd();
        }

        return $b->orderBy('ar.rokad_id', 'desc')->limit($limit)->get()->getResult();
    }

    public function rokadAttachmentsStorage(): array
    {
        $out = ['total_bytes' => 0, 'img_bytes' => 0, 'voice_bytes' => 0, 'video_bytes' => 0,
                'img_files' => 0, 'voice_files' => 0, 'video_files' => 0, 'present' => 0, 'missing' => 0];

        $rows = $this->db()->table('aa_rokad ar')
            ->select('ar.image_path, ar.voice_note_path, ar.video_note_path', false)
            ->where('ar.template_id', fy()->template_id)
            ->where('ar.status <>', 'Delete')
            ->where("((ar.image_path IS NOT NULL AND ar.image_path <> '')
                OR (ar.voice_note_path IS NOT NULL AND ar.voice_note_path <> '')
                OR (ar.video_note_path IS NOT NULL AND ar.video_note_path <> ''))", null, false)
            ->get()->getResult();

        $sizeOf = function ($path) use (&$out) {
            $path = trim((string) $path);
            if ($path === '') { return 0; }
            $abs = FCPATH . ltrim($path, '/\\');
            if (is_file($abs)) {
                $sz = @filesize($abs);
                if ($sz !== false) { $out['present']++; return (int) $sz; }
            }
            $out['missing']++;
            return 0;
        };

        foreach ($rows as $r) {
            if (! empty($r->image_path))      { $out['img_bytes']   += $sizeOf($r->image_path);      $out['img_files']++; }
            if (! empty($r->voice_note_path)) { $out['voice_bytes'] += $sizeOf($r->voice_note_path);  $out['voice_files']++; }
            if (! empty($r->video_note_path)) { $out['video_bytes'] += $sizeOf($r->video_note_path);  $out['video_files']++; }
        }
        $out['total_bytes'] = $out['img_bytes'] + $out['voice_bytes'] + $out['video_bytes'];
        return $out;
    }
}
