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
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    /**
     * Helpers available to every controller.
     *
     * @var list<string>
     */
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text'];

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);
    }

    /**
     * Render a page through the master layout (the "slice" pattern).
     *
     * The controller only needs the page name — the module (folder) is resolved
     * automatically from the controller's namespace. All forms are accepted:
     *
     *   $this->render('index', $data)             // current module's Views/index
     *   $this->render('users/index', $data)       // Modules\Users\Views\index
     *   $this->render('Modules\Users\Views\index', $data)   // fully qualified
     *
     * @param string               $page  page name (see forms above)
     * @param array<string, mixed> $data  view data (title, breadcrumb, css, js, inline_js, ...)
     */
    protected function render(string $page, array $data = []): string
    {
        $viewPath = $this->resolveViewPath($page);

        // Render the page body first, then hand it to the layout as $content.
        $data['content'] = view($viewPath, $data);
        $data['page']    = $viewPath;

        return view('layout', $data);
    }

    /**
     * Turn a short page name into a full CI4 view path, resolving the module
     * (folder) from the current controller when only a page name is given.
     */
    protected function resolveViewPath(string $page): string
    {
        // Already fully-qualified namespace (contains a backslash).
        if (strpos($page, '\\') !== false) {
            return $page;
        }

        // "folder/page" — map folder to a module namespace (StudlyCase).
        if (strpos($page, '/') !== false) {
            [$folder, $view] = explode('/', $page, 2);
            return 'Modules\\' . $this->studly($folder) . '\\Views\\' . $view;
        }

        // Bare page name — use the current controller's module.
        $ns = (new \ReflectionClass($this))->getNamespaceName(); // e.g. Modules\Users\Controllers
        if (preg_match('/^Modules\\\\([^\\\\]+)\\\\Controllers/', $ns, $m)) {
            return 'Modules\\' . $m[1] . '\\Views\\' . $page;
        }

        // Fallback: plain app/Views path.
        return $page;
    }

    /** Convert "user-types" / "user_types" to "UserTypes". */
    private function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }
}
