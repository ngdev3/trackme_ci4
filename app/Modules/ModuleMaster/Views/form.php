<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0"><?= esc($title) ?></h3></div>
            <form action="<?= $mode === 'edit' ? site_url('modules/update/' . $row['id']) : site_url('modules/store') ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6"><?= view('components/form_field', ['name' => 'name', 'label' => 'Module Name', 'value' => old_value('name', $row), 'required' => true, 'errors' => $errors]) ?></div>
                        <div class="col-md-6"><?= view('components/form_field', ['name' => 'code', 'label' => 'Code', 'value' => old_value('code', $row), 'required' => true, 'errors' => $errors, 'help' => 'Unique key used by permissions.']) ?></div>
                        <div class="col-md-6"><?= view('components/form_field', ['name' => 'url', 'label' => 'URL Segment', 'value' => old_value('url', $row), 'errors' => $errors, 'help' => 'Leave blank for a parent menu.']) ?></div>
                        <div class="col-md-6"><?= view('components/form_field', ['name' => 'icon', 'label' => 'Icon Class', 'value' => old_value('icon', $row, 'bi bi-circle'), 'errors' => $errors, 'help' => 'Bootstrap Icon class, e.g. "bi bi-people".']) ?></div>
                        <div class="col-md-6"><?= view('components/form_field', ['name' => 'parent_id', 'label' => 'Parent Module', 'type' => 'select', 'options' => $parentOptions, 'value' => old_value('parent_id', $row), 'errors' => $errors]) ?></div>
                        <div class="col-md-6"><?= view('components/form_field', ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'value' => old_value('sort_order', $row, 0), 'errors' => $errors]) ?></div>
                    </div>
                    <?= view('components/form_field', ['name' => 'is_menu', 'label' => 'Show in sidebar menu', 'type' => 'checkbox', 'value' => old_value('is_menu', $row, 1), 'errors' => $errors]) ?>
                    <?= view('components/form_field', ['name' => 'status', 'label' => 'Active', 'type' => 'checkbox', 'value' => old_value('status', $row, 1), 'errors' => $errors]) ?>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="<?= site_url('modules') ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

