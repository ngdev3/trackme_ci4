<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\DashboardModel;
use App\Modules\Admin\Models\AppUpdateModel;
use App\Modules\Admin\Models\AppSettingModel;

/**
 * Dashboard — CI4 port of admin/Dashboard::index rendering the exact CI3
 * personalised dashboard (site_dashboard.php): hero+clock, weather, live module
 * tiles, APK widget, and the drag-reorder/show-hide layout (App Settings). Live
 * module tiles + APK stats + saved layout are wired; the heavy sales/purchase,
 * ageing, login and real-time paddy analytics are supplied as safe defaults
 * until their subsystems (accounting, lot/paddy) are ported.
 */
class Dashboard extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function index()
    {
        $uid = (int) (currentuserinfo()->id ?? 0);

        $apkStats = null;
        if (function_exists('erp_current_user_can') && erp_current_user_can('app_update', 'view')) {
            $apk = new AppUpdateModel();
            $apk->ensureTables();
            $apkStats = $apk->dashboardStats();
        }

        return _layout('\App\Modules\Admin\Views\dashboard\site_dashboard', [
            'title'            => 'Dashboard · C R Industries ERP',
            // Live widgets
            'module_tiles'     => (new DashboardModel())->moduleTiles(),
            'apk_stats'        => $apkStats,
            'dashboard_layout' => (new AppSettingModel())->resolveLayout($uid),
            // Analytics (safe defaults until their subsystems are ported)
            'sp_analytics'            => [],
            'ageing'                  => [],
            'user_login_analytics'    => null,
            'tradeparty_position'     => [],
            'status_report'           => [],
            'getStockDetails'         => [],
            'getStockDetailsEinvoice' => [],
            'getStockDetailsForDashbaordunregistered' => [],
            'RealTimeDataCount'       => ['first' => [], 'FinalAmountPaddy' => 0, 'TotalKatti' => 0, 'maxpurchaser' => null, 'billing' => 0],
            'RealTimeLotStatus'       => [],
            'ActiveParcha'            => [],
            'todays_KisanVahi'        => [],
            'total_weight'            => 0,
            'FinalAmountPaddy'        => 0,
            'TotalKatti'              => 0,
            'maxpurchaser'            => null,
            'totalrealtimeCenterSum'  => 0,
            'total_runningcampaigns'  => 0,
        ]);
    }
}
