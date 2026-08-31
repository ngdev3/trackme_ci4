<?php $t = $result; $comments = $comments ?? []; $uid = $current_uid ?? 0;
$renderComment = function ($c, $depth = 0) use (&$renderComment, $uid) {
    $pad = $depth * 28; ?>
    <div style="margin-left:<?= $pad; ?>px;padding:10px 12px;border-left:3px solid #e3e9f2;margin-bottom:8px;background:#fbfdff;border-radius:0 8px 8px 0;">
      <div style="font-weight:800;"><?= esc($c->name); ?> <small class="text-muted">· <?= esc($c->role); ?> · <?= ! empty($c->added_date) ? date('d M Y H:i', strtotime($c->added_date)) : ''; ?></small></div>
      <div style="margin:4px 0;"><?= nl2br(esc($c->comment_text)); ?></div>
      <?php foreach (($c->attachments ?? []) as $a): ?>
        <a href="<?= esc($a->file_url); ?>" target="_blank" class="label label-info"><i class="fa fa-paperclip"></i> <?= esc($a->file_name); ?></a>
      <?php endforeach; ?>
      <div style="margin-top:4px;">
        <a href="javascript:void(0)" class="tsk-reply" data-id="<?= (int) $c->comment_id; ?>" style="font-size:12px;">Reply</a>
        <?php if ((int) $c->user_id === (int) $uid): ?> · <a href="javascript:void(0)" class="tsk-cdel" data-id="<?= (int) $c->comment_id; ?>" style="font-size:12px;color:#c22;">Delete</a><?php endif; ?>
      </div>
    </div>
    <?php foreach (($c->replies ?? []) as $rep) { $renderComment($rep, $depth + 1); }
};
?>
<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:900px;margin:0 auto;">
  <a href="<?= base_url('task/task'); ?>" class="btn btn-xs btn-default"><i class="fa fa-arrow-left"></i> Back</a>
  <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:20px;margin-top:12px;">
    <h3 style="font-weight:900;margin-top:0;"><?= esc($t->title); ?></h3>
    <p><span class="label label-default"><?= esc(ucwords(str_replace('_', ' ', $t->status))); ?></span>
       <span class="label label-info"><?= esc(ucfirst($t->priority)); ?></span>
       <?php if (! empty($t->due_date)): ?><span class="text-muted">· Due <?= date('d M Y', strtotime($t->due_date)); ?></span><?php endif; ?>
    </p>
    <p><?= nl2br(esc($t->description ?? '')); ?></p>
    <p class="text-muted">Assigned to: <strong><?= esc(trim(($t->assignee_first ?? '') . ' ' . ($t->assignee_last ?? '')) ?: 'Unassigned'); ?></strong>
       · Created by <?= esc(trim(($t->creator_first ?? '') . ' ' . ($t->creator_last ?? ''))); ?></p>
    <div style="margin:10px 0;">
      <?php foreach (['open' => 'Open', 'in_progress' => 'In Progress', 'done' => 'Mark Done', 'closed' => 'Close'] as $k => $l): ?>
        <button class="btn btn-xs btn-default tsk-status" data-status="<?= $k; ?>"><?= $l; ?></button>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="background:#fff;border:1px solid #e3e9f2;border-radius:12px;padding:20px;margin-top:14px;">
    <h4 style="font-weight:800;">Discussion</h4>
    <div id="tsk-thread">
      <?php if ($comments) { foreach ($comments as $c) { $renderComment($c); } } else { echo '<p class="text-muted">No comments yet.</p>'; } ?>
    </div>
    <form id="tsk-comment-form" style="margin-top:12px;">
      <input type="hidden" name="parent_id" id="tsk-parent" value="0">
      <div id="tsk-replying" style="display:none;font-size:12px;color:#1769c2;">Replying… <a href="javascript:void(0)" id="tsk-cancel-reply">cancel</a></div>
      <textarea class="form-control" name="comment_text" rows="2" placeholder="Write a comment…"></textarea>
      <div style="margin-top:8px;"><input type="file" name="attachment[]" multiple>
        <button class="btn btn-primary" type="submit" style="float:right;">Post</button></div>
    </form>
  </div>
</div></div></main>
<script>
var BASE = "<?= base_url(); ?>", TASK_ID = <?= (int) $t->task_id; ?>, TASK_ENC = "<?= esc(ID_encode((int) $t->task_id), 'js'); ?>";
$('.tsk-status').on('click',function(){ $.post(BASE+"task/task/set_status",{id:TASK_ENC,status:$(this).data('status')},function(){location.reload();},'json'); });
$(document).on('click','.tsk-reply',function(){ $('#tsk-parent').val($(this).data('id')); $('#tsk-replying').show(); $('textarea[name=comment_text]').focus(); });
$('#tsk-cancel-reply').on('click',function(){ $('#tsk-parent').val(0); $('#tsk-replying').hide(); });
$(document).on('click','.tsk-cdel',function(){ if(!confirm('Delete this comment?'))return;
 $.post(BASE+"task/task/comment_delete",{comment_id:$(this).data('id')},function(r){ if(r&&r.status==='success'){location.reload();}else{alert((r&&r.error_msg)||'Failed');} },'json'); });
$('#tsk-comment-form').on('submit',function(e){ e.preventDefault();
 var fd=new FormData(this); fd.append('task_id',TASK_ID);
 $.ajax({url:BASE+"task/task/comment_add",type:'POST',data:fd,processData:false,contentType:false,dataType:'json',
  success:function(r){ if(r&&r.status==='success'){location.reload();}else{alert((r&&r.error_msg)||'Failed');} }}); });
</script>
