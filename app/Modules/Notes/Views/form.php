<?php /** Add/Edit note form with debounced autosave. Rendered inside layout.php. */ ?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="bi bi-sticky me-1"></i> <?= esc($title) ?></h3>
                <span id="autosaveStatus" class="small text-muted"></span>
            </div>
            <form id="noteForm" action="<?= site_url('notes/save') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="noteId" value="<?= esc($row['id'] ?? '') ?>">
                <div class="card-body">
                    <?php if (! empty($errors)): ?>
                        <div class="alert alert-danger"><?= esc(is_array($errors) ? implode(' ', $errors) : $errors) ?></div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="noteTitle" class="form-control" required
                               value="<?= esc($row['title'] ?? old('title')) ?>" placeholder="Note title">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="content" id="noteContent" class="form-control" rows="10"
                                  placeholder="Write your note..."><?= esc($row['content'] ?? old('content')) ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">— None —</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= (int) ($row['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Manage categories from the Notes list.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tags <span class="text-muted small">(comma separated)</span></label>
                            <input type="text" name="tags" class="form-control" value="<?= esc($row['tags'] ?? '') ?>" placeholder="work, urgent, ideas">
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
                            <input type="text" name="attach_ref" class="form-control" value="<?= esc($row['attach_ref'] ?? '') ?>" placeholder="e.g. INV-1024">
                        </div>
                    </div>

                    <div class="form-check mt-3">
                        <input type="checkbox" class="form-check-input" id="is_important" name="is_important" value="1" <?= ! empty($row['is_important']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_important"><i class="bi bi-star-fill text-warning"></i> Mark as important</label>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= site_url('notes') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i> Save Note</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-clock-history me-1"></i> Details</h3></div>
            <div class="card-body small">
                <?php if (! empty($row)): ?>
                    <p class="mb-1"><span class="text-muted">Created:</span> <?= esc(date('d M Y, H:i', strtotime($row['created_at']))) ?></p>
                    <p class="mb-0"><span class="text-muted">Last updated:</span> <?= esc(date('d M Y, H:i', strtotime($row['updated_at'] ?: $row['created_at']))) ?></p>
                <?php else: ?>
                    <p class="text-muted mb-0">Autosave keeps your draft safe as you type.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (! empty($history)): ?>
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-journals me-1"></i> Edit History</h3></div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($history as $h): ?>
                        <li class="list-group-item small">
                            <div class="fw-semibold text-truncate"><?= esc($h['title'] ?: '(untitled)') ?></div>
                            <div class="text-muted"><?= esc(date('d M Y, H:i', strtotime($h['created_at']))) ?></div>
                            <?php if (! empty($h['content'])): ?>
                                <div class="text-body-secondary"><?= esc(character_limiter(strip_tags($h['content']), 80)) ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var meta   = document.querySelector('meta[name="csrf-token"]');
    var csrf   = { name: meta.getAttribute('data-name'), hash: meta.getAttribute('content') };
    var form   = document.getElementById('noteForm');
    var idEl   = document.getElementById('noteId');
    var status = document.getElementById('autosaveStatus');
    var timer  = null;
    var url    = '<?= site_url('notes/autosave') ?>';

    function collect() {
        var fd = new FormData(form);
        fd.set(csrf.name, csrf.hash); // always send the freshest token
        return fd;
    }

    function autosave() {
        var title = document.getElementById('noteTitle').value.trim();
        if (!title && !document.getElementById('noteContent').value.trim()) { return; }
        status.textContent = 'Saving…';
        fetch(url, { method: 'POST', body: collect(), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.csrf) { csrf.hash = res.csrf; meta.setAttribute('content', res.csrf); }
                if (res.status === 'success') {
                    if (res.id && !idEl.value) { idEl.value = res.id; }
                    status.innerHTML = '<i class="bi bi-check-circle text-success"></i> Saved ' + res.savedAt;
                } else {
                    status.textContent = '';
                }
            })
            .catch(function () { status.textContent = ''; });
    }

    function schedule() {
        clearTimeout(timer);
        status.textContent = 'Editing…';
        timer = setTimeout(autosave, 1200); // debounce while typing
    }

    ['noteTitle', 'noteContent'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', schedule);
    });
})();
</script>
