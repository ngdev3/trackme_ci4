<?php

namespace App\Modules\Ricemill\Models;

use Config\Database;

/**
 * RicemillSiteModel — CI4 port of the public-website bits of CI3
 * admin/Ricemill_mod: the firm profile shown on the marketing site and the
 * public inquiry insert (aa_ricemill_inquiry).
 */
class RicemillSiteModel
{
    private const TABLE = 'aa_ricemill_inquiry';

    protected function db()
    {
        return Database::connect();
    }

    /** Firm profile from firm_name (case/space-insensitive name match), Active only. */
    public function millProfile(string $name = 'CR INDUSTRIES')
    {
        $db   = $this->db();
        $norm = strtoupper(preg_replace('/\s+/', '', $name));
        return $db->table('firm_name')
            ->where("UPPER(REPLACE(`name`, ' ', '')) = " . $db->escape($norm), null, false)
            ->where('status', 'Active')
            ->limit(1)
            ->get()->getRow();
    }

    /** Store a public inquiry; returns the inserted id or 0. */
    public function addInquiry(array $data): int
    {
        $row = [
            'name'       => $data['name'] ?? '',
            'mobile_no'  => $data['mobile_no'] ?? '',
            'address'    => $data['address'] ?? '',
            'product'    => $data['product'] ?? '',
            'quantity'   => $data['quantity'] ?? '',
            'message'    => $data['message'] ?? '',
            'status'     => 'New',
            'ip_address' => $data['ip_address'] ?? '',
        ];
        $this->db()->table(self::TABLE)->insert($row);
        return (int) $this->db()->insertID();
    }
}
