<?php
/**
 * Reusable proof-file gallery. Renders images as thumbnails, plays video / voice
 * notes inline, and shows bills / challans / PDFs as download cards. Optionally
 * shows a delete button per file (owner/admin only).
 *
 * Expects: $attachments (array), $canDelete (bool, optional).
 */
$canDelete = $canDelete ?? false;
$kindIcon  = [
    'image' => 'bi-image', 'video' => 'bi-camera-video', 'audio' => 'bi-mic',
    'pdf'   => 'bi-file-earmark-pdf', 'doc' => 'bi-file-earmark-text',
];
$fmtSize = static function ($b) {
    $b = (int) $b;
    return $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : ($b >= 1024 ? round($b / 1024) . ' KB' : $b . ' B');
};
?>
<?php if (empty($attachments)): ?>
    <div class="inv-empty-mini"><i class="bi bi-paperclip"></i>No proof files attached yet.</div>
<?php else: ?>
    <div class="inv-att-grid">
        <?php foreach ($attachments as $a):
            $url = site_url('inventory/attachment/' . $a['id']);
        ?>
            <div class="inv-att-card <?= esc($a['kind']) ?>">
                <?php if ($a['kind'] === 'image'): ?>
                    <a href="<?= $url ?>" target="_blank" class="inv-att-thumb" style="background-image:url('<?= $url ?>')"></a>
                <?php elseif ($a['kind'] === 'video'): ?>
                    <video class="inv-att-media" src="<?= $url ?>" controls preload="metadata"></video>
                <?php elseif ($a['kind'] === 'audio'): ?>
                    <div class="inv-att-audio"><i class="bi bi-soundwave"></i><audio src="<?= $url ?>" controls preload="none"></audio></div>
                <?php else: ?>
                    <a href="<?= $url ?>" target="_blank" class="inv-att-file">
                        <i class="bi <?= $kindIcon[$a['kind']] ?? 'bi-file-earmark' ?>"></i>
                    </a>
                <?php endif; ?>

                <div class="inv-att-info">
                    <a href="<?= $url ?>" target="_blank" class="inv-att-name" title="<?= esc($a['original_name'], 'attr') ?>"><?= esc($a['original_name']) ?></a>
                    <span class="inv-att-meta"><?= esc(ucfirst($a['kind'])) ?> · <?= $fmtSize($a['size']) ?><?= ! empty($a['created_at']) ? ' · ' . esc(date('d M, H:i', strtotime($a['created_at']))) : '' ?></span>
                </div>

                <?php if ($canDelete): ?>
                    <form action="<?= site_url('inventory/attachment/' . $a['id'] . '/delete') ?>" method="post" class="inv-att-del"
                          data-no-validate data-confirm="This proof file will be removed." data-confirm-title="Remove file?" data-confirm-btn="Yes, remove">
                        <?= csrf_field() ?>
                        <button type="submit" title="Remove"><i class="bi bi-trash"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
