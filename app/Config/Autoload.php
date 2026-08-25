<?php

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

/**
 * -------------------------------------------------------------------
 * AUTOLOADER CONFIGURATION
 * -------------------------------------------------------------------
 *
 * This file defines the namespaces and class maps so the Autoloader
 * can find the files as needed.
 *
 * NOTE: If you use an identical key in $psr4 or $classmap, then
 *       the values in this file will overwrite the framework's values.
 *
 * NOTE: This class is required prior to Autoloader instantiation,
 *       and does not extend BaseConfig.
 */
class Autoload extends AutoloadConfig
{
    /**
     * -------------------------------------------------------------------
     * Namespaces
     * -------------------------------------------------------------------
     * This maps the locations of any namespaces in your application to
     * their location on the file system. These are used by the autoloader
     * to locate files the first time they have been instantiated.
     *
     * The 'Config' (APPPATH . 'Config') and 'CodeIgniter' (SYSTEMPATH) are
     * already mapped for you.
     *
     * You may change the name of the 'App' namespace if you wish,
     * but this should be done prior to creating any namespaced classes,
     * else you will need to modify all of those classes for this to work.
     *
     * @var array<string, list<string>|string>
     */
    public $psr4 = [
        APP_NAMESPACE => APPPATH,

        // HMVC modules — each is its own namespace so CI4 auto-discovers
        // its Config/Routes.php, Controllers, Models and Views.
        'Modules'              => APPPATH . 'Modules',
        'Modules\Auth'         => APPPATH . 'Modules/Auth',
        'Modules\Dashboard'    => APPPATH . 'Modules/Dashboard',
        'Modules\Users'        => APPPATH . 'Modules/Users',
        'Modules\UserTypes'    => APPPATH . 'Modules/UserTypes',
        'Modules\Roles'        => APPPATH . 'Modules/Roles',
        'Modules\ModuleMaster' => APPPATH . 'Modules/ModuleMaster',
        'Modules\Permissions'  => APPPATH . 'Modules/Permissions',
        'Modules\Logs'         => APPPATH . 'Modules/Logs',
        'Modules\Notifications'=> APPPATH . 'Modules/Notifications',
        'Modules\PushNotifications'=> APPPATH . 'Modules/PushNotifications',
        'Modules\Profile'      => APPPATH . 'Modules/Profile',
        'Modules\Api'          => APPPATH . 'Modules/Api',
        'Modules\Settings'     => APPPATH . 'Modules/Settings',
        'Modules\Calculator'   => APPPATH . 'Modules/Calculator',
        'Modules\Notes'        => APPPATH . 'Modules/Notes',
        'Modules\Reminders'    => APPPATH . 'Modules/Reminders',
        'Modules\Company'      => APPPATH . 'Modules/Company',
        'Modules\FirmUsers'    => APPPATH . 'Modules/FirmUsers',
        'Modules\SuperAdmin'   => APPPATH . 'Modules/SuperAdmin',
        'Modules\Accounting'   => APPPATH . 'Modules/Accounting',
        'Modules\Rokad'        => APPPATH . 'Modules/Rokad',
        'Modules\Transactions' => APPPATH . 'Modules/Transactions',
        'Modules\Passwords'    => APPPATH . 'Modules/Passwords',
        'Modules\Help'         => APPPATH . 'Modules/Help',
        'Modules\UpiQr'        => APPPATH . 'Modules/UpiQr',
        'Modules\Inventory'    => APPPATH . 'Modules/Inventory',
        'Modules\ApiMonitor'   => APPPATH . 'Modules/ApiMonitor',
    ];

    /**
     * -------------------------------------------------------------------
     * Class Map
     * -------------------------------------------------------------------
     * The class map provides a map of class names and their exact
     * location on the drive. Classes loaded in this manner will have
     * slightly faster performance because they will not have to be
     * searched for within one or more directories as they would if they
     * were being autoloaded through a namespace.
     *
     * Prototype:
     *   $classmap = [
     *       'MyClass'   => '/path/to/class/file.php'
     *   ];
     *
     * @var array<string, string>
     */
    public $classmap = [];

    /**
     * -------------------------------------------------------------------
     * Files
     * -------------------------------------------------------------------
     * The files array provides a list of paths to __non-class__ files
     * that will be autoloaded. This can be useful for bootstrap operations
     * or for loading functions.
     *
     * Prototype:
     *   $files = [
     *       '/path/to/my/file.php',
     *   ];
     *
     * @var list<string>
     */
    public $files = [];

    /**
     * -------------------------------------------------------------------
     * Helpers
     * -------------------------------------------------------------------
     * Prototype:
     *   $helpers = [
     *       'form',
     *   ];
     *
     * @var list<string>
     */
    public $helpers = ['auth', 'menu', 'ui', 'settings', 'hashid', 'format', 'subscription', 'brand', 'dashcache'];
}
