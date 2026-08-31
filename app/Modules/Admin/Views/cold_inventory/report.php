<?php helper(['url']); $numeric = isset($report['numeric']) ? $report['numeric'] : []; $g = function ($k) use ($filters) { return isset($filters[$k]) ? $filters[$k] : ''; }; $qs = http_build_query(service('request')->getGet() ?? []); ?>
<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/select2/select2.css">
<style>
    .ci-page{color:#18243c;padding:22px}.ci-shell{margin:0 auto;max-width:1320px}
    .ci-tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
    .ci-tab{background:#fff;border:1px solid #dce6f2;border-radius:9px;color:#52657a;font-weight:800;padding:9px 14px;text-decoration:none;font-size:13px}
    .ci-tab.active,.ci-tab:hover{background:#1769c2;border-color:#1769c2;color:#fff}
    .ci-bar{align-items:center;background:#fff;border:1px solid #dce6f2;border-radius:10px;box-shadow:0 8px 22px rgba(24,36,60,.05);display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end;margin-bottom:14px;padding:12px 16px}
    .ci-bar .ci-h{margin-right:auto;font-size:16px;font-weight:900;color:#18243c;display:flex;align-items:center;gap:8px}
    .ci-btn{align-items:center;border:0;border-radius:8px;cursor:pointer;display:inline-flex;font-weight:800;gap:6px;min-height:38px;padding:8px 13px;text-decoration:none;font-size:13px}
    .ci-excel{background:#1f7a4d;color:#fff}.ci-csv{background:#0f8a8a;color:#fff}.ci-pdf{background:#c0392b;color:#fff}.ci-print{background:#34495e;color:#fff}.ci-ghost{background:#eef3fa;color:#26374f}
    .ci-btn:hover{filter:brightness(1.06);color:#fff}.ci-ghost:hover{color:#26374f}
    .ci-filter{background:#fff;border:1px solid #dce6f2;border-radius:10px;margin-bottom:14px;padding:14px 16px}
    .ci-grid{display:grid;gap:10px;grid-template-columns:repeat(4,1fr)}
    .ci-grid label{color:#516174;display:block;font-size:10px;font-weight:900;letter-spacing:.03em;margin-bottom:4px;text-transform:uppercase}
    .ci-grid input,.ci-grid select{background:#fbfdff;border:1px solid #dce6f2;border-radius:8px;color:#18243c;font-weight:700;min-height:40px;padding:8px 11px;width:100%}
    .ci-fbtns{display:flex;gap:8px;margin-top:12px}
    .ci-panel{background:#fff;border:1px solid #dce6f2;border-radius:10px;box-shadow:0 16px 38px rgba(24,36,60,.08);overflow:hidden}
    .ci-twrap{overflow-x:auto;padding:16px}
    .ci-sorthint{color:#6b7a90;font-size:11px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px}
    #ciTable{border-collapse:collapse;width:100%;min-width:720px}
    #ciTable th{background:#f7f9fc;border:1px solid #dce6f2;color:#516174;font-size:10px;font-weight:900;padding:10px 9px;text-transform:uppercase;white-space:nowrap;text-align:left}
    #ciTable td{border:1px solid #e6edf5;color:#26374f;font-size:12px;font-weight:700;padding:9px 9px}
    #ciTable td.num,#ciTable th.num{text-align:right;white-space:nowrap}
    #ciTable tbody tr:hover td{background:#f5f9ff}
    #ciTable tfoot td{background:#f2f6fb;border:1px solid #dce6f2;font-weight:900}
    .ci-empty{color:#9aa7b6;font-style:italic;text-align:center}
    .ci-sortable th{cursor:pointer;user-select:none;position:relative;padding-right:20px!important}
    .ci-sortable th:hover{background:#e8eef7}
    .ci-sortable th:after{content:'\2195';position:absolute;right:6px;top:50%;transform:translateY(-50%);opacity:.3;font-size:11px}
    .ci-sortable th.ci-asc:after{content:'\2191';opacity:.95;color:#1769c2}
    .ci-sortable th.ci-desc:after{content:'\2193';opacity:.95;color:#1769c2}
    @media(max-width:900px){.ci-grid{grid-template-columns:1fr 1fr}.ci-page{padding:14px}}
</style>

<main class="main-content bgc-grey-100 ci-page">
  <div id="mainContent">
    <div class="container-fluid ci-shell">
      <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>

      <div class="ci-tabs">
        <a href="<?= base_url('admin/cold_inventory/overview') ?>" class="ci-tab"><i class="ti-dashboard"></i> Overview</a>
        <?php foreach ($views as $key => $label): ?>
          <a href="<?= base_url('admin/cold_inventory/report/' . $key) ?>" class="ci-tab <?= $active === $key ? 'active' : '' ?>"><?= esc($label) ?></a>
        <?php endforeach; ?>
      </div>

      <div class="ci-bar">
        <span class="ci-h"><i class="ti-package"></i> <?= esc($report['title']) ?></span>
        <a href="<?= base_url('admin/cold_inventory/report_csv/' . $active) . ($qs ? '?' . $qs : '') ?>" class="ci-btn ci-csv"><i class="ti-receipt"></i> CSV</a>
        <a href="<?= base_url('admin/cold_inventory/report_pdf/' . $active) . ($qs ? '?' . $qs : '') ?>" class="ci-btn ci-pdf"><i class="ti-file"></i> PDF</a>
        <button type="button" class="ci-btn ci-print" onclick="ciPrint()"><i class="ti-printer"></i> Print</button>
      </div>

      <form method="get" class="ci-filter">
        <div class="ci-grid">
          <?php if ($active === 'movement'): ?>
            <div><label>From Date</label><input type="date" name="from_date" value="<?= esc($g('from')) ?>"></div>
            <div><label>To Date</label><input type="date" name="to_date" value="<?= esc($g('to')) ?>"></div>
          <?php else: ?>
            <div><label>Stock As On</label><input type="date" name="as_on" value="<?= esc($g('as_on')) ?>"></div>
            <div></div>
          <?php endif; ?>
          <div>
            <label>Variety</label>
            <select name="variety">
              <option value="">All varieties</option>
              <?php foreach ($varieties as $v): ?><option value="<?= (int) $v->id ?>" <?= ($g('variety') == $v->id) ? 'selected' : '' ?>><?= esc($v->name) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>Kisan</label>
            <select name="kisan_id" id="ciKisan">
              <option value="">All kisans</option>
              <?php foreach ($kisans as $k): ?><option value="<?= (int) $k->id ?>" <?= ($g('kisan_id') == $k->id) ? 'selected' : '' ?>><?= esc($k->alias_id . ' — ' . $k->kisan_name) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="ci-fbtns">
          <button type="submit" class="ci-btn ci-btn" style="background:#1769c2;color:#fff"><i class="ti-search"></i> Apply</button>
          <a href="<?= base_url('admin/cold_inventory/report/' . $active) ?>" class="ci-btn ci-ghost"><i class="ti-reload"></i> Reset</a>
        </div>
      </form>

      <div class="ci-panel"><div class="ci-twrap">
        <div class="ci-sorthint"><i class="ti-exchange-vertical"></i> Click any column header to sort</div>
        <table id="ciTable" class="ci-sortable">
          <thead><tr>
            <?php foreach ($report['columns'] as $idx => $c): ?>
              <th class="<?= in_array($idx, $numeric) ? 'num' : '' ?>"><?= esc($c) ?></th>
            <?php endforeach; ?>
          </tr></thead>
          <tbody>
            <?php if (empty($report['rows'])): ?>
              <tr><td colspan="<?= count($report['columns']) ?>" class="ci-empty">No stock records found for the selected filters.</td></tr>
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

<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/select2/select2.min.js"></script>
<script>
  $(function(){ if ($.fn.select2) { $('#ciKisan').select2({ placeholder:'Search Kisan', allowClear:true, width:'100%' }); } });

  function ciPrint(){
    var tbl = document.getElementById('ciTable').outerHTML;
    var title = <?= json_encode($report['title']); ?>;
    var sub = <?= json_encode(trim(((fy()->firm_name ?? '') ? (fy()->firm_name ?? '') . ' | ' : '') . 'Printed ' . date('d-m-Y H:i'))); ?>;
    var w = window.open('', '_blank', 'height=900,width=1100');
    if(!w){ if(window.showToast){showToast('warning','Popup blocked! Allow popups to print.');}else{alert('Popup blocked!');} return; }
    w.document.write('<html><head><title>'+title+'</title><meta charset="utf-8"><style>'+
      'body{font-family:Arial,sans-serif;color:#000;font-size:11px;margin:0;padding:14px;}'+
      'h3{font-size:14px;margin:0 0 2px;} .s{font-size:10px;color:#333;margin:0 0 8px;}'+
      'table{width:100%;border-collapse:collapse;} th,td{border:1px solid #000;padding:3px 5px;font-size:10.5px;}'+
      'th{background:#eee !important;font-weight:bold;text-align:center;-webkit-print-color-adjust:exact;} td.num,th.num{text-align:right;white-space:nowrap;}'+
      'tfoot td{font-weight:bold;background:#f2f2f2 !important;-webkit-print-color-adjust:exact;}'+
      '@page{size:A4 landscape;margin:10mm;} thead{display:table-header-group;} tr{page-break-inside:avoid;}'+
      '</style></head><body><h3>'+title+'</h3><p class="s">'+sub+'</p>'+tbl+'</body></html>');
    w.document.close(); w.focus(); setTimeout(function(){ w.print(); }, 350);
  }

  (function () {
    var table = document.getElementById('ciTable');
    if (!table || (' ' + table.className + ' ').indexOf(' ci-sortable ') === -1) { return; }
    var thead = table.tHead, tbody = table.tBodies[0];
    if (!thead || !tbody) { return; }
    var ths = thead.rows[0].cells, state = { col: -1, dir: 1 };
    function hasData(){ return !(tbody.rows.length === 1 && tbody.rows[0].cells.length === 1 && (' '+tbody.rows[0].cells[0].className+' ').indexOf(' ci-empty ') !== -1); }
    function txt(r,i){ return (r.cells[i]?r.cells[i].textContent:'').trim(); }
    function isNum(i){ return ths[i] && (' '+ths[i].className+' ').indexOf(' num ') !== -1; }
    function num(t){ var n = parseFloat(t.replace(/[^0-9.\-]/g,'')); return isNaN(n)?null:n; }
    for (var i=0;i<ths.length;i++){ (function(idx){
      ths[idx].addEventListener('click', function(){
        if(!hasData()) return;
        var dir = (state.col===idx)?-state.dir:1; state={col:idx,dir:dir};
        var numeric = isNum(idx), rows = Array.prototype.slice.call(tbody.rows);
        rows.sort(function(a,b){
          var ta=txt(a,idx),tb=txt(b,idx);
          if(numeric){ var na=num(ta),nb=num(tb); if(na===null&&nb===null)return 0; if(na===null)return 1; if(nb===null)return -1; return (na-nb)*dir; }
          return ta.localeCompare(tb,undefined,{numeric:true,sensitivity:'base'})*dir;
        });
        for(var r=0;r<rows.length;r++){ tbody.appendChild(rows[r]); }
        for(var j=0;j<ths.length;j++){ ths[j].classList.remove('ci-asc','ci-desc'); }
        ths[idx].classList.add(dir>0?'ci-asc':'ci-desc');
      });
    })(i); }
  })();
</script>
