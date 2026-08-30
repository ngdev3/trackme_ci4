<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * FyContextFilter — replaces MY_Controller step 1 + CI3 validate_admin_login():
 * loads the active aa_template row for the logged-in user's firm into session
 * 'fy', and exposes the firm/user context to every view.
 */
class FyContextFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $ctx = service('fyContext');
        $ctx->loadFirmContext(); // CI3 validate_admin_login() equivalent

        // Available to all views (CI3 $this->datawert['fy'] + currentuserinfo()).
        $renderer = service('renderer');
        $renderer->setVar('fy', $ctx->fyRow());
        $renderer->setVar('currentUser', $ctx->userInfo());
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
