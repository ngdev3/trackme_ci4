<?php

namespace Modules\Settings\Controllers;

use App\Controllers\BaseController;
use App\Models\SettingModel;

/**
 * Minimal Settings surface. Appearance values are saved per user; the optional
 * application name remains a global Super Admin setting.
 */
class SettingsController extends BaseController
{
    protected string $vns = 'Modules\Settings\Views\\';

    private const APPEARANCE_KEYS = [
        'theme_mode',
        'font_color',
        'background_color',
        'primary_color',
        'secondary_color',
        'sidebar_color',
        'header_color',
    ];

    private const APPEARANCE_DEFAULTS = [
        'theme_mode'       => 'system',
        'font_color'       => '#1f2a3d',
        'background_color' => '#eef2f8',
        'primary_color'    => '#0d6efd',
        'secondary_color'  => '#6610f2',
        'sidebar_color'    => '#0e1626',
        'header_color'     => '#ffffff',
    ];

    public function index()
    {
        $settings  = (new SettingModel())->allFor(0);
        $effective = settings()->all();

        return $this->render('index', [
            'title'      => 'Settings',
            'breadcrumb' => [['label' => 'Settings']],
            'settings'   => $settings,
            'appearance' => array_merge(self::APPEARANCE_DEFAULTS, array_intersect_key($effective, self::APPEARANCE_DEFAULTS)),
            'appearanceDefaults' => self::APPEARANCE_DEFAULTS,
            'appearancePresets'  => $this->appearancePresets(),
            'moduleCode' => 'settings',
            'baseRoute'  => 'settings',
        ]);
    }

    public function save()
    {
        if (session()->get('is_superadmin')) {
            $name = trim((string) $this->request->getPost('app_name'));
            if ($name !== '') {
                (new SettingModel())->put('app_name', mb_substr($name, 0, 60), 0);
                settings()->flush();
                activity_log('Settings', 'Edit', 'Updated application name');
                return redirect()->to(site_url('settings'))->with('success', 'Settings saved.');
            }
        }

        return redirect()->to(site_url('settings'))->with('info', 'No changes to save.');
    }

    public function saveAppearance()
    {
        $userId = (int) user_id();
        if ($userId <= 0) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Please sign in again.']);
        }

        $pairs = $this->collectAppearance();
        (new SettingModel())->putMany($pairs, $userId);
        settings()->flush();

        activity_log('Settings', 'Edit', 'Updated personal appearance settings');
        return $this->response->setJSON([
            'status'     => 'success',
            'message'    => 'Appearance saved.',
            'appearance' => array_merge(self::APPEARANCE_DEFAULTS, $pairs),
            'csrf'       => ['name' => csrf_token(), 'token' => csrf_hash()],
        ]);
    }

    public function resetAppearance()
    {
        $userId = (int) user_id();
        if ($userId <= 0) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Please sign in again.']);
        }

        (new SettingModel())->putMany(self::APPEARANCE_DEFAULTS, $userId);
        settings()->flush();

        activity_log('Settings', 'Edit', 'Reset personal appearance settings');
        return $this->response->setJSON([
            'status'     => 'success',
            'message'    => 'Appearance reset to default.',
            'appearance' => self::APPEARANCE_DEFAULTS,
            'csrf'       => ['name' => csrf_token(), 'token' => csrf_hash()],
        ]);
    }

    private function collectAppearance(): array
    {
        $pairs = [];
        $mode = (string) $this->request->getPost('theme_mode');
        $pairs['theme_mode'] = in_array($mode, ['light', 'dark', 'system'], true) ? $mode : self::APPEARANCE_DEFAULTS['theme_mode'];

        foreach (self::APPEARANCE_KEYS as $key) {
            if ($key === 'theme_mode') {
                continue;
            }
            $value = (string) $this->request->getPost($key);
            $pairs[$key] = preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : self::APPEARANCE_DEFAULTS[$key];
        }

        return $pairs;
    }

    private function appearancePresets(): array
    {
        return [
            'classic' => [
                'name' => 'Classic ERP',
                'theme_mode' => 'system',
                'font_color' => '#1f2a3d',
                'background_color' => '#eef2f8',
                'primary_color' => '#0d6efd',
                'secondary_color' => '#6610f2',
                'sidebar_color' => '#0e1626',
                'header_color' => '#ffffff',
            ],
            'emerald' => [
                'name' => 'Emerald Desk',
                'theme_mode' => 'light',
                'font_color' => '#17312b',
                'background_color' => '#edf7f2',
                'primary_color' => '#198754',
                'secondary_color' => '#20c997',
                'sidebar_color' => '#08352b',
                'header_color' => '#f8fffb',
            ],
            'midnight' => [
                'name' => 'Midnight Ops',
                'theme_mode' => 'dark',
                'font_color' => '#e7ecf5',
                'background_color' => '#0b111c',
                'primary_color' => '#4f8cff',
                'secondary_color' => '#8b5cf6',
                'sidebar_color' => '#070b13',
                'header_color' => '#121a28',
            ],
            'graphite' => [
                'name' => 'Graphite Focus',
                'theme_mode' => 'light',
                'font_color' => '#202733',
                'background_color' => '#f3f5f8',
                'primary_color' => '#526f8b',
                'secondary_color' => '#78909c',
                'sidebar_color' => '#263443',
                'header_color' => '#ffffff',
            ],
            'sunrise' => [
                'name' => 'Sunrise Ledger',
                'theme_mode' => 'light',
                'font_color' => '#32261f',
                'background_color' => '#fff6ed',
                'primary_color' => '#e85d24',
                'secondary_color' => '#f59f00',
                'sidebar_color' => '#3d2518',
                'header_color' => '#fffaf4',
            ],
            'royal' => [
                'name' => 'Royal Indigo',
                'theme_mode' => 'dark',
                'font_color' => '#f0eefc',
                'background_color' => '#111027',
                'primary_color' => '#7c3aed',
                'secondary_color' => '#06b6d4',
                'sidebar_color' => '#15112f',
                'header_color' => '#1d1938',
            ],
        ];
    }
}
