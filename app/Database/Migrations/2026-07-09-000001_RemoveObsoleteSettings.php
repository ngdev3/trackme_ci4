<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveObsoleteSettings extends Migration
{
    private array $keys = [
        'currency_symbol',
        'currency_code',
        'number_grouping',
        'currency_decimals',
        'date_format',
        'time_format',
        'timezone',
        'weather_city',
        'weather_units',
        'dashboard_widgets',
        'toast_success_color',
        'toast_error_color',
        'toast_warning_color',
        'toast_info_color',
        'alert_color',
        'prompt_color',
        'confirm_color',
        'sweetalert_confirm_color',
        'sweetalert_cancel_color',
        'web_push_enabled',
        'vapid_subject',
    ];

    public function up()
    {
        if ($this->db->tableExists('settings')) {
            $this->db->table('settings')->whereIn('setting_key', $this->keys)->delete();
        }
    }

    public function down()
    {
        // Obsolete settings are intentionally not restored.
    }
}
