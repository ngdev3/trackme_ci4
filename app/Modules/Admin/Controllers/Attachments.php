<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\AttachmentsModel;

/**
 * Attachments — CI4 port of admin/Attachments. Gallery of cash-book (aa_rokad)
 * entries that carry an image / voice / video, with on-disk storage totals.
 * Own RBAC key 'attachments'; shares the report/attachments view.
 */
class Attachments extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function index()
    {
        $model = new AttachmentsModel();
        return _layout('\App\Modules\Admin\Views\report\attachments', [
            'title'   => 'Attachments Gallery · C R Industries ERP',
            'rows'    => $model->rokadAttachments(500),
            'storage' => $model->rokadAttachmentsStorage(),
        ]);
    }
}
