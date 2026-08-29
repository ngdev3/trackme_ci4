<?php /** Firm users list. Rendered inside layout.php. */ ?>
<div class="cust-page">
    <section class="cust-hero">
        <div>
            <h4 class="cust-title">Firm Users</h4>
            <p class="cust-subtitle">Team members with access to <strong><?= esc($firm['name'] ?? 'this firm') ?></strong> — roles, status and last login.</p>
        </div>
        <div class="cust-hero-actions">
            <a href="<?= site_url('firm-users/create') ?>" class="cust-btn cust-btn-primary"><i class="bi bi-person-plus"></i> Add User</a>
        </div>
    </section>
    <section class="cust-panel cust-table-panel">
        <div class="cust-toolbar">
            <div>
                <h5 class="cust-table-title">User Records</h5>
                <p class="cust-table-note">Edit a user's role and access, or remove them from the firm.</p>
            </div>
            <span class="cust-total-tag"><i class="bi bi-people"></i> <?= number_format(count($rows ?? [])) ?> total</span>
        </div>
        <div class="erp-tbl-wrap">
            <table class="erp-tbl">
                <thead><tr>
                    <th class="text-start" style="width:200px">Name</th><th class="text-start" style="width:240px">Email</th><th class="text-start" style="width:140px">Mobile</th><th class="text-start" style="width:140px">Role</th><th class="text-center" style="width:120px">Status</th><th class="text-start" style="width:160px">Last Login</th><th class="text-end" style="width:130px">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="erp-empty"><i class="bi bi-people"></i><div>No firm users yet. Click <strong>Add User</strong> to create one.</div></td></tr>
                <?php else: $labels = firm_roles(); foreach ($rows as $r): ?>
                    <tr>
                        <?php $fuActive = (int) $r['status'] === 1 && (int) $r['user_status'] === 1; ?>
                        <td class="text-start"><?= erp_cell_name((string) $r['name'], [
                            'type' => 'Firm User', 'icon' => 'person',
                            'chips' => [
                                ['t' => $labels[$r['role']] ?? ucfirst((string) $r['role']), 'ic' => 'person-badge'],
                                ['t' => $fuActive ? 'Active' : 'Inactive', 'ic' => $fuActive ? 'check-circle-fill' : 'pause-circle-fill', 'ok' => $fuActive],
                            ],
                            'rows' => array_values(array_filter([
                                ! empty($r['email']) ? ['ic' => 'envelope', 'l' => 'Email', 'v' => (string) $r['email']] : null,
                                ! empty($r['mobile']) ? ['ic' => 'telephone', 'l' => 'Mobile', 'v' => (string) $r['mobile']] : null,
                                ['ic' => 'clock-history', 'l' => 'Last login', 'v' => $r['last_login_at'] ? date('d M Y, H:i', strtotime($r['last_login_at'])) : 'Never'],
                            ])),
                            'foot' => 'Firm user',
                        ], ['green' => $fuActive]) ?></td>
                        <td class="text-start"><span class="erp-truncate erp-muted" title="<?= esc($r['email'], 'attr') ?>"><?= esc($r['email']) ?></span></td>
                        <td class="text-start"><span class="erp-muted"><?= esc($r['mobile'] ?: '—') ?></span></td>
                        <td class="text-start"><span class="erp-pill"><?= esc($labels[$r['role']] ?? ucfirst($r['role'])) ?></span></td>
                        <td class="text-center"><?= (int) $r['status'] === 1 && (int) $r['user_status'] === 1 ? '<span class="erp-status active">Active</span>' : '<span class="erp-status inactive">Inactive</span>' ?></td>
                        <td class="text-start"><span class="erp-muted"><?= $r['last_login_at'] ? esc(date('d M Y, H:i', strtotime($r['last_login_at']))) : 'Never' ?></span></td>
                        <td class="text-end">
                            <div class="erp-actions">
                                <a href="<?= site_url('firm-users/edit/' . $r['user_id']) ?>" class="erp-act" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form action="<?= site_url('firm-users/delete/' . $r['user_id']) ?>" method="post" class="d-inline" data-no-validate data-confirm="This user will be removed from the firm." data-confirm-title="Remove user?" data-confirm-btn="Yes, remove">
                                    <?= csrf_field() ?>
                                    <button class="erp-act red" title="Remove"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
