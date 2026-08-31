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

    // ------------------------------------------------------------------
    // Registration report (status / center) — CI3 Kisanreg_mod parity.
    // The real center comes from the mapped Kisan Vahi row
    // (kisanvahidata.CenterName -> aa_center_name), the same source the listing
    // uses — NOT reg_kisanvahidata.origin_type. Scoped by FY + product_type.
    // ------------------------------------------------------------------
    public function summary_stats()
    {
        return $this->db()->table('reg_kisanvahidata')
            ->select("COUNT(*) AS total,
                SUM(status='Verified') AS verified, SUM(status='Unverified') AS unverified,
                SUM(status='Mapped') AS mapped, SUM(status='Dead') AS dead, SUM(status='Suspended') AS suspended,
                COALESCE(SUM(Quantity),0) AS total_qty, COALESCE(SUM(left_quantity),0) AS left_qty", false)
            ->where('FY', fy()->FY)->where('product_type', fy()->product_type)
            ->get()->getRow();
    }

    /** Apply the shared report filters (status + center) to a builder. */
    private function applyReportFilters($b): void
    {
        $status = service('request')->getGet('status');
        $center = service('request')->getGet('center');
        if (! empty($status) && $status !== 'none') { $b->where('r.status', $status); }
        if (! empty($center)) { $b->where('kvd.CenterName', (int) $center); }
    }

    public function report_by_status(): array
    {
        $b = $this->db()->table('reg_kisanvahidata r')
            ->select("r.status AS status, COUNT(*) AS cnt,
                COALESCE(SUM(r.Quantity),0) AS qty, COALESCE(SUM(r.left_quantity),0) AS lqty", false)
            ->join('kisanvahidata kvd', 'kvd.Farmer_ID = r.Farmer_ID AND kvd.FY = r.FY', 'left')
            ->join('aa_center_name cn', 'cn.center_id = kvd.CenterName', 'left')
            ->where('r.FY', fy()->FY)->where('r.product_type', fy()->product_type);
        $this->applyReportFilters($b);
        return $b->groupBy('r.status')->orderBy('cnt', 'DESC')->get()->getResult();
    }

    public function report_by_center(): array
    {
        $b = $this->db()->table('reg_kisanvahidata r')
            ->select("COALESCE(NULLIF(cn.name,''),'— Unmapped —') AS center,
                COUNT(*) AS cnt, COALESCE(SUM(r.Quantity),0) AS qty, COALESCE(SUM(r.left_quantity),0) AS lqty", false)
            ->join('kisanvahidata kvd', 'kvd.Farmer_ID = r.Farmer_ID AND kvd.FY = r.FY', 'left')
            ->join('aa_center_name cn', 'cn.center_id = kvd.CenterName', 'left')
            ->where('r.FY', fy()->FY)->where('r.product_type', fy()->product_type);
        $this->applyReportFilters($b);
        return $b->groupBy('cn.center_id')->orderBy('cnt', 'DESC')->get()->getResult();
    }

    public function report_centers(): array
    {
        return $this->db()->table('reg_kisanvahidata r')->distinct()
            ->select('cn.center_id AS id, cn.name AS name')
            ->join('kisanvahidata kvd', 'kvd.Farmer_ID = r.Farmer_ID AND kvd.FY = r.FY', 'inner')
            ->join('aa_center_name cn', 'cn.center_id = kvd.CenterName', 'inner')
            ->where('r.FY', fy()->FY)->where('r.product_type', fy()->product_type)
            ->where('cn.name !=', '')->orderBy('cn.name', 'asc')
            ->get()->getResult();
    }
}
