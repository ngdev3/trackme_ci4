<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-people me-1"></i> Users</h3>
        <form class="d-flex gap-2" method="get">
            <input type="search" name="q" value="<?= esc($search) ?>" class="form-control form-control-sm" placeholder="Search name, email, username...">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
            <?php if (can($moduleCode, 'add')): ?>
                <a href="<?= site_url($baseRoute . '/create') ?>" class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> Add User</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>
                    <th style="width:60px">#</th><th></th><th>Name</th><th>Username</th><th>Email</th>
                    <th>Mobile</th><th>Type</th><th>Roles</th><th>Status</th><th class="col-actions text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="10" class="text-center text-secondary py-4">No users found.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= esc($r['id']) ?></td>
                        <td>
                            <?php if (! empty($r['profile_image'])): ?>
                                <img src="<?= base_url('uploads/users/' . $r['profile_image']) ?>" class="avatar-sm" alt="">
                            <?php else: ?>
                                <span class="badge rounded-circle text-bg-secondary" style="width:36px;height:36px;line-height:26px;"><?= strtoupper(substr($r['name'], 0, 1)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold"><?= esc($r['name']) ?></td>
                        <td><code><?= esc($r['username']) ?></code></td>
                        <td><?= esc($r['email']) ?></td>
                        <td><?= esc($r['mobile']) ?></td>
                        <td><span class="badge text-bg-light border"><?= esc($r['user_type_name'] ?? '—') ?></span></td>
                        <td><small><?= esc($r['role_names'] ?? '—') ?></small></td>
                        <td>
                            <?php if (can($moduleCode, 'edit')): ?>
                                <a href="<?= site_url($baseRoute . '/toggle/' . $r['id']) ?>" class="text-decoration-none"><?= status_badge($r['status']) ?></a>
                            <?php else: ?><?= status_badge($r['status']) ?><?php endif; ?>
                        </td>
                        <td class="text-end"><?= action_buttons($moduleCode, $baseRoute, (int) $r['id']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (isset($pager)): ?><div class="card-footer d-flex justify-content-end"><?= $pager->links() ?></div><?php endif; ?>
</div>

