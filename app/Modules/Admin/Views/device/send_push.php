<link href="<?php echo base_url();?>assets/global/css/components-rounded.css" id="style_components" rel="stylesheet" type="text/css"/>

<?php
// Group the active push devices by their owner so the target picker reads as
// "Owner -> their devices", and unmapped devices fall under a clear heading.
$grouped = array();
foreach ($devices as $dev) {
    $owner = trim((isset($dev->first_name) ? $dev->first_name : '') . ' ' . (isset($dev->last_name) ? $dev->last_name : ''));
    if ($owner === '') {
        $owner = 'Unmapped devices';
    } elseif (!empty($dev->email)) {
        $owner .= ' (' . $dev->email . ')';
    }
    $grouped[$owner][] = $dev;
}
ksort($grouped);
?>

<style>
    .push-wrap { max-width: 1100px; margin: 0 auto; }
    .push-card { background:#fff; border:1px solid #e4e7ec; border-radius:8px; box-shadow:0 4px 14px rgba(16,24,40,.05); }
    .push-card .push-card-head { padding:16px 20px; border-bottom:1px solid #eef0f3; display:flex; align-items:center; gap:10px; }
    .push-card .push-card-head h4 { margin:0; font-size:16px; font-weight:700; color:#1d2939; }
    .push-card .push-card-head i { color:#2557a7; }
    .push-card .push-card-body { padding:20px; }
    .push-grid { display:grid; grid-template-columns: 1fr 1fr; gap:20px; align-items:start; }
    .push-form .form-group { margin-bottom:16px; }
    .push-form label { font-weight:600; color:#344054; font-size:13px; margin-bottom:6px; display:block; }
    .push-form .form-control { border-radius:6px; border:1px solid #d0d5dd; box-shadow:none; }
    .push-form .form-control:focus { border-color:#2557a7; box-shadow:0 0 0 3px rgba(37,87,167,.12); }
    .push-hint { color:#667085; font-size:12px; margin-top:4px; }
    .push-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:8px; }
    .push-actions .btn { border-radius:6px; font-weight:600; }
    .push-table { width:100%; font-size:13px; margin:0; }
    .push-table th { background:#f9fafb; color:#475467; font-weight:600; white-space:nowrap; }
    .push-table td, .push-table th { vertical-align:middle; }
    .badge-soft { background:#eef4ff; color:#2557a7; border-radius:20px; padding:2px 9px; font-size:11px; font-weight:600; }
    @media (max-width: 991px) {
        .push-grid { grid-template-columns: 1fr; }
    }
</style>

<main class="main-content bgc-grey-100">
    <div id="mainContent">
        <div class="container-fluid">
            <div class="push-wrap">
                <?php
                foreach (['success', 'error', 'warning', 'info'] as $ftype) {
                    $fmsg = session()->getFlashdata($ftype);
                    if (! empty($fmsg)) {
                        $cls = $ftype === 'error' ? 'danger' : $ftype;
                        echo '<div class="alert alert-' . $cls . '">' . esc($fmsg) . '</div>';
                    }
                }
                ?>

                <?php if (!empty($fcm_disabled)): ?>
                    <div class="alert alert-warning">
                        <strong>Heads up:</strong> the FCM service account JSON was not found at
                        <code>application/config/fcm-service-account.json</code>. Notifications will be
                        logged but not delivered until you download it from Firebase Console &rarr;
                        Project settings &rarr; Service accounts &rarr; Generate new private key, and
                        save it there.
                    </div>
                <?php endif; ?>

                <div class="push-grid">
                    <div class="push-card push-form">
                        <div class="push-card-head">
                            <i class="fa fa-bell"></i>
                            <h4>Send Push Notification</h4>
                        </div>
                        <div class="push-card-body">
                            <form method="post" action="<?= base_url('admin/device/send_push') ?>">
                                <div class="form-group">
                                    <label>Target</label>
                                    <select name="device_id" class="form-control">
                                        <option value="">All active devices (<?= count($devices) ?>)</option>
                                        <?php foreach ($grouped as $owner => $devs): ?>
                                            <optgroup label="<?= htmlspecialchars($owner) ?>">
                                                <?php foreach ($devs as $dev): ?>
                                                    <?php
                                                    $meta = trim($dev->device_type . ' ' . $dev->platform);
                                                    $label = $dev->device_name;
                                                    if ($meta !== '') {
                                                        $label .= ' — ' . $meta;
                                                    }
                                                    ?>
                                                    <option value="<?= htmlspecialchars($dev->device_id) ?>">
                                                        <?= htmlspecialchars($label) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="push-hint">Choose a person's device, or leave on "All active devices" to broadcast.</div>
                                </div>
                                <div class="form-group">
                                    <label>Title <span class="c-red-500">*</span></label>
                                    <input type="text" name="title" class="form-control" value="<?= esc(old('title')) ?>" maxlength="120" required>
                                    <span class="c-red-500"><?= form_error('title') ?></span>
                                </div>
                                <div class="form-group">
                                    <label>Message <span class="c-red-500">*</span></label>
                                    <textarea name="body" class="form-control" rows="4" maxlength="500" required><?= esc(old('body')) ?></textarea>
                                    <span class="c-red-500"><?= form_error('body') ?></span>
                                </div>
                                <div class="push-actions">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i> Send</button>
                                    <a href="<?= base_url('admin/device/listing') ?>" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="push-card">
                        <div class="push-card-head">
                            <i class="fa fa-history"></i>
                            <h4>Recent Notifications</h4>
                        </div>
                        <div class="push-card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered push-table">
                                    <thead>
                                        <tr><th>Title</th><th>Target</th><th>Sent</th><th>OK</th><th>When</th></tr>
                                    </thead>
                                    <tbody>
                                    <?php if (!empty($recent)): foreach ($recent as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log->title) ?></td>
                                            <td><?= $log->target_type === 'all' ? '<span class="badge-soft">All</span>' : htmlspecialchars($log->target_device) ?></td>
                                            <td><?= (int)$log->target_count ?></td>
                                            <td><?= (int)$log->success_count ?></td>
                                            <td style="white-space:nowrap;"><?= date('d-M H:i', strtotime($log->created_at)) ?></td>
                                        </tr>
                                    <?php endforeach; else: ?>
                                        <tr><td colspan="5" class="c-grey-600">No notifications sent yet.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
