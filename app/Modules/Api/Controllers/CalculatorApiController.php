<?php

namespace Modules\Api\Controllers;

use App\Models\CalculatorHistoryModel;

/**
 * Calculator saved-history API for the mobile app. Per-USER (not company), so a
 * user's saved calculations follow them across firms. Mirrors the web
 * Modules\Calculator\Controllers\CalculatorController (index/save/delete).
 * Gated by the `calculator` feature (Standard plan and up).
 *
 *   GET    calculator            list the caller's saved calculations
 *   POST   calculator            save a calculation {expression, result, title?}
 *   DELETE calculator/(:num)     delete one of the caller's calculations
 */
class CalculatorApiController extends BaseApiController
{
    protected $helpers = ['settings', 'subscription'];

    private const FEATURE = 'calculator';

    /** @return array{user:array}|array{error:\CodeIgniter\HTTP\ResponseInterface} */
    private function guardUser(): array
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return ['error' => $this->failUnauthorized('Invalid or missing token.')];
        }
        if (! $this->apiHasFeature($user, self::FEATURE)) {
            return ['error' => $this->failForbidden('Your plan does not include the Calculator.')];
        }
        return ['user' => $user];
    }

    public function index()
    {
        $g = $this->guardUser();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $rows = (new CalculatorHistoryModel())->forUser((int) $g['user']['id']);
        return $this->respond(['status' => 'ok', 'history' => array_map([$this, 'shape'], $rows)]);
    }

    public function save()
    {
        $g = $this->guardUser();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $expression = trim((string) ($this->input('expression') ?? ''));
        $result     = trim((string) ($this->input('result') ?? ''));
        $title      = trim((string) ($this->input('title') ?? ''));

        if ($expression === '' || $result === '') {
            return $this->failValidationErrors('Nothing to save yet.');
        }
        if (mb_strlen($expression) > 255 || mb_strlen($result) > 100 || mb_strlen($title) > 150) {
            return $this->failValidationErrors('The calculation is too long to save.');
        }

        $model = new CalculatorHistoryModel();
        $id    = $model->insert([
            'user_id'    => (int) $g['user']['id'],
            'title'      => $title !== '' ? $title : null,
            'expression' => $expression,
            'result'     => $result,
        ], true);
        if (! $id) {
            return $this->failValidationErrors($model->errors() ?: 'Could not save.');
        }
        if ($title === '') {
            $title = 'Calculation #' . $id;
            $model->update($id, ['title' => $title]);
        }
        if (function_exists('activity_log')) {
            activity_log('Calculator', 'Add', "Saved calculation #{$id} (mobile)");
        }

        return $this->respondCreated(['status' => 'ok', 'entry' => $this->shape($model->find($id))]);
    }

    public function delete($id = null)
    {
        $g = $this->guardUser();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $model = new CalculatorHistoryModel();
        $row   = $model->find((int) $id);
        if (! $row || (int) $row['user_id'] !== (int) $g['user']['id']) {
            return $this->failNotFound('Calculation not found.');
        }
        $model->delete((int) $id);
        return $this->respond(['status' => 'ok', 'id' => (int) $id, 'message' => 'Deleted.']);
    }

    private function shape(array $r): array
    {
        return [
            'id'         => (int) $r['id'],
            'title'      => $r['title'] ?? ('Calculation #' . $r['id']),
            'expression' => $r['expression'],
            'result'     => $r['result'],
            'created_at' => $r['created_at'] ?? null,
        ];
    }
}
