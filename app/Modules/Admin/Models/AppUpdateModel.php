<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * AppUpdateModel — CI4 port of admin/models/App_update_mod + the app_update
 * helper's settings/latest accessors. Manages Android build publishing:
 * app_versions (+ download/activity logs) and app_update_settings (single KV
 * store). Tables are lazily created + seeded. Global (one app for all firms).
 */
class AppUpdateModel
{
    protected function db()
    {
        return Database::connect();
    }

    public function ensureTables(): void
    {
        $db = $this->db();
        $db->query("CREATE TABLE IF NOT EXISTS `app_versions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT, `version_name` VARCHAR(50) NOT NULL, `version_code` INT(11) NOT NULL,
            `apk_file_name` VARCHAR(255) NOT NULL, `apk_file_path` VARCHAR(500) NOT NULL, `file_size` BIGINT(20) NOT NULL DEFAULT 0,
            `release_notes` TEXT NULL, `status` ENUM('Active','Inactive','Delete') NOT NULL DEFAULT 'Active',
            `is_latest` TINYINT(1) NOT NULL DEFAULT 0, `force_update` TINYINT(1) NOT NULL DEFAULT 0, `website_visible` TINYINT(1) NOT NULL DEFAULT 0,
            `download_count` INT(11) NOT NULL DEFAULT 0, `uploaded_by` INT(11) NULL, `created_at` DATETIME NULL, `updated_at` DATETIME NULL,
            PRIMARY KEY (`id`), UNIQUE KEY `uq_app_version_code` (`version_code`), KEY `idx_app_is_latest` (`is_latest`), KEY `idx_app_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->query("CREATE TABLE IF NOT EXISTS `app_download_logs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT, `app_version_id` INT(11) NOT NULL, `user_id` INT(11) NULL, `source` VARCHAR(20) NOT NULL DEFAULT 'admin',
            `ip_address` VARCHAR(45) NULL, `user_agent` VARCHAR(500) NULL, `device_information` VARCHAR(255) NULL, `downloaded_at` DATETIME NULL,
            PRIMARY KEY (`id`), KEY `idx_adl_version` (`app_version_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->query("CREATE TABLE IF NOT EXISTS `app_update_settings` (
            `id` INT(11) NOT NULL AUTO_INCREMENT, `setting_key` VARCHAR(100) NOT NULL, `setting_value` TEXT NULL,
            `created_at` DATETIME NULL, `updated_at` DATETIME NULL, PRIMARY KEY (`id`), UNIQUE KEY `uq_aus_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->query("CREATE TABLE IF NOT EXISTS `app_activity_logs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT, `app_version_id` INT(11) NULL, `action` VARCHAR(50) NOT NULL, `details` VARCHAR(500) NULL,
            `user_id` INT(11) NULL, `ip_address` VARCHAR(45) NULL, `created_at` DATETIME NULL, PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->seedSettings();
    }

    private function seedSettings(): void
    {
        $now = date('Y-m-d H:i:s');
        $defaults = [
            'play_store_url' => '', 'website_section_enabled' => '1', 'public_download_enabled' => '0',
            'app_name' => 'C R Industries ERP', 'keep_apk_files' => '5', 'max_apk_mb' => '150',
        ];
        foreach ($defaults as $k => $v) {
            if ($this->db()->table('app_update_settings')->where('setting_key', $k)->countAllResults() === 0) {
                $this->db()->table('app_update_settings')->insert(['setting_key' => $k, 'setting_value' => $v, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function setting(string $key, string $default = ''): string
    {
        $r = $this->db()->table('app_update_settings')->where('setting_key', $key)->get()->getRow();
        return $r ? (string) $r->setting_value : $default;
    }

    public function allSettings(): array
    {
        $out = [];
        foreach ($this->db()->table('app_update_settings')->get()->getResult() as $r) { $out[$r->setting_key] = $r->setting_value; }
        return $out;
    }

    public function saveSetting(string $key, string $value): void
    {
        $now = date('Y-m-d H:i:s');
        if ($this->db()->table('app_update_settings')->where('setting_key', $key)->countAllResults() > 0) {
            $this->db()->table('app_update_settings')->where('setting_key', $key)->update(['setting_value' => $value, 'updated_at' => $now]);
        } else {
            $this->db()->table('app_update_settings')->insert(['setting_key' => $key, 'setting_value' => $value, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function getVersions(): array
    {
        return $this->db()->table('app_versions v')
            ->select('v.*, CONCAT(COALESCE(u.first_name,"")," ",COALESCE(u.last_name,"")) AS uploaded_by_name', false)
            ->join('users u', 'u.id = v.uploaded_by', 'left')
            ->where('v.status !=', 'Delete')->orderBy('v.version_code', 'DESC')->get()->getResult();
    }

    public function countVersions(): int
    {
        return $this->db()->table('app_versions')->where('status !=', 'Delete')->countAllResults();
    }

    public function getVersion(int $id)
    {
        return $this->db()->table('app_versions')->where('id', $id)->get()->getRow();
    }

    public function versionCodeExists(int $code, int $exceptId = 0): bool
    {
        $b = $this->db()->table('app_versions')->where('version_code', $code)->where('status !=', 'Delete');
        if ($exceptId) { $b->where('id !=', $exceptId); }
        return $b->countAllResults() > 0;
    }

    public function insertVersion(array $data): int
    {
        $this->db()->table('app_versions')->insert($data);
        return (int) $this->db()->insertID();
    }

    public function updateVersion(int $id, array $data): bool
    {
        $this->db()->table('app_versions')->where('id', $id)->update($data);
        return true;
    }

    public function markLatest(int $id): bool
    {
        $this->db()->table('app_versions')->where('id !=', $id)->update(['is_latest' => 0]);
        $this->db()->table('app_versions')->where('id', $id)->update(['is_latest' => 1, 'status' => 'Active']);
        return true;
    }

    public function latestVersion()
    {
        return $this->db()->table('app_versions')->where('status', 'Active')->where('is_latest', 1)->orderBy('version_code', 'DESC')->get()->getRow();
    }

    public function softDelete(int $id): bool
    {
        $this->db()->table('app_versions')->where('id', $id)->update(['status' => 'Delete', 'is_latest' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function getDownloadLogs(int $limit = 500): array
    {
        return $this->db()->table('app_download_logs l')
            ->select('l.*, v.version_name, v.version_code, CONCAT(COALESCE(u.first_name,"")," ",COALESCE(u.last_name,"")) AS user_name', false)
            ->join('app_versions v', 'v.id = l.app_version_id', 'left')
            ->join('users u', 'u.id = l.user_id', 'left')
            ->orderBy('l.id', 'DESC')->limit($limit)->get()->getResult();
    }

    public function dashboardStats(): array
    {
        $latest = $this->latestVersion();
        $tot = $this->db()->table('app_versions')->selectSum('download_count', 't')->where('status !=', 'Delete')->get()->getRow();
        return [
            'total_versions'  => $this->countVersions(),
            'total_downloads' => (int) ($tot->t ?? 0),
            'latest'          => $latest,
            'website_visible' => $latest ? ((int) $latest->website_visible === 1 && $this->setting('website_section_enabled', '1') === '1') : false,
            'force_update'    => $latest ? ((int) $latest->force_update === 1) : false,
        ];
    }
}
