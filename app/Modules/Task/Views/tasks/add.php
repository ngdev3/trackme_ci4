<?php $r = $result ?? false; $isEdit = ($r && ! empty($r->task_id));
$v = fn($f, $d = '') => esc($r && isset($r->$f) ? $r->$f : $d); ?>
<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:760px;margin:0 auto;">
  <h3 style="font-weight:900;"><?= $isEdit ? 'Edit Task' : 'Add Task'; ?></h3>
  <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>
  <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:20px;">
    <form method="post" action="<?= current_url(); ?>">
      <div class="form-group"><label>Title *</label><input class="form-control" name="title" value="<?= $v('title'); ?>" required></div>
      <div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="4"><?= $v('description'); ?></textarea></div>
      <div class="row">
        <div class="col-sm-4 form-group"><label>Status</label>
          <select class="form-control" name="status">
            <?php foreach (['open' => 'Open', 'in_progress' => 'In Progress', 'done' => 'Done', 'closed' => 'Closed'] as $k => $l): ?>
              <option value="<?= $k; ?>" <?= ($r && $r->status === $k) ? 'selected' : ''; ?>><?= $l; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-4 form-group"><label>Priority</label>
          <select class="form-control" name="priority">
            <?php foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $k => $l): ?>
              <option value="<?= $k; ?>" <?= ($r && $r->priority === $k) ? 'selected' : ''; ?>><?= $l; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-4 form-group"><label>Due Date</label><input class="form-control" type="date" name="due_date" value="<?= $v('due_date'); ?>"></div>
      </div>
      <div class="form-group"><label>Assign To</label>
        <select class="form-control" name="assigned_to">
          <option value="">— Unassigned —</option>
          <?php foreach (($users ?? []) as $u): ?>
            <option value="<?= (int) $u->id; ?>" <?= ($r && (int) $r->assigned_to === (int) $u->id) ? 'selected' : ''; ?>><?= esc(trim($u->first_name . ' ' . $u->last_name)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Update' : 'Create'; ?></button>
      <a class="btn btn-default" href="<?= base_url('task/task'); ?>">Cancel</a>
    </form>
  </div>
</div></div></main>
