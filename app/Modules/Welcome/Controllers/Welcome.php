<?php

namespace App\Modules\Welcome\Controllers;

use App\Controllers\BaseController;

/**
 * Welcome — CI4 port of the vestigial CI3 `welcome` module. The real public
 * landing is `ricemill` (the default route); `welcome` is legacy. Kept so the
 * /welcome URL still responds. Renders a minimal page.
 */
class Welcome extends BaseController
{
    public function index()
    {
        return view('\App\Modules\Welcome\Views\welcome_message');
    }
}
