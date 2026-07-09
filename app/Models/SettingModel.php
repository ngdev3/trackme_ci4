<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Key/value settings store. A user_id of 0 means a global (web-app) setting;
 * a positive user_id is a personal preference that overrides the global value.
 */
class SettingModel extends Model
{
    protected $table         = 'settings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['user_id', 'setting_key', 'setting_value'];

    /**
     * Read a single value (global scope by default).
     */
    public function get(string $key, int $userId = 0, $default = null)
    {
        $row = $this->where('user_id', $userId)->where('setting_key', $key)->first();
        return $row ? $row['setting_value'] : $default;
    }

    /**
     * All settings for a scope as key => value.
     *
     * @return array<string, string|null>
     */
    public function allFor(int $userId = 0): array
    {
        $out = [];
        foreach ($this->where('user_id', $userId)->findAll() as $r) {
            $out[$r['setting_key']] = $r['setting_value'];
        }
        return $out;
    }

    /**
     * Insert or update a single value (upsert on the user_id + key unique key).
     */
    public function put(string $key, $value, int $userId = 0): void
    {
        $value = is_array($value) ? json_encode($value) : (string) $value;
        $row   = $this->where('user_id', $userId)->where('setting_key', $key)->first();
        if ($row) {
            $this->update($row['id'], ['setting_value' => $value]);
        } else {
            $this->insert(['user_id' => $userId, 'setting_key' => $key, 'setting_value' => $value]);
        }
    }

    /**
     * Bulk upsert of key => value pairs for a scope.
     *
     * @param array<string, mixed> $pairs
     */
    public function putMany(array $pairs, int $userId = 0): void
    {
        foreach ($pairs as $k => $v) {
            $this->put((string) $k, $v, $userId);
        }
    }
}
