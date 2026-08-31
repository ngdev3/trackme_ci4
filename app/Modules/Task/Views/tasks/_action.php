<?php
/** Per-row action cell for the task listing (CI4 inline button group). */
$enc = ID_encode($row->task_id);
?>
<div class="btn-group">
    <a class="btn btn-xs btn-info" href="<?= base_url('task/task/view/' . $enc) ?>" title="View"><i class="fa fa-eye"></i></a>
    <a class="btn btn-xs btn-primary" href="<?= base_url('task/task/edit/' . $enc) ?>" title="Edit"><i class="fa fa-edit"></i></a>
    <button type="button" class="btn btn-xs btn-danger" onclick="delete_task('<?= esc($enc, 'js') ?>')" title="Delete"><i class="fa fa-trash"></i></button>
</div>
