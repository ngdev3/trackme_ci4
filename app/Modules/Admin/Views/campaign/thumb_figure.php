<?php
helper(['url', 'app']);
/**
 * Temp Farmer Thumb Management  —  admin/accountMapping/thumb_figure
 *
 * Center-wise daily temp-farmer thumb sheet with per-center Date Locking.
 * Each center is an independent card; the center name sits prominently in the
 * middle of its header. Add / View / Edit / Delete + drag-and-drop between
 * centers are all AJAX, re-rendering only from the returned `centers` payload.
 * The date-lock engine (Temp_thumb_mod) enforces every rule server-side; the
 * UI mirrors it for affordance only.
 *
 * Vars: $centers (per-center payload), $center_list (active centers),
 *       $pending_count (Super Admin only).
 */
$cur_fy   = (fy() && isset(fy()->FY)) ? fy()->FY : '';
$uid      = (int) currentuserinfo()->id;
$is_super = function_exists('erp_is_super_admin') && erp_is_super_admin();
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<style>
  .tf,#tfModal,#tfSpin{--g1:#0f8a5f;--g2:#0a6f49;--ink:#152238;--muted:#6b7a90;--line:#e7edf5;--green:#d6f5df;--greenln:#28a745;--amber:#b45309;--red:#d64545}
  .tf{color:var(--ink);font-family:'Plus Jakarta Sans',system-ui,-apple-system,'Segoe UI',sans-serif;-webkit-font-smoothing:antialiased}
  #tfModal{font-family:'Plus Jakarta Sans',system-ui,-apple-system,'Segoe UI',sans-serif}
  .tf *{box-sizing:border-box}
  .tf-shell{margin:0 auto;max-width:1240px}
  .tf-hero{position:relative;overflow:hidden;align-items:center;
    background:radial-gradient(1000px 220px at 88% -40%,rgba(255,255,255,.28),transparent 60%),linear-gradient(135deg,#0f8a5f,#0a6f49 60%,#095a3c);
    border-radius:20px;box-shadow:0 22px 46px -18px rgba(10,111,73,.5);color:#fff;display:flex;flex-wrap:wrap;gap:16px;
    justify-content:space-between;margin-bottom:20px;padding:22px 26px}
  .tf-hero h4{font-size:22px;font-weight:800;margin:0;letter-spacing:-.02em}
  .tf-hero p{font-size:13px;font-weight:500;margin:4px 0 0;opacity:.9}
  .tf-hero-r{display:flex;align-items:center;gap:10px;position:relative;z-index:1}
  .tf-btn{background:#fff;color:var(--g2);border:none;border-radius:10px;padding:9px 16px;font-weight:700;cursor:pointer;font:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
  .tf-btn:hover{background:#eafaf1}
  .tf-btn.ghost{background:rgba(255,255,255,.16);color:#fff}
  .tf-btn.ghost:hover{background:rgba(255,255,255,.28)}
  .tf-badge{background:#fff;color:var(--g2);border-radius:20px;padding:3px 11px;font-size:12px;font-weight:800}

  /* Add bar */
  .tf-add{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;padding:16px 18px;margin-bottom:20px;
    background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 16px 36px -24px rgba(24,36,60,.35)}
  .tf-f{display:flex;flex-direction:column;gap:5px}
  .tf-f label{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.03em;display:flex;align-items:center;gap:7px}
  .tf-tag{background:#d6f5df;color:var(--g2);border-radius:6px;padding:1px 7px;font-size:10px;font-weight:800;text-transform:none}
  .tf-tag.acc{background:#e7eefc;color:#3557b7}
  .tf-f input,.tf-f select{border:1.5px solid var(--line);border-radius:10px;padding:9px 11px;font:inherit;font-size:13px;min-height:40px;background:#fff}
  .tf-f input:focus,.tf-f select:focus{outline:none;border-color:var(--greenln);box-shadow:0 0 0 3px rgba(40,167,69,.12)}
  .tf-f input[readonly]{background:#f4f7fa;color:#42506a}
  .tf-f-farmer{flex:1 1 230px;min-width:210px}.tf-f-farmer input{width:100%}
  .tf-f-center{flex:1 1 200px;min-width:180px}.tf-f-center select{width:100%}
  .tf-f-med{flex:1 1 190px;min-width:170px}.tf-f-med input{width:100%}
  .tf-f-date input{width:150px}.tf-f-qty input{width:100px}
  .tf-btn-add{background:linear-gradient(135deg,var(--g1),var(--g2));color:#fff;min-height:40px;padding:10px 20px;border:0;border-radius:10px;font-weight:700;cursor:pointer;font:inherit}
  .tf-btn-add:hover{background:linear-gradient(135deg,#12a06f,#0a6f49)}
  .tf-btn-add:disabled{opacity:.5;cursor:not-allowed}
  .tf-addnote{flex:1 1 100%;font-size:12px;font-weight:700;color:var(--amber);margin-top:-2px}

  /* combo picker */
  .tf-combo{position:relative}
  .tf-combo-list{position:absolute;z-index:60;top:calc(100% + 5px);left:0;right:0;max-height:260px;overflow:auto;background:#fff;
    border:1px solid var(--line);border-radius:12px;box-shadow:0 18px 40px -18px rgba(24,36,60,.4);display:none}
  .tf-combo-list.show{display:block}
  .tf-combo-list .opt{padding:10px 13px;cursor:pointer;font-size:13px;font-weight:600;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:10px}
  .tf-combo-list .opt:last-child{border-bottom:0}
  .tf-combo-list .opt:hover,.tf-combo-list .opt.active{background:#f1faf5;color:var(--g2)}
  .tf-combo-list .opt .sub{color:#93a1b5;font-weight:700;font-size:11.5px;white-space:nowrap}
  .tf-combo-list .none{padding:13px;color:#93a1b5;font-weight:600;font-size:13px}

  /* center cards */
  .tf-centers{display:grid;grid-template-columns:repeat(auto-fill,minmax(560px,1fr));gap:18px}
  .tf-card{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 16px 36px -24px rgba(24,36,60,.35);overflow:hidden;transition:box-shadow .15s,outline-color .15s;outline:2px solid transparent}
  .tf-card.drop-hot{outline-color:var(--greenln);box-shadow:0 0 0 4px rgba(40,167,69,.15),0 16px 36px -18px rgba(10,111,73,.5)}
  .tf-card-h{padding:14px 16px;border-bottom:1px solid var(--line);display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:8px;background:linear-gradient(180deg,#f6fbf8,#fff)}
  .tf-card-h .cname{grid-column:2;text-align:center;font-weight:800;font-size:16px;color:var(--ink)}
  .tf-card-h .lstate{grid-column:3;justify-self:end;display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:flex-end}
  .tf-pill{border-radius:20px;padding:3px 10px;font-size:11px;font-weight:800;white-space:nowrap}
  .tf-pill.active{background:#e6f7ee;color:var(--g2)}
  .tf-pill.frozen{background:#fff7ed;color:var(--amber);border:1px solid #fed7aa}
  .tf-pill.locked{background:#eef2f7;color:#5f6b7d;border:1px solid #d9e0ea}
  .tf-pill.future{background:#f4f7fb;color:#93a1b5;border:1px dashed #cdd8e6}
  .tf-mini{border:0;border-radius:8px;padding:5px 10px;font-size:11.5px;font-weight:800;cursor:pointer;font:inherit;display:inline-flex;align-items:center;gap:5px}
  .tf-mini.lock{background:var(--g2);color:#fff}.tf-mini.lock:hover{background:#0a6f49}
  .tf-mini.relock{background:var(--amber);color:#fff}
  .tf-mini.unlock{background:#eef2f7;color:#42506a}.tf-mini.unlock:hover{background:#e2e8f0}
  table.tf-tbl{width:100%;border-collapse:collapse;font-size:13px}
  .tf-tbl th,.tf-tbl td{padding:8px 10px;border-bottom:1px solid var(--line);text-align:left;vertical-align:middle}
  .tf-tbl th{background:#f6f9fc;font-weight:700;color:#42506a;font-size:11px;text-transform:uppercase;letter-spacing:.02em;white-space:nowrap}
  .tf-tbl td.num,.tf-tbl th.num{text-align:right;font-variant-numeric:tabular-nums}
  .tf-tbl tbody tr.editable{background:var(--green)}
  .tf-tbl tbody tr.editable:hover{background:#c9f0d5}
  .tf-tbl tbody tr.locked{background:#fbfcfe;color:#5b6b82}
  .tf-tbl tbody tr[draggable=true]{cursor:grab}
  .tf-tbl tbody tr.dragging{opacity:.4}
  .tf-drag{color:#9fb0c4;cursor:grab;font-weight:800;padding-right:2px}
  .tf-drag.off{color:#dbe2ea;cursor:not-allowed}
  .tf-act{display:inline-flex;gap:4px}
  .tf-ic{border:0;border-radius:7px;width:26px;height:26px;cursor:pointer;font-size:12px;line-height:1;display:inline-flex;align-items:center;justify-content:center}
  .tf-ic.v{background:#eef2fc;color:#3557b7}.tf-ic.e{background:#e6f7ee;color:var(--g2)}
  .tf-ic.d{background:#fdecec;color:var(--red)}.tf-ic.rq{background:#fff7ed;color:var(--amber);width:auto;padding:0 8px;font-size:11px;font-weight:800}
  .tf-tbl tfoot td{font-weight:800;background:#f0f7f2;border-top:2px solid var(--greenln)}
  .tf-cardempty{padding:20px;text-align:center;color:#93a1b5;font-weight:600;font-size:13px}
  .tf-empty{padding:40px;text-align:center;color:var(--muted);font-weight:600;background:#fff;border:1px dashed var(--line);border-radius:16px}
  .tf-lockbar{padding:9px 16px;background:#f6fbf8;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:12px;font-weight:700;color:var(--muted)}

  /* modal */
  .tf-modal{position:fixed;inset:0;z-index:1200;display:none;align-items:center;justify-content:center;background:rgba(11,30,45,.55);backdrop-filter:blur(3px);padding:16px}
  .tf-modal.show{display:flex;animation:tfmfade .16s ease}
  @keyframes tfmfade{from{opacity:0}to{opacity:1}}
  .tf-modal-box{background:#fff;border-radius:20px;width:min(560px,96vw);max-height:92vh;overflow:hidden;box-shadow:0 40px 90px -25px rgba(0,0,0,.55);display:flex;flex-direction:column;animation:tfmpop .22s cubic-bezier(.2,.9,.3,1.25)}
  @keyframes tfmpop{from{transform:translateY(14px) scale(.97);opacity:0}to{transform:none;opacity:1}}
  .tf-modal-h{position:relative;padding:18px 22px;color:#fff;background:linear-gradient(135deg,var(--g1),var(--g2));display:flex;align-items:center;gap:12px;font-weight:800;font-size:17px}
  .tf-modal-h .ic{width:38px;height:38px;border-radius:11px;background:rgba(255,255,255,.20);display:grid;place-items:center;font-size:16px;flex:0 0 auto}
  .tf-modal-h .htxt{display:flex;flex-direction:column;line-height:1.25}
  .tf-modal-h .htxt small{font-size:11px;font-weight:700;opacity:.85;text-transform:none;letter-spacing:0}
  .tf-modal-h .x{margin-left:auto;cursor:pointer;color:#fff;font-size:22px;line-height:1;border:0;background:rgba(255,255,255,.16);width:32px;height:32px;border-radius:9px}
  .tf-modal-h .x:hover{background:rgba(255,255,255,.30)}
  .tf-modal-b{padding:20px 22px;display:flex;flex-direction:column;gap:14px;overflow:auto}
  .tf-modal-b .tf-f{width:100%}.tf-modal-b .tf-f input,.tf-modal-b .tf-f select{width:100%}
  .tfm-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  @media (max-width:480px){.tfm-grid{grid-template-columns:1fr}}
  .tf-modal-f{padding:15px 22px;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:10px;background:#fbfdff}
  .tf-modal-f .tf-btn{border:1.5px solid var(--line);padding:10px 18px}
  .tf-modal-f .tf-btn-add{display:inline-flex;align-items:center;gap:7px}
  .tf-spin{display:none;position:fixed;top:78px;right:24px;z-index:1300;background:#fff;border:1px solid var(--line);border-radius:10px;padding:8px 14px;font-size:12px;font-weight:800;color:var(--g2);box-shadow:0 12px 30px -12px rgba(0,0,0,.3)}
  .tf-spin.show{display:flex;align-items:center;gap:8px}
  .tf-spin .dot{width:14px;height:14px;border:2px solid #d6f5df;border-top-color:var(--g2);border-radius:50%;animation:tfspin .7s linear infinite}
  @keyframes tfspin{to{transform:rotate(360deg)}}
  @media (max-width:640px){.tf-centers{grid-template-columns:1fr}.tf-card-h{grid-template-columns:1fr;text-align:center}.tf-card-h .cname,.tf-card-h .lstate{grid-column:1;justify-self:center}}
  .tf-avail{display:inline-block;margin-left:6px;padding:1px 8px;border-radius:20px;background:#e7f6ee;color:#0a6f49;font-size:11px;font-weight:800}
  .tf-avail.zero{background:#fdeaea;color:#c0392b}
  .tf-qtyerr{margin-top:5px;color:#c0392b;font-size:11.5px;font-weight:800;line-height:1.3}
  input.tf-invalid{border-color:#c0392b !important;box-shadow:0 0 0 3px rgba(192,57,43,.14) !important}
  .tf-inpwrap{position:relative}
  .tf-inpwrap>input{width:100%;padding-right:34px !important}
  .tf-clear{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:22px;height:22px;border-radius:50%;background:#eef2f7;color:#6b7a90;display:none;align-items:center;justify-content:center;cursor:pointer;font-size:17px;line-height:1;user-select:none}
  .tf-clear:hover{background:#fdeaea;color:#c0392b}
  .tf-clear.show{display:flex}
  .tf-datenav{display:flex;align-items:center;gap:4px}
  .tf-datenav input{flex:1 1 auto;min-width:0}
  .tf-datebtn{flex:0 0 auto;width:34px;min-height:40px;border:1.5px solid var(--line);border-radius:9px;background:#fff;color:var(--g2);font-size:20px;font-weight:800;cursor:pointer;line-height:1;padding:0}
  .tf-datebtn:hover{background:#eafaf1;border-color:var(--greenln)}
  .tf-datebtn:disabled{opacity:.35;cursor:not-allowed}
</style>

<main class="main-content bgc-grey-100 tf">
  <div class="tf-shell">

    <div class="tf-hero">
      <div>
        <h4>Temp Farmer Thumb Management</h4>
        <p>Center-wise daily thumb sheet with sequential date-locking<?= $cur_fy ? ' &nbsp;•&nbsp; FY ' . html_escape($cur_fy) : '' ?></p>
      </div>
      <div class="tf-hero-r">
        <?php if ($is_super): ?>
          <a class="tf-btn" href="<?= base_url('admin/accountMapping/temp_unlock_requests') ?>">
            <i class="ti-key"></i> Unlock Requests<?= $pending_count ? ' (' . (int) $pending_count . ')' : '' ?>
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Add bar -->
    <div class="tf-add">
      <div class="tf-f tf-f-farmer tf-combo">
        <label>Farmer ID / Name <?php if ($cur_fy): ?><span class="tf-tag">FY <?= html_escape($cur_fy) ?></span><?php endif; ?></label>
        <div class="tf-inpwrap">
          <input type="text" id="tfFarmerInp" placeholder="Search farmer by ID or name…" autocomplete="off">
          <span class="tf-clear" id="tfFarmerClear" title="Clear and choose another farmer">&times;</span>
        </div>
        <input type="hidden" id="tfFarmerId"><input type="hidden" id="tfFarmerName">
        <div class="tf-combo-list" id="tfFarmerList"></div>
      </div>
      <div class="tf-f tf-f-center">
        <label>Center * <span class="tf-tag" id="tfCenterLock" style="display:none;background:#fdeaea;color:#c0392b">locked to farmer</span></label>
        <select id="tfCenter">
          <option value="0">— Select Center —</option>
          <?php if (!empty($center_list)): foreach ($center_list as $c): ?>
            <option value="<?= (int) $c->center_id ?>"><?= html_escape($c->name) ?></option>
          <?php endforeach; endif; ?>
        </select>
      </div>
      <div class="tf-f tf-f-date">
        <label>Date <span class="tf-tag" id="tfDateTag" style="display:none">open</span></label>
        <div class="tf-datenav">
          <button type="button" class="tf-datebtn" id="tfDatePrev" title="Previous date">&lsaquo;</button>
          <input type="date" id="tfDate">
          <button type="button" class="tf-datebtn" id="tfDateNext" title="Next date">&rsaquo;</button>
        </div>
      </div>
      <div class="tf-f tf-f-qty">
        <label>Qty * <span class="tf-avail" id="tfQtyAvail" style="display:none"></span></label>
        <input type="number" id="tfQty" step="0.01" min="0" placeholder="0.00">
        <div class="tf-qtyerr" id="tfQtyErr" style="display:none"></div>
      </div>
      <div class="tf-f tf-f-med tf-combo">
        <label>Mediator <span class="tf-tag acc">Account</span></label>
        <input type="text" id="tfMed" placeholder="Search account name…" autocomplete="off">
        <div class="tf-combo-list" id="tfMedList"></div>
      </div>
      <div class="tf-f">
        <label>&nbsp;</label>
        <button type="button" class="tf-btn-add" id="tfAddBtn">+ Add</button>
      </div>
      <div class="tf-addnote" id="tfAddNote" style="display:none"></div>
    </div>

    <div class="tf-centers" id="tfCenters"></div>
    <div class="tf-empty" id="tfEmpty" style="display:none">No temp-thumb records yet. Pick a center and add the first farmer above.</div>

  </div>
</main>

<!-- Edit / View modal -->
<div class="tf-modal" id="tfModal">
  <div class="tf-modal-box">
    <div class="tf-modal-h">
      <span class="ic"><i class="ti-pencil" id="tfmIc"></i></span>
      <span class="htxt"><span id="tfmTitle">Edit Entry</span><small>Temp farmer thumb sheet</small></span>
      <button class="x" id="tfmClose">&times;</button>
    </div>
    <div class="tf-modal-b">
      <input type="hidden" id="tfmId">
      <div class="tfm-grid">
        <div class="tf-f"><label>Date</label><input type="text" id="tfmDate" readonly></div>
        <div class="tf-f"><label>Qty <span class="tf-avail" id="tfmQtyAvail" style="display:none"></span></label><input type="number" id="tfmQty" step="0.01" min="0" placeholder="0.00"><div class="tf-qtyerr" id="tfmQtyErr" style="display:none"></div></div>
      </div>
      <div class="tf-f tf-combo"><label>Farmer ID / Name</label>
        <input type="text" id="tfmFarmerInp" autocomplete="off" placeholder="Search farmer by ID or name…"><input type="hidden" id="tfmFarmerId"><input type="hidden" id="tfmFarmerName">
        <div class="tf-combo-list" id="tfmFarmerList"></div>
      </div>
      <div class="tfm-grid">
        <div class="tf-f tf-combo"><label>Mediator (Account)</label>
          <input type="text" id="tfmMed" autocomplete="off" placeholder="Search account…"><div class="tf-combo-list" id="tfmMedList"></div>
        </div>
        <div class="tf-f"><label>Center</label>
          <select id="tfmCenter">
            <?php if (!empty($center_list)): foreach ($center_list as $c): ?>
              <option value="<?= (int) $c->center_id ?>"><?= html_escape($c->name) ?></option>
            <?php endforeach; endif; ?>
          </select>
        </div>
      </div>
    </div>
    <div class="tf-modal-f">
      <button class="tf-btn" id="tfmCancel"><i class="ti-close"></i> Cancel</button>
      <button class="tf-btn-add" id="tfmSave"><i class="ti-check"></i> Save Changes</button>
    </div>
  </div>
</div>

<div class="tf-spin" id="tfSpin"><span class="dot"></span> Working…</div>

<script>
(function ($) {
  var TF_BASE = "<?= base_url() ?>";
  var TF_UID  = <?= $uid ?>;
  var TF_IS_SUPER = <?= $is_super ? 'true' : 'false' ?>;
  var TF_CENTERS = <?= json_encode(!empty($centers) ? $centers : array(), JSON_UNESCAPED_UNICODE) ?>;
  var TF_CENTER_LIST = <?= json_encode(!empty($center_list) ? $center_list : array(), JSON_UNESCAPED_UNICODE) ?>;
  var TODAY = "<?= date('Y-m-d') ?>";

  function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
  function num(n){ return (parseFloat(n)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
  function fmt(ymd){ if(!ymd) return ''; var p=String(ymd).split('-'); return p.length===3?p[2]+'-'+p[1]+'-'+p[0]:ymd; }
  function toast(t,m,ti){ if(window.showToast){ showToast(t,m,ti||''); } }
  function spin(on){ $('#tfSpin').toggleClass('show', !!on); }

  // The date a given center is currently editable on (null = blocked for me).
  function editableDate(c){
    if(!c || !c.lock) return null;
    if(c.lock.mode==='frozen'){ return (parseInt(c.lock.frozen_for,10)===TF_UID || TF_IS_SUPER) ? c.lock.frozen_date : null; }
    return c.lock.active_date;
  }
  function centerById(id){ for(var i=0;i<TF_CENTERS.length;i++){ if(parseInt(TF_CENTERS[i].center_id,10)===parseInt(id,10)) return TF_CENTERS[i]; } return null; }
  function centerName(id){ for(var i=0;i<TF_CENTER_LIST.length;i++){ if(parseInt(TF_CENTER_LIST[i].center_id,10)===parseInt(id,10)) return TF_CENTER_LIST[i].name; } return '#'+id; }

  /* ---------- render ---------- */
  function render(){
    var $wrap = $('#tfCenters').empty();
    if(!TF_CENTERS.length){ $('#tfEmpty').show(); return; }
    $('#tfEmpty').hide();
    var vd = $('#tfDate').val();   // the date currently being viewed (date-wise)
    // Count each farmer's TOTAL entries (all centers/dates). A "fresh" farmer
    // (only this one entry) may be dragged to another center; once allocated
    // (has more than one entry) they are pinned to their center.
    var farmerCount = {};
    TF_CENTERS.forEach(function(c){ (c.rows||[]).forEach(function(r){ farmerCount[r.farmer_id]=(farmerCount[r.farmer_id]||0)+1; }); });

    TF_CENTERS.forEach(function(c){
      var lock = c.lock || {mode:'normal'};
      var edit = editableDate(c);   // this center's OPEN date (null if frozen for others)
      // Header reflects the VIEWED date's status for THIS center, so it matches the rows.
      var pill, tool = '';
      if(lock.mode==='frozen'){
        pill = '<span class="tf-pill frozen"><i class="ti-lock"></i> Unlocked '+fmt(lock.frozen_date)+'</span>';
        // The user it was unlocked for re-locks their own date; Super Admin may override.
        if(parseInt(lock.frozen_for,10)===TF_UID || TF_IS_SUPER)
          tool = '<button class="tf-mini relock" data-act="relock" data-c="'+c.center_id+'" data-d="'+lock.frozen_date+'"><i class="ti-lock"></i> Re-lock '+fmt(lock.frozen_date)+'</button>';
      } else if(edit && vd===edit){                 // viewing the OPEN date → add + lock
        pill = '<span class="tf-pill active">Open '+fmt(edit)+'</span>';
        tool = '<button class="tf-mini lock" data-act="lock" data-c="'+c.center_id+'" data-d="'+edit+'"><i class="ti-lock"></i> Lock '+fmt(edit)+'</button>';
      } else if(edit && vd && vd<edit){             // viewing a finalized (locked) day
        pill = '<span class="tf-pill locked"><i class="ti-lock"></i> Locked '+fmt(vd)+'</span>';
      } else if(edit && vd && vd>edit){             // a day not open yet
        pill = '<span class="tf-pill future">Not open '+fmt(vd)+'</span>';
      } else {                                       // no date / fresh center
        pill = '<span class="tf-pill active">Open '+fmt(edit||lock.active_date)+'</span>';
        if(edit) tool = '<button class="tf-mini lock" data-act="lock" data-c="'+c.center_id+'" data-d="'+edit+'"><i class="ti-lock"></i> Lock '+fmt(edit)+'</button>';
      }

      // Date-wise view: show ONLY the rows for the currently-navigated date (vd).
      var rows = (c.rows||[]).filter(function(r){ return !vd || r.entry_date===vd; })
                             .sort(function(a,b){ return (a.entry_date<b.entry_date?-1:a.entry_date>b.entry_date?1:a.id-b.id); });
      var body='', i=0, sum=0;
      rows.forEach(function(r){
        i++; sum += parseFloat(r.qty)||0;
        var canEdit = edit && r.entry_date===edit;
        var isFresh = (farmerCount[r.farmer_id]||0) <= 1;   // only one entry → can be moved
        var canDrag = canEdit && isFresh;
        var isLockedRow = !canEdit && lock.mode==='normal' && r.entry_date < (lock.active_date||'9999');
        var acts = '<button class="tf-ic v" data-act="view" data-id="'+r.id+'" title="View">&#128065;</button>';
        if(canEdit){
          acts += '<button class="tf-ic e" data-act="edit" data-id="'+r.id+'" title="Edit">&#9998;</button>'
                + '<button class="tf-ic d" data-act="del" data-id="'+r.id+'" title="Delete">&times;</button>';
        } else if(isLockedRow){
          acts += '<button class="tf-ic rq" data-act="requnlock" data-c="'+c.center_id+'" data-d="'+r.entry_date+'" title="Request unlock">Request Unlock</button>';
        }
        body += '<tr class="'+(canEdit?'editable':'locked')+'" '+(canDrag?'draggable="true"':'')+' data-id="'+r.id+'" data-c="'+c.center_id+'">'
             + '<td>'+(canDrag?'<span class="tf-drag" title="Drag to move this farmer to another center">&#8942;&#8942;</span>':(canEdit?'<span class="tf-drag off" title="Already allocated to this center — cannot be moved">&#8942;&#8942;</span>':''))+i+'</td>'
             + '<td>'+fmt(r.entry_date)+'</td>'
             + '<td>'+esc(r.farmer_id)+'</td>'
             + '<td>'+esc(r.farmer_name)+'</td>'
             + '<td class="num">'+num(r.qty)+'</td>'
             + '<td>'+esc(r.mediator_name||'')+'</td>'
             + '<td><span class="tf-act">'+acts+'</span></td></tr>';
      });
      if(!body){ body = '<tr><td colspan="7" class="tf-cardempty">No records'+(vd?(' on '+fmt(vd)):'')+' for this center.</td></tr>'; }

      var card = '<div class="tf-card" data-center="'+c.center_id+'">'
        + '<div class="tf-card-h"><span></span><div class="cname">'+esc(c.center_name||centerName(c.center_id))+'</div>'
        + '<div class="lstate">'+pill+tool+'</div></div>'
        + '<div style="overflow-x:auto"><table class="tf-tbl"><thead><tr>'
        + '<th style="width:60px">S.No</th><th>Date</th><th>Farmer ID</th><th>Farmer Name</th>'
        + '<th class="num" style="width:90px">Qty</th><th>Mediator</th><th style="width:150px">Actions</th>'
        + '</tr></thead><tbody>'+body+'</tbody>'
        + (rows.length?'<tfoot><tr><td colspan="4">Total</td><td class="num">'+num(sum)+'</td><td colspan="2"></td></tr></tfoot>':'')
        + '</table></div></div>';
      $wrap.append(card);
    });
    bindDnD();
  }
  function refresh(payload){ if(payload){ TF_CENTERS = payload; } render(); }

  /* ---------- add bar: center → date behaviour + Prev/Next navigation ---------- */
  var $addNote = $('#tfAddNote');
  // The date OPEN for adding at a center (null = frozen for another user, ''=fresh→today).
  function centerOpenDate(cid){
    if(!cid) return '';
    var c = centerById(cid);
    // FRESH center (no records/locks yet) → it can be STARTED on any date you're
    // viewing, so treat the viewed date as its open date (no "lock first" warning).
    if(!c) return $('#tfDate').val() || TODAY;
    return editableDate(c);           // active date, or the frozen date for the approved user
  }
  // Safe local-date +/- days (avoid UTC/toISOString off-by-one on IST).
  function ymdShift(ymd, delta){
    var p=(ymd||TODAY).split('-'); var d=new Date(+p[0], (+p[1])-1, +p[2]); d.setDate(d.getDate()+delta);
    return d.getFullYear()+'-'+('0'+(d.getMonth()+1)).slice(-2)+'-'+('0'+d.getDate()).slice(-2);
  }
  // Prev/Next are pure DATE-WISE VIEW navigation — ALWAYS enabled so you can
  // browse any date's data. Only the Add button is gated by the lock rules.
  function refreshDateState(){
    $('#tfDatePrev,#tfDateNext').prop('disabled', false);
    var cid = parseInt($('#tfCenter').val(),10)||0;
    var cur = $('#tfDate').val();
    if(!cid){ $('#tfDateTag').hide(); $addNote.hide(); $('#tfAddBtn').prop('disabled',false); return; }
    var open = centerOpenDate(cid);
    if(open===null){ // temporarily unlocked for someone else
      $('#tfDateTag').hide(); $addNote.text('This center is unlocked for another user — adding is blocked until it is re-locked.').show();
      $('#tfAddBtn').prop('disabled',true); return;
    }
    if(cur===open){ $('#tfDateTag').show(); $addNote.hide(); $('#tfAddBtn').prop('disabled',false); }
    else if(cur < open){ $('#tfDateTag').hide(); $addNote.text('Viewing a locked date — review only. Go to the open date '+fmt(open)+' to add.').show(); $('#tfAddBtn').prop('disabled',true); }
    else { $('#tfDateTag').hide(); $addNote.text('This date is not open for adding yet — lock the open date ('+fmt(open)+') first.').show(); $('#tfAddBtn').prop('disabled',true); }
  }
  // Selecting a center jumps to its open date; then gate everything.
  function syncAddDate(){
    var cid = parseInt($('#tfCenter').val(),10)||0;
    if(!cid){ $('#tfDate').val(''); refreshDateState(); return; }
    var open = centerOpenDate(cid);
    if(open===null){ var c=centerById(cid); open=(c&&c.lock)?c.lock.frozen_date:''; }
    $('#tfDate').val(open || TODAY);
    refreshDateState();
  }
  // The most recent date that has any records — the default date-wise view on load.
  function latestDataDate(){ var mx=''; TF_CENTERS.forEach(function(c){ (c.rows||[]).forEach(function(r){ if(r.entry_date>mx) mx=r.entry_date; }); }); return mx || TODAY; }
  function gotoDate(ymd){ $('#tfDate').val(ymd); refreshDateState(); render(); }
  $('#tfCenter').on('change', function(){ syncAddDate(); render(); });
  $('#tfDate').on('change', function(){ refreshDateState(); render(); });
  $('#tfDatePrev').on('click', function(){ gotoDate(ymdShift($('#tfDate').val(), -1)); });
  $('#tfDateNext').on('click', function(){ if($(this).prop('disabled')) return; gotoDate(ymdShift($('#tfDate').val(), 1)); });

  /* ---------- pickers (farmer + account) ---------- */
  function attachFarmer(inpSel, listSel, idSel, nameSel, onPick){
    var $inp=$(inpSel),$list=$(listSel),req=null,tmr=null;
    function runSearch(){
      var q=$inp.val(); clearTimeout(tmr);
      tmr=setTimeout(function(){ if(req)req.abort();
        req=$.ajax({url:TF_BASE+'admin/accountMapping/thumb_farmer_search',type:'POST',dataType:'json',data:{q:q},
          success:function(rows){ rows=rows||[];
            if(!rows.length){ $list.html('<div class="none">No matching farmer.</div>').addClass('show'); return; }
            $list.html(rows.map(function(r){ var sub=(r.center_name?esc(r.center_name)+' · ':'')+'#'+esc(r.farmer_id);
              return '<div class="opt" data-id="'+esc(r.farmer_id)+'" data-name="'+esc(r.farmer_name)+'"><span>'+esc(r.farmer_name||'—')+'</span><span class="sub">'+sub+'</span></div>'; }).join('')).addClass('show');
          }});
      },180);
    }
    // Focus: if a farmer is already chosen, keep it and just select the text so the
    // user can type over it (no needless "No matching farmer" flash).
    $inp.on('focus',function(){ if($(idSel).val()){ this.select(); return; } runSearch(); });
    // Typing clears the current selection and searches live (type an ID or name).
    $inp.on('input',function(){ $(idSel).val(''); $(nameSel).val(''); if(typeof onPick==='function'){ onPick(''); } runSearch(); });
    $list.on('click','.opt',function(){ var $o=$(this); $(idSel).val($o.data('id')); $(nameSel).val($o.data('name'));
      $inp.val($o.data('name')+'  (#'+$o.data('id')+')'); $list.removeClass('show');
      if(typeof onPick==='function'){ onPick(String($o.data('id'))); } });
  }
  function attachAccount(inpSel, listSel){
    var $inp=$(inpSel),$list=$(listSel),req=null,tmr=null;
    $inp.on('input focus',function(){ if(this.readOnly){ return; } var q=$inp.val(); clearTimeout(tmr);
      tmr=setTimeout(function(){ if(req)req.abort();
        req=$.ajax({url:TF_BASE+'admin/accountMapping/thumb_account_search',type:'POST',dataType:'json',data:{q:q},
          success:function(rows){ rows=rows||[];
            if(!rows.length){ $list.html('<div class="none">No matching account.</div>').addClass('show'); return; }
            $list.html(rows.map(function(a){ return '<div class="opt" data-name="'+esc(a.name)+'"><span>'+esc(a.name)+'</span><span class="sub">#'+esc(a.id)+'</span></div>'; }).join('')).addClass('show');
          }});
      },180);
    });
    $list.on('click','.opt',function(){ $inp.val($(this).data('name')); $list.removeClass('show'); });
  }
  attachFarmer('#tfFarmerInp','#tfFarmerList','#tfFarmerId','#tfFarmerName', loadAddFarmerQty);
  attachAccount('#tfMed','#tfMedList');
  attachFarmer('#tfmFarmerInp','#tfmFarmerList','#tfmFarmerId','#tfmFarmerName', loadEditFarmerQty);
  attachAccount('#tfmMed','#tfmMedList');
  $(document).on('click',function(e){ if(!$(e.target).closest('.tf-combo').length){ $('.tf-combo-list').removeClass('show'); } });

  /* ---------- live qty cap (available = registration left-quantity − already used) ---------- */
  var addAvail=null, addCap=false, editAvail=null, editCap=false;
  function loadFarmerQty(fid, ignoreId, cb){
    if(!fid){ cb(null); return; }
    $.ajax({url:TF_BASE+'admin/accountMapping/thumb_farmer_qty',type:'POST',dataType:'json',
      data:{farmer_id:fid, ignore_id:ignoreId||0},
      success:function(r){ cb(r&&r.status==='success'?r:null); },
      error:function(){ cb(null); }});
  }
  function showAvail($tag, cap, rem){
    if(!cap){ $tag.hide(); return; }
    $tag.text('Available: '+num(rem)).toggleClass('zero', rem<=0).show();
  }
  function lockCenter(){ $('#tfCenter').prop('disabled',true); $('#tfCenterLock').show(); }
  function unlockCenter(){ $('#tfCenter').prop('disabled',false); $('#tfCenterLock').hide(); }
  function lockMed(v){ $('#tfMed').val(v).prop('readonly',true); $('#tfMedLock').show(); $('#tfMedList').removeClass('show'); }
  function unlockMed(){ $('#tfMed').prop('readonly',false); $('#tfMedLock').hide(); }
  function clearAddFarmer(){
    $('#tfFarmerId').val(''); $('#tfFarmerName').val(''); $('#tfFarmerInp').val('');
    $('#tfFarmerClear').removeClass('show'); $('#tfFarmerList').removeClass('show');
    addCap=false; addAvail=null; $('#tfQtyAvail').hide(); $('#tfQtyErr').hide(); $('#tfQty').removeClass('tf-invalid');
    $('#tfMed').val(''); unlockMed(); unlockCenter();
    $('#tfFarmerInp').focus();
  }
  $('#tfFarmerClear').on('click', function(e){ e.stopPropagation(); clearAddFarmer(); });
  function loadAddFarmerQty(fid){
    addAvail=null; addCap=false; $('#tfQtyAvail').hide(); unlockCenter(); unlockMed(); validateAddQty();
    $('#tfFarmerClear').toggleClass('show', !!fid);
    if(!fid) return;
    loadFarmerQty(fid, 0, function(r){
      if(!r) return;
      addCap=!!r.has_cap; addAvail=parseFloat(r.remaining);
      showAvail($('#tfQtyAvail'), addCap, addAvail);
      // Mediator: auto-FILL as a convenience but keep it editable (not locked).
      unlockMed();
      if(r.existing_mediator && String(r.existing_mediator).trim()!==''){ $('#tfMed').val(r.existing_mediator); }
      // Center: auto-select + lock, but DON'T jump the date — keep the user on the
      // date they're viewing. refreshDateState() then tells them if it's the
      // farmer's open date (Add enabled) or to move to it (Add disabled + note).
      if(parseInt(r.existing_center,10)>0){
        $('#tfCenter').val(String(r.existing_center)); lockCenter();
      } else { unlockCenter(); }
      refreshDateState();
      validateAddQty();
    });
  }
  function validateAddQty(){
    var v=parseFloat($('#tfQty').val())||0;
    if(addCap && addAvail!==null && v>addAvail+0.0001){
      $('#tfQtyErr').text('Not allowed — quantity more than available ('+num(addAvail)+').').show();
      $('#tfQty').addClass('tf-invalid'); return false;
    }
    $('#tfQtyErr').hide(); $('#tfQty').removeClass('tf-invalid'); return true;
  }
  function loadEditFarmerQty(fid){
    editAvail=null; editCap=false; $('#tfmQtyAvail').hide(); validateEditQty();
    if(!fid) return;
    var ignore=parseInt($('#tfmId').val(),10)||0;
    loadFarmerQty(fid, ignore, function(r){ if(!r) return; editCap=!!r.has_cap; editAvail=parseFloat(r.remaining); showAvail($('#tfmQtyAvail'),editCap,editAvail); validateEditQty(); });
  }
  function validateEditQty(){
    var v=parseFloat($('#tfmQty').val())||0;
    if(editCap && editAvail!==null && v>editAvail+0.0001){
      $('#tfmQtyErr').text('Not allowed — quantity more than available ('+num(editAvail)+').').show();
      $('#tfmQty').addClass('tf-invalid'); return false;
    }
    $('#tfmQtyErr').hide(); $('#tfmQty').removeClass('tf-invalid'); return true;
  }
  $('#tfQty').on('input',validateAddQty);
  $('#tfmQty').on('input',validateEditQty);

  /* ---------- add ---------- */
  $('#tfAddBtn').on('click',function(){
    var cid=$('#tfCenter').val(), date=$('#tfDate').val(), fid=$('#tfFarmerId').val(), fname=$('#tfFarmerName').val();
    if(!cid || cid==='0'){ toast('warning','Pick a center.','Missing'); return; }
    if(!date){ toast('warning','Pick a date.','Missing'); return; }
    if(!fid){ toast('warning','Search and pick a farmer.','Missing'); $('#tfFarmerInp').focus(); return; }
    var qty=$('#tfQty').val(); if(qty===''||isNaN(parseFloat(qty))){ toast('warning','Enter a quantity.','Missing'); return; }
    if(!validateAddQty()){ toast('error','Quantity is more than the available limit.','Not allowed'); $('#tfQty').focus(); return; }
    var $b=$(this).prop('disabled',true); spin(true);
    $.ajax({url:TF_BASE+'admin/accountMapping/temp_add',type:'POST',dataType:'json',
      data:{date:date,center_id:cid,farmer_id:fid,farmer_name:fname,qty:qty,mediator_name:$('#tfMed').val()},
      success:function(res){ $b.prop('disabled',false); spin(false);
        if(res&&res.status==='success'){ refresh(res.centers); toast('success',res.message,'Added');
          $('#tfFarmerId').val('');$('#tfFarmerName').val('');$('#tfFarmerInp').val('');$('#tfQty').val('');$('#tfMed').val('');
          addCap=false; addAvail=null; $('#tfQtyAvail').hide(); $('#tfQtyErr').hide(); $('#tfQty').removeClass('tf-invalid'); unlockCenter(); unlockMed(); $('#tfFarmerClear').removeClass('show');
          syncAddDate(); $('#tfFarmerInp').focus();
        } else { toast('error',res.message||'Could not add.','Error'); }
      }, error:function(){ $b.prop('disabled',false); spin(false); toast('error','Request failed.','Error'); }});
  });

  /* ---------- row actions ---------- */
  $('#tfCenters').on('click','[data-act]',function(){
    var act=$(this).data('act'), id=$(this).data('id');
    if(act==='view'||act==='edit'){ openModal(id, act==='view'); }
    else if(act==='del'){ delRecord(id); }
    else if(act==='lock'){
      var lc=$(this).data('c'), ld=$(this).data('d');
      var doLock=function(){ post('temp_lock',{center_id:lc,date:ld},'Locked'); };
      if(window.showConfirm){ showConfirm('Lock '+fmt(ld)+'?','Once this date is locked it is finalized — you CANNOT add, edit, delete or move any entry for it. To change it later you must request a Super-Admin unlock. Continue?',doLock,null,{okText:'Lock date',type:'warning'}); }
      else if(confirm('Lock '+fmt(ld)+'? You cannot make any changes after locking.')){ doLock(); }
    }
    else if(act==='relock'){ post('temp_relock',{center_id:$(this).data('c'),date:$(this).data('d')},'Re-locked'); }
    else if(act==='requnlock'){ requestUnlock($(this).data('c'),$(this).data('d')); }
  });
  function post(ep,data,ok){ spin(true);
    $.ajax({url:TF_BASE+'admin/accountMapping/'+ep,type:'POST',dataType:'json',data:data,
      success:function(res){ spin(false); if(res&&res.status==='success'){ refresh(res.centers); toast('success',res.message,ok); } else { toast('error',res.message||'Failed','Error'); } },
      error:function(){ spin(false); toast('error','Request failed.','Error'); }});
  }
  function delRecord(id){
    var go=function(){ post('temp_delete',{id:id},'Deleted'); };
    if(window.showConfirm){ showConfirm('Delete entry','Delete this entry? This cannot be undone.',go,null,{okText:'Delete',type:'warning'}); }
    else if(confirm('Delete this entry?')){ go(); }
  }
  function requestUnlock(cid,date){
    var send=function(reason){ if(!reason||!reason.trim()){ toast('warning','A reason is required.','Missing'); return; }
      post('temp_request_unlock',{center_id:cid,date:date,reason:reason},'Requested'); };
    if(window.showPrompt){ showPrompt('Request Unlock','Reason to unlock '+fmt(date)+'?','',send,null,{okText:'Send Request',required:true,requiredMsg:'A reason is required.'}); }
    else { var r=prompt('Reason to unlock '+fmt(date)+'?'); if(r!==null) send(r); }
  }

  /* ---------- edit/view modal ---------- */
  var mView=false;
  function openModal(id, viewOnly){
    mView=!!viewOnly; spin(true);
    $.ajax({url:TF_BASE+'admin/accountMapping/temp_get',type:'POST',dataType:'json',data:{id:id},
      success:function(res){ spin(false);
        if(!res||res.status!=='success'||!res.record){ toast('error','Record not found.','Error'); return; }
        var r=res.record;
        $('#tfmTitle').text(viewOnly?'View Entry':'Edit Entry');
        $('#tfmIc').attr('class', viewOnly?'ti-eye':'ti-pencil');
        $('#tfmId').val(r.id); $('#tfmDate').val(fmt(r.entry_date));
        $('#tfmFarmerId').val(r.farmer_id); $('#tfmFarmerName').val(r.farmer_name);
        $('#tfmFarmerInp').val(r.farmer_name+'  (#'+r.farmer_id+')');
        $('#tfmQty').val(r.qty); $('#tfmMed').val(r.mediator_name||''); $('#tfmCenter').val(String(r.center_id));
        $('#tfModal .tf-modal-b input, #tfModal .tf-modal-b select').prop('disabled', viewOnly);
        $('#tfmSave').toggle(!viewOnly);
        $('#tfmQtyAvail').hide(); $('#tfmQtyErr').hide(); $('#tfmQty').removeClass('tf-invalid');
        if(!viewOnly){ loadEditFarmerQty(String(r.farmer_id)); }
        $('#tfModal').addClass('show');
      }, error:function(){ spin(false); toast('error','Request failed.','Error'); }});
  }
  function closeModal(){ $('#tfModal').removeClass('show'); }
  $('#tfmClose,#tfmCancel').on('click',closeModal);
  $('#tfModal').on('click',function(e){ if(e.target===this) closeModal(); });
  $('#tfmSave').on('click',function(){
    var id=$('#tfmId').val(), fid=$('#tfmFarmerId').val(), fname=$('#tfmFarmerName').val(), qty=$('#tfmQty').val();
    if(!fid){ toast('warning','Pick a farmer.','Missing'); return; }
    if(qty===''||isNaN(parseFloat(qty))){ toast('warning','Enter a quantity.','Missing'); return; }
    if(!validateEditQty()){ toast('error','Quantity is more than the available limit.','Not allowed'); return; }
    spin(true);
    $.ajax({url:TF_BASE+'admin/accountMapping/temp_edit',type:'POST',dataType:'json',
      data:{id:id,farmer_id:fid,farmer_name:fname,qty:qty,mediator_name:$('#tfmMed').val(),center_id:$('#tfmCenter').val()},
      success:function(res){ spin(false);
        if(res&&res.status==='success'){ closeModal(); refresh(res.centers); toast('success',res.message,'Updated'); }
        else { toast('error',res.message||'Could not update.','Error'); }
      }, error:function(){ spin(false); toast('error','Request failed.','Error'); }});
  });

  /* ---------- drag & drop between centers ---------- */
  var dragId=null, dragFrom=null;
  function bindDnD(){
    $('#tfCenters tr[draggable=true]').each(function(){
      this.addEventListener('dragstart',function(e){ dragId=$(this).data('id'); dragFrom=$(this).data('c'); $(this).addClass('dragging'); e.dataTransfer.effectAllowed='move'; });
      this.addEventListener('dragend',function(){ $(this).removeClass('dragging'); $('.tf-card').removeClass('drop-hot'); });
    });
    $('#tfCenters .tf-card').each(function(){
      this.addEventListener('dragover',function(e){ e.preventDefault(); $(this).addClass('drop-hot'); });
      this.addEventListener('dragleave',function(){ $(this).removeClass('drop-hot'); });
      this.addEventListener('drop',function(e){ e.preventDefault(); $(this).removeClass('drop-hot');
        var target=$(this).data('center');
        if(dragId==null || parseInt(target,10)===parseInt(dragFrom,10)){ return; }
        post('temp_move',{id:dragId,center_id:target},'Moved'); dragId=null;
      });
    });
  }

  if(!$('#tfDate').val()){ $('#tfDate').val(latestDataDate()); }
  refreshDateState();
  render();
})(jQuery);
</script>
