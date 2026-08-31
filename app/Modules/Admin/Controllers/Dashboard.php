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
        $dm  = new DashboardModel();

        $apkStats = null;
        if (function_exists('erp_current_user_can') && erp_current_user_can('app_update', 'view')) {
            $apk = new AppUpdateModel();
            $apk->ensureTables();
            $apkStats = $apk->dashboardStats();
        }

        // Base data (always present)
        $data = [
            'title'            => 'Dashboard · C R Industries ERP',
            'module_tiles'     => $dm->moduleTiles(),
            'apk_stats'        => $apkStats,
            'dashboard_layout' => (new AppSettingModel())->resolveLayout($uid),
            'ActiveParcha'            => $dm->realTimeActiveParcha(),
            'total_runningcampaigns'  => 50,
            // Super-Admin-only login analytics (privacy).
            'user_login_analytics'    => (function_exists('erp_is_super_admin') && erp_is_super_admin())
                ? $dm->userLoginAnalytics() : null,
            'tradeparty_position'     => [],
            // Real-time paddy defaults (populated below for allowed users).
            'sp_analytics'            => [],
            'ageing'                  => [],
            'status_report'           => [],
            'getStockDetails'         => [],
            'getStockDetailsEinvoice' => [],
            'getStockDetailsForDashbaordunregistered' => [],
            'RealTimeDataCount'       => ['first' => [], 'FinalAmountPaddy' => 0, 'TotalKatti' => 0, 'maxpurchaser' => null, 'billing' => 0],
            'RealTimeLotStatus'       => [],
            'todays_KisanVahi'        => [],
            'total_weight'            => 0,
            'FinalAmountPaddy'        => 0,
            'TotalKatti'              => 0,
            'maxpurchaser'            => null,
            'totalrealtimeCenterSum'  => 0,
        ];

        // Full paddy/analytics band only for whitelisted users (aa_blacklist_search),
        // mirroring the CI3 BlackList_Search_USER_IDS() gate.
        if (function_exists('BlackList_Search_USER_IDS') && BlackList_Search_USER_IDS($uid)) {
            $rt = $dm->realTimeDataCount();
            $data['RealTimeDataCount'] = $rt;
            $data['total_weight']      = $rt['billing'];
            $data['FinalAmountPaddy']  = $rt['FinalAmountPaddy'];
            $data['TotalKatti']        = $rt['TotalKatti'];
            $data['maxpurchaser']      = $rt['maxpurchaser'];
            $data['RealTimeLotStatus'] = $dm->realTimeLotStatus();
            $data['status_report']     = $dm->lotStatusReport();
            $data['getStockDetails']              = $dm->stockDetailsBos();
            $data['getStockDetailsEinvoice']      = $dm->stockDetailsEinvoice();
            $data['getStockDetailsForDashbaordunregistered'] = $dm->stockDetailsUnregistered();
            $data['sp_analytics']      = $dm->salesPurchaseAnalytics();
            $data['ageing']            = $dm->ageingAnalysis();

            $sum = 0;
            foreach ($rt['first'] as $c) { $sum += (float) ($c->totalQuant ?? 0); }
            $data['totalrealtimeCenterSum'] = $sum;

            $data['todays_KisanVahi'] = $dm->todaysKisanVahi();
        }

        return _layout('\App\Modules\Admin\Views\dashboard\site_dashboard', $data);
    }
}
