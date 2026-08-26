<?php

namespace Modules\Inventory\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\StockMovementModel;

/**
 * Stock / Inventory (firm portal). Product Master CRUD + Stock In/Out + a stock
 * report — the web counterpart of the mobile inventory suite, sharing the
 * products / stock_movements tables. Company-scoped via company_id().
 */
class InventoryController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'company'];

    private function cid(): int
    {
        return (int) company_id();
    }

    /** Inventory dashboard — headline figures + quick links + recent items. */
    public function index()
    {
        $cid = $this->cid();
        $model = new ProductModel();
        $summary  = $cid > 0 ? $model->summary($cid) : ['count' => 0, 'stock_value' => 0, 'sale_value' => 0, 'low' => 0, 'out' => 0];
        $products = $cid > 0 ? $model->scoped($cid)->orderBy('id', 'DESC')->findAll(8) : [];

        return $this->render('dashboard', [
            'title'      => 'Stock / Inventory',
            'breadcrumb' => [['label' => 'Home', 'url' => site_url('dashboard')], ['label' => 'Stock / Inventory']],
            'css'        => ['assets/css/inventory.css'],
            'tab'        => 'dashboard',
            'summary'    => $summary,
            'recent'     => $products,
        ]);
    }

    /** Product Master — full catalogue list (+ add/edit modal). */
    public function products()
    {
        $cid  = $this->cid();
        $rows = $cid > 0 ? (new ProductModel())->scoped($cid)->orderBy('name', 'ASC')->findAll() : [];

        return $this->render('products', [
            'title'      => 'Product Master',
            'breadcrumb' => [
                ['label' => 'Home', 'url' => site_url('dashboard')],
                ['label' => 'Stock / Inventory', 'url' => site_url('inventory')],
                ['label' => 'Product Master'],
            ],
            'css'  => ['assets/css/inventory.css'],
            'tab'  => 'products',
            'rows' => $rows,
        ]);
    }

    /** Create or update a product. */
    public function saveProduct()
    {
        $cid = $this->cid();
        if ($cid <= 0) {
            return redirect()->to(site_url('inventory/products'))->with('error', 'Select a company first.');
        }
        $req  = $this->request;
        $id   = (int) $req->getPost('id');
        $name = trim((string) $req->getPost('name'));
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Product name is required.');
        }

        $num = static fn ($v): float => round((float) $v, 3);
        $data = [
            'company_id'     => $cid,
            'name'           => mb_substr($name, 0, 191),
            'sku'            => mb_substr(trim((string) $req->getPost('sku')), 0, 60) ?: null,
            'category'       => mb_substr(trim((string) $req->getPost('category')), 0, 80) ?: null,
            'unit'           => mb_substr(trim((string) $req->getPost('unit')), 0, 20) ?: null,
            'hsn'            => mb_substr(trim((string) $req->getPost('hsn')), 0, 20) ?: null,
            'sale_price'     => $num($req->getPost('sale_price')),
            'purchase_price' => $num($req->getPost('purchase_price')),
            'low_stock'      => $num($req->getPost('low_stock')),
            'tax_rate'       => round((float) $req->getPost('tax_rate'), 2),
            'description'    => mb_substr(trim((string) $req->getPost('description')), 0, 500) ?: null,
            'status'         => 1,
        ];

        $model = new ProductModel();
        if ($id > 0 && $model->scoped($cid)->find($id)) {
            $model->update($id, $data);
            $msg = 'Product updated.';
        } else {
            // New product: seed opening_stock into current_stock.
            $open = $num($req->getPost('opening_stock'));
            $data['opening_stock'] = $open;
            $data['current_stock'] = $open;
            $data['created_by']    = (int) user_id();
            $model->insert($data);
            $msg = 'Product added.';
        }

        return redirect()->to(site_url('inventory/products'))->with('success', $msg);
    }

    /** Soft-delete a product. */
    public function deleteProduct($id = null)
    {
        $cid   = $this->cid();
        $model = new ProductModel();
        if ($model->scoped($cid)->find((int) $id)) {
            $model->delete((int) $id);
            return redirect()->to(site_url('inventory/products'))->with('success', 'Product removed.');
        }
        return redirect()->to(site_url('inventory/products'))->with('error', 'Product not found.');
    }

    /** Stock In / Out — form + recent movement ledger. */
    public function stock()
    {
        $cid = $this->cid();
        $products  = $cid > 0 ? (new ProductModel())->scoped($cid)->orderBy('name', 'ASC')->findAll() : [];
        $movements = $cid > 0 ? (new StockMovementModel())->recentFor($cid, null, 60) : [];

        return $this->render('stock', [
            'title'      => 'Stock In / Out',
            'breadcrumb' => [
                ['label' => 'Home', 'url' => site_url('dashboard')],
                ['label' => 'Stock / Inventory', 'url' => site_url('inventory')],
                ['label' => 'Stock In / Out'],
            ],
            'css'       => ['assets/css/inventory.css'],
            'tab'       => 'stock',
            'products'  => $products,
            'movements' => $movements,
        ]);
    }

    /** Record a stock movement + adjust current_stock. */
    public function moveStock()
    {
        $cid = $this->cid();
        if ($cid <= 0) {
            return redirect()->to(site_url('inventory/stock'))->with('error', 'Select a company first.');
        }
        $req  = $this->request;
        $pid  = (int) $req->getPost('product_id');
        $type = $req->getPost('type') === 'out' ? 'out' : 'in';
        $qty  = round((float) $req->getPost('qty'), 3);
        $rate = round((float) $req->getPost('rate'), 2);
        $note = trim((string) $req->getPost('note')) ?: null;

        if ($qty <= 0) {
            return redirect()->back()->withInput()->with('error', 'Enter a quantity greater than zero.');
        }
        $products = new ProductModel();
        $product  = $products->scoped($cid)->find($pid);
        if (! $product) {
            return redirect()->back()->withInput()->with('error', 'Product not found.');
        }
        $current  = (float) $product['current_stock'];
        $newStock = $type === 'in' ? $current + $qty : $current - $qty;
        if ($newStock < 0) {
            return redirect()->back()->withInput()->with('error', 'Not enough stock. Available: ' . rtrim(rtrim((string) $current, '0'), '.') . '.');
        }

        (new StockMovementModel())->insert([
            'company_id' => $cid,
            'product_id' => $pid,
            'type'       => $type,
            'qty'        => $qty,
            'rate'       => $rate,
            'note'       => $note,
            'created_by' => (int) user_id(),
        ]);
        $products->update($pid, ['current_stock' => round($newStock, 3)]);

        return redirect()->to(site_url('inventory/stock'))
            ->with('success', ($type === 'in' ? 'Stock added to ' : 'Stock removed from ') . $product['name'] . '.');
    }

    /** Stock report — summary table + movement statement (printable). */
    public function reports()
    {
        $cid = $this->cid();
        $products  = $cid > 0 ? (new ProductModel())->scoped($cid)->orderBy('name', 'ASC')->findAll() : [];
        $movements = $cid > 0 ? (new StockMovementModel())->recentFor($cid, null, 200) : [];

        return $this->render('reports', [
            'title'      => 'Inventory Report',
            'breadcrumb' => [
                ['label' => 'Home', 'url' => site_url('dashboard')],
                ['label' => 'Stock / Inventory', 'url' => site_url('inventory')],
                ['label' => 'Report'],
            ],
            'css'       => ['assets/css/inventory.css'],
            'tab'       => 'reports',
            'products'  => $products,
            'movements' => $movements,
            'company'   => current_company(),
        ]);
    }
}
