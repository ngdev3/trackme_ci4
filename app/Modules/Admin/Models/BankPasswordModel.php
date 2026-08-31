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
}
