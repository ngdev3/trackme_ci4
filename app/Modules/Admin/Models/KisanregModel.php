<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/** KisanregModel — CI4 port of the reg_kisanvahidata (KV registration) listing. */
class KisanregModel
{
    protected function db()
    {
        return Database::connect();
    }

    private function base()
    {
        return $this->db()->table('reg_kisanvahidata')
            ->where('template_id', fy()->template_id)->where('FY', fy()->FY)
            ->where('product_type', fy()->product_type)
            ->where("COALESCE(status,'') != 'Delete'", null, false);
    }

    public function countData(): int
    {
        return $this->base()->select('Kisan_ID')->countAllResults();
    }

    public function getData(): array
    {
        $post = service('request')->getPost();
        $b = $this->base()->orderBy('Kisan_ID', 'desc');
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }
}
