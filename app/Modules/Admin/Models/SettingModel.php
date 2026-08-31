<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * SettingModel — CI4 port of the firm/FY switch backend (CI3 Setting_mod::add_fy,
 * getUserDetail, log_template_switch). Powers the top-nav "Change Firm" modal
 * (admin/setting/change_fy_id): update the user's default_firm, refresh their
 * session row, and record the switch in aa_template_switch_log (lazy-created).
 */
class SettingModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Point the user's active workspace at $templateId (users.default_firm). */
    public function switchFirm(int $userId, int $templateId): int
    {
        $this->db()->table('users')->where('id', $userId)->update(['default_firm' => $templateId]);
        return $this->db()->affectedRows();
    }

    /** Fresh user row (to reload into session after a switch). */
    public function reloadUser(string $email)
    {
        return $this->db()->table('users')->where('email', $email)->get()->getRow();
    }

    /** All Active firms/FYs for the Change Firm dropdown (firm-name joined). */
    public function financialYears(): array
    {
        return $this->db()->table('aa_template atp')
            ->select('atp.template_id, atp.FY, atp.track_name, atp.template_name, atp.product_type, frn.name as firm_name')
            ->join('firm_name frn', 'frn.id = atp.firm_name_id', 'left')
            ->where('atp.status', 'Active')
            ->orderBy('frn.name', 'asc')->orderBy('atp.FY', 'desc')
            ->get()->getResult();
    }

    /** Create the switch audit-log table if absent (self-heals; CI3 parity). */
    public function ensureSwitchLog(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $this->db()->query("CREATE TABLE IF NOT EXISTS aa_template_switch_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            template_id INT NULL,
            selected_at DATETIME NULL,
            ip_address VARCHAR(45) NULL,
            source VARCHAR(10) NULL,
            KEY idx_tsl_user (user_id),
            KEY idx_tsl_template (template_id)
        )");
    }

    /** Record one firm switch (who / which template / when / ip / source). */
    public function logSwitch(?int $userId, int $templateId, string $source, ?string $ip): int
    {
        $this->ensureSwitchLog();
        $this->db()->table('aa_template_switch_log')->insert([
            'user_id'     => (int) $userId,
            'template_id' => $templateId,
            'selected_at' => date('Y-m-d H:i:s'),
            'ip_address'  => $ip,
            'source'      => $source,
        ]);
        return (int) $this->db()->insertID();
    }
}
