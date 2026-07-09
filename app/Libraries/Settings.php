<?php

namespace App\Libraries;

use App\Models\SettingModel;
use Config\Services;

/**
 * Request-cached settings reader. Resolves an "effective" value as:
 *   current user's personal override  ->  global value  ->  provided default.
 *
 * Writes go through SettingModel directly (see the Settings module controller);
 * this library is the read/merge layer used across the app + layout.
 */
class Settings
{
    protected SettingModel $model;
    protected ?array $global = null;
    protected ?array $user   = null;

    public function __construct()
    {
        $this->model = new SettingModel();
    }

    protected function loadGlobal(): array
    {
        return $this->global ??= $this->model->allFor(0);
    }

    protected function loadUser(): array
    {
        if ($this->user !== null) {
            return $this->user;
        }
        $uid = (int) (Services::session()->get('user_id') ?? 0);
        return $this->user = $uid > 0 ? $this->model->allFor($uid) : [];
    }

    /**
     * Effective value (personal override wins over global).
     */
    public function get(string $key, $default = null)
    {
        $user = $this->loadUser();
        if (array_key_exists($key, $user) && $user[$key] !== null && $user[$key] !== '') {
            return $user[$key];
        }
        $global = $this->loadGlobal();
        return array_key_exists($key, $global) ? $global[$key] : $default;
    }

    /**
     * Decode a JSON-encoded setting to an array (e.g. widget toggles).
     */
    public function getArray(string $key, array $default = []): array
    {
        $raw = $this->get($key, null);
        if ($raw === null || $raw === '') {
            return $default;
        }
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * All effective settings merged (global first, user overrides on top).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->loadGlobal(), $this->loadUser());
    }

    /**
     * Forget the request cache (after a save within the same request).
     */
    public function flush(): void
    {
        $this->global = null;
        $this->user   = null;
    }
}
