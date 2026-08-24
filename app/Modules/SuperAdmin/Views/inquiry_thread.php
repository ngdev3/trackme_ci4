<?php
/** Super Admin — inquiry detail + two-way reply thread. Rendered in layout.php. */
$inquiry = $inquiry ?? [];
$replies = $replies ?? [];
$subjectMeta = [
    'general' => 'General', 'pricing' => 'Pricing', 'demo' => 'Demo',
    'support' => 'Support', 'partnership' => 'Partnership',
];
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= site_url('admin/inquiries') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
                    <h3 class="card-title mb-0"><i class="bi bi-chat-dots me-1"></i>
                        <?= esc($subjectMeta[$inquiry['subject'] ?? ''] ?? 'Inquiry') ?> · <?= esc($inquiry['name']) ?>
                    </h3>
                </div>
                <a href="mailto:<?= esc($inquiry['email'], 'attr') ?>" class="erp-muted small"><i class="bi bi-envelope me-1"></i><?= esc($inquiry['email']) ?></a>
            </div>

            <div class="card-body erp-chat" id="erpChatBody">
                <!-- Original inquiry = first customer message -->
                <div class="erp-msg erp-msg-in">
                    <div class="erp-msg-avatar"><?= esc(strtoupper(mb_substr((string) $inquiry['name'], 0, 1) ?: '?')) ?></div>
                    <div class="erp-msg-bubble">
                        <div class="erp-msg-meta"><strong><?= esc($inquiry['name']) ?></strong> · <?= esc(date('d M Y, H:i', strtotime((string) $inquiry['created_at']))) ?></div>
                        <div class="erp-msg-text"><?= nl2br(esc($inquiry['message'])) ?></div>
                    </div>
                </div>

                <?php foreach ($replies as $rep): $admin = $rep['sender_type'] === 'admin'; ?>
                    <div class="erp-msg <?= $admin ? 'erp-msg-out' : 'erp-msg-in' ?>">
                        <div class="erp-msg-avatar <?= $admin ? 'admin' : '' ?>"><i class="bi bi-<?= $admin ? 'headset' : 'person' ?>"></i></div>
                        <div class="erp-msg-bubble">
                            <div class="erp-msg-meta"><strong><?= esc($rep['name'] ?: ($admin ? 'Support' : 'Customer')) ?></strong> · <?= esc(date('d M Y, H:i', strtotime((string) $rep['created_at']))) ?></div>
                            <div class="erp-msg-text"><?= nl2br(esc($rep['message'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card-footer">
                <form action="<?= site_url('admin/inquiries/' . $inquiry['id'] . '/reply') ?>" method="post" class="d-flex gap-2 align-items-end">
                    <?= csrf_field() ?>
                    <div class="flex-grow-1">
                        <textarea name="message" class="form-control" rows="2" maxlength="5000" required placeholder="Write a reply to <?= esc($inquiry['name'], 'attr') ?>…"></textarea>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-send me-1"></i> Send</button>
                </form>
                <div class="form-text mt-1"><i class="bi bi-info-circle me-1"></i>The customer sees your reply on the web portal and in the mobile app, and can reply back here.</div>
            </div>
        </div>
    </div>
</div>

<style>
.erp-chat { display: flex; flex-direction: column; gap: 14px; max-height: 60vh; overflow-y: auto; background: #f7fafc; }
.erp-msg { display: flex; gap: 10px; max-width: 78%; }
.erp-msg-in { align-self: flex-start; }
.erp-msg-out { align-self: flex-end; flex-direction: row-reverse; }
.erp-msg-avatar { flex: 0 0 auto; width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center;
    background: #e2e8f0; color: #475569; font-weight: 800; font-size: 14px; }
.erp-msg-avatar.admin { background: linear-gradient(135deg, #1769c2, #3b82f6); color: #fff; }
.erp-msg-bubble { background: #fff; border: 1px solid #e6edf5; border-radius: 14px; padding: 9px 13px; box-shadow: 0 1px 2px rgba(15,30,60,.05); }
.erp-msg-out .erp-msg-bubble { background: #1769c2; border-color: #1769c2; color: #fff; }
.erp-msg-meta { font-size: 10.5px; font-weight: 700; opacity: .7; margin-bottom: 3px; }
.erp-msg-out .erp-msg-meta { color: #dbeafe; opacity: .9; }
.erp-msg-text { font-size: 13.5px; line-height: 1.5; word-break: break-word; }
</style>
<script>
(function () { var b = document.getElementById('erpChatBody'); if (b) { b.scrollTop = b.scrollHeight; } })();
</script>
