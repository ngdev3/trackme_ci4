<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/** AccountMappingModel — CI4 port (slice: center list for the Farmer Captures inbox). */
class AccountMappingModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Active centers (aa_center_name). Returns false when none, matching CI3. */
    public function center_list()
    {
        $rows = $this->db()->table('aa_center_name')->where('status', 'Active')->get()->getResult();
        return $rows ?: false;
    }
}
