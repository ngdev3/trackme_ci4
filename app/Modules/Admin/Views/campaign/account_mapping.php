<?php
helper(['url', 'form', 'app']);
/**
 * Farmer Account Mapping — किसान खाता नक्शा
 * admin/accountMapping/account_mapping
 *
 * Assign a farmer's pending Kisan Vahi purchases (at a center, current FY/firm)
 * to a ledger account and mark them recorded. Redesigned "am-" green design
 * system: guided 2-step flow, real center names (from center_list, not the old
 * hardcoded Center_1/No_Detail), searchable account picker, client-side
 * validation + a review-and-confirm modal before submitting.
 *
 * Posts: rokad_type (center id), farmer_id, farmer_name, CenterName (display),
 * quantity, amount, account_name ("Name_ID"). Server re-validates everything.
 */
$centers = array();
if (!empty($center_list)) { foreach ($center_list as $c) { $centers[(string) $c->center_id] = $c->name; } }
$accounts = array();
if (!empty($account_list)) { foreach ($account_list as $a) { $accounts[] = array('id' => (int) $a->account_id, 'name' => (string) $a->name); } }
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap">
<style>
  .am{--g1:#0f8a5f;--g2:#0a6f49;--ink:#152238;--muted:#6b7a90;--line:#e7edf5;
      color:var(--ink);padding:26px 22px;font-family:'Plus Jakarta Sans',system-ui,-apple-system,'Segoe UI',sans-serif;-webkit-font-smoothing:antialiased}
  .am *{box-sizing:border-box}
  .am-shell{margin:0 auto;max-width:1080px}
  .am-num{font-family:'Sora','Plus Jakarta Sans',sans-serif;font-feature-settings:'tnum' 1;letter-spacing:-.01em}
  @keyframes amUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
  .am-anim{animation:amUp .5s cubic-bezier(.2,.7,.2,1) both}

  .am-hero{position:relative;overflow:hidden;align-items:center;
    background:radial-gradient(1000px 220px at 88% -40%,rgba(255,255,255,.28),transparent 60%),linear-gradient(135deg,#0f8a5f,#0a6f49 60%,#095a3c);
    border-radius:22px;box-shadow:0 24px 50px -18px rgba(10,111,73,.55);color:#fff;display:flex;flex-wrap:wrap;gap:18px;
    justify-content:space-between;margin-bottom:20px;padding:24px 28px}
  .am-hero::before{content:'';position:absolute;width:220px;height:220px;right:-60px;top:-90px;border-radius:50%;border:2px solid rgba(255,255,255,.14)}
  .am-hero-l{align-items:center;display:flex;gap:16px;position:relative;z-index:1}
  .am-hero-ic{align-items:center;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);border-radius:16px;
    display:flex;font-size:27px;height:60px;justify-content:center;width:60px}
  .am-hero h4{font-size:23px;font-weight:800;margin:0;letter-spacing:-.02em}
  .am-hero p{font-size:13px;font-weight:500;margin:4px 0 0;opacity:.9}
  .am-chip{align-items:center;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.22);border-radius:12px;color:#fff;
    display:inline-flex;font-weight:700;gap:8px;padding:9px 14px;font-size:12.5px;position:relative;z-index:1}

  /* NOTE: no overflow:hidden here — it would clip the Farmer/Account search
     dropdowns. The header rounds its own top corners instead. */
  .am-card{background:#fff;border:1px solid var(--line);border-radius:20px;box-shadow:0 18px 40px -24px rgba(24,36,60,.35);margin-bottom:18px}
  /* While a search dropdown is open, lift its card above the page footer and
     sibling cards (the fade-in animation otherwise traps the dropdown below the
     later-painted footer). */
  .am-card.am-open{position:relative;z-index:100}
  .am-card-h{align-items:center;border-bottom:1px solid var(--line);display:flex;gap:12px;padding:15px 22px;background:linear-gradient(180deg,#fbfefc,#f6faf8);border-radius:19px 19px 0 0}
  .am-card-h h5{font-size:15px;font-weight:800;margin:0}
  .am-step{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#12a06f,#0a6f49);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;flex:none}
  .am-card-b{padding:20px 22px}
  .am-grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
  .am-f{display:flex;flex-direction:column;min-width:0;position:relative}
  .am-f label{color:#41506a;font-size:11px;font-weight:700;letter-spacing:.03em;margin-bottom:7px;text-transform:uppercase}
  .am-f input,.am-f select{background:#f7fafc;border:1.6px solid #e1e9f2;border-radius:13px;color:var(--ink);font-size:14px;font-weight:600;
    font-family:inherit;min-height:48px;outline:none;padding:11px 14px;width:100%;transition:border-color .18s,box-shadow .18s,background .18s}
  .am-f input:focus,.am-f select:focus{border-color:var(--g1);background:#fff;box-shadow:0 0 0 4px rgba(15,138,95,.12)}
  .am-f input[readonly]{background:#eef2f6;color:#41506a;cursor:default}
  .am-hint{color:#93a1b5;font-size:11px;font-weight:600;margin-top:5px}

  .am-btn{position:relative;overflow:hidden;align-items:center;border:0;border-radius:13px;cursor:pointer;display:inline-flex;font-family:inherit;
    font-size:14px;font-weight:700;gap:9px;min-height:48px;padding:11px 22px;text-decoration:none;transition:transform .12s,box-shadow .2s,filter .2s;white-space:nowrap}
  .am-btn::after{content:'';position:absolute;top:0;left:-130%;width:55%;height:100%;transform:skewX(-22deg);background:linear-gradient(120deg,transparent,rgba(255,255,255,.4),transparent);transition:left .6s}
  .am-btn:hover::after{left:150%}
  .am-btn:active{transform:translateY(1px) scale(.99)}
  .am-btn[disabled]{opacity:.5;cursor:not-allowed;pointer-events:none;box-shadow:none}
  .am-btn-primary{background:linear-gradient(135deg,#12a06f,#0a6f49);color:#fff;box-shadow:0 14px 26px -10px rgba(15,138,95,.6)}
  .am-btn-primary:hover{color:#fff;transform:translateY(-2px)}
  .am-btn-ghost{background:#eef3fa;color:#4a607d;border:1.5px solid #e1e9f2}.am-btn-ghost:hover{background:#fff;color:#0a6f49;border-color:#bfe3d1}
  .am-btn-danger{background:linear-gradient(135deg,#f43f5e,#be123c);color:#fff;box-shadow:0 14px 26px -10px rgba(225,29,72,.55)}
  .am-btn-danger:hover{color:#fff;transform:translateY(-2px)}
  .am-btn-block{width:100%;justify-content:center}
  /* Map / Unmap segmented toggle */
  .am-modetog{display:inline-flex;background:#eef3fa;border:1.5px solid #e1e9f2;border-radius:12px;padding:3px;gap:3px}
  .am-modebtn{border:0;background:transparent;font-family:inherit;font-weight:700;font-size:12.5px;color:#5b6b80;cursor:pointer;
    border-radius:9px;padding:8px 15px;display:inline-flex;align-items:center;gap:6px;transition:all .15s}
  .am-modebtn:hover{color:#0a6f49}
  .am-modebtn.active{background:#fff;color:#0a6f49;box-shadow:0 3px 8px rgba(24,36,60,.12)}
  .am-modebtn.active[data-mode="unmap"]{color:#be123c}

  /* Farmer summary */
  .am-sum{display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr))}
  .am-sum .cell{background:#f7fafc;border:1px solid #eef2f6;border-radius:14px;padding:13px 15px}
  .am-sum .cell .k{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.03em}
  .am-sum .cell .v{font-size:17px;font-weight:800;margin-top:3px;word-break:break-word}
  .am-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:800}
  .am-badge-pending{background:#fff4e5;color:#b45309}
  .am-badge-done{background:#e7f6ee;color:#0a6f49}
  .am-note{background:#fff8ec;border:1px solid #ffe6bf;color:#8a5a12;border-radius:12px;padding:12px 15px;font-size:13px;font-weight:600;display:flex;gap:9px;align-items:flex-start}

  /* Account searchable picker */
  .am-combo{position:relative}
  .am-combo-list{position:absolute;z-index:60;top:calc(100% + 6px);left:0;right:0;max-height:260px;overflow:auto;background:#fff;
    border:1px solid #e1e9f2;border-radius:13px;box-shadow:0 18px 40px rgba(24,36,60,.18);display:none}
  .am-combo-list.show{display:block}
  .am-combo-list .opt{padding:11px 15px;cursor:pointer;font-size:13.5px;font-weight:600;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:10px}
  .am-combo-list .opt:last-child{border-bottom:0}
  .am-combo-list .opt:hover,.am-combo-list .opt.active{background:#f1faf5;color:#0a6f49}
  .am-combo-list .opt .id{color:#93a1b5;font-weight:700;font-size:12px}
  .am-combo-list .none{padding:14px 15px;color:#93a1b5;font-weight:600;font-size:13px}
  .am-selected{display:none;align-items:center;gap:10px;margin-top:10px;background:#e7f6ee;border:1px solid #bfe3d1;border-radius:12px;padding:10px 14px}
  .am-selected.show{display:flex}
  .am-selected .nm{font-weight:800;color:#0a6f49}
  .am-selected .rm{margin-left:auto;color:#0a6f49;cursor:pointer;font-weight:800;background:none;border:0;font-size:16px;line-height:1}

  .am-foot{display:flex;gap:12px;flex-wrap:wrap;justify-content:flex-end;padding:4px 2px 2px}

  /* confirm modal */
  .amm-back{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(11,30,22,.52);backdrop-filter:blur(3px);opacity:0;transition:opacity .18s;font-family:'Plus Jakarta Sans',sans-serif}
  .amm-back.show{opacity:1}
  .amm{width:100%;max-width:440px;background:#fff;border-radius:22px;overflow:hidden;box-shadow:0 34px 80px -22px rgba(0,0,0,.55);transform:translateY(14px) scale(.97);transition:transform .22s cubic-bezier(.2,.7,.2,1)}
  .amm-back.show .amm{transform:none}
  .amm-h{display:flex;align-items:center;gap:13px;padding:19px 22px;color:#fff;background:linear-gradient(135deg,#12a06f,#0a6f49)}
  .amm-h.amm-h-danger{background:linear-gradient(135deg,#f43f5e,#be123c)}
  .amm-h .ic{width:42px;height:42px;border-radius:13px;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.28);display:flex;align-items:center;justify-content:center;font-size:20px}
  .amm-h h5{margin:0;font-size:17.5px;font-weight:800}
  .amm-h p{margin:3px 0 0;font-size:12px;opacity:.9;font-weight:500}
  .amm-body{padding:16px 22px 4px}
  .amm-row{display:flex;justify-content:space-between;gap:14px;padding:11px 0;border-top:1px solid #eef2f6;font-size:13.5px}
  .amm-row:first-child{border-top:0}
  .amm-row .k{color:#6b7a90;font-weight:600;white-space:nowrap}
  .amm-row .v{color:#152238;font-weight:800;text-align:right;word-break:break-word}
  .amm-f{display:flex;gap:10px;padding:16px 22px 20px}
  .amm-f .am-btn{flex:1;min-height:46px}

  @media(max-width:640px){.am{padding:16px 12px}.am-hero{padding:22px}.am-hero h4{font-size:20px}.am-foot .am-btn{flex:1}}
</style>

<main class="main-content bgc-grey-100 am">
  <div id="mainContent">
    <div class="container-fluid am-shell">
      <?= get_flashdata() ?>

      <section class="am-hero am-anim">
        <div class="am-hero-l">
          <span class="am-hero-ic"><i class="ti-link"></i></span>
          <div>
            <h4>किसान खाता नक्शा &nbsp;<span style="font-weight:600;opacity:.8;font-size:15px">Farmer Account Mapping</span></h4>
            <p>Assign a farmer's pending purchases to a ledger account.</p>
          </div>
        </div>
        <span class="am-chip"><i class="ti-calendar"></i> FY <?= html_escape(fy()->FY) ?></span>
      </section>

      <?php echo form_open('', array('id' => 'amForm', 'autocomplete' => 'off')); ?>
        <!-- hidden authoritative fields submitted to the server -->
        <input type="hidden" name="rokad_type"   id="f_center">
        <input type="hidden" name="farmer_id"    id="f_farmer">
        <input type="hidden" name="farmer_name"  id="f_farmer_name">
        <input type="hidden" name="CenterName"   id="f_center_name">
        <input type="hidden" name="quantity"     id="f_qty">
        <input type="hidden" name="amount"       id="f_amount">
        <input type="hidden" name="account_name" id="f_account">
        <input type="hidden" name="map_action"   id="f_map_action" value="map">

        <!-- STEP 1 -->
        <div class="am-card am-anim" style="animation-delay:.04s">
          <div class="am-card-h">
            <span class="am-step">1</span><h5>Select Center &amp; Farmer</h5>
            <div class="am-modetog" style="margin-left:auto">
              <button type="button" class="am-modebtn active" data-mode="map"><i class="ti-link"></i> Map</button>
              <button type="button" class="am-modebtn" data-mode="unmap"><i class="ti-unlink"></i> Unmap</button>
            </div>
          </div>
          <div class="am-card-b">
            <div class="am-grid">
              <div class="am-f am-combo">
                <label>Center *</label>
                <!-- Holds the center id. Kept as #centerSel so every existing
                     $center.val() / .on('change') / .val('') call still works. -->
                <input type="hidden" id="centerSel">
                <input type="text" id="centerInp" placeholder="Search center by name or ID…" autocomplete="off">
                <div class="am-combo-list" id="centerList"></div>
                <div class="am-selected" id="centerSelected">
                  <span class="ti-check" style="color:#0a6f49"></span>
                  <span class="nm" id="centerSelName"></span>
                  <button type="button" class="rm" id="centerClear" title="Change">&times;</button>
                </div>
              </div>
              <div class="am-f">
                <label>Farmer ID *</label>
                <div class="am-combo" style="width:100%">
                  <input type="text" id="farmerInp" placeholder="Search Farmer ID or name…" disabled autocomplete="off">
                  <div class="am-combo-list" id="farmerListBox"></div>
                </div>
                <span class="am-hint" id="farmerHint">Select a center first.</span>
              </div>
              <div class="am-f" style="justify-content:flex-end">
                <label>&nbsp;</label>
                <button type="button" class="am-btn am-btn-primary am-btn-block" id="loadBtn" disabled><i class="ti-download"></i> Load Farmer</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Farmer summary (hidden until loaded) -->
        <div class="am-card am-anim" id="sumCard" style="display:none">
          <div class="am-card-h"><span class="am-step" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)"><i class="ti-user"></i></span><h5>Farmer Details</h5><span id="sumStatus" style="margin-left:auto"></span></div>
          <div class="am-card-b">
            <div class="am-sum">
              <div class="cell"><div class="k">Farmer Name</div><div class="v" id="sName">—</div></div>
              <div class="cell"><div class="k">Farmer ID</div><div class="v am-num" id="sId">—</div></div>
              <div class="cell"><div class="k">Center</div><div class="v" id="sCenter">—</div></div>
              <div class="cell"><div class="k">Quantity (Qtl)</div><div class="v am-num" id="sQty">—</div></div>
              <div class="cell"><div class="k">Amount</div><div class="v am-num" id="sAmt">—</div></div>
            </div>
          </div>
        </div>

        <!-- STEP 2 -->
        <div class="am-card am-anim" id="acctCard" style="display:none">
          <div class="am-card-h"><span class="am-step">2</span><h5 id="step2Title">Choose Account to Map</h5></div>
          <div class="am-card-b">
            <!-- MAP pane -->
            <div id="mapPane">
              <div class="am-f am-combo">
                <label>Account *</label>
                <input type="text" id="acctInp" placeholder="Search account by name…">
                <div class="am-combo-list" id="acctList"></div>
                <div class="am-selected" id="acctSelected">
                  <span class="ti-check" style="color:#0a6f49"></span>
                  <span class="nm" id="acctSelName"></span>
                  <button type="button" class="rm" id="acctClear" title="Change">&times;</button>
                </div>
                <span class="am-hint">Pick from the list — the account must exist.</span>
              </div>
              <div class="am-foot" style="margin-top:18px">
                <button type="button" class="am-btn am-btn-ghost" id="resetBtn"><i class="ti-reload"></i> Reset</button>
                <button type="button" class="am-btn am-btn-primary" id="mapBtn" disabled><i class="ti-link"></i> Map Account</button>
              </div>
            </div>
            <!-- UNMAP pane -->
            <div id="unmapPane" style="display:none">
              <div class="am-f">
                <label>Currently Mapped Account</label>
                <input type="text" id="curAcct" readonly value="—">
              </div>
              <div class="am-note" style="margin-top:14px"><i class="ti-alert" style="margin-top:2px"></i>
                <span>Unmapping sets this farmer's recorded purchases back to <b>pending</b> and removes the account link, so they can be re-mapped. This does not delete any purchase.</span>
              </div>
              <div class="am-foot" style="margin-top:18px">
                <button type="button" class="am-btn am-btn-ghost" id="resetBtn2"><i class="ti-reload"></i> Reset</button>
                <button type="button" class="am-btn am-btn-danger" id="unmapBtn" disabled><i class="ti-unlink"></i> Unmap Account</button>
              </div>
            </div>
          </div>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</main>

<script>
  var AM_CENTERS = <?= json_encode($centers, JSON_UNESCAPED_UNICODE) ?>;
  var AM_ACCOUNTS = <?= json_encode($accounts, JSON_UNESCAPED_UNICODE) ?>;
  var AM_BASE = "<?= base_url() ?>";

  function amEsc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
  function amINR(n){ return (parseFloat(String(n).replace(/[^0-9.\-]/g,''))||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
  function amToast(t,m,ti){ if(window.showToast){ showToast(t,m,ti||''); } else { alert(m); } }

  $(function(){
    // Submission only happens via the confirm modal — never let a stray Enter
    // in a text field post the form and bypass the review step.
    document.getElementById('amForm').addEventListener('keydown', function(e){
      if (e.key === 'Enter' && e.target && e.target.tagName === 'INPUT') { e.preventDefault(); }
    });

    var $center = $('#centerSel'), $farmer = $('#farmerInp'), $load = $('#loadBtn');
    var farmers = [];     // rows for the current center (searchable dropdown source)
    var farmerMap = {};   // Farmer_ID -> {name, pending}
    var loaded = null;    // last successfully loaded farmer row
    var chosenAccount = null; // {id, name}
    var mode = 'map';     // 'map' | 'unmap'

    /* ---------- Map / Unmap mode toggle ---------- */
    $('.am-modebtn').on('click', function(){ applyMode($(this).data('mode')); });
    function applyMode(m){
      mode = (m === 'unmap') ? 'unmap' : 'map';
      $('#f_map_action').val(mode);
      $('.am-modebtn').removeClass('active');
      $('.am-modebtn[data-mode="' + mode + '"]').addClass('active');
      var unmap = (mode === 'unmap');
      $('#step2Title').text(unmap ? 'Confirm Unmap' : 'Choose Account to Map');
      $('#mapPane').toggle(!unmap);
      $('#unmapPane').toggle(unmap);
      resetLoaded();
      // if a farmer is already picked, reload it under the new mode
      if ($center.val() && $farmer.val().trim() !== '') { doLoad(); }
    }

    /* ---------- Step 1: center → farmers ---------- */
    $center.on('change', function(){
      resetFarmer();
      var cid = $(this).val();
      $('#f_center').val(cid);
      if (!cid){ $farmer.prop('disabled', true); $('#farmerHint').text('Select a center first.'); return; }
      $('#farmerHint').text('Loading farmers…');
      $.ajax({
        url: AM_BASE + 'admin/billing/account_mapping_name', type:'POST', dataType:'json',
        data:{ center_type: cid },
        success:function(rows){
          farmers = rows || [];
          farmerMap = {};
          farmers.forEach(function(r){ farmerMap[r.Farmer_ID] = { name: r.Farmer_name, pending: parseInt(r.pending_count,10)||0 }; });
          $farmer.prop('disabled', farmers.length === 0);
          var n = farmers.length;
          $('#farmerHint').text(n ? ('Search among ' + n + ' farmer(s) at this center') : 'No farmers found at this center for this year.');
        },
        error:function(){ $('#farmerHint').text('Could not load farmers. Try again.'); amToast('error','Could not load farmers for this center.','Error'); }
      });
    });

    /* ---------- Center searchable dropdown ---------- */
    // #centerSel is a hidden input; this combo drives it and fires 'change'
    // manually (a hidden input does not emit change on .val()).
    var $ci = $('#centerInp'), $cl = $('#centerList');
    function renderCenters(q){
      q = (q||'').trim().toLowerCase();
      var ids = Object.keys(AM_CENTERS).filter(function(id){
        return String(id).indexOf(q) !== -1
            || String(AM_CENTERS[id]||'').toLowerCase().indexOf(q) !== -1;
      }).slice(0, 60);
      if (!ids.length){ $cl.html('<div class="none">No matching center.</div>').addClass('show'); return; }
      $cl.html(ids.map(function(id){
        return '<div class="opt" data-id="'+amEsc(id)+'" data-name="'+amEsc(AM_CENTERS[id])+'">'
             + '<span>'+amEsc(AM_CENTERS[id])+'</span><span class="id">#'+amEsc(id)+'</span></div>';
      }).join('')).addClass('show');
    }
    function clearCenter(){
      $center.val('');
      $ci.val('');
      $('#centerSelected').removeClass('show');
      $cl.removeClass('show');
      $center.trigger('change');
    }
    $ci.on('focus input', function(){ renderCenters($ci.val()); syncComboZ(); });
    $cl.on('click', '.opt', function(){
      var cid = String($(this).data('id')), cname = String($(this).data('name'));
      $center.val(cid);
      $('#centerSelName').text(cname + ' (#' + cid + ')');
      $('#centerSelected').addClass('show');
      $ci.val('');
      $cl.removeClass('show');
      syncComboZ();
      $center.trigger('change');
    });
    $('#centerClear').on('click', function(){ clearCenter(); $ci.focus(); });

    /* ---------- Farmer searchable dropdown ---------- */
    var $flist = $('#farmerListBox');
    // Elevate whichever card currently has an open dropdown so it clears the footer.
    function syncComboZ(){
      $('.am-card').removeClass('am-open');
      $('.am-combo-list.show').closest('.am-card').addClass('am-open');
    }
    function renderFarmers(q){
      q = (q||'').trim().toLowerCase();
      var list = farmers.filter(function(r){
        return String(r.Farmer_ID).toLowerCase().indexOf(q) !== -1
            || String(r.Farmer_name||'').toLowerCase().indexOf(q) !== -1;
      }).slice(0, 60);
      if (!list.length){ $flist.html('<div class="none">No matching farmer.</div>').addClass('show'); return; }
      $flist.html(list.map(function(r){
        var pend = (parseInt(r.pending_count,10)||0) > 0;
        var badge = pend
          ? '<span class="am-badge am-badge-pending" style="padding:2px 9px;font-size:10px">pending</span>'
          : '<span class="am-badge am-badge-done" style="padding:2px 9px;font-size:10px">mapped</span>';
        return '<div class="opt" data-id="'+amEsc(r.Farmer_ID)+'">'
             + '<span>'+amEsc(r.Farmer_name||'—')+' <span class="id">'+amEsc(r.Farmer_ID)+'</span></span>'
             + badge + '</div>';
      }).join('')).addClass('show');
    }
    $farmer.on('focus input', function(){
      $load.prop('disabled', ($(this).val().trim()===''));
      if (!$farmer.prop('disabled')) { renderFarmers($farmer.val()); }
      syncComboZ();
    });
    $flist.on('click', '.opt', function(){
      var fid = String($(this).data('id'));
      $farmer.val(fid);
      $flist.removeClass('show');
      syncComboZ();
      $load.prop('disabled', false);
      $load.click();   // auto-load the picked farmer
    });

    /* ---------- Load farmer (pending row for map / recorded row for unmap) ---------- */
    $load.on('click', doLoad);
    function doLoad(){
      var cid = $center.val(), fid = $farmer.val().trim();
      if (!cid){ amToast('warning','Please select a center.','Missing'); return; }
      if (!fid){ amToast('warning','Please enter a Farmer ID.','Missing'); $farmer.focus(); return; }
      var unmap = (mode === 'unmap');
      $load.prop('disabled', true);
      $.ajax({
        url: AM_BASE + 'admin/billing/account_kishanName', type:'POST', dataType:'json',
        data:{ farmer_id: fid, center_type: cid, mode: mode },
        success:function(a){
          $load.prop('disabled', false);
          if (!a){
            resetLoaded();
            var msg = unmap
              ? 'No mapped purchase found for this farmer — nothing to unmap.'
              : (farmerMap[fid] ? 'No pending purchase for this farmer — it may already be mapped.' : 'No purchase found for this Farmer ID at the selected center.');
            amToast('info', msg, unmap ? 'Nothing to unmap' : 'Nothing to map');
            return;
          }
          loaded = a;
          $('#sName').text(a.Farmer_name || '—');
          $('#sId').text(a.Farmer_ID || fid);
          $('#sCenter').text(AM_CENTERS[cid] || a.CenterName || cid);
          $('#sQty').text(a.Quantity!=null ? amINR(a.Quantity).replace('.00','') : '—');
          $('#sAmt').html('&#8377; ' + amINR(a.Ammount));
          $('#sumStatus').html(unmap
            ? '<span class="am-badge am-badge-done"><i class="ti-check"></i> Mapped — ready to unmap</span>'
            : '<span class="am-badge am-badge-pending"><i class="ti-time"></i> Pending — ready to map</span>');
          // fill hidden form fields
          $('#f_farmer').val(a.Farmer_ID || fid);
          $('#f_farmer_name').val(a.Farmer_name || '');
          $('#f_center_name').val(AM_CENTERS[cid] || a.CenterName || cid);
          $('#f_qty').val(a.Quantity || '');
          $('#f_amount').val(a.Ammount || '');
          $('#sumCard').show(); $('#acctCard').show();
          if (unmap){
            var acctTxt = a.account_name ? (a.account_name + (a.account_no ? ' (#' + a.account_no + ')' : '')) : '— (no account on record)';
            $('#curAcct').val(acctTxt);
            refreshUnmapBtn();
          } else {
            refreshMapBtn();
            $('#acctInp').focus();
          }
        },
        error:function(){ $load.prop('disabled', false); amToast('error','Could not load the farmer. Try again.','Error'); }
      });
    }

    /* ---------- Step 2: account searchable picker ---------- */
    var $ai = $('#acctInp'), $al = $('#acctList');
    function renderAccts(q){
      q = (q||'').trim().toLowerCase();
      var list = AM_ACCOUNTS.filter(function(a){ return a.name.toLowerCase().indexOf(q) !== -1 || String(a.id).indexOf(q) !== -1; }).slice(0, 50);
      if (!list.length){ $al.html('<div class="none">No matching account.</div>').addClass('show'); return; }
      $al.html(list.map(function(a){
        return '<div class="opt" data-id="'+a.id+'" data-name="'+amEsc(a.name)+'"><span>'+amEsc(a.name)+'</span><span class="id">#'+a.id+'</span></div>';
      }).join('')).addClass('show');
    }
    $ai.on('focus input', function(){ chosenAccount=null; $('#acctSelected').removeClass('show'); refreshMapBtn(); renderAccts($ai.val()); syncComboZ(); });
    $al.on('click', '.opt', function(){
      chosenAccount = { id: parseInt($(this).data('id'),10), name: String($(this).data('name')) };
      $('#f_account').val(chosenAccount.name + '_' + chosenAccount.id);
      $('#acctSelName').text(chosenAccount.name + ' (#' + chosenAccount.id + ')');
      $('#acctSelected').addClass('show');
      $ai.val('');
      $al.removeClass('show');
      syncComboZ();
      refreshMapBtn();
    });
    $('#acctClear').on('click', function(){ chosenAccount=null; $('#f_account').val(''); $('#acctSelected').removeClass('show'); refreshMapBtn(); $ai.focus(); });
    $(document).on('click', function(e){ if (!$(e.target).closest('.am-combo').length){ $('.am-combo-list').removeClass('show'); syncComboZ(); } });

    function refreshMapBtn(){ $('#mapBtn').prop('disabled', !(loaded && chosenAccount)); }
    function refreshUnmapBtn(){ $('#unmapBtn').prop('disabled', !loaded); }

    function commonRows(){
      return [
        { k:'Farmer', v: amEsc((loaded.Farmer_name||'') + ' (' + (loaded.Farmer_ID||'') + ')') },
        { k:'Center', v: amEsc(AM_CENTERS[$center.val()] || loaded.CenterName || $center.val()) },
        { k:'Quantity', v: amEsc(String(loaded.Quantity)) + ' Qtl' },
        { k:'Amount', v: '&#8377; ' + amINR(loaded.Ammount) }
      ];
    }

    /* ---------- Map → confirm → submit ---------- */
    $('#mapBtn').on('click', function(){
      if (!loaded){ amToast('warning','Load a farmer first.','Missing'); return; }
      if (!chosenAccount){ amToast('warning','Choose an account to map.','Missing'); $ai.focus(); return; }
      var rows = commonRows();
      rows.push({ k:'Account', v: amEsc(chosenAccount.name + ' (#' + chosenAccount.id + ')') });
      amConfirm({ title:'Confirm Mapping', subtitle:'Review before assigning the account', icon:'ti-link', okText:'Confirm & Map', rows:rows })
        .then(function(ok){ if (ok){ $('#mapBtn').prop('disabled', true); document.getElementById('amForm').submit(); } });
    });

    /* ---------- Unmap → confirm → submit ---------- */
    $('#unmapBtn').on('click', function(){
      if (!loaded){ amToast('warning','Load a farmer first.','Missing'); return; }
      var rows = commonRows();
      rows.push({ k:'Current Account', v: amEsc(loaded.account_name || '—') });
      rows.push({ k:'Effect', v: 'Recorded rows &rarr; <b>pending</b>' });
      amConfirm({ title:'Confirm Unmap', subtitle:'This reverts the account mapping', icon:'ti-unlink', okText:'Confirm & Unmap', danger:true, rows:rows })
        .then(function(ok){ if (ok){ $('#unmapBtn').prop('disabled', true); document.getElementById('amForm').submit(); } });
    });

    $('#resetBtn').on('click', function(){ clearCenter(); resetFarmer(); });
    $('#resetBtn2').on('click', function(){ clearCenter(); resetFarmer(); });

    function resetLoaded(){
      loaded=null; $('#sumCard').hide(); $('#acctCard').hide();
      ['f_farmer','f_farmer_name','f_center_name','f_qty','f_amount','f_account'].forEach(function(i){ $('#'+i).val(''); });
      chosenAccount=null; $('#acctSelected').removeClass('show'); $ai.val('');
      $('#curAcct').val('—'); $('#unmapBtn').prop('disabled', true);
      refreshMapBtn();
    }
    function resetFarmer(){ $farmer.val(''); farmers=[]; farmerMap={}; $flist.removeClass('show').empty(); $load.prop('disabled', true); resetLoaded(); }
  });

  /* ---------- review & confirm modal (generic) ----------
     opts: { title, subtitle, icon, rows:[{k,v}], okText, danger } */
  function amConfirm(opts){
    opts = opts || {};
    return new Promise(function(resolve){
      var rows = (opts.rows||[]).map(function(r){
        return '<div class="amm-row"><span class="k">'+amEsc(r.k)+'</span><span class="v">'+(r.v==null?'':r.v)+'</span></div>';
      }).join('');
      var okClass = opts.danger ? 'am-btn-danger' : 'am-btn-primary';
      var headClass = opts.danger ? ' amm-h-danger' : '';
      var back = document.createElement('div'); back.className='amm-back';
      back.innerHTML =
        '<div class="amm" role="dialog" aria-modal="true">'+
          '<div class="amm-h'+headClass+'"><span class="ic"><i class="'+(opts.icon||'ti-link')+'"></i></span><div><h5>'+amEsc(opts.title||'Please confirm')+'</h5><p>'+amEsc(opts.subtitle||'')+'</p></div></div>'+
          '<div class="amm-body">'+ rows +'</div>'+
          '<div class="amm-f">'+
            '<button type="button" class="am-btn am-btn-ghost" data-act="cancel"><i class="ti-close"></i> Cancel</button>'+
            '<button type="button" class="am-btn '+okClass+'" data-act="ok"><i class="ti-check"></i> '+amEsc(opts.okText||'Confirm')+'</button>'+
          '</div>'+
        '</div>';
      document.body.appendChild(back);
      requestAnimationFrame(function(){ back.classList.add('show'); });
      var done=false;
      function close(v){ if(done)return; done=true; document.removeEventListener('keydown',onKey); back.classList.remove('show'); setTimeout(function(){ if(back.parentNode) back.parentNode.removeChild(back); },200); resolve(v); }
      function onKey(e){ if(e.key==='Escape') close(false); }
      back.addEventListener('click', function(e){ if(e.target===back){ close(false); return; } var a=e.target.closest?e.target.closest('[data-act]'):null; if(a) close(a.getAttribute('data-act')==='ok'); });
      document.addEventListener('keydown', onKey);
      setTimeout(function(){ var b=back.querySelector('[data-act="ok"]'); if(b) b.focus(); }, 60);
    });
  }
</script>
