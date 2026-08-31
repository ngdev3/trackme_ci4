<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * DocumentModel — CI4 port of admin/models/Document_mod. Document-renewal tracker
 * (aa_document). Scope: rows belonging to the current firm+FY OR marked is_common=1
 * (visible to all firms). Self-heals the file columns.
 */
class DocumentModel
{
    protected function db()
    {
        return Database::connect();
    }

    public function ensureColumns(): void
    {
        $db = $this->db();
        if (! $db->tableExists('aa_document')) { return; }
        $add = [
            'document_file'      => "VARCHAR(255) NULL",
            'original_file_name' => "VARCHAR(255) NULL",
            'file_type'          => "VARCHAR(100) NULL",
            'file_size'          => "DECIMAL(10,2) NULL",
            'is_common'          => "TINYINT(1) NOT NULL DEFAULT 0",
        ];
        foreach ($add as $col => $def) {
            if (! $db->fieldExists($col, 'aa_document')) {
                $db->query("ALTER TABLE `aa_document` ADD COLUMN `{$col}` {$def}");
            }
        }
    }

    private function applyScope($b, string $alias = ''): void
    {
        $p   = $alias !== '' ? $alias . '.' : '';
        $fy  = $this->db()->escape(fy()->FY);
        $tid = (int) fy()->template_id;
        $b->where("(({$p}FY = {$fy} AND {$p}template_id = {$tid}) OR {$p}is_common = 1)", null, false);
    }

    public function countBillingData(): int
    {
        $req = service('request');
        $b = $this->db()->table('aa_document');
        if ($req->getGet('status')) { $b->where('status', $req->getGet('status')); }
        $post = $req->getPost();
        if (! empty($post['search']['value'])) { $b->like('name', $post['search']['value']); }
        $this->applyScope($b);
        return $b->countAllResults();
    }

    public function getBillingData(): array
    {
        $post = service('request')->getPost();
        $b = $this->db()->table('aa_document ab')
            ->select("ab.*, CONCAT(acn.first_name, ' ', acn.last_name) as user_name")
            ->join('users acn', 'acn.id = ab.user_id', 'left');
        $this->applyScope($b, 'ab');
        if (! empty($post['search']['value'])) { $b->like("(CONCAT(ab.name,' ',ab.remark))", $post['search']['value']); }
        $cols = [1 => 'id', 2 => 'name'];
        if (! empty($post['order'][0]['column']) && ! empty($post['order'][0]['dir'])) {
            $b->orderBy($cols[$post['order'][0]['column']] ?? 'ab.id', $post['order'][0]['dir']);
        } else {
            $b->orderBy('ab.id', 'desc');
        }
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }

    public function add(array $data): int
    {
        $this->db()->table('aa_document')->insert($data);
        return (int) $this->db()->insertID();
    }

    public function edit(int $id, array $data): bool
    {
        $b = $this->db()->table('aa_document')->where('id', $id);
        $this->applyScope($b);
        $b->update($data);
        return true;
    }

    public function view(int $id)
    {
        $b = $this->db()->table('aa_document')->where('id', $id);
        $this->applyScope($b);
        return $b->get()->getRow();
    }

    public function getAccessible(int $id)
    {
        return $this->view($id);
    }

    public function deletePermanent(int $id): bool
    {
        $b = $this->db()->table('aa_document')->where('id', $id);
        $this->applyScope($b);
        $b->delete();
        return $this->db()->affectedRows() > 0;
    }
}
