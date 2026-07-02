<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0"><?= esc($title) ?></h3></div>
            <form action="<?= $mode === 'edit' ? site_url('user-types/update/' . $row['id']) : site_url('user-types/store') ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <?= view('components/form_field', ['name' => 'name', 'label' => 'Name', 'value' => old_value('name', $row), 'required' => true, 'errors' => $errors]) ?>
                    <?= view('components/form_field', ['name' => 'code', 'label' => 'Code', 'value' => old_value('code', $row), 'required' => true, 'errors' => $errors, 'help' => 'Unique machine key, e.g. "manager".']) ?>
                    <?= view('components/form_field', ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'value' => old_value('description', $row), 'errors' => $errors]) ?>
                    <?= view('components/form_field', ['name' => 'status', 'label' => 'Active', 'type' => 'checkbox', 'value' => old_value('status', $row, 1), 'errors' => $errors]) ?>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="<?= site_url('user-types') ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

