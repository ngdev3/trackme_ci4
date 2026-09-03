<?php
helper(['url', 'app']);
/**
 * Temp Farmer Thumb — Unlock Requests (approval inbox + audit trail).
 * admin/accountMapping/temp_unlock_requests
 * Vars: $requests (pending), $history (all requests), $audit (recent log),
 *       $can_approve (bool — Super Admin or a delegated user).
 */
$can_approve = !empty($can_approve);
// Map a request status to a coloured pill class reused by the audit badges.
$statusClass = array('pending' => 'unlock_request', 'approved' => 'approve', 'rejected' => 'reject');
$fmt = function ($dt) { return $dt ? date('d-m-Y H:i', strtotime($dt)) : '—'; };
$who = function ($name, $id) {
    $name = trim((string) $name);
    return $name !== '' ? $name : ($id ? ('User #' . (int) $id) : '—');
};
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<style>
  .ur{--g2:#0a6f49;--ink:#152238;--muted:#6b7a90;--line:#e7edf5;--amber:#b45309;--red:#d64545;
      font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:var(--ink)}
  .ur *{box-sizing:border-box}
  .ur-shell{margin:0 auto;max-width:1120px}
  .ur-hero{background:linear-gradient(135deg,#0f8a5f,#0a6f49);color:#fff;border-radius:18px;padding:20px 24px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
  .ur-hero h4{margin:0;font-size:20px;font-weight:800}.ur-hero p{margin:4px 0 0;opacity:.9;font-size:13px}
  .ur-back{background:rgba(255,255,255,.16);color:#fff;border-radius:10px;padding:9px 14px;font-weight:700;text-decoration:none}
  .ur-card{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 16px 36px -24px rgba(24,36,60,.35);margin-bottom:22px;overflow:hidden}
  .ur-card-h{padding:14px 18px;border-bottom:1px solid var(--line);font-weight:800;font-size:15px}
  table.ur-tbl{width:100%;border-collapse:collapse;font-size:13px}
  .ur-tbl th,.ur-tbl td{padding:10px 12px;border-bottom:1px solid var(--line);text-align:left;vertical-align:middle}
  .ur-tbl th{background:#f6f9fc;font-weight:700;color:#42506a;font-size:11px;text-transform:uppercase}
  .ur-btn{border:0;border-radius:8px;padding:6px 12px;font-weight:800;font-size:12px;cursor:pointer;font:inherit}
  .ur-btn.ok{background:#0a6f49;color:#fff}.ur-btn.no{background:#fdecec;color:var(--red);margin-left:6px}
  .ur-empty{padding:26px;text-align:center;color:var(--muted);font-weight:600}
  .ur-badge{border-radius:20px;padding:2px 9px;font-size:11px;font-weight:800}
  .ur-badge.lock{background:#e6f7ee;color:var(--g2)}.ur-badge.unlock_request{background:#fff7ed;color:var(--amber)}
  .ur-badge.approve{background:#e6f7ee;color:var(--g2)}.ur-badge.reject{background:#fdecec;color:var(--red)}.ur-badge.relock{background:#eef2fc;color:#3557b7}
</style>

<main class="main-content bgc-grey-100 ur">
  <div class="ur-shell">
    <div class="ur-hero">
      <div><h4>Unlock Requests</h4><p>Approve or reject requests to edit finalized (locked) center dates.</p></div>
      <a class="ur-back" href="<?= base_url('admin/accountMapping/thumb_figure') ?>"><i class="ti-arrow-left"></i> Back to Thumb</a>
    </div>

    <div class="ur-card">
      <div class="ur-card-h">Pending Requests</div>
      <div style="overflow-x:auto"><table class="ur-tbl"><thead><tr>
        <th>Center</th><th>Locked Date</th><th>Requested By</th><th>Requested At</th><th>Reason</th>
        <?php if ($can_approve): ?><th style="width:220px">Action</th><?php endif; ?>
      </tr></thead><tbody id="urBody">
        <?php $pcols = $can_approve ? 6 : 5; ?>
        <?php if (empty($requests)): ?>
          <tr><td colspan="<?= $pcols ?>" class="ur-empty">No pending requests.</td></tr>
        <?php else: foreach ($requests as $r): ?>
          <tr data-id="<?= (int) $r->id ?>">
            <td><?= html_escape($r->center_name ? $r->center_name : ('#' . $r->center_id)) ?></td>
            <td><?= date('d-m-Y', strtotime($r->entry_date)) ?></td>
            <td><?= html_escape($who($r->requested_by_name, $r->requested_by)) ?></td>
            <td><?= html_escape($fmt($r->created_at)) ?></td>
            <td><?= html_escape($r->reason) ?></td>
            <?php if ($can_approve): ?>
            <td>
              <button class="ur-btn ok" data-act="approve" data-id="<?= (int) $r->id ?>">Approve</button>
              <button class="ur-btn no" data-act="reject" data-id="<?= (int) $r->id ?>">Reject</button>
            </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; endif; ?>
      </tbody></table></div>
    </div>

    <?php if ($can_approve): ?>
    <div class="ur-card">
      <div class="ur-card-h">Currently Unlocked (Frozen) Centers</div>
      <div style="overflow-x:auto"><table class="ur-tbl"><thead><tr>
        <th>Center</th><th>Unlocked Date</th><th>Unlocked For</th><th>Unlocked By</th><th>Unlocked At</th><th style="width:170px">Action</th>
      </tr></thead><tbody id="urFrozen">
        <?php if (empty($unlocked)): ?>
          <tr><td colspan="6" class="ur-empty">No centers are currently unlocked.</td></tr>
        <?php else: foreach ($unlocked as $u): ?>
          <tr data-c="<?= (int) $u->center_id ?>" data-d="<?= html_escape($u->entry_date) ?>">
            <td><?= html_escape($u->center_name ? $u->center_name : ('#' . $u->center_id)) ?></td>
            <td><?= date('d-m-Y', strtotime($u->entry_date)) ?></td>
            <td><?= html_escape($who($u->unlocked_for_name, $u->unlocked_for)) ?></td>
            <td><?= html_escape($who($u->unlocked_by_name, $u->unlocked_by)) ?></td>
            <td><?= html_escape($fmt($u->unlocked_at)) ?></td>
            <td><button class="ur-btn ok" data-act="relock" data-c="<?= (int) $u->center_id ?>" data-d="<?= html_escape($u->entry_date) ?>"><i class="ti-lock"></i> Re-lock</button></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody></table></div>
    </div>
    <?php endif; ?>

    <div class="ur-card">
      <div class="ur-card-h">Request History &mdash; who raised it, who decided it, when</div>
      <div style="overflow-x:auto"><table class="ur-tbl"><thead><tr>
        <th>Center</th><th>Locked Date</th><th>Status</th>
        <th>Requested By</th><th>Requested At</th>
        <th>Decided By</th><th>Decided At</th>
        <th>Reason</th><th>Admin Remark</th>
      </tr></thead><tbody>
        <?php if (empty($history)): ?>
          <tr><td colspan="9" class="ur-empty">No unlock requests yet.</td></tr>
        <?php else: foreach ($history as $h): $sc = isset($statusClass[$h->status]) ? $statusClass[$h->status] : 'lock'; ?>
          <tr>
            <td><?= html_escape($h->center_name ? $h->center_name : ('#' . $h->center_id)) ?></td>
            <td><?= date('d-m-Y', strtotime($h->entry_date)) ?></td>
            <td><span class="ur-badge <?= $sc ?>"><?= html_escape(ucfirst($h->status)) ?></span></td>
            <td><?= html_escape($who($h->requested_by_name, $h->requested_by)) ?></td>
            <td><?= html_escape($fmt($h->created_at)) ?></td>
            <td><?= $h->status === 'pending' ? '<span style="color:var(--muted)">Awaiting</span>' : html_escape($who($h->approved_by_name, $h->approved_by)) ?></td>
            <td><?= $h->status === 'pending' ? '—' : html_escape($fmt($h->approved_at)) ?></td>
            <td><?= html_escape($h->reason) ?></td>
            <td><?= html_escape($h->admin_remark ? $h->admin_remark : '—') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody></table></div>
    </div>

    <div class="ur-card">
      <div class="ur-card-h">Audit Trail (latest)</div>
      <div style="overflow-x:auto"><table class="ur-tbl"><thead><tr>
        <th>When</th><th>Action</th><th>Center</th><th>Date</th>
        <th>By</th><th>Requested By</th><th>Approved By</th><th>Reason / Remark</th><th>IP</th>
      </tr></thead><tbody>
        <?php if (empty($audit)): ?>
          <tr><td colspan="9" class="ur-empty">No activity yet.</td></tr>
        <?php else: foreach ($audit as $a): ?>
          <tr>
            <td><?= html_escape($fmt($a->created_at)) ?></td>
            <td><span class="ur-badge <?= html_escape($a->action) ?>"><?= html_escape($a->action) ?></span></td>
            <td><?= html_escape($a->center_name ? $a->center_name : ('#' . $a->center_id)) ?></td>
            <td><?= $a->entry_date ? date('d-m-Y', strtotime($a->entry_date)) : '—' ?></td>
            <td><?= html_escape($who($a->user_name, $a->user_id)) ?></td>
            <td><?= $a->requested_by ? html_escape($who($a->requested_by_name, $a->requested_by)) : '—' ?></td>
            <td><?= $a->approved_by ? html_escape($who($a->approved_by_name, $a->approved_by)) : '—' ?></td>
            <td><?= html_escape(trim(($a->reason ? $a->reason : '') . ' ' . ($a->remarks ? '· ' . $a->remarks : ''))) ?></td>
            <td><?= html_escape($a->ip_address) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody></table></div>
    </div>
  </div>
</main>

<script>
(function ($) {
  var BASE = "<?= base_url() ?>";
  function toast(t,m,ti){ if(window.showToast){ showToast(t,m,ti||''); } }
  $('#urBody').on('click','[data-act]',function(){
    var act=$(this).data('act'), id=$(this).data('id'), $row=$(this).closest('tr');
    var go=function(remark){
      $.ajax({url:BASE+'admin/accountMapping/temp_'+act,type:'POST',dataType:'json',data:{id:id,remark:remark||''},
        success:function(res){ if(res&&res.status==='success'){ $row.fadeOut(200,function(){ $(this).remove();
            if(!$('#urBody tr').length){ $('#urBody').html('<tr><td colspan="<?= $pcols ?>" class="ur-empty">No pending requests.</td></tr>'); } });
            toast('success',res.message,act==='approve'?'Approved':'Rejected'); }
          else { toast('error',res.message||'Failed','Error'); } },
        error:function(){ toast('error','Request failed.','Error'); }});
    };
    var label=act==='approve'?'Approve':'Reject';
    if(window.showPrompt){ showPrompt(label+' request','Add an optional remark for this '+label.toLowerCase()+':','',go,null,{okText:label,required:false,placeholder:'Optional remark'}); }
    else { go(prompt(label+' — optional remark:')||''); }
  });

  // Re-lock a currently-frozen center (any approver may clear any center).
  $('#urFrozen').on('click','[data-act="relock"]',function(){
    var $b=$(this), c=$b.data('c'), d=$b.data('d'), $row=$b.closest('tr');
    var send=function(){
      $b.prop('disabled',true);
      $.ajax({url:BASE+'admin/accountMapping/temp_relock',type:'POST',dataType:'json',data:{center_id:c,date:d},
        success:function(res){
          if(res&&res.status==='success'){ $row.fadeOut(200,function(){ $(this).remove();
              if(!$('#urFrozen tr').length){ $('#urFrozen').html('<tr><td colspan="6" class="ur-empty">No centers are currently unlocked.</td></tr>'); } });
            toast('success',res.message,'Re-locked'); }
          else { $b.prop('disabled',false); toast('error',res.message||'Failed','Error'); } },
        error:function(){ $b.prop('disabled',false); toast('error','Request failed.','Error'); }});
    };
    if(window.showConfirm){ showConfirm('Re-lock this date?','This restores the normal workflow for the center. Any unsaved edits on the unlocked date stay as-is.',send,null,{okText:'Re-lock'}); }
    else if(confirm('Re-lock this date?')){ send(); }
  });
})(jQuery);
</script>
