<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * LetterPadModel — CI4 port of admin/models/Letter_pad_mod. Firm-letterhead
 * letters (letter_pad_documents) with unique letter number + QR verify token +
 * generated PDF. Soft delete (status='Delete'). Self-healing table.
 */
class LetterPadModel
{
    private string $tbl = 'letter_pad_documents';

    protected function db()
    {
        return Database::connect();
    }

    public function ensureTable(): void
    {
        $this->db()->query("CREATE TABLE IF NOT EXISTS `letter_pad_documents` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `template_id` INT(11) NOT NULL, `FY` VARCHAR(20) NULL,
            `title` VARCHAR(255) NULL, `subject` VARCHAR(255) NULL, `letter_date` DATE NULL,
            `recipient` TEXT NULL, `content` LONGTEXT NOT NULL,
            `signature_name` VARCHAR(255) NULL, `signature_designation` VARCHAR(255) NULL,
            `letter_no` VARCHAR(30) NULL, `verify_token` VARCHAR(64) NULL, `watermark_code` VARCHAR(20) NULL,
            `pdf_path` VARCHAR(500) NULL, `page_count` INT NULL,
            `created_by` INT(11) NULL, `created_at` DATETIME NULL, `updated_at` DATETIME NULL, `deleted_at` DATETIME NULL,
            `status` ENUM('Active','Delete') NOT NULL DEFAULT 'Active',
            PRIMARY KEY (`id`), UNIQUE KEY `uq_lpd_letter_no` (`letter_no`),
            KEY `idx_lpd_template` (`template_id`), KEY `idx_lpd_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function firmSelect(): string
    {
        return "t.template_id, t.template_name, t.FY, f.id AS firm_id, f.name AS firm_name,
            f.address, f.gst_no, f.fssai_no, f.company_email, f.mandi_license_mandi, f.mandi_license_mill,
            f.bank_name, f.bank_ifsc, f.bank_number";
    }

    public function getFirmTemplates(?string $fy = null): array
    {
        $b = $this->db()->table('aa_template t')->select($this->firmSelect())
            ->join('firm_name f', 'f.id = t.firm_name_id')
            ->where('t.status', 'Active')->where('f.status !=', 'Delete');
        if ($fy) { $b->where('t.FY', $fy); }
        return $b->orderBy('f.name', 'asc')->orderBy('t.FY', 'desc')->get()->getResult();
    }

    public function getTemplateDetail(int $templateId)
    {
        return $this->db()->table('aa_template t')->select($this->firmSelect())
            ->join('firm_name f', 'f.id = t.firm_name_id')->where('t.template_id', $templateId)->get()->getRow();
    }

    private function applyFilters($b): void
    {
        $req = service('request');
        $b->where('lp.status !=', 'Delete');
        if ($req->getPost('firm_filter')) { $b->where('lp.template_id', (int) $req->getPost('firm_filter')); }
        if ($req->getPost('date_from'))   { $b->where('lp.letter_date >=', $req->getPost('date_from')); }
        if ($req->getPost('date_to'))     { $b->where('lp.letter_date <=', $req->getPost('date_to')); }
        $post = $req->getPost();
        if (! empty($post['search']['value'])) {
            $s = $post['search']['value'];
            $b->groupStart()->like('lp.title', $s)->orLike('lp.subject', $s)->orLike('lp.letter_no', $s)
              ->orLike('lp.recipient', $s)->orLike('lp.signature_name', $s)->groupEnd();
        }
    }

    public function countData(): int
    {
        $b = $this->db()->table($this->tbl . ' lp');
        $this->applyFilters($b);
        return $b->countAllResults();
    }

    public function getData(): array
    {
        $post = service('request')->getPost();
        $b = $this->db()->table($this->tbl . ' lp')
            ->select("lp.*, t.template_name, t.FY AS template_fy, f.name AS firm_name, CONCAT(u.first_name,' ',u.last_name) AS created_by_name")
            ->join('aa_template t', 't.template_id = lp.template_id', 'left')
            ->join('firm_name f', 'f.id = t.firm_name_id', 'left')
            ->join('users u', 'u.id = lp.created_by', 'left');
        $this->applyFilters($b);
        $b->orderBy('lp.id', 'desc');
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }

    public function add(array $data): int
    {
        $this->db()->table($this->tbl)->insert($data);
        return (int) $this->db()->insertID();
    }

    public function edit(int $id, array $data): bool
    {
        $this->db()->table($this->tbl)->where('id', $id)->where('status !=', 'Delete')->update($data);
        return true;
    }

    public function view(int $id)
    {
        return $this->db()->table($this->tbl)->where('id', $id)->where('status !=', 'Delete')->get()->getRow();
    }

    public function letterNoExists(string $no): bool
    {
        return $this->db()->table($this->tbl)->where('letter_no', $no)->countAllResults() > 0;
    }

    public function softDelete(int $id): bool
    {
        $this->db()->table($this->tbl)->where('id', $id)->where('status !=', 'Delete')
            ->update(['status' => 'Delete', 'deleted_at' => date('Y-m-d H:i:s')]);
        return $this->db()->affectedRows() > 0;
    }
}
