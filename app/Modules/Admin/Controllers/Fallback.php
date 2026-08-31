<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Fallback — the app's 404 override (Config\Routing::$override404). For an
 * authenticated admin/master path with no ported route yet, it renders a clean
 * "being migrated" screen inside the Metronic shell instead of a raw 404, so
 * every menu link resolves to a page. Any other unmatched path -> standard 404.
 *
 * Runs OUTSIDE the normal filter pipeline, so it checks session + loads the
 * firm context itself.
 */
class Fallback extends BaseController
{
    public function index()
    {
        // Robust path resolution (the override context can vary).
        $path = '';
        try {
            $path = trim((string) $this->request->getPath(), '/');
        } catch (\Throwable $e) {
            $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        }
        // Strip a subfolder/public prefix if present (e.g. trackme_ci4/public/admin/...).
        if (($pos = strpos($path, 'admin/')) !== false) {
            $path = substr($path, $pos);
        } elseif (($pos = strpos($path, 'master/')) !== false) {
            $path = substr($path, $pos);
        }

        $isAdminArea = preg_match('#^(admin|master)(/|$)#', $path) === 1;
        $ctx = service('fyContext');

        if (! $isAdminArea) {
            throw PageNotFoundException::forPageNotFound();
        }
        if (! $ctx->isLoggedIn()) {
            return redirect()->to(site_url('admin/auth') . '?redirect=' . rawurlencode('/' . $path));
        }

        $ctx->loadFirmContext();

        $seg   = explode('/', $path);
        $mod   = $seg[1] ?? '';
        $meth  = $seg[2] ?? '';
        $label = trim(ucwords(str_replace(['_', '-'], ' ', $mod))
            . ($meth && $meth !== 'index' && $meth !== 'listing' ? ' · ' . ucwords(str_replace(['_', '-'], ' ', $meth)) : ''));

        return $this->response->setStatusCode(200)->setBody(_layout('\App\Modules\Admin\Views\_migrating', [
            'title'      => ($label ?: 'Screen') . ' · C R Industries ERP',
            'modLabel'   => $label ?: 'This screen',
            'targetPath' => $path,
        ]));
    }
}
