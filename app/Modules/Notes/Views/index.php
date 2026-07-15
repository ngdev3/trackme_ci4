<?php /** Notes landing = mini dashboard + notes list. Rendered inside layout.php. */ ?>

<!-- ===== Mini dashboard ===== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg">
        <div class="card h-100 border-start border-4 border-danger">
            <div class="card-body py-2">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted small">Overdue</span>
                    <i class="bi bi-exclamation-octagon text-danger"></i>
                </div>
                <div class="fs-4 fw-bold"><?= count($overdue) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="card h-100 border-start border-4 border-warning">
            <div class="card-body py-2">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted small">Today</span>
                    <i class="bi bi-alarm text-warning"></i>
                </div>
                <div class="fs-4 fw-bold"><?= count($dueToday) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="card h-100 border-start border-4 border-info">
            <div class="card-body py-2">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted small">Upcoming</span>
                    <i class="bi bi-calendar-event text-info"></i>
                </div>
                <div class="fs-4 fw-bold"><?= count($upcoming) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="card h-100 border-start border-4 border-primary">
            <div class="card-body py-2">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted small">Important notes</span>
                    <i class="bi bi-star-fill text-primary"></i>
                </div>
                <div class="fs-4 fw-bold"><?= count($importantNotes) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-body py-2">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small">Reminders</span>
                    <a href="<?= site_url('reminders') ?>" class="small">View all →</a>
                </div>
                <?php
                $peek = array_slice(array_merge($overdue, $dueToday, $upcoming), 0, 3);
                ?>
                <?php if (empty($peek)): ?>
                    <div class="text-secondary small">No pending reminders.</div>
                <?php else: foreach ($peek as $rm): ?>
                    <div class="d-flex justify-content-between align-items-center small py-1 border-bottom">
                        <span class="text-truncate me-2"><?= esc($rm['title']) ?></span>
                        <span class="text-nowrap text-muted"><?= esc(date('d M, H:i', strtotime($rm['snoozed_until'] ?: $rm['remind_at']))) ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== Notes toolbar ===== -->
<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2 align-items-center" method="get">
            <input type="search" name="q" value="<?= esc($search) ?>" class="form-control form-control-sm" style="max-width:220px" placeholder="Search title, content, tags...">
            <select name="category" class="form-select form-select-sm" style="max-width:170px" onchange="this.form.submit()">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $category === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="filter" class="form-select form-select-sm" style="max-width:150px" onchange="this.form.submit()">
                <option value="">All notes</option>
                <option value="pinned" <?= $filter === 'pinned' ? 'selected' : '' ?>>Pinned</option>
                <option value="important" <?= $filter === 'important' ? 'selected' : '' ?>>Important</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#categoriesModal">
                <i class="bi bi-tags"></i> Categories
            </button>
            <a href="<?= site_url('notes/recycle-bin') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-trash"></i> Recycle Bin <span class="badge text-bg-secondary"><?= (int) $binCount ?></span>
            </a>
            <?php if (can($moduleCode, 'add')): ?>
                <a href="<?= site_url('notes/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Note</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== Notes grid ===== -->
<?php if (empty($rows)): ?>
    <div class="card"><div class="card-body text-center text-secondary py-5">
        <i class="bi bi-sticky fs-1 d-block mb-2"></i> No notes yet. Click <strong>Add Note</strong> to create one.
    </div></div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($rows as $n): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 <?= $n['is_pinned'] ? 'border-warning' : '' ?>">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h5 class="card-title mb-0 text-truncate" title="<?= esc($n['title'], 'attr') ?>">
                                <?php if ($n['is_important']): ?><i class="bi bi-star-fill text-warning me-1"></i><?php endif; ?>
                                <?= esc($n['title']) ?>
                            </h5>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <a href="<?= site_url('notes/toggle-pin/' . $n['id']) ?>" class="text-decoration-none" title="Pin">
                                    <i class="bi <?= $n['is_pinned'] ? 'bi-pin-angle-fill text-warning' : 'bi-pin-angle text-muted' ?>"></i>
                                </a>
                            </div>
                        </div>
                        <?php if (! empty($n['category_name'])): ?>
                            <div class="mb-1"><span class="badge" style="background:<?= esc($n['category_color'] ?: '#6c757d', 'attr') ?>"><?= esc($n['category_name']) ?></span></div>
                        <?php endif; ?>
                        <p class="card-text small text-body-secondary flex-grow-1">
                            <?= esc(character_limiter(strip_tags((string) $n['content']), 140)) ?>
                        </p>
                        <?php if (! empty($n['tags'])): ?>
                            <div class="mb-2">
                                <?php foreach (array_filter(array_map('trim', explode(',', $n['tags']))) as $tag): ?>
                                    <span class="badge text-bg-light border">#<?= esc($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (! empty($n['attach_module'])): ?>
                            <div class="small text-muted mb-2"><i class="bi bi-paperclip"></i> <?= esc(ucfirst($n['attach_module'])) ?><?= $n['attach_ref'] ? ' · ' . esc($n['attach_ref']) : '' ?></div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-auto">
                            <small class="text-muted" title="Updated <?= esc($n['updated_at']) ?>">
                                <i class="bi bi-clock-history"></i> <?= esc(date('d M Y', strtotime($n['updated_at'] ?: $n['created_at']))) ?>
                            </small>
                            <div class="d-flex gap-2">
                                <?php if (can($moduleCode, 'edit')): ?>
                                    <a href="<?= site_url('notes/edit/' . $n['id']) ?>" class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <?php if (can($moduleCode, 'delete')): ?>
                                    <form action="<?= site_url('notes/delete/' . $n['id']) ?>" method="post" data-no-validate data-confirm="This note will be moved to the recycle bin." data-confirm-title="Move to recycle bin?" data-confirm-btn="Yes, move" data-confirm-icon="warning" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (isset($pager)): ?><div class="mt-3 d-flex justify-content-end"><?= $pager->links() ?></div><?php endif; ?>
<?php endif; ?>

<!-- ===== Categories modal ===== -->
<div class="modal fade" id="categoriesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-tags me-1"></i> Manage Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= site_url('notes/category/store') ?>" method="post" class="d-flex gap-2 mb-3">
                    <?= csrf_field() ?>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="New category name" required>
                    <input type="color" name="color" class="form-control form-control-color form-control-sm" value="#6c757d" title="Colour">
                    <button class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> Add</button>
                </form>
                <?php if (empty($categories)): ?>
                    <p class="text-secondary small mb-0">No categories yet.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($categories as $c): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><span class="badge" style="background:<?= esc($c['color'] ?: '#6c757d', 'attr') ?>">&nbsp;</span> <?= esc($c['name']) ?></span>
                                <form action="<?= site_url('notes/category/delete/' . $c['id']) ?>" method="post"
                                      data-no-validate data-confirm="Notes keep their content but lose this label." data-confirm-title="Delete category?" data-confirm-btn="Yes, delete">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
