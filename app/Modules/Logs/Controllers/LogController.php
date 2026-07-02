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
        $search  = trim((string) $this->request->getGet('q'));
        $model   = new LoginLogModel();
        $builder = $model->select('login_logs.*, users.name AS user_name')
            ->join('users', 'users.id = login_logs.user_id', 'left')
            ->orderBy('login_logs.id', 'DESC');

        if ($search !== '') {
            $builder->groupStart()
                ->like('login_logs.username', $search)
                ->orLike('login_logs.ip_address', $search)
                ->orLike('login_logs.status', $search)
                ->groupEnd();
        }

        return $this->render('logins', [
            'title'      => 'Login Logs',
            'breadcrumb' => [['label' => 'Login Logs']],
            'rows'       => $builder->paginate(15),
            'pager'      => $model->pager,
            'search'     => $search,
        ]);
    }
}
