<?php
/** Add/Edit reminder form. Rendered inside layout.php. */
$dtValue = '';
if (! empty($row['remind_at'])) {
    $dtValue = date('Y-m-d\TH:i', strtotime($row['remind_at']));
}
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-alarm me-1"></i> <?= esc($title) ?></h3></div>
            <form action="<?= site_url('reminders/save') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= esc($row['id'] ?? '') ?>">
                <div class="card-body">
                    <?php if (! empty($errors)): ?>
                        <div class="alert alert-danger"><?= esc(is_array($errors) ? implode(' ', $errors) : $errors) ?></div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required value="<?= esc($row['title'] ?? old('title')) ?>" placeholder="What to remember">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Optional details..."><?= esc($row['description'] ?? old('description')) ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date &amp; Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="remind_at" class="form-control" required value="<?= esc($dtValue) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <?php foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $val => $lbl): ?>
                                    <option value="<?= $val ?>" <?= ($row['priority'] ?? 'medium') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-md-4">
                            <label class="form-label">Repeat</label>
                            <select name="repeat_type" id="repeatType" class="form-select">
                                <?php foreach (['none' => 'Does not repeat', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly', 'custom' => 'Custom (every N days)'] as $val => $lbl): ?>
                                    <option value="<?= $val ?>" <?= ($row['repeat_type'] ?? 'none') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="customIntervalWrap" style="display:none">
                            <label class="form-label">Every N days</label>
                            <input type="number" name="repeat_interval" class="form-control" min="1" value="<?= esc($row['repeat_interval'] ?? 1) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Repeat until <span class="text-muted small">(optional)</span></label>
                            <input type="date" name="repeat_until" class="form-control" value="<?= esc(! empty($row['repeat_until']) ? date('Y-m-d', strtotime($row['repeat_until'])) : '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <label class="form-label">Attach to module</label>
                            <select name="attach_module" class="form-select">
                                <?php foreach (attachable_modules() as $val => $label): ?>
                                    <option value="<?= esc($val, 'attr') ?>" <?= ($row['attach_module'] ?? '') === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference <span class="text-muted small">(id / label)</span></label>
                            <input type="text" name="attach_ref" class="form-control" value="<?= esc($row['attach_ref'] ?? '') ?>" placeholder="e.g. TASK-42">
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= site_url('reminders') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i> Save Reminder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var sel  = document.getElementById('repeatType');
    var wrap = document.getElementById('customIntervalWrap');
    function toggle() { wrap.style.display = sel.value === 'custom' ? '' : 'none'; }
    sel.addEventListener('change', toggle);
    toggle();
})();
</script>
