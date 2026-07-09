<?php

namespace Modules\Settings\Controllers;

use App\Controllers\BaseController;
use App\Libraries\WebPush;
use App\Models\ModuleModel;
use App\Models\SettingModel;

/**
 * Web-app settings: appearance/theme, alert colours, default weather city,
 * dashboard widget toggles, and sidebar menu ordering. All values are global
 * (user_id = 0) and read app-wide through the Settings service.
 */
class SettingsController extends BaseController
{
    protected string $vns = 'Modules\Settings\Views\\';

    /** Whitelisted setting keys the form is allowed to write, with a type hint. */
    private const KEYS = [
        // appearance
        'theme_mode', 'sidebar_theme', 'primary_color', 'accent_color',
        // localization
        'currency_symbol', 'currency_code', 'number_grouping', 'currency_decimals',
        'date_format', 'time_format', 'timezone',
        // weather
        'weather_city', 'weather_units',
        // alert colours
        'toast_success_color', 'toast_error_color', 'toast_warning_color', 'toast_info_color',
        'alert_color', 'prompt_color', 'confirm_color',
        'sweetalert_confirm_color', 'sweetalert_cancel_color',
        // web push
        'web_push_enabled', 'vapid_subject',
    ];

    public function index()
    {
        $model    = new SettingModel();
        $settings = $model->allFor(0);

        // Modules grouped parent-then-child for the reorder panel.
        $modules = (new ModuleModel())
            ->orderBy('COALESCE(parent_id, id)', 'ASC', false)
            ->orderBy('parent_id', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        return $this->render('index', [
            'title'      => 'Web App Settings',
            'breadcrumb' => [['label' => 'Settings']],
            'settings'   => $settings,
            'widgets'    => settings()->getArray('dashboard_widgets', []),
            'modules'    => $modules,
            'moduleCode' => 'settings',
            'baseRoute'  => 'settings',
        ]);
    }

    public function save()
    {
        $model = new SettingModel();
        $pairs = [];
        foreach (self::KEYS as $key) {
            $pairs[$key] = (string) $this->request->getPost($key);
        }
        // Checkbox: web_push_enabled off => empty; normalise to 0/1.
        $pairs['web_push_enabled'] = $this->request->getPost('web_push_enabled') ? '1' : '0';

        // Dashboard widget toggles -> JSON map.
        $known   = ['stats', 'weather', 'recent_logins', 'activity', 'notifications'];
        $checked = (array) $this->request->getPost('widgets');
        $widgets = [];
        foreach ($known as $w) {
            $widgets[$w] = in_array($w, $checked, true);
        }
        $pairs['dashboard_widgets'] = json_encode($widgets);

        // Application name — only a Super Admin may rename the whole app.
        if (session()->get('is_superadmin')) {
            $name = trim((string) $this->request->getPost('app_name'));
            if ($name !== '') {
                $pairs['app_name'] = mb_substr($name, 0, 60);
            }
        }

        $model->putMany($pairs, 0);
        settings()->flush();

        activity_log('Settings', 'Edit', 'Updated web app settings');
        return redirect()->to(site_url('settings'))->with('success', 'Settings saved.');
    }

    /**
     * Persist the sidebar menu order: sort[<moduleId>] = integer position.
     */
    public function saveMenu()
    {
        $orders = (array) $this->request->getPost('sort');
        $modules = new ModuleModel();
        foreach ($orders as $moduleId => $pos) {
            $modules->update((int) $moduleId, ['sort_order' => (int) $pos]);
        }
        activity_log('Settings', 'Edit', 'Reordered the sidebar menu');
        return redirect()->to(site_url('settings') . '#menu')->with('success', 'Menu order updated.');
    }

    /**
     * Generate a fresh VAPID key pair for Web Push and store it.
     */
    public function generateVapid()
    {
        try {
            [$public, $private] = WebPush::generateVapidKeys();
        } catch (\Throwable $e) {
            return redirect()->to(site_url('settings') . '#push')->with('error', 'Could not generate keys: ' . $e->getMessage());
        }
        $model = new SettingModel();
        $model->put('vapid_public_key', $public, 0);
        $model->put('vapid_private_key', $private, 0);
        settings()->flush();

        activity_log('Settings', 'Edit', 'Generated new VAPID keys');
        return redirect()->to(site_url('settings') . '#push')->with('success', 'New VAPID keys generated.');
    }
}
