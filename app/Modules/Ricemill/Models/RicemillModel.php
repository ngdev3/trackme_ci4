<?php

namespace App\Modules\Ricemill\Models;

use Config\Database;

/**
 * RicemillModel — CI4 port of the public website's data access
 * (CI3 admin/Ricemill_mod::mill_profile + add_inquiry). Reads the firm
 * profile from firm_name and stores public inquiries in aa_ricemill_inquiry.
 */
class RicemillModel
{
    const TABLE = 'aa_ricemill_inquiry';

    protected function db()
    {
        return Database::connect();
    }

    /** Real mill profile row (firm_name) matched by normalised name, or null. */
    public function mill_profile(string $name = 'CR INDUSTRIES')
    {
        $db   = $this->db();
        $norm = strtoupper(preg_replace('/\s+/', '', $name));
        return $db->table('firm_name')
            ->where("UPPER(REPLACE(`name`, ' ', '')) = " . $db->escape($norm), null, false)
            ->where('status', 'Active')
            ->limit(1)
            ->get()->getRow();
    }

    /** Store a public inquiry; returns the inserted id (0 on failure). */
    public function add_inquiry(array $data): int
    {
        $row = [
            'name'       => $data['name']       ?? '',
            'mobile_no'  => $data['mobile_no']  ?? '',
            'address'    => $data['address']    ?? '',
            'product'    => $data['product']    ?? '',
            'quantity'   => $data['quantity']   ?? '',
            'message'    => $data['message']    ?? '',
            'status'     => 'New',
            'ip_address' => $data['ip_address'] ?? '',
        ];
        $this->db()->table(self::TABLE)->insert($row);
        return (int) $this->db()->insertID();
    }
}
