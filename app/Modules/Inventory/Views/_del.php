<?php /** Tiny delete form for a master item. */ ?>
<form action="<?= site_url('inventory/masters/delete/' . esc($type, 'url') . '/' . (int) $id) ?>" method="post" class="inv-del"
      onsubmit="return confirm('Remove this item?');">
    <?= csrf_field() ?>
    <button class="btn btn-sm btn-link text-danger p-0" title="Remove"><i class="bi bi-x-circle"></i></button>
</form>
