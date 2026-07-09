<?php

namespace Modules\Calculator\Controllers;

use App\Controllers\BaseController;
use App\Models\CalculatorHistoryModel;

/**
 * In-app calculator with per-user saved history. Each saved calculation may be
 * given a title; when the title is blank an automatic "Calculation #<id>"
 * label is assigned from the row id.
 */
class CalculatorController extends BaseController
{
    protected string $vns = 'Modules\Calculator\Views\\';
    protected string $moduleCode = 'calculator';

    protected CalculatorHistoryModel $history;

    public function __construct()
    {
        $this->history = new CalculatorHistoryModel();
    }

    public function index()
    {
        return $this->render('index', [
            'title'      => 'Calculator',
            'breadcrumb' => [['label' => 'Calculator']],
            'rows'       => $this->history->forUser((int) user_id()),
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => 'calculator',
        ]);
    }

    /**
     * Persist a calculation. Responds with JSON so the page can update the
     * history list without a reload.
     */
    public function save()
    {
        $expression = trim((string) $this->request->getPost('expression'));
        $result     = trim((string) $this->request->getPost('result'));
        $title      = trim((string) $this->request->getPost('title'));

        if ($expression === '' || $result === '') {
            return $this->json(422, ['status' => 'error', 'message' => 'Nothing to save yet.']);
        }
        if (mb_strlen($expression) > 255 || mb_strlen($result) > 100 || mb_strlen($title) > 150) {
            return $this->json(422, ['status' => 'error', 'message' => 'The calculation is too long to save.']);
        }

        $id = $this->history->insert([
            'user_id'    => (int) user_id(),
            'title'      => $title !== '' ? $title : null,
            'expression' => $expression,
            'result'     => $result,
        ], true);

        if (! $id) {
            return $this->json(422, ['status' => 'error', 'message' => implode(' ', $this->history->errors() ?: ['Could not save.'])]);
        }

        // Auto-assign a title from the id when none was provided.
        if ($title === '') {
            $title = 'Calculation #' . $id;
            $this->history->update($id, ['title' => $title]);
        }

        activity_log('Calculator', 'Add', "Saved calculation #{$id}");

        return $this->json(200, [
            'status' => 'success',
            'entry'  => [
                'id'         => (int) $id,
                'title'      => $title,
                'expression' => $expression,
                'result'     => $result,
                'time'       => date('d M Y, H:i'),
            ],
        ]);
    }

    /**
     * JSON response that always carries a fresh CSRF hash, so the AJAX page can
     * keep saving/deleting after the token rotates (regenerate = true).
     */
    private function json(int $status, array $body)
    {
        $body['csrf'] = csrf_hash();
        return $this->response->setStatusCode($status)->setJSON($body);
    }

    /**
     * Delete one of the current user's saved calculations.
     */
    public function delete($id = null)
    {
        $id  = (int) $id;
        $row = $this->history->find($id);
        if (! $row || (int) $row['user_id'] !== (int) user_id()) {
            return $this->json(404, ['status' => 'error', 'message' => 'Entry not found.']);
        }
        $this->history->delete($id);
        activity_log('Calculator', 'Delete', "Deleted calculation #{$id}");

        return $this->json(200, ['status' => 'success']);
    }
}
