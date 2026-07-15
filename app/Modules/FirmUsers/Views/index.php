<?php /** Firm users list. Rendered inside layout.php. */ ?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-people me-1"></i> Firm Users — <?= esc($firm['name'] ?? '') ?></h3>
        <a href="<?= site_url('firm-users/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-person-plus"></i> Add User</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>
                    <th>Name</th><th>Email</th><th>Mobile</th><th>Role</th><th>Status</th><th>Last Login</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-center text-secondary py-4">No firm users yet. Click <strong>Add User</strong> to create one.</td></tr>
                <?php else: $labels = firm_roles(); foreach ($rows as $r): ?>
                    <tr>
                        <td class="fw-semibold"><?= esc($r['name']) ?></td>
                        <td><?= esc($r['email']) ?></td>
                        <td><?= esc($r['mobile'] ?: '—') ?></td>
                        <td><span class="badge text-bg-info"><?= esc($labels[$r['role']] ?? ucfirst($r['role'])) ?></span></td>
                        <td><?= (int) $r['status'] === 1 && (int) $r['user_status'] === 1 ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?></td>
                        <td><small class="text-muted"><?= $r['last_login_at'] ? esc(date('d M Y, H:i', strtotime($r['last_login_at']))) : 'Never' ?></small></td>
                        <td class="text-end">
                            <a href="<?= site_url('firm-users/edit/' . $r['user_id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="<?= site_url('firm-users/delete/' . $r['user_id']) ?>" method="post" class="d-inline" data-no-validate data-confirm="This user will be removed from the firm." data-confirm-title="Remove user?" data-confirm-btn="Yes, remove">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
