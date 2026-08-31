<?php helper(['url']); $numeric = isset($report['numeric']) ? $report['numeric'] : []; $g = function ($k) use ($filters) { return isset($filters[$k]) ? $filters[$k] : ''; }; $qs = http_build_query(array_filter(service('request')->getGet() ?? [])); $st = isset($stats) ? $stats : null; ?>
<style>
  .kr-page{color:#1e2a3d;padding:22px}.kr-shell{margin:0 auto;max-width:1160px}
  .kr-hero{align-items:center;background:linear-gradient(135deg,#1769c2,#0f4e97);border-radius:14px;box-shadow:0 16px 40px rgba(23,105,194,.25);color:#fff;display:flex;flex-wrap:wrap;gap:14px;justify-content:space-between;margin-bottom:16px;padding:20px 24px}
  .kr-hero-l{align-items:center;display:flex;gap:14px}.kr-hero-ic{align-items:center;background:rgba(255,255,255,.16);border-radius:12px;display:flex;font-size:22px;height:50px;justify-content:center;width:50px}
  .kr-hero h4{font-size:21px;font-weight:900;margin:0}.kr-hero p{font-size:12.5px;font-weight:600;margin:3px 0 0;opacity:.9}
  .kr-hlink{align-items:center;background:rgba(255,255,255,.18);border-radius:10px;color:#fff;display:inline-flex;font-weight:800;gap:7px;padding:9px 15px;text-decoration:none}.kr-hlink:hover{background:rgba(255,255,255,.28);color:#fff}
  .kr-cards{display:grid;gap:12px;grid-template-columns:repeat(6,1fr);margin-bottom:16px}
  .kr-card{background:#fff;border:1px solid #e5ecf5;border-radius:12px;box-shadow:0 8px 20px rgba(24,36,60,.05);padding:13px 15px;border-left:4px solid #1769c2}
  .kr-card.g{border-left-color:#16a34a}.kr-card.a{border-left-color:#ea580c}.kr-card.b{border-left-color:#2563eb}.kr-card.t{border-left-color:#0f8a8a}.kr-card.p{border-left-color:#7b4bd0}
  .kr-card .l{color:#8394a7;font-size:10px;font-weight:900;text-transform:uppercase}.kr-card .v{color:#18243c;font-size:20px;font-weight:900;margin-top:4px}
  .kr-tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
  .kr-tab{background:#fff;border:1px solid #dce6f2;border-radius:9px;color:#52657a;font-size:13px;font-weight:800;padding:9px 15px;text-decoration:none}
  .kr-tab.active,.kr-tab:hover{background:#1769c2;border-color:#1769c2;color:#fff}
  .kr-bar{align-items:center;background:#fff;border:1px solid #e5ecf5;border-radius:12px;box-shadow:0 8px 22px rgba(24,36,60,.05);display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end;margin-bottom:14px;padding:12px 16px}
  .kr-bar .h{margin-right:auto;font-size:16px;font-weight:900;display:flex;align-items:center;gap:8px}
  .kr-btn{align-items:center;border:0;border-radius:8px;cursor:pointer;display:inline-flex;font-size:13px;font-weight:800;gap:6px;min-height:38px;padding:8px 13px;text-decoration:none;color:#fff}
  .kr-csv{background:#0f8a8a}.kr-pdf{background:#c0392b}.kr-print{background:#34495e}.kr-btn:hover{filter:brightness(1.06);color:#fff}
  .kr-filter{background:#fff;border:1px solid #e5ecf5;border-radius:12px;margin-bottom:14px;padding:14px 16px}
  .kr-fg{display:grid;gap:12px;grid-template-columns:repeat(3,1fr)}
  .kr-fg label{color:#516174;display:block;font-size:10px;font-weight:900;margin-bottom:4px;text-transform:uppercase}
  .kr-fg select{background:#fbfdff;border:1.5px solid #dce6f2;border-radius:9px;color:#1e2a3d;font-weight:700;min-height:42px;padding:9px 12px;width:100%}
  .kr-fb{display:flex;gap:8px;margin-top:12px}
  .kr-panel{background:#fff;border:1px solid #e5ecf5;border-radius:12px;box-shadow:0 16px 38px rgba(24,36,60,.07);overflow:hidden}
  .kr-twrap{overflow-x:auto;padding:16px}
  .kr-hint{color:#6b7a90;font-size:11px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px}
  #krTable{border-collapse:collapse;width:100%;min-width:520px}
  #krTable th{background:#f7f9fc;border:1px solid #dce6f2;color:#516174;font-size:10px;font-weight:900;padding:11px 10px;text-transform:uppercase;text-align:left;white-space:nowrap}
  #krTable td{border:1px solid #e6edf5;color:#26374f;font-size:12.5px;font-weight:700;padding:10px 10px}
  #krTable td.num,#krTable th.num{text-align:right;white-space:nowrap}
  #krTable tbody tr:hover td{background:#f5f9ff}
  #krTable tfoot td{background:#f2f6fb;border:1px solid #dce6f2;font-weight:900}
  .kr-sortable th{cursor:pointer;user-select:none;position:relative;padding-right:20px!important}
  .kr-sortable th:hover{background:#e8eef7}.kr-sortable th:after{content:'\2195';position:absolute;right:6px;top:50%;transform:translateY(-50%);opacity:.3;font-size:11px}
  .kr-sortable th.kr-asc:after{content:'\2191';opacity:.95;color:#1769c2}.kr-sortable th.kr-desc:after{content:'\2193';opacity:.95;color:#1769c2}
  .kr-empty{color:#9aa7b6;font-style:italic;text-align:center}
  @media(max-width:1000px){.kr-cards{grid-template-columns:repeat(3,1fr)}.kr-fg{grid-template-columns:1fr 1fr}}
  @media(max-width:560px){.kr-page{padding:14px}.kr-cards{grid-template-columns:1fr 1fr}.kr-fg{grid-template-columns:1fr}}
</style>

<main class="main-content bgc-grey-100 kr-page">
  <div id="mainContent">
    <div class="container-fluid kr-shell">
      <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>

      <section class="kr-hero">
        <div class="kr-hero-l">
          <span class="kr-hero-ic"><i class="ti-bar-chart"></i></span>
          <div>
            <h4>Kisan Registration Report</h4>
            <p>Registrations &amp; quantities by status and center — FY <?= esc(fy()->FY ?? '') ?>.</p>
          </div>
        </div>
        <a href="<?= base_url('admin/Kisanreg/listing') ?>" class="kr-hlink"><i class="ti-list"></i> All Registrations</a>
      </section>

      <?php if ($st): ?>
      <section class="kr-cards">
        <div class="kr-card"><div class="l">Total</div><div class="v"><?= number_format((int) $st->total) ?></div></div>
        <div class="kr-card g"><div class="l">Verified</div><div class="v"><?= number_format((int) $st->verified) ?></div></div>
        <div class="kr-card a"><div class="l">Unverified</div><div class="v"><?= number_format((int) $st->unverified) ?></div></div>
        <div class="kr-card b"><div class="l">Mapped</div><div class="v"><?= number_format((int) $st->mapped) ?></div></div>
        <div class="kr-card t"><div class="l">Total Qty</div><div class="v"><?= number_format((float) $st->total_qty, 0) ?></div></div>
        <div class="kr-card p"><div class="l">Left Qty</div><div class="v"><?= number_format((float) $st->left_qty, 0) ?></div></div>
      </section>
      <?php endif; ?>

      <div class="kr-tabs">
        <?php foreach ($report_views as $key => $label): ?>
          <a href="<?= base_url('admin/Kisanreg/report/' . $key) . ($qs ? '?' . $qs : '') ?>" class="kr-tab <?= $active === $key ? 'active' : '' ?>"><?= esc($label) ?></a>
        <?php endforeach; ?>
      </div>

      <div class="kr-bar">
        <span class="h"><i class="ti-bar-chart"></i> <?= esc($report['title']) ?></span>
        <a href="<?= base_url('admin/Kisanreg/report_csv/' . $active) . ($qs ? '?' . $qs : '') ?>" class="kr-btn kr-csv"><i class="ti-receipt"></i> CSV</a>
        <a href="<?= base_url('admin/Kisanreg/report_pdf/' . $active) . ($qs ? '?' . $qs : '') ?>" class="kr-btn kr-pdf"><i class="ti-file"></i> PDF</a>
        <button type="button" class="kr-btn kr-print" onclick="krPrint()"><i class="ti-printer"></i> Print</button>
      </div>

      <form method="get" class="kr-filter">
        <div class="kr-fg">
          <div>
            <label>Status</label>
            <select name="status">
              <option value="">All statuses</option>
              <?php foreach (['Unverified','Verified','Mapped','Dead','Suspended'] as $s): ?>
                <option value="<?= $s ?>" <?= $g('status') === $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>Center</label>
            <select name="center">
              <option value="">All centers</option>
              <?php if (!empty($centers)) foreach ($centers as $c): ?>
                <option value="<?= (int) $c->id ?>" <?= $g('center') == $c->id ? 'selected' : '' ?>><?= esc($c->name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="display:flex;align-items:flex-end">
            <div class="kr-fb">
              <button type="submit" class="kr-btn" style="background:#1769c2"><i class="ti-search"></i> Apply</button>
              <a href="<?= base_url('admin/Kisanreg/report/' . $active) ?>" class="kr-btn" style="background:#8394a7"><i class="ti-reload"></i> Reset</a>
            </div>
          </div>
        </div>
      </form>

      <div class="kr-panel"><div class="kr-twrap">
        <div class="kr-hint"><i class="ti-exchange-vertical"></i> Click any column header to sort</div>
        <table id="krTable" class="kr-sortable">
          <thead><tr>
            <?php foreach ($report['columns'] as $idx => $c): ?>
              <th class="<?= in_array($idx, $numeric) ? 'num' : '' ?>"><?= esc($c) ?></th>
            <?php endforeach; ?>
          </tr></thead>
          <tbody>
            <?php if (empty($report['rows'])): ?>
              <tr><td colspan="<?= count($report['columns']) ?>" class="kr-empty">No registrations found for the selected filters.</td></tr>
            <?php else: foreach ($report['rows'] as $row): ?>
              <tr><?php foreach ($row as $idx => $cell): ?><td class="<?= in_array($idx, $numeric) ? 'num' : '' ?>"><?= esc((string) $cell) ?></td><?php endforeach; ?></tr>
            <?php endforeach; endif; ?>
          </tbody>
          <?php if (!empty($report['totals'])): ?>
            <tfoot><tr><?php foreach ($report['totals'] as $idx => $cell): ?><td class="<?= in_array($idx, $numeric) ? 'num' : '' ?>"><?= esc((string) $cell) ?></td><?php endforeach; ?></tr></tfoot>
          <?php endif; ?>
        </table>
      </div></div>
    </div>
  </div>
</main>

<script>
  function krPrint(){
    var tbl = document.getElementById('krTable').outerHTML;
    var title = <?= json_encode($report['title']); ?>;
    var sub = <?= json_encode(trim(((fy()->firm_name ?? '') ? (fy()->firm_name ?? '') . ' | ' : '') . 'FY ' . (fy()->FY ?? '') . ' | Printed ' . date('d-m-Y H:i'))); ?>;
    var w = window.open('', '_blank', 'height=800,width=900');
    if(!w){ if(window.showToast) showToast('warning','Popup blocked! Allow popups to print.'); return; }
    w.document.write('<html><head><title>'+title+'</title><meta charset="utf-8"><style>'+
      'body{font-family:Arial,sans-serif;color:#000;font-size:12px;margin:0;padding:16px;}h3{font-size:15px;margin:0 0 2px;}.s{font-size:10px;color:#333;margin:0 0 10px;}'+
      'table{width:100%;border-collapse:collapse;}th,td{border:1px solid #000;padding:5px 8px;}th{background:#eee!important;-webkit-print-color-adjust:exact;}td.num,th.num{text-align:right;}tfoot td{font-weight:bold;background:#f2f2f2!important;-webkit-print-color-adjust:exact;}'+
      '@page{size:A4 portrait;margin:12mm;}</style></head><body><h3>'+title+'</h3><p class="s">'+sub+'</p>'+tbl+'</body></html>');
    w.document.close(); w.focus(); setTimeout(function(){ w.print(); }, 300);
  }

  (function () {
    var table = document.getElementById('krTable');
    if (!table || (' ' + table.className + ' ').indexOf(' kr-sortable ') === -1) { return; }
    var thead = table.tHead, tbody = table.tBodies[0]; if (!thead || !tbody) { return; }
    var ths = thead.rows[0].cells, state = { col: -1, dir: 1 };
    function hasData(){ return !(tbody.rows.length === 1 && tbody.rows[0].cells.length === 1 && (' '+tbody.rows[0].cells[0].className+' ').indexOf(' kr-empty ') !== -1); }
    function txt(r,i){ return (r.cells[i]?r.cells[i].textContent:'').trim(); }
    function isNum(i){ return ths[i] && (' '+ths[i].className+' ').indexOf(' num ') !== -1; }
    function num(t){ var n = parseFloat(t.replace(/[^0-9.\-]/g,'')); return isNaN(n)?null:n; }
    for (var i=0;i<ths.length;i++){ (function(idx){
      ths[idx].addEventListener('click', function(){
        if(!hasData()) return;
        var dir=(state.col===idx)?-state.dir:1; state={col:idx,dir:dir};
        var numeric=isNum(idx), rows=Array.prototype.slice.call(tbody.rows);
        rows.sort(function(a,b){ var ta=txt(a,idx),tb=txt(b,idx);
          if(numeric){ var na=num(ta),nb=num(tb); if(na===null&&nb===null)return 0; if(na===null)return 1; if(nb===null)return -1; return (na-nb)*dir; }
          return ta.localeCompare(tb,undefined,{numeric:true,sensitivity:'base'})*dir; });
        for(var r=0;r<rows.length;r++){ tbody.appendChild(rows[r]); }
        for(var j=0;j<ths.length;j++){ ths[j].classList.remove('kr-asc','kr-desc'); }
        ths[idx].classList.add(dir>0?'kr-asc':'kr-desc');
      });
    })(i); }
  })();
</script>
