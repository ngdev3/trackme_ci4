<?php
/** Customer — one ongoing support conversation (chat). Rendered in layout.php. */
$messages = $messages ?? [];
$open     = $open ?? true;
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="bi bi-headset me-1"></i> Support</h3>
                <a href="<?= site_url('help') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-question-circle me-1"></i>Help &amp; FAQ</a>
            </div>

            <div class="card-body erp-chat" id="erpChatBody">
                <?php if (empty($messages)): ?>
                    <div class="erp-chat-empty">
                        <i class="bi bi-chat-left-dots"></i>
                        <div>Start a conversation with our support team. Ask anything — it all stays in this one thread.</div>
                    </div>
                <?php else: foreach ($messages as $msg): $mine = $msg['sender'] === 'customer'; ?>
                    <div class="erp-msg <?= $mine ? 'erp-msg-out' : 'erp-msg-in' ?>">
                        <div class="erp-msg-avatar <?= $mine ? 'you' : 'admin' ?>"><i class="bi bi-<?= $mine ? 'person' : 'headset' ?>"></i></div>
                        <div class="erp-msg-bubble">
                            <div class="erp-msg-meta"><strong><?= $mine ? 'You' : 'Support' ?></strong> · <?= esc(date('d M Y, H:i', strtotime((string) $msg['at']))) ?></div>
                            <div class="erp-msg-text"><?= nl2br(esc($msg['message'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <div class="card-footer">
                <?php if ($open): ?>
                    <form action="<?= site_url('help/support/send') ?>" method="post" class="d-flex gap-2 align-items-end">
                        <?= csrf_field() ?>
                        <div class="flex-grow-1"><textarea name="message" class="form-control" rows="2" maxlength="5000" required placeholder="Type your message…"></textarea></div>
                        <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Send</button>
                    </form>
                    <div class="form-text mt-1"><i class="bi bi-info-circle me-1"></i>Every message — a new question or a reply — stays in this single thread.</div>
                <?php else: ?>
                    <div class="text-secondary small text-center py-2"><i class="bi bi-check2-circle me-1"></i>This conversation was closed by support. Send a message to reopen it.</div>
                    <form action="<?= site_url('help/support/send') ?>" method="post" class="d-flex gap-2 align-items-end mt-2">
                        <?= csrf_field() ?>
                        <div class="flex-grow-1"><textarea name="message" class="form-control" rows="2" maxlength="5000" required placeholder="Reopen the conversation…"></textarea></div>
                        <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Send</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style nonce="{csp-style-nonce}">
.erp-chat { display: flex; flex-direction: column; gap: 12px; min-height: 240px; max-height: 60vh; overflow-y: auto; background: #f7fafc; }
.erp-chat-empty { margin: auto; text-align: center; color: #94a3b8; max-width: 320px; }
.erp-chat-empty .bi { font-size: 34px; display: block; margin-bottom: 8px; opacity: .6; }
.erp-msg { display: flex; gap: 10px; max-width: 82%; }
.erp-msg-in { align-self: flex-start; }
.erp-msg-out { align-self: flex-end; flex-direction: row-reverse; }
.erp-msg-avatar { flex: 0 0 auto; width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; color: #fff; font-size: 14px; }
.erp-msg-avatar.you { background: linear-gradient(135deg, #1769c2, #3b82f6); }
.erp-msg-avatar.admin { background: #475569; }
.erp-msg-bubble { background: #fff; border: 1px solid #e6edf5; border-radius: 14px; padding: 9px 13px; box-shadow: 0 1px 2px rgba(15,30,60,.05); }
.erp-msg-out .erp-msg-bubble { background: #1769c2; border-color: #1769c2; color: #fff; }
.erp-msg-meta { font-size: 10.5px; font-weight: 700; opacity: .7; margin-bottom: 3px; }
.erp-msg-out .erp-msg-meta { color: #dbeafe; opacity: .9; }
.erp-msg-text { font-size: 13.5px; line-height: 1.5; word-break: break-word; }
</style>
<script nonce="{csp-script-nonce}">(function () { var b = document.getElementById('erpChatBody'); if (b) { b.scrollTop = b.scrollHeight; } })();</script>
