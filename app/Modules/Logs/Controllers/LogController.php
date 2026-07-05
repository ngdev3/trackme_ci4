<?php

namespace Modules\Logs\Controllers;

use App\Controllers\BaseController;
use App\Models\ActivityLogModel;
use App\Models\LoginLogModel;

class LogController extends BaseController
{
    protected string $vns = 'Modules\Logs\Views\\';

    public function activity()
    {
        $search  = trim((string) $this->request->getGet('q'));
        $builder = (new ActivityLogModel())->withUser()->orderBy('activity_logs.id', 'DESC');

        if ($search !== '') {
            $builder->groupStart()
                ->like('activity_logs.module', $search)
                ->orLike('activity_logs.action', $search)
                ->orLike('activity_logs.description', $search)
                ->orLike('users.name', $search)
                ->groupEnd();
        }

        return $this->render('activity', [
            'title'      => 'Activity Logs',
            'breadcrumb' => [['label' => 'Activity Logs']],
            'rows'       => $builder->paginate(15),
            'pager'      => (new ActivityLogModel())->pager,
            'search'     => $search,
        ]);
    }

    public function logins()
    {
        return $this->renderLoginHistory(false);
    }

    public function myLogins()
    {
        return $this->renderLoginHistory(true);
    }

    public function exportLogins(string $format)
    {
        return $this->exportLoginHistory($format, false);
    }

    public function exportMyLogins(string $format)
    {
        return $this->exportLoginHistory($format, true);
    }

    private function renderLoginHistory(bool $mine): string
    {
        $model = new LoginLogModel();
        $builder = $this->loginHistoryBuilder($model, $mine);

        return $this->render('logins', [
            'title'      => $mine ? 'My Login History' : 'Login Logs',
            'breadcrumb' => [['label' => $mine ? 'My Login History' : 'Login Logs']],
            'rows'       => $builder->paginate(15),
            'pager'      => $model->pager,
            'filters'    => $this->loginFilters(),
            'mine'       => $mine,
        ]);
    }

    private function loginHistoryBuilder(LoginLogModel $model, bool $mine)
    {
        $filters = $this->loginFilters();
        $builder = $model->select('login_logs.*, users.name AS user_name')
            ->join('users', 'users.id = login_logs.user_id', 'left');

        if ($mine) {
            $builder->where('login_logs.user_id', user_id());
        }

        if ($filters['q'] !== '') {
            $builder->groupStart()
                ->like('login_logs.username', $filters['q'])
                ->orLike('users.name', $filters['q'])
                ->orLike('login_logs.ip_address', $filters['q'])
                ->orLike('login_logs.browser', $filters['q'])
                ->orLike('login_logs.operating_system', $filters['q'])
                ->orLike('login_logs.status', $filters['q'])
                ->groupEnd();
        }
        if ($filters['status'] !== '') {
            $builder->where('login_logs.status', $filters['status']);
        }
        if ($filters['device'] !== '') {
            $builder->where('login_logs.device_type', $filters['device']);
        }
        if ($filters['suspicious'] !== '') {
            $builder->where('login_logs.is_suspicious', $filters['suspicious'] === '1' ? 1 : 0);
        }
        if ($filters['from'] !== '') {
            $builder->where('login_logs.created_at >=', $filters['from'] . ' 00:00:00');
        }
        if ($filters['to'] !== '') {
            $builder->where('login_logs.created_at <=', $filters['to'] . ' 23:59:59');
        }

        $sort = in_array($filters['sort'], ['created_at', 'username', 'status', 'ip_address', 'browser', 'session_duration'], true)
            ? $filters['sort']
            : 'created_at';
        $dir = strtolower($filters['dir']) === 'asc' ? 'ASC' : 'DESC';

        return $builder->orderBy('login_logs.' . $sort, $dir)->orderBy('login_logs.id', 'DESC');
    }

    private function loginFilters(): array
    {
        return [
            'q'          => trim((string) $this->request->getGet('q')),
            'status'     => trim((string) $this->request->getGet('status')),
            'device'     => trim((string) $this->request->getGet('device')),
            'suspicious' => trim((string) $this->request->getGet('suspicious')),
            'from'       => trim((string) $this->request->getGet('from')),
            'to'         => trim((string) $this->request->getGet('to')),
            'sort'       => trim((string) ($this->request->getGet('sort') ?: 'created_at')),
            'dir'        => trim((string) ($this->request->getGet('dir') ?: 'desc')),
        ];
    }

    private function exportLoginHistory(string $format, bool $mine)
    {
        $format = strtolower($format);
        $rows = $this->loginHistoryBuilder(new LoginLogModel(), $mine)->findAll(5000);
        $filename = ($mine ? 'my-login-history' : 'login-history') . '-' . date('Ymd-His');

        if ($format === 'csv') {
            return $this->csvResponse($rows, $filename . '.csv');
        }
        if (in_array($format, ['xls', 'excel'], true)) {
            return $this->excelResponse($rows, $filename . '.xls');
        }
        if ($format === 'pdf' || $format === 'print') {
            return $this->printResponse($rows, $filename . '.html');
        }

        return redirect()->back()->with('error', 'Unsupported export format.');
    }

    private function exportColumns(): array
    {
        return [
            'id' => '#',
            'user_name' => 'User',
            'username' => 'Username/Email',
            'status' => 'Status',
            'failure_reason' => 'Failure Reason',
            'ip_address' => 'IP Address',
            'browser' => 'Browser',
            'operating_system' => 'Operating System',
            'device_type' => 'Device',
            'login_at' => 'Login At',
            'logout_at' => 'Logout At',
            'last_activity_at' => 'Last Activity',
            'session_duration' => 'Duration Seconds',
            'is_suspicious' => 'Suspicious',
            'suspicious_reason' => 'Suspicious Reason',
            'user_agent' => 'User Agent',
        ];
    }

    private function csvResponse(array $rows, string $filename)
    {
        $handle = fopen('php://temp', 'r+');
        $columns = $this->exportColumns();
        fputcsv($handle, array_values($columns));
        foreach ($rows as $row) {
            fputcsv($handle, array_map(static fn ($key) => (string) ($row[$key] ?? ''), array_keys($columns)));
        }
        rewind($handle);
        $body = stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($body);
    }

    private function excelResponse(array $rows, string $filename)
    {
        $columns = $this->exportColumns();
        $html = '<table><thead><tr>';
        foreach ($columns as $label) {
            $html .= '<th>' . esc($label) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach (array_keys($columns) as $key) {
                $html .= '<td>' . esc((string) ($row[$key] ?? '')) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.ms-excel')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($html);
    }

    private function printResponse(array $rows, string $filename)
    {
        $columns = $this->exportColumns();
        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Login History</title>';
        $html .= '<style>body{font-family:Arial,sans-serif;font-size:12px;color:#111}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:6px;text-align:left}th{background:#f1f5f9}.danger{background:#fee2e2}@media print{button{display:none}}</style>';
        $html .= '</head><body><button onclick="window.print()">Print / Save as PDF</button><h2>Login History</h2><table><thead><tr>';
        foreach ($columns as $label) {
            $html .= '<th>' . esc($label) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr class="' . (! empty($row['is_suspicious']) ? 'danger' : '') . '">';
            foreach (array_keys($columns) as $key) {
                $html .= '<td>' . esc((string) ($row[$key] ?? '')) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></body></html>';

        return $this->response
            ->setHeader('Content-Type', 'text/html')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($html);
    }
}
