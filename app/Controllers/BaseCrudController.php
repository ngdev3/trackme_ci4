<?php

namespace App\Controllers;

use CodeIgniter\Model;

/**
 * Reusable CRUD controller (Task 12).
 *
 * A concrete module controller sets the config properties and — where it
 * needs custom behaviour — overrides the small hook methods. It provides:
 *   - listing with search + pagination
 *   - create / store / edit / update
 *   - soft delete + status toggle
 *   - validation via the model
 *   - activity logging
 *
 * Views expected (relative to $viewNamespace):
 *   index.php, form.php
 */
abstract class BaseCrudController extends BaseController
{
    /** Model instance (set in child constructor). */
    protected Model $model;

    /** DB-side module code used for permission checks + logging (e.g. "users"). */
    protected string $moduleCode = '';

    /** URL base for this resource (e.g. "users"). */
    protected string $baseRoute = '';

    /** View namespace/prefix (e.g. "Modules\Users\Views\\"). */
    protected string $viewNamespace = '';

    /** Human labels. */
    protected string $titlePlural   = 'Records';
    protected string $titleSingular = 'Record';

    /** Columns searched by the list search box. */
    protected array $searchable = ['name'];

    /** Per-page pagination size. */
    protected int $perPage = 10;

    // ---------------------------------------------------------------
    // Listing
    // ---------------------------------------------------------------
    public function index()
    {
        $search  = trim((string) $this->request->getGet('q'));
        $builder = $this->listQuery();

        if ($search !== '' && $this->searchable !== []) {
            $builder->groupStart();
            foreach ($this->searchable as $i => $col) {
                $i === 0 ? $builder->like($col, $search) : $builder->orLike($col, $search);
            }
            $builder->groupEnd();
        }

        $data = $this->viewData([
            'title'      => $this->titlePlural,
            'breadcrumb' => [['label' => $this->titlePlural]],
            'rows'       => $builder->paginate($this->perPage),
            'pager'      => $this->model->pager,
            'search'     => $search,
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
        ]);

        return $this->render('index', $data);
    }

    // ---------------------------------------------------------------
    // Create / Store
    // ---------------------------------------------------------------
    public function create()
    {
        $data = $this->viewData([
            'title'      => 'Add ' . $this->titleSingular,
            'breadcrumb' => [
                ['label' => $this->titlePlural, 'url' => site_url($this->baseRoute)],
                ['label' => 'Add'],
            ],
            'row'    => null,
            'errors' => session()->getFlashdata('errors') ?? [],
            'mode'   => 'create',
        ]);
        return $this->render('form', $data);
    }

    public function store()
    {
        $payload = $this->prepareData($this->request->getPost(), null);

        if (! $this->model->insert($payload)) {
            return redirect()->back()->withInput()->with('errors', $this->model->errors());
        }

        $id = $this->model->getInsertID();
        $this->afterSave((int) $id, $payload, 'create');
        activity_log($this->titlePlural, 'Add', $this->titleSingular . " #{$id} created");

        return redirect()->to(site_url($this->baseRoute))
            ->with('success', $this->titleSingular . ' created successfully.');
    }

    // ---------------------------------------------------------------
    // Edit / Update
    // ---------------------------------------------------------------
    public function edit($id = null)
    {
        $row = $this->model->find((int) $id);
        if (! $row) {
            return redirect()->to(site_url($this->baseRoute))->with('error', $this->titleSingular . ' not found.');
        }

        $data = $this->viewData([
            'title'      => 'Edit ' . $this->titleSingular,
            'breadcrumb' => [
                ['label' => $this->titlePlural, 'url' => site_url($this->baseRoute)],
                ['label' => 'Edit'],
            ],
            'row'    => $row,
            'errors' => session()->getFlashdata('errors') ?? [],
            'mode'   => 'edit',
        ]);
        return $this->render('form', $data);
    }

    public function update($id = null)
    {
        $id  = (int) $id;
        $row = $this->model->find($id);
        if (! $row) {
            return redirect()->to(site_url($this->baseRoute))->with('error', $this->titleSingular . ' not found.');
        }

        $payload = $this->prepareData($this->request->getPost(), $row);

        if (! $this->model->update($id, $payload)) {
            return redirect()->back()->withInput()->with('errors', $this->model->errors());
        }

        $this->afterSave($id, $payload, 'edit');
        activity_log($this->titlePlural, 'Edit', $this->titleSingular . " #{$id} updated");

        return redirect()->to(site_url($this->baseRoute))
            ->with('success', $this->titleSingular . ' updated successfully.');
    }

    // ---------------------------------------------------------------
    // Delete (soft) + status toggle
    // ---------------------------------------------------------------
    public function delete($id = null)
    {
        $id = (int) $id;
        if (! $this->model->find($id)) {
            return redirect()->to(site_url($this->baseRoute))->with('error', $this->titleSingular . ' not found.');
        }

        $this->model->delete($id); // soft delete
        activity_log($this->titlePlural, 'Delete', $this->titleSingular . " #{$id} deleted");

        return redirect()->to(site_url($this->baseRoute))
            ->with('success', $this->titleSingular . ' deleted successfully.');
    }

    public function toggleStatus($id = null)
    {
        $id  = (int) $id;
        $row = $this->model->find($id);
        if (! $row) {
            return redirect()->to(site_url($this->baseRoute))->with('error', $this->titleSingular . ' not found.');
        }

        $new = (int) $row['status'] === 1 ? 0 : 1;
        $this->model->update($id, ['status' => $new]);
        activity_log($this->titlePlural, 'Edit', $this->titleSingular . " #{$id} status set to " . ($new ? 'active' : 'inactive'));

        return redirect()->back()->with('success', 'Status updated.');
    }

    // ---------------------------------------------------------------
    // Hooks — override in child controllers as needed
    // ---------------------------------------------------------------

    /** The base query builder for the listing (override to add joins). */
    protected function listQuery()
    {
        return $this->model->orderBy($this->model->getTable() . '.id', 'DESC');
    }

    /** Shape/whitelist POST data before insert/update. */
    protected function prepareData(array $post, ?array $existing): array
    {
        return $post;
    }

    /** Called after a successful insert/update (sync relations, uploads, ...). */
    protected function afterSave(int $id, array $data, string $mode): void
    {
    }

    /** Merge in extra data every view of this controller needs. */
    protected function viewData(array $data): array
    {
        return $data;
    }
}
