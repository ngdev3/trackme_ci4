<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Helpers available to every controller (CI3 autoload.php equivalent).
     * `app` = ported function_helper; `cr_cache` = the caching layer.
     * Add ported helpers here as they land (permission, seo, gstin, …).
     *
     * @var list<string>
     */
    protected $helpers = ['url', 'form', 'text', 'app', 'cr_cache'];

    /** Multi-tenant (firm × FY × product) context — CI3 $this->datawert['fy']. */
    protected \App\Libraries\FyContext $fy;

    /** Session service. */
    protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        $this->session = service('session');
        $this->fy      = service('fyContext');
    }
}
