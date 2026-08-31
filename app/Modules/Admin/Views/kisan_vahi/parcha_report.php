<?php
/**
 * Kisan Vahi Parcha Report (admin/kisan_vahi/report) — CI4 functional port.
 * Read-only: the CI3 inline "record payment" write is intentionally omitted
 * until the payment write flow is migrated. Session-stored filters; stat cards;
 * sortable, horizontally-scrolling table; CSV + print export.
 */
helper(['url']);
$s = session();
$fromDate = $s->get('setParchaFromDate');
$toDate   = $s->get('setParchaToDate');
$f_from = ! empty($fromDate) ? date('Y-m-d', strtotime($fromDate)) : '';
$f_to   = ! empty($toDate) ? date('Y-m-d', strtotime($toDate)) : '';
$f_search = (string) $s->get('kvParchaSearch');
$f_center = (string) $s->get('kvParchaCenter');
$f_pfms   = (string) $s->get('kvParchaPfms');
$f_paid   = (string) $s->get('kvParchaPaid');

$rows = ! empty($kisanVahiData) ? $kisanVahiData : [];
$amt = fn($v) => (float) preg_replace('/[^0-9.\-]/', '', (string) $v);
$nf  = fn($v) => number_format((float) $v, 2);

$stat_qty = 0; $stat_amt = 0; $stat_paid = 0; $stat_pending = 0;
foreach ($rows as $r) {
    $stat_qty += (float) $r->Quantity;
    $a = $amt($r->Ammount);
    $stat_amt += $a;
    if (isset($r->paid_status) && (int) $r->paid_status === 1) { $stat_paid += $a; } else { $stat_pending += $a; }
}
?>
<style>
  .kv{color:#1e2a3d;padding:22px}.kv-shell{margin:0 auto;max-width:1320px}
  .kv-hero{align-items:center;background:linear-gradient(135deg,#0f8a5f,#0a6f49);border-radius:14px;box-shadow:0 16px 40px rgba(15,138,95,.24);color:#fff;display:flex;flex-wrap:wrap;gap:14px;justify-content:space-between;margin-bottom:16px;padding:20px 24px}
  .kv-hero-l{align-items:center;display:flex;gap:14px}.kv-hero-ic{align-items:center;background:rgba(255,255,255,.16);border-radius:12px;display:flex;font-size:22px;height:50px;justify-content:center;width:50px}
  .kv-hero h4{font-size:21px;font-weight:900;margin:0}.kv-hero p{font-size:12.5px;font-weight:600;margin:3px 0 0;opacity:.9}
  .kv-hlink{align-items:center;background:rgba(255,255,255,.18);border-radius:10px;color:#fff;display:inline-flex;font-weight:800;gap:7px;padding:9px 15px;text-decoration:none}.kv-hlink:hover{background:rgba(255,255,255,.28);color:#fff}
  .kv-cards{display:grid;gap:12px;grid-template-columns:repeat(5,1fr);margin-bottom:16px}
  .kv-card{background:#fff;border:1px solid #e5ecf5;border-radius:12px;box-shadow:0 8px 20px rgba(24,36,60,.05);padding:13px 15px;border-left:4px solid #1769c2}
  .kv-card.g{border-left-color:#16a34a}.kv-card.a{border-left-color:#ea580c}.kv-card.t{border-left-color:#0f8a8a}.kv-card.p{border-left-color:#7b4bd0}
  .kv-card .l{color:#8394a7;font-size:10px;font-weight:900;text-transform:uppercase}.kv-card .v{color:#18243c;font-size:19px;font-weight:900;margin-top:4px}
  .kv-bar{align-items:center;background:#fff;border:1px solid #e5ecf5;border-radius:12px;box-shadow:0 8px 22px rgba(24,36,60,.05);display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end;margin-bottom:14px;padding:12px 16px}
  .kv-bar .h{margin-right:auto;font-size:15px;font-weight:900;display:flex;align-items:center;gap:8px}
  .kv-btn{align-items:center;border:0;border-radius:8px;cursor:pointer;display:inline-flex;font-size:13px;font-weight:800;gap:6px;min-height:38px;padding:8px 13px;text-decoration:none;color:#fff}
  .kv-csv{background:#0f8a8a}.kv-print{background:#34495e}.kv-btn:hover{filter:brightness(1.06);color:#fff}
  .kv-filter{background:#fff;border:1px solid #e5ecf5;border-radius:12px;margin-bottom:14px;padding:14px 16px}
  .kv-fg{display:grid;gap:12px;grid-template-columns:repeat(3,1fr)}
  .kv-fg label{color:#516174;display:block;font-size:10px;font-weight:900;margin-bottom:4px;text-transform:uppercase}
  .kv-fg input,.kv-fg select{background:#fbfdff;border:1.5px solid #dce6f2;border-radius:9px;color:#1e2a3d;font-weight:700;min-height:42px;padding:9px 12px;width:100%}
  .kv-fb{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}
  .kv-panel{background:#fff;border:1px solid #e5ecf5;border-radius:12px;box-shadow:0 16px 38px rgba(24,36,60,.07);overflow:hidden}
  .kv-twrap{overflow-x:auto;padding:16px}
  #kvTable{border-collapse:collapse;width:100%;min-width:1050px}
  #kvTable th{background:#f7f9fc;border:1px solid #dce6f2;color:#516174;font-size:10px;font-weight:900;padding:10px 9px;text-transform:uppercase;text-align:left;white-space:nowrap;cursor:pointer;position:relative}
  #kvTable th:hover{background:#e8eef7}
  #kvTable td{border:1px solid #e6edf5;color:#26374f;font-size:12px;font-weight:700;padding:9px 9px;white-space:nowrap}
  #kvTable td.num{text-align:right}
  #kvTable tbody tr:hover td{background:#f5f9ff}
  .kv-pill{display:inline-block;padding:2px 9px;border-radius:20px;font-size:10.5px;font-weight:800}
  .kv-pill-paid{background:#e7f6ee;color:#0a6f49}.kv-pill-pend{background:#fef2e6;color:#b45309}
  .kv-empty{color:#9aa7b6;font-style:italic;text-align:center}
  @media(max-width:1000px){.kv-cards{grid-template-columns:repeat(2,1fr)}.kv-fg{grid-template-columns:1fr 1fr}}
  @media(max-width:560px){.kv{padding:14px}.kv-cards{grid-template-columns:1fr 1fr}.kv-fg{grid-template-columns:1fr}}
</style>

<main class="main-content bgc-grey-100 kv">
  <div id="mainContent">
    <div class="container-fluid kv-shell">

      <section class="kv-hero">
        <div class="kv-hero-l">
          <span class="kv-hero-ic"><i class="ti-agenda"></i></span>
          <div>
            <h4>Kisan Vahi Parcha Report</h4>
            <p>Farmer purchase entries — <?= esc($f_from ? date('d M Y', strtotime($f_from)) : '—') ?> to <?= esc($f_to ? date('d M Y', strtotime($f_to)) : '—') ?>.</p>
          </div>
        </div>
        <a href="<?= base_url('admin/kisan_vahi') ?>" class="kv-hlink"><i class="ti-list"></i> Kisan Vahi</a>
      </section>

      <section class="kv-cards">
        <div class="kv-card"><div class="l">Entries</div><div class="v"><?= number_format(count($rows)) ?></div></div>
        <div class="kv-card t"><div class="l">Total Qty (Qtl)</div><div class="v"><?= $nf($stat_qty) ?></div></div>
        <div class="kv-card a"><div class="l">Total Amount</div><div class="v">&#8377;<?= $nf($stat_amt) ?></div></div>
        <div class="kv-card g"><div class="l">Paid</div><div class="v">&#8377;<?= $nf($stat_paid) ?></div></div>
        <div class="kv-card p"><div class="l">Pending</div><div class="v">&#8377;<?= $nf($stat_pending) ?></div></div>
      </section>

      <form method="post" action="<?= base_url('admin/kisan_vahi/report') ?>" class="kv-filter">
        <?= csrf_field() ?>
        <div class="kv-fg">
          <div><label>From Date</label><input type="date" name="setParchaFromDate" value="<?= esc($f_from) ?>"></div>
          <div><label>To Date</label><input type="date" name="setParchaToDate" value="<?= esc($f_to) ?>"></div>
          <div><label>Search (farmer / mobile / purchase / account)</label><input type="text" name="kvSearch" value="<?= esc($f_search) ?>" placeholder="Type to search…"></div>
          <div>
            <label>Center</label>
            <select name="kvCenter">
              <option value="">All centers</option>
              <?php foreach (($center_list ?? []) as $c): ?>
                <option value="<?= esc($c->center_id) ?>" <?= ((string) $f_center === (string) $c->center_id) ? 'selected' : '' ?>><?= esc($c->name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>PFMS Status</label>
            <select name="kvPfms">
              <option value="">All</option>
              <?php foreach (($pfms_options ?? []) as $p): ?>
                <option value="<?= esc($p) ?>" <?= ($f_pfms === (string) $p) ? 'selected' : '' ?>><?= esc($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>Payment</label>
            <select name="kvPaid">
              <option value="" <?= $f_paid === '' ? 'selected' : '' ?>>All</option>
              <option value="1" <?= $f_paid === '1' ? 'selected' : '' ?>>Paid</option>
              <option value="0" <?= $f_paid === '0' ? 'selected' : '' ?>>Pending</option>
            </select>
          </div>
        </div>
        <div class="kv-fb">
          <button type="submit" class="kv-btn" style="background:#1769c2"><i class="ti-search"></i> Apply</button>
          <a href="<?= base_url('admin/kisan_vahi/report?reset=1') ?>" class="kv-btn" style="background:#8394a7"><i class="ti-reload"></i> Reset</a>
        </div>
      </form>

      <div class="kv-bar">
        <span class="h"><i class="ti-agenda"></i> Parcha Entries (<?= number_format(count($rows)) ?>)</span>
        <a href="<?= base_url('admin/kisan_vahi/report_csv') ?>" class="kv-btn kv-csv"><i class="ti-receipt"></i> CSV</a>
        <button type="button" class="kv-btn kv-print" onclick="kvPrint()"><i class="ti-printer"></i> Print</button>
      </div>

      <div class="kv-panel"><div class="kv-twrap">
        <table id="kvTable">
          <thead><tr>
            <th>#</th><th>Purchase Date</th><th>Farmer ID</th><th>Farmer Name</th><th>Mobile</th>
            <th>Center</th><th class="num">Qty (Qtl)</th><th class="num">Amount</th><th>Account</th>
            <th>Bank / IFSC</th><th>Ack</th><th>PFMS</th><th>Payment</th><th>UTR No</th>
          </tr></thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="14" class="kv-empty">No entries found for the selected filters.</td></tr>
            <?php else: $i = 0; foreach ($rows as $r): $i++;
              $paid = isset($r->paid_status) && (int) $r->paid_status === 1; ?>
              <tr>
                <td><?= $i ?></td>
                <td><?= esc($r->Purchase_Date ?? '') ?></td>
                <td><?= esc($r->Farmer_ID ?? '') ?></td>
                <td><?= esc($r->Farmer_name ?? '') ?></td>
                <td><?= esc($r->mobile_no ?? '') ?></td>
                <td><?= esc($r->centern ?? '') ?></td>
                <td class="num"><?= esc($r->Quantity ?? '') ?></td>
                <td class="num"><?= $nf($amt($r->Ammount ?? 0)) ?></td>
                <td><?= esc($r->name ?? '') ?></td>
                <td><?= esc(trim(($r->bank_name ?? '') . ' ' . ($r->ifsc_code ?? ''))) ?: '—' ?></td>
                <td><?= esc($r->Ack_Status ?? '') ?></td>
                <td><?= esc($r->PFMS_Status ?? '') ?></td>
                <td><span class="kv-pill <?= $paid ? 'kv-pill-paid' : 'kv-pill-pend' ?>"><?= $paid ? 'Paid' : 'Pending' ?></span></td>
                <td><?= esc($r->UTR_No ?? '') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div></div>
    </div>
  </div>
</main>

<script>
  function kvPrint(){
    var tbl = document.getElementById('kvTable').outerHTML;
    var w = window.open('', '_blank', 'height=800,width=1150');
    if(!w){ alert('Popup blocked!'); return; }
    w.document.write('<html><head><title>Kisan Vahi Parcha Report</title><meta charset="utf-8"><style>'+
      'body{font-family:Arial,sans-serif;color:#000;font-size:10px;margin:0;padding:14px;}'+
      'table{width:100%;border-collapse:collapse;}th,td{border:1px solid #000;padding:3px 5px;font-size:9.5px;}'+
      'th{background:#eee!important;-webkit-print-color-adjust:exact;}td.num{text-align:right;}'+
      '@page{size:A4 landscape;margin:8mm;}</style></head><body><h3>Kisan Vahi Parcha Report</h3>'+tbl+'</body></html>');
    w.document.close(); w.focus(); setTimeout(function(){ w.print(); }, 300);
  }
  // Lightweight numeric-aware column sort.
  (function(){
    var t=document.getElementById('kvTable'); if(!t) return; var th=t.tHead.rows[0].cells, tb=t.tBodies[0], st={c:-1,d:1};
    function val(r,i){return (r.cells[i]?r.cells[i].textContent:'').trim();}
    function isNum(i){return (' '+th[i].className+' ').indexOf(' num ')!==-1;}
    for(var i=0;i<th.length;i++)(function(idx){th[idx].addEventListener('click',function(){
      if(tb.rows.length===1&&tb.rows[0].cells.length===1)return;
      var d=(st.c===idx)?-st.d:1; st={c:idx,d:d}; var num=isNum(idx), rows=[].slice.call(tb.rows);
      rows.sort(function(a,b){var x=val(a,idx),y=val(b,idx);
        if(num){var nx=parseFloat(x.replace(/[^0-9.\-]/g,'')),ny=parseFloat(y.replace(/[^0-9.\-]/g,''));return((isNaN(nx)?-1e15:nx)-(isNaN(ny)?-1e15:ny))*d;}
        return x.localeCompare(y,undefined,{numeric:true})*d;});
      rows.forEach(function(r){tb.appendChild(r);});
    });})(i);
  })();
</script>
