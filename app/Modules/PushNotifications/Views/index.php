<?php
$templates = $templates ?? [];
$users = $users ?? [];
$typeOptions = \App\Models\NotificationModel::TYPES;
$priorityOptions = \App\Models\NotificationModel::PRIORITIES;
?>
<style>
    .push-hero { border-radius: 18px; padding: 22px; color: #fff; background: linear-gradient(135deg, #17326b, #0f766e); box-shadow: 0 14px 34px rgba(15, 23, 42, .16); }
    .template-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 10px; }
    .template-option { display: block; cursor: pointer; border: 1px solid #e1e8f3; border-radius: 14px; padding: 12px; background: #fff; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
    .template-option:hover { border-color: #0f766e; box-shadow: 0 10px 24px rgba(15, 118, 110, .12); transform: translateY(-1px); }
    .template-option input { margin-right: 7px; }
    .recipient-list { max-height: 340px; overflow: auto; border: 1px solid #e5ebf3; border-radius: 14px; }
    .recipient-row { display: grid; grid-template-columns: 32px 1fr auto; gap: 10px; align-items: center; padding: 10px 12px; border-bottom: 1px solid #edf2f7; }
    .recipient-row:last-child { border-bottom: 0; }
    .preview-phone { max-width: 340px; border-radius: 28px; border: 10px solid #111827; background: #f8fafc; padding: 16px; margin-inline: auto; }
    .push-preview { border-radius: 16px; background: #fff; box-shadow: 0 12px 30px rgba(15,23,42,.15); padding: 14px; }
</style>

<div class="push-hero mb-3">
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
        <div>
            <div class="text-uppercase fw-bold small opacity-75">Template based push sender</div>
            <h2 class="mb-1">Push Notification Center</h2>
            <p class="mb-0 opacity-75">Pick a template, choose users, customize the message, and send it to mobile/browser subscribers.</p>
        </div>
        <a class="btn btn-light btn-sm" href="<?= site_url('notifications') ?>"><i class="bi bi-bell me-1"></i>Notification inbox</a>
    </div>
</div>

<form method="post" action="<?= site_url('push-notifications/send') ?>" id="pushForm">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-ui-checks-grid me-1"></i>1. Choose Template</h3></div>
                <div class="card-body">
                    <div class="template-grid">
                        <?php foreach ($templates as $key => $tpl): ?>
                            <label class="template-option">
                                <input type="radio" name="template" value="<?= esc($key) ?>" <?= $key === 'custom' ? 'checked' : '' ?>
                                       data-title="<?= esc($tpl['title'], 'attr') ?>"
                                       data-message="<?= esc($tpl['message'], 'attr') ?>"
                                       data-type="<?= esc($tpl['type'], 'attr') ?>"
                                       data-priority="<?= esc($tpl['priority'], 'attr') ?>"
                                       data-url="<?= esc($tpl['action_url'], 'attr') ?>">
                                <strong><?= esc($tpl['name']) ?></strong>
                                <div class="text-secondary small mt-1"><?= esc($tpl['title'] ?: 'Write your own title and message') ?></div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-pencil-square me-1"></i>2. Compose Message</h3></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Title</label>
                            <input name="title" id="pushTitle" class="form-control" maxlength="180" required placeholder="Notification title">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select name="type" id="pushType" class="form-select">
                                <?php foreach ($typeOptions as $type): ?><option value="<?= esc($type) ?>"><?= esc($type) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Priority</label>
                            <select name="priority" id="pushPriority" class="form-select">
                                <?php foreach ($priorityOptions as $priority): ?><option value="<?= esc($priority) ?>"><?= esc($priority) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea name="message" id="pushMessage" class="form-control" rows="4" required placeholder="Write the message users will see"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Open URL</label>
                            <input name="action_url" id="pushUrl" class="form-control" placeholder="<?= esc(site_url('notifications')) ?>">
                            <div class="form-text">Users will open this page when they tap the notification.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-people me-1"></i>3. Choose Recipients</h3></div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <?php foreach (['with_devices' => 'Users with devices', 'customers' => 'All customers', 'firm_users' => 'All firm users', 'all' => 'All active users', 'selected' => 'Selected users only'] as $value => $label): ?>
                            <div class="col-6 col-md-auto">
                                <label class="form-check">
                                    <input class="form-check-input" type="radio" name="target" value="<?= esc($value) ?>" <?= $value === 'with_devices' ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= esc($label) ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="recipient-list">
                        <?php if (empty($users)): ?>
                            <div class="p-3 text-secondary">No users found.</div>
                        <?php else: foreach ($users as $u): ?>
                            <label class="recipient-row">
                                <input type="checkbox" name="users[]" value="<?= (int) $u['id'] ?>">
                                <span>
                                    <strong><?= esc($u['name'] ?: $u['email'] ?: ('User #' . $u['id'])) ?></strong>
                                    <span class="text-secondary small d-block"><?= esc(($u['email'] ?: $u['mobile'] ?: '-') . ' - ' . ($u['account_type'] ?: 'user')) ?></span>
                                </span>
                                <span class="badge <?= (int) $u['device_count'] > 0 ? 'text-bg-success' : 'text-bg-light border' ?>"><?= (int) $u['device_count'] ?> device<?= (int) $u['device_count'] === 1 ? '' : 's' ?></span>
                            </label>
                        <?php endforeach; endif; ?>
                    </div>
                    <div class="form-text mt-2">Selected users are used only when target is "Selected users only". Users without devices still receive an in-app notification.</div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-send-fill me-1"></i>Send Notification</button>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card sticky-xl-top" style="top: 1rem;">
                <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-phone-vibrate me-1"></i>Live Preview</h3></div>
                <div class="card-body">
                    <div class="preview-phone">
                        <div class="push-preview">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge text-bg-success">HK</span>
                                <strong>HissabKitaab</strong>
                                <small class="text-secondary ms-auto">now</small>
                            </div>
                            <div class="fw-bold" id="previewTitle">Notification title</div>
                            <div class="text-secondary small" id="previewMessage">Your message preview appears here.</div>
                            <div class="small text-primary mt-2" id="previewUrl"><?= esc(site_url('notifications')) ?></div>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 small">Push delivery is best-effort. FCM/Web Push settings and user device subscriptions decide whether a real device alert is delivered.</div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    var title = document.getElementById('pushTitle');
    var message = document.getElementById('pushMessage');
    var type = document.getElementById('pushType');
    var priority = document.getElementById('pushPriority');
    var url = document.getElementById('pushUrl');
    var pTitle = document.getElementById('previewTitle');
    var pMessage = document.getElementById('previewMessage');
    var pUrl = document.getElementById('previewUrl');
    function syncPreview() {
        pTitle.textContent = title.value || 'Notification title';
        pMessage.textContent = message.value || 'Your message preview appears here.';
        pUrl.textContent = url.value || '<?= esc(site_url('notifications')) ?>';
    }
    document.querySelectorAll('input[name="template"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (!radio.checked) { return; }
            title.value = radio.dataset.title || '';
            message.value = radio.dataset.message || '';
            type.value = radio.dataset.type || 'info';
            priority.value = radio.dataset.priority || 'normal';
            url.value = radio.dataset.url || '<?= esc(site_url('notifications')) ?>';
            syncPreview();
        });
    });
    [title, message, url].forEach(function (el) { el.addEventListener('input', syncPreview); });
    var checked = document.querySelector('input[name="template"]:checked');
    if (checked) { checked.dispatchEvent(new Event('change')); }
})();
</script>
