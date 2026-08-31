<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/** KisanVahiModel — CI4 port of the kisanvahidata (Kisan Vahi purchases) listing. */
class KisanVahiModel
{
    protected function db()
    {
        return Database::connect();
    }

    private function base()
    {
        return $this->db()->table('kisanvahidata')
            ->where('template_id', fy()->template_id)->where('FY', fy()->FY)
            ->where('product_type', fy()->product_type);
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
