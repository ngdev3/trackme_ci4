<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * BankPasswordModel — CI4 port of the aa_bank_passwords vault listing. Shows
 * metadata only (never the secret values). Scope: current firm OR is_common,
 * soft-delete-aware.
 */
class BankPasswordModel
{
    protected function db()
    {
        return Database::connect();
    }

    private function base()
    {
        return $this->db()->table('aa_bank_passwords')
            ->groupStart()->where('template_id', fy()->template_id)->orWhere('is_common', 1)->groupEnd()
            ->where("COALESCE(status,'') != 'Delete'", null, false);
    }

    public function countData(): int
    {
        return $this->base()->select('id')->countAllResults();
    }

    public function getData(): array
    {
        $post = service('request')->getPost();
        $b = $this->base()->orderBy('id', 'desc');
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }

    /** Insert one credential. Stamps created_date. Returns the new id. (CI3 add() 1:1) */
    public function add(array $data)
    {
        $data['created_date'] = date('Y-m-d H:i:s');
        $this->db()->table('aa_bank_passwords')->insert($data);
        return $this->db()->insertID();
    }

    /** Insert an export/disclosure audit row. Returns the new id. (CI3 add_audit_log() 1:1) */
    public function addAuditLog(array $data)
    {
        $this->db()->table('aa_password_audit_logs')->insert($data);
        return $this->db()->insertID();
    }

    /**
     * Export/print/share history for the current firm + FY, newest first.
     * (CI3 audit_logs() 1:1 — scoped by template_id AND FY.)
     */
    public function auditLogs(): array
    {
        return $this->db()->table('aa_password_audit_logs')
            ->where('template_id', (int) fy()->template_id)
            ->where('FY', fy()->FY)
            ->orderBy('id', 'desc')
            ->get()->getResult();
    }
}
