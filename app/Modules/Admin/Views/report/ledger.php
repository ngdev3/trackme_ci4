<?php
// Default range = selected financial year start (1 Apr) → today, so the
// default covers the whole FY's data (KisanVahi rows often predate this month).
$fyParts     = explode('-', fy()->FY);
$fromDefault = (isset($fyParts[0]) && $fyParts[0] !== '' ? $fyParts[0] : date('Y')) . '-04-01';
$toDefault   = date('Y-m-d');

// Retain state across refresh/bookmark via GET: ?acc=Name_<id>&from=Y-m-d&to=Y-m-d
$accDefault = isset($_GET['acc']) ? trim((string) $_GET['acc']) : '';
$gFrom = isset($_GET['from']) ? trim((string) $_GET['from']) : '';
$gTo   = isset($_GET['to'])   ? trim((string) $_GET['to'])   : '';
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $gFrom)) { $fromDefault = $gFrom; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $gTo))   { $toDefault   = $gTo; }
?>
<style>
* { box-sizing: border-box; }
.ledger-page { color: var(--tm-ink, #18243c); }
.ledger-shell { max-width: 1560px; margin: 0 auto; }

.ledger-title { position: relative; margin: 6px 0 18px; padding: 18px 20px 18px 66px; border: 1px solid rgba(var(--tm-brand-rgb,23,105,194), .13); border-radius: 12px; color: var(--tm-ink,#18243c) !important; background: linear-gradient(135deg, rgba(255,255,255,.98), rgba(255,255,255,.88)), radial-gradient(circle at 96% 0, rgba(var(--tm-brand-rgb,23,105,194), .16), transparent 38%); box-shadow: 0 14px 34px rgba(24,36,60,.08); font-size: 23px; font-weight: 900; }
.ledger-title:before { content: "\e6a4"; position: absolute; left: 20px; top: 50%; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 9px; color: #fff; background: linear-gradient(135deg, var(--tm-brand,#1769c2), var(--tm-brand-dark,#0c315f)); box-shadow: 0 10px 22px rgba(var(--tm-brand-rgb,23,105,194), .22); font-family: themify; font-size: 15px; transform: translateY(-50%); }
.ledger-title small { display:block; font-size:12px; font-weight:700; color:#8190a5; margin-top:3px; }

.ledger-panel { border: 1px solid var(--tm-line,#dce6f2); border-radius: 12px; background: #fff; box-shadow: 0 14px 34px rgba(24,36,60,.07); }

.ledger-control { padding: 16px 18px; margin-bottom: 16px; }
.lc-row { display: grid; grid-template-columns: minmax(220px,1.5fr) repeat(2, minmax(140px,.8fr)) auto auto auto; gap: 12px; align-items: end; }
.lc-field label { display:block; margin-bottom:7px; color:var(--tm-muted,#718096); font-size:11px; font-weight:900; letter-spacing:.05em; text-transform:uppercase; }
.lc-field input[type=text], .lc-field input[type=date] { width: 100%; min-height: 46px; border: 1px solid var(--tm-line,#dce6f2); border-radius: 10px; background: #fff; font-size: 14px; font-weight: 700; color: var(--tm-ink,#18243c); padding: 0 12px; }
.lc-field input:focus { outline:none; border-color: var(--tm-brand,#1769c2); box-shadow: 0 0 0 4px rgba(var(--tm-brand-rgb,23,105,194),.12); }
.lc-btn { min-height:46px; display:inline-flex; align-items:center; justify-content:center; gap:7px; padding:0 18px; border-radius:10px; font-weight:900; border:0; cursor:pointer; white-space:nowrap; }
.lc-btn-primary { color:#fff; background: linear-gradient(135deg, var(--tm-brand,#1769c2), var(--tm-brand-dark,#0c315f)); }
.lc-btn-primary.is-loading { opacity:.75; pointer-events:none; }
.lc-btn-primary .lg-btn-spin { display:inline-block; width:14px; height:14px; border:2px solid rgba(255,255,255,.5); border-top-color:#fff; border-radius:50%; animation: lgSpin .7s linear infinite; }
.lc-btn-nav { background:#eef3fa; color:var(--tm-brand-dark,#0c315f); border:1px solid #dce6f2; }
.lc-btn-nav:hover { background:var(--tm-brand,#1769c2); color:#fff; border-color:var(--tm-brand,#1769c2); }

/* tabs */
.ledger-tabs { display:flex; gap:8px; margin-bottom:16px; }
.lg-tab { display:inline-flex; align-items:center; gap:8px; padding:11px 20px; border-radius:10px 10px 0 0; border:1px solid var(--tm-line,#dce6f2); border-bottom:0; background:#eef3fa; color:#516174; font-weight:900; cursor:pointer; }
.lg-tab .lg-tab-cnt { font-size:11px; font-weight:900; background:#fff; color:#1769c2; border-radius:20px; padding:1px 9px; }
.lg-tab.active { background:#fff; color:var(--tm-brand-dark,#0c315f); box-shadow:0 -3px 0 var(--tm-brand,#1769c2) inset; }
.lg-tab.tab-kv.active { box-shadow:0 -3px 0 #d97706 inset; }
.lg-section { display:none; }
.lg-section.active { display:block; }

.ledger-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 16px; }
.ls-card { position: relative; overflow: hidden; border: 1px solid var(--tm-line,#dce6f2); border-radius: 12px; background: #fff; padding: 16px 18px; box-shadow: 0 10px 24px rgba(24,36,60,.06); }
.ls-card:before { content:""; position:absolute; left:0; top:0; bottom:0; width:5px; background:#94a3b8; }
.ls-card.ls-open:before { background:#1769c2; } .ls-card.ls-debit:before { background:#e5484d; } .ls-card.ls-credit:before { background:#1f9d70; } .ls-card.ls-closing:before { background: linear-gradient(#f59e0b,#d97706); }
.ls-card .ls-label { font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; color: var(--tm-muted,#718096); }
.ls-card .ls-value { margin-top: 7px; font-size: 21px; font-weight: 900; color: var(--tm-ink,#18243c); font-variant-numeric: tabular-nums; }
.ls-card.ls-debit .ls-value { color:#e5484d; } .ls-card.ls-credit .ls-value { color:#1f9d70; }
.ls-card .ls-sub { margin-top: 3px; font-size: 11px; font-weight: 700; color: #94a3b8; }

.ledger-charts { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 14px; margin-bottom: 16px; }
.lc-chart-card { border: 1px solid var(--tm-line,#dce6f2); border-radius: 12px; background: #fff; padding: 14px 16px; box-shadow: 0 10px 24px rgba(24,36,60,.06); }
.lc-chart-card h6 { margin: 0 0 10px; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; color: var(--tm-muted,#718096); }
.lc-canvas-wrap { position: relative; height: 240px; }
.lc-canvas-wrap.empty:after { content: "No data for this range"; position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#a8b4c4; font-size:12px; font-weight:800; }

.ledger-results { overflow: hidden; }
.ledger-meta { padding: 13px 16px; border-bottom: 1px solid var(--tm-line,#dce6f2); font-size: 13px; font-weight: 800; color: #516174; display:flex; flex-wrap:wrap; gap:6px 14px; align-items:center; }
.ledger-meta .lm-chip { background:#f1f6fc; border:1px solid #e1ecf8; border-radius:20px; padding:3px 12px; font-size:11.5px; color:#0c315f; }
.ledger-table-wrap { padding: 6px 14px 14px; }
table.ledger-table { margin: 0; width: 100%; border-collapse: separate; border-spacing: 0; }
table.ledger-table thead th { border: 0 !important; padding: 11px 10px !important; color: var(--tm-muted,#718096); background: linear-gradient(180deg, var(--tm-brand-soft,#eaf3ff), #fff); font-size: 11px; font-weight: 900; text-transform: uppercase; white-space: nowrap; }
table.ledger-table.kv thead th { background: linear-gradient(180deg, #fdf0e6, #fff); }
table.ledger-table tbody td { vertical-align: middle !important; border-top: 1px solid var(--tm-line,#dce6f2) !important; padding: 10px 10px !important; font-size: 13px; }
table.ledger-table tbody tr { cursor: pointer; }
table.ledger-table tbody tr:hover { background: rgba(var(--tm-brand-rgb,23,105,194), .045); }
.ledger-table .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.ledger-table .col-debit { color: #e5484d; font-weight: 800; }
.ledger-table .col-credit { color: #1f9d70; font-weight: 800; }
.ledger-table .col-balance { font-weight: 900; }
.ledger-table .col-balance.bal-cr { color: #1f9d70; }
.ledger-table .col-balance.bal-dr { color: #e5484d; }
.lg-remark { display:block; margin-top:3px; font-size:11px; font-weight:700; color:#94a3b8; }
.lg-created { font-size:11px; color:#8190a5; white-space:nowrap; }
.lg-remark-cell { color:#475569; font-size:12px; font-weight:600; max-width:240px; white-space:normal; word-break:break-word; }
.lg-remark-cell .text-muted { color:#c3ccd9; font-weight:400; }
.lg-dt-date { display:block; font-weight:700; color:#334155; white-space:nowrap; }
.lg-dt-time { display:block; margin-top:2px; font-size:11px; font-weight:700; color:#8190a5; white-space:nowrap; }

/* DataTables export buttons row */
.lg-dtbar { display:flex; align-items:center; justify-content:flex-end; gap:12px; flex-wrap:wrap; margin:2px 0 10px; }
.lg-dtbar .dt-buttons { margin:0; }

/* Live advanced search + autocomplete (no page reload) */
.lg-search { position:relative; max-width:440px; margin:0 0 14px; }
.lg-search-ic { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:14px; pointer-events:none; }
.lg-search-input { width:100%; min-height:44px; padding:0 40px 0 36px; border:1px solid #dce6f2; border-radius:12px; background:#fbfdff; font-weight:700; font-size:13px; color:#223; }
.lg-search-input:focus { outline:none; border-color:#1769c2; box-shadow:0 0 0 3px rgba(23,105,194,.12); }
.lg-search-clear { position:absolute; right:9px; top:50%; transform:translateY(-50%); border:0; background:#eef2f7; color:#64748b; width:24px; height:24px; border-radius:50%; cursor:pointer; font-size:15px; line-height:22px; text-align:center; display:none; }
.lg-search-clear:hover { background:#e0e7ef; }
.lg-search.has-val .lg-search-clear { display:block; }
.lg-suggest { position:absolute; top:calc(100% + 6px); left:0; right:0; background:#fff; border:1px solid #e3e9f2; border-radius:12px; box-shadow:0 18px 44px rgba(24,36,60,.18); z-index:60; max-height:340px; overflow:auto; display:none; padding:5px; }
.lg-suggest.show { display:block; }
.lg-sg-item { display:flex; align-items:center; gap:10px; padding:9px 11px; border-radius:8px; cursor:pointer; }
.lg-sg-item:hover, .lg-sg-item.active { background:#eff5ff; }
.lg-sg-ic { width:27px; height:27px; border-radius:7px; display:grid; place-items:center; font-size:12px; color:#fff; flex:none; }
.lg-sg-body { min-width:0; flex:1; }
.lg-sg-tp { display:block; font-size:9px; text-transform:uppercase; letter-spacing:.3px; color:#94a3b8; font-weight:800; margin-bottom:1px; }
.lg-sg-tx { display:block; font-size:13px; color:#334155; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.lg-sg-tx b { color:#1d4ed8; background:#e7efff; border-radius:3px; }
.lg-sg-cnt { font-size:11px; font-weight:800; color:#94a3b8; flex:none; }
.lg-sg-empty { padding:14px; text-align:center; color:#94a3b8; font-size:12.5px; font-weight:700; }
.sg-remark { background:#e08a12; } .sg-particulars { background:#2563eb; } .sg-voucher { background:#47566d; } .sg-user { background:#7c3aed; }

.lg-src { display:inline-flex; align-items:center; gap:4px; font-size:10.5px; font-weight:900; padding:2px 9px; border-radius:20px; white-space:nowrap; }
.lg-src-web { background:#e6f0fb; color:#1769c2; } .lg-src-app { background:#e8f7ee; color:#1f9d70; } .lg-src-kv { background:#fdf0e6; color:#d97706; }
.lg-attach { display:inline-flex; gap:7px; font-size:15px; }
.lg-attach .ti-camera { color:#1769c2; } .lg-attach .ti-microphone { color:#d97706; } .lg-attach .ti-video-camera { color:#e5484d; }

.lg-load-cell { text-align:center !important; padding:34px 12px !important; color:#516174; font-weight:700; }
.lg-load-cell .lg-load-text { display:block; margin-top:12px; font-size:13px; color:#718096; }
.lg-load-cell.lg-error { color:#e5484d; }
.lg-spinner { display:inline-block; width:22px; height:22px; border:3px solid #d6e4f5; border-top-color:#1769c2; border-radius:50%; animation: lgSpin .7s linear infinite; vertical-align:middle; }
@keyframes lgSpin { to { transform: rotate(360deg); } }

.dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--tm-brand,#1769c2) !important; border-color: var(--tm-brand,#1769c2) !important; color: #fff !important; border-radius: 6px; }
.dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 6px; }
.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { font-size: 12.5px; font-weight: 700; color: #516174; padding: 6px 2px; }
.dt-buttons { margin-bottom: 8px; } .dt-buttons .dt-button { border-radius:7px !important; font-weight:800 !important; }

.autocomplete { position: relative; display: block; }
.autocomplete-items { position: absolute; z-index: 99; top: calc(100% + 6px); left: 0; right: 0; max-height: 240px; overflow: auto; border: 1px solid var(--tm-line,#dce6f2); border-radius: 10px; background: #fff; box-shadow: 0 16px 34px rgba(24,36,60,.14); }
.autocomplete-items div { padding: 10px 12px; cursor: pointer; border-bottom: 1px solid var(--tm-line,#dce6f2); background: #fff; }
.autocomplete-items div:hover, .autocomplete-active { color: var(--tm-brand-dark,#0c315f) !important; background: var(--tm-brand-soft,#eaf3ff) !important; }

.lg-overlay { position:fixed; inset:0; z-index:20000; display:none; align-items:flex-start; justify-content:center; padding:40px 16px; background:rgba(15,23,42,.55); overflow:auto; }
.lg-overlay.show { display:flex; }
.lg-modal { width:640px; max-width:100%; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 30px 80px rgba(15,23,42,.4); }
.lg-head { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; background:linear-gradient(135deg,#1769c2,#0c315f); color:#fff; }
.lg-head h5 { margin:0; font-size:16px; font-weight:900; color:#fff; }
.lg-close { background:transparent; border:0; color:#fff; font-size:26px; line-height:1; cursor:pointer; }
.lg-body { padding:18px; }
.lg-grid { display:grid; grid-template-columns:130px 1fr; gap:8px 14px; margin-bottom:8px; }
.lg-k { font-size:11px; font-weight:900; text-transform:uppercase; letter-spacing:.03em; color:#718096; align-self:center; }
.lg-v { font-size:14px; font-weight:700; color:#18243c; }
.lg-media-wrap { margin-top:8px; border-top:1px dashed #dce6f2; padding-top:14px; }
.lg-media { margin-bottom:16px; }
.lg-media label { display:block; font-size:11px; font-weight:900; text-transform:uppercase; color:#718096; margin-bottom:6px; }
.lg-media img { max-width:100%; border-radius:8px; border:1px solid #dce6f2; }
.lg-media audio, .lg-media video { width:100%; border-radius:8px; }

@media (max-width: 1100px) { .ledger-charts { grid-template-columns: 1fr 1fr; } .ledger-charts .lc-chart-card:first-child { grid-column: 1 / -1; } .lc-row { grid-template-columns: 1fr 1fr; } }
@media (max-width: 700px) { .ledger-summary, .ledger-charts, .lc-row { grid-template-columns: 1fr; } }
</style>
<meta charset="UTF-8">

<main class="main-content bgc-grey-100 ledger-page">
  <div id="mainContent">
    <div class="container-fluid ledger-shell">
      <h4 class="ledger-title">Account Ledger <small>Cash &amp; KisanVahi shown and downloaded separately</small></h4>

      <div class="ledger-panel ledger-control">
        <div class="lc-row">
          <div class="lc-field autocomplete">
            <label for="myInput">Account</label>
            <?php echo form_input(array('autocomplete' => 'off', 'id' => 'myInput', 'name' => 'search_name', 'data-acc-picker' => '1', 'data-acc-nocreate' => '1', 'class' => 'form-control', 'placeholder' => 'Search an account…', 'value' => $accDefault)); ?>
          </div>
          <div class="lc-field"><label for="fromDate">From Date</label><input type="date" id="fromDate" value="<?= $fromDefault ?>"></div>
          <div class="lc-field"><label for="toDate">To Date</label><input type="date" id="toDate" value="<?= $toDefault ?>"></div>
          <button type="button" class="lc-btn lc-btn-primary" id="ledgerFind"><i class="ti-search"></i> Search</button>
          <button type="button" class="lc-btn lc-btn-nav" id="lgPrev" title="Previous period"><i class="ti-angle-left"></i> Prev</button>
          <button type="button" class="lc-btn lc-btn-nav" id="lgNext" title="Next period">Next <i class="ti-angle-right"></i></button>
        </div>
      </div>

      <div class="ledger-tabs">
        <div class="lg-tab tab-cash active" id="tabCash" onclick="switchTab('cash')"><i class="ti-wallet"></i> Account Ledger <span class="lg-tab-cnt" id="tabCashCnt">0</span></div>
        <div class="lg-tab tab-kv" id="tabKv" onclick="switchTab('kv')"><i class="ti-agenda"></i> Kisan Vahi <span class="lg-tab-cnt" id="tabKvCnt">0</span></div>
      </div>

      <!-- ===== Cash section ===== -->
      <div class="lg-section active" id="secCash">
        <div class="ledger-summary">
          <div class="ls-card ls-open"><div class="ls-label">Opening Balance</div><div class="ls-value" id="cashOpen">—</div><div class="ls-sub">before From date</div></div>
          <div class="ls-card ls-debit"><div class="ls-label">Total नाम (Debit)</div><div class="ls-value" id="cashDr">—</div><div class="ls-sub" id="cashDrCnt">&nbsp;</div></div>
          <div class="ls-card ls-credit"><div class="ls-label">Total जमा (Credit)</div><div class="ls-value" id="cashCr">—</div><div class="ls-sub" id="cashCrCnt">&nbsp;</div></div>
          <div class="ls-card ls-closing"><div class="ls-label">Closing Balance</div><div class="ls-value" id="cashClose">—</div><div class="ls-sub">as on To date</div></div>
        </div>
        <div class="ledger-charts">
          <div class="lc-chart-card"><h6>Daily नाम vs जमा</h6><div class="lc-canvas-wrap" id="barWrap"><canvas id="lgBarChart"></canvas></div></div>
          <div class="lc-chart-card"><h6>Balance Trend</h6><div class="lc-canvas-wrap" id="lineWrap"><canvas id="lgLineChart"></canvas></div></div>
          <div class="lc-chart-card"><h6>Source Mix</h6><div class="lc-canvas-wrap" id="srcWrap"><canvas id="lgSrcChart"></canvas></div></div>
        </div>
        <div class="ledger-panel ledger-results">
          <div class="ledger-meta" id="cashMeta">Search an account to load the cash ledger.</div>
          <div class="lg-search" data-sec="cash">
            <i class="ti-search lg-search-ic"></i>
            <input type="text" class="lg-search-input" id="cashSearch" placeholder="Search remark, particulars, voucher, amount, user…" autocomplete="off">
            <button type="button" class="lg-search-clear" id="cashSearchClear" title="Clear">&times;</button>
            <div class="lg-suggest" id="cashSuggest"></div>
          </div>
          <div class="ledger-table-wrap">
            <table class="table ledger-table" id="cashTable" style="width:100%">
              <thead><tr>
                <th>#</th><th>Date &amp; Time</th><th>Remark</th><th>Source</th><th>Added By</th><th>Created</th>
                <th class="num">नाम (Dr)</th><th class="num">जमा (Cr)</th><th class="num">Balance</th><th>Attach</th>
              </tr></thead>
              <tbody id="cashBody"><tr><td class="lg-load-cell" colspan="10"><span class="lg-load-text">Search an account to load the cash ledger.</span></td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ===== KisanVahi section ===== -->
      <div class="lg-section" id="secKv">
        <div class="ledger-summary">
          <div class="ls-card ls-open"><div class="ls-label">Opening (Outstanding)</div><div class="ls-value" id="kvOpen">—</div><div class="ls-sub">before From date</div></div>
          <div class="ls-card ls-debit"><div class="ls-label">Total नाम (Amount)</div><div class="ls-value" id="kvDr">—</div><div class="ls-sub" id="kvDrCnt">&nbsp;</div></div>
          <div class="ls-card ls-credit"><div class="ls-label">Total जमा (Paid)</div><div class="ls-value" id="kvCr">—</div><div class="ls-sub" id="kvCrCnt">&nbsp;</div></div>
          <div class="ls-card ls-closing"><div class="ls-label">Closing (Outstanding)</div><div class="ls-value" id="kvClose">—</div><div class="ls-sub">as on To date</div></div>
        </div>
        <div class="ledger-panel ledger-results">
          <div class="ledger-meta" id="kvMeta">Search an account to load the KisanVahi ledger.</div>
          <div class="lg-search" data-sec="kv">
            <i class="ti-search lg-search-ic"></i>
            <input type="text" class="lg-search-input" id="kvSearch" placeholder="Search farmer, remark, voucher, amount…" autocomplete="off">
            <button type="button" class="lg-search-clear" id="kvSearchClear" title="Clear">&times;</button>
            <div class="lg-suggest" id="kvSuggest"></div>
          </div>
          <div class="ledger-table-wrap">
            <table class="table ledger-table kv" id="kvTable" style="width:100%">
              <thead><tr>
                <th>#</th><th>Date</th><th>Farmer</th><th>Remark</th><th>Voucher</th>
                <th class="num">नाम (Amount)</th><th class="num">जमा (Paid)</th><th class="num">Balance</th>
              </tr></thead>
              <tbody id="kvBody"><tr><td class="lg-load-cell" colspan="8"><span class="lg-load-text">Search an account to load the KisanVahi ledger.</span></td></tr></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<div class="lg-overlay" id="ledgerMediaModal">
  <div class="lg-modal">
    <div class="lg-head"><h5>Entry Details</h5><button type="button" class="lg-close" onclick="closeLedgerMedia()">&times;</button></div>
    <div class="lg-body" id="ledgerMediaBody"></div>
  </div>
</div>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.23/css/jquery.dataTables.min.css" />
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.4.0/css/buttons.dataTables.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.4.0/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.4.0/js/buttons.flash.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.4.0/js/buttons.html5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
var LG = null;                 // { account, cash:{opening,rows}, kv:{opening,rows} }
var lgCharts = { bar: null, line: null, src: null };

var SECTIONS = {
  cash: { kind:'cash', label:'Account Ledger', table:'#cashTable', body:'#cashBody', meta:'#cashMeta',
          open:'#cashOpen', dr:'#cashDr', cr:'#cashCr', close:'#cashClose', drCnt:'#cashDrCnt', crCnt:'#cashCrCnt',
          cols:10, hasAttach:true, hasCharts:true,
          pdfDr:'Withdrawal (Naam)', pdfCr:'Deposit (Jama)', prDr:'नाम (Withdrawal)', prCr:'जमा (Deposit)' },
  kv:   { kind:'kv', label:'KisanVahi Ledger', table:'#kvTable', body:'#kvBody', meta:'#kvMeta',
          open:'#kvOpen', dr:'#kvDr', cr:'#kvCr', close:'#kvClose', drCnt:'#kvDrCnt', crCnt:'#kvCrCnt',
          cols:8, hasAttach:false, hasCharts:false,
          pdfDr:'Amount (Naam)', pdfCr:'Paid (Jama)', prDr:'नाम (Amount)', prCr:'जमा (Paid)' }
};

function lgEsc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
function fmtINR(n){ return (parseFloat(n)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function fmtShort(n){ var v=Math.abs(parseFloat(n)||0); if(v>=1e7)return (v/1e7).toFixed(2)+' Cr'; if(v>=1e5)return (v/1e5).toFixed(2)+' L'; if(v>=1e3)return (v/1e3).toFixed(1)+'k'; return String(Math.round(v)); }
function fmtDate(s){ if(!s)return''; var p=String(s).split(' ')[0].split('-'); return (p.length===3)?(p[2]+'-'+p[1]+'-'+p[0]):s; }
function lgTimeOf(s){ if(!s) return ''; var m=String(s).match(/\d{1,2}:\d{2}(\s*[APap][Mm])?/); return m?m[0].replace(/\s+/g,' ').trim():''; }
function fmtDateTime(d){ var t=lgTimeOf(d.added_on)||lgTimeOf(d.date); return '<span class="lg-dt-date">'+lgEsc(fmtDate(d.date))+'</span>'+(t?'<span class="lg-dt-time">'+lgEsc(t)+'</span>':''); }
function balLabel(n){ var v=Math.abs(n); if(Math.round(v*100)===0)return fmtINR(0); return fmtINR(v)+(n>0?' Cr':' Dr'); }
function sourceText(src){ var s=String(src||'').toLowerCase(); if(s==='app')return 'Mobile'; if(s==='kv')return 'KV'; return 'Web'; }
function sourceBadge(src){ var s=String(src||'').toLowerCase(); if(s==='app')return '<span class="lg-src lg-src-app"><i class="ti-mobile"></i> Mobile</span>'; if(s==='kv')return '<span class="lg-src lg-src-kv"><i class="ti-agenda"></i> KV</span>'; return '<span class="lg-src lg-src-web"><i class="ti-desktop"></i> Web</span>'; }
function attachIcons(d){ var ic=''; if(d.image_url)ic+='<i class="ti-camera" title="Photo"></i>'; if(d.voice_url)ic+='<i class="ti-microphone" title="Voice"></i>'; if(d.video_url)ic+='<i class="ti-video-camera" title="Video"></i>'; return ic?'<span class="lg-attach">'+ic+'</span>':'<span class="text-muted">—</span>'; }
function hasAccount(){ return (($('#myInput').val()||'').indexOf('_')!==-1); }
function isoDate(d){ return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); }
function secData(k){ return LG ? (k==='cash'?LG.cash:LG.kv) : null; }

/* ---- prev / next period (shift window by its own length) ---- */
function shiftRange(dir){
  var f=$('#fromDate').val(), t=$('#toDate').val();
  if(!f||!t){ alert('Please set both From and To dates.'); return; }
  var fd=new Date(f+'T00:00:00'), td=new Date(t+'T00:00:00');
  if(td<fd){ var tmp=fd; fd=td; td=tmp; }
  var span=Math.round((td-fd)/86400000), nf, nt;
  if(dir<0){ nt=new Date(fd); nt.setDate(nt.getDate()-1); nf=new Date(nt); nf.setDate(nf.getDate()-span); }
  else { nf=new Date(td); nf.setDate(nf.getDate()+1); nt=new Date(nf); nt.setDate(nt.getDate()+span); }
  $('#fromDate').val(isoDate(nf)); $('#toDate').val(isoDate(nt));
  if(hasAccount()) loadLedger();
}

/* ---- balance computation (shared) ---- */
function computeBalances(section){
  var opening=parseFloat(section.opening)||0, bal=opening, totDr=0, totCr=0, dCnt=0, cCnt=0, out=[];
  (section.rows||[]).forEach(function(d){
    var dr=parseFloat(d.debit)||0, cr=parseFloat(d.credit)||0;
    bal += cr - dr; totDr+=dr; totCr+=cr; if(dr>0)dCnt++; if(cr>0)cCnt++;
    out.push({d:d, bal:bal});
  });
  return { opening:opening, rows:out, totDr:totDr, totCr:totCr, closing:bal, dCnt:dCnt, cCnt:cCnt };
}

/* ---- row html ---- */
function statusRow(cols, state, text){
  var inner=(state==='spinner')?'<span class="lg-spinner"></span><span class="lg-load-text">'+lgEsc(text)+'</span>':'<span class="lg-load-text">'+lgEsc(text)+'</span>';
  return '<tr><td class="lg-load-cell'+(state==='error'?' lg-error':'')+'" colspan="'+cols+'">'+inner+'</td></tr>';
}
function cashRowHtml(d,i,bal){
  var balCls=(bal>0)?'bal-cr':(bal<0?'bal-dr':'');
  var remark=(d.remark&&String(d.remark).trim()!=='')?lgEsc(d.remark):'<span class="text-muted">—</span>';
  return '<tr onclick="showEntry(\'cash\','+i+')">' +
    '<td data-order="'+(i+1)+'">'+(i+1)+'</td><td data-order="'+lgEsc(String(d.date||"").split(" ")[0])+'">'+fmtDateTime(d)+'</td><td class="lg-remark-cell">'+remark+'</td>' +
    '<td>'+sourceBadge(d.source_label)+'</td><td>'+lgEsc(d.added_by_name||'-')+'</td><td><span class="lg-created">'+lgEsc(d.added_on||'-')+'</span></td>' +
    '<td class="num col-debit" data-order="'+(parseFloat(d.debit)||0)+'">'+(d.debit>0?fmtINR(d.debit):'')+'</td>' +
    '<td class="num col-credit" data-order="'+(parseFloat(d.credit)||0)+'">'+(d.credit>0?fmtINR(d.credit):'')+'</td>' +
    '<td class="num col-balance '+balCls+'" data-order="'+bal+'">'+balLabel(bal)+'</td>' +
    '<td>'+attachIcons(d)+'</td></tr>';
}
function kvRowHtml(d,i,bal){
  var balCls=(bal>0)?'bal-cr':(bal<0?'bal-dr':'');
  var remark=(d.remark&&String(d.remark).trim()!=='')?lgEsc(d.remark):'<span class="text-muted">—</span>';
  return '<tr onclick="showEntry(\'kv\','+i+')">' +
    '<td data-order="'+(i+1)+'">'+(i+1)+'</td><td data-order="'+lgEsc(String(d.date||"").split(" ")[0])+'">'+lgEsc(fmtDate(d.date))+'</td><td>'+lgEsc(d.particulars)+'</td><td class="lg-remark-cell">'+remark+'</td><td>'+lgEsc(d.voucher)+'</td>' +
    '<td class="num col-debit" data-order="'+(parseFloat(d.debit)||0)+'">'+(d.debit>0?fmtINR(d.debit):'')+'</td>' +
    '<td class="num col-credit" data-order="'+(parseFloat(d.credit)||0)+'">'+(d.credit>0?fmtINR(d.credit):'')+'</td>' +
    '<td class="num col-balance '+balCls+'" data-order="'+bal+'">'+balLabel(bal)+'</td></tr>';
}

/* ---- render a section ---- */
function renderSection(k, from, to){
  var cfg=SECTIONS[k], sec=secData(k);
  if($.fn.DataTable.isDataTable(cfg.table)) $(cfg.table).DataTable().clear().destroy();
  if(!sec){ return; }
  var c=computeBalances(sec);
  $(cfg.open).text(balLabel(c.opening));
  $(cfg.dr).text(fmtINR(c.totDr));
  $(cfg.cr).text(fmtINR(c.totCr));
  $(cfg.close).text(balLabel(c.closing));
  $(cfg.drCnt).text(c.dCnt+' entr'+(c.dCnt===1?'y':'ies'));
  $(cfg.crCnt).text(c.cCnt+' entr'+(c.cCnt===1?'y':'ies'));
  $(k==='cash'?'#tabCashCnt':'#tabKvCnt').text(sec.rows.length);

  if(!sec.rows.length){
    $(cfg.body).html(statusRow(cfg.cols,'empty','No '+(k==='cash'?'cash':'KisanVahi')+' entries for this range.'));
    updateMeta(cfg.meta, from, to, 0);
    if(cfg.hasCharts) renderCharts([], c.opening);
    return;
  }
  var html='', builder=(k==='cash')?cashRowHtml:kvRowHtml;
  for(var i=0;i<c.rows.length;i++) html += builder(c.rows[i].d, i, c.rows[i].bal);
  $(cfg.body).html(html);
  updateMeta(cfg.meta, from, to, sec.rows.length);
  initSectionTable(k);
  if(cfg.hasCharts) renderCharts(sec.rows, c.opening);
}

function updateMeta(sel, from, to, count){
  var acct=LG&&LG.account?LG.account:null;
  var name=acct&&acct.name?acct.name:($('#myInput').val()||'').split('_')[0];
  var bank=acct&&acct.bank_name?acct.bank_name:'';
  var ac=acct&&acct.purchaser_account_no?('A/c '+acct.purchaser_account_no):'';
  var chips='<span class="lm-chip">'+lgEsc(fmtDate(from))+' → '+lgEsc(fmtDate(to))+'</span>';
  if(bank) chips+='<span class="lm-chip">'+lgEsc(bank)+'</span>';
  if(ac) chips+='<span class="lm-chip">'+lgEsc(ac)+'</span>';
  chips+='<span class="lm-chip">'+count+' entr'+(count===1?'y':'ies')+'</span>';
  $(sel).html('<b>'+lgEsc(name)+'</b> '+chips);
}

function initSectionTable(k){
  var cfg=SECTIONS[k];
  setTimeout(function(){
    if($.fn.DataTable.isDataTable(cfg.table)) $(cfg.table).DataTable().clear().destroy();
    var name=(LG&&LG.account&&LG.account.name)?LG.account.name:($('#myInput').val()||'').split('_')[0];
    var title=(name?name+' - ':'')+cfg.label;
    var exportOpts={ format:{ body:function(data){ return String(data==null?'':data).replace(/<[^>]*>/g,'').replace(/\s+/g,' ').trim(); } } };
    if(cfg.hasAttach) exportOpts.columns=':not(:last-child)';
    $(cfg.table).DataTable({
      dom: "<'lg-dtbar'B>rt<'row'<'col-sm-6'l><'col-sm-6'i>>p",
      destroy:true, paging:true, pageLength:25, lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']], info:true,
      ordering:true, order:[], columnDefs: cfg.hasAttach ? [{ orderable:false, targets:-1 }] : [],
      buttons:[
        { extend:'copy', title:title, exportOptions:exportOpts },
        { extend:'csv', title:title, filename:title, exportOptions:exportOpts },
        { extend:'excel', title:title, filename:title, exportOptions:exportOpts },
        { text:'<i class="ti-file"></i> PDF', className:'buttons-pdf', action:function(){ pdfSection(k); } },
        { text:'<i class="ti-printer"></i> Print', className:'buttons-print', action:function(){ printSection(k); } }
      ]
    });
  },0);
}

/* ---- tab switch ---- */
function switchTab(k){
  $('#tabCash').toggleClass('active', k==='cash');
  $('#tabKv').toggleClass('active', k==='kv');
  $('#secCash').toggleClass('active', k==='cash');
  $('#secKv').toggleClass('active', k==='kv');
  var cfg=SECTIONS[k];
  if($.fn.DataTable.isDataTable(cfg.table)) $(cfg.table).DataTable().columns.adjust();
}

/* ---- charts (cash only) ---- */
function destroyCharts(){ ['bar','line','src'].forEach(function(x){ if(lgCharts[x]){ lgCharts[x].destroy(); lgCharts[x]=null; } }); }
function renderCharts(rows, opening){
  destroyCharts();
  var hasData=rows&&rows.length;
  $('#barWrap').toggleClass('empty',!hasData); $('#lineWrap').toggleClass('empty',!hasData); $('#srcWrap').toggleClass('empty',!hasData);
  if(!hasData) return;
  var days={}, order=[];
  rows.forEach(function(r){ var d=String(r.date).split(' ')[0]; if(!days[d]){days[d]={dr:0,cr:0};order.push(d);} days[d].dr+=parseFloat(r.debit)||0; days[d].cr+=parseFloat(r.credit)||0; });
  order.sort();
  var labels=order.map(function(d){return d.slice(8)+'/'+d.slice(5,7);});
  var drData=order.map(function(d){return days[d].dr;}), crData=order.map(function(d){return days[d].cr;});
  var bal=opening, balData=order.map(function(d){ bal+=days[d].cr-days[d].dr; return bal; });
  var moneyTick=function(v){return fmtShort(v);};
  lgCharts.bar=new Chart(document.getElementById('lgBarChart'),{ type:'bar',
    data:{labels:labels,datasets:[{label:'नाम (Dr)',data:drData,backgroundColor:'#e5484d',borderRadius:4,maxBarThickness:26},{label:'जमा (Cr)',data:crData,backgroundColor:'#1f9d70',borderRadius:4,maxBarThickness:26}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{boxWidth:12,font:{weight:'700'}}},tooltip:{callbacks:{label:function(c){return c.dataset.label+': ₹ '+fmtINR(c.parsed.y);}}}},scales:{x:{grid:{display:false}},y:{ticks:{callback:moneyTick},grid:{color:'#eef2f7'}}}} });
  lgCharts.line=new Chart(document.getElementById('lgLineChart'),{ type:'line',
    data:{labels:labels,datasets:[{label:'Balance',data:balData,borderColor:'#1769c2',backgroundColor:'rgba(23,105,194,.12)',fill:true,tension:.3,pointRadius:2,borderWidth:2}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return 'Balance: '+balLabel(c.parsed.y);}}}},scales:{x:{grid:{display:false}},y:{ticks:{callback:moneyTick},grid:{color:'#eef2f7'}}}} });
  var src={Mobile:0,Web:0,KV:0};
  rows.forEach(function(r){ var s=sourceText(r.source_label); var a=(parseFloat(r.debit)||0)+(parseFloat(r.credit)||0); if(src[s]==null)src[s]=0; src[s]+=a; });
  lgCharts.src=new Chart(document.getElementById('lgSrcChart'),{ type:'doughnut',
    data:{labels:['Mobile','Web','KV'],datasets:[{data:[src.Mobile,src.Web,src.KV],backgroundColor:['#1f9d70','#1769c2','#d97706'],borderWidth:2,borderColor:'#fff'}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'58%',plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{weight:'700'}}},tooltip:{callbacks:{label:function(c){return c.label+': ₹ '+fmtINR(c.parsed);}}}}} });
}

/* ---- detail popup (Remarks + attachments) ---- */
function lgRow(k,v){ return '<div class="lg-k">'+k+'</div><div class="lg-v">'+v+'</div>'; }
function showEntry(k,i){
  var sec=secData(k); if(!sec) return;
  var d=sec.rows[i]; if(!d) return;
  var g='';
  g+=lgRow('Date',lgEsc(fmtDate(d.date)));
  g+=lgRow('Particulars',lgEsc(d.particulars));
  if(d.voucher) g+=lgRow('Voucher',lgEsc(d.voucher));
  if(parseFloat(d.debit)>0) g+=lgRow(k==='kv'?'नाम (Amount)':'नाम (Debit)','&#8377; '+fmtINR(d.debit));
  if(parseFloat(d.credit)>0) g+=lgRow(k==='kv'?'जमा (Paid)':'जमा (Credit)','&#8377; '+fmtINR(d.credit));
  if(k==='cash'){
    g+=lgRow('Source',sourceBadge(d.source_label));
    if(d.added_by_name) g+=lgRow('Added By',lgEsc(d.added_by_name));
    if(d.added_on) g+=lgRow('Created',lgEsc(d.added_on));
    g+=lgRow('IP Address', d.ip?lgEsc(d.ip):'<span class="text-muted">Not captured</span>');
    if(d.lat&&d.lng){ g+=lgRow('Location','<a href="https://www.google.com/maps?q='+encodeURIComponent(d.lat+','+d.lng)+'" target="_blank" rel="noopener"><i class="ti-location-pin"></i> '+lgEsc(d.lat)+', '+lgEsc(d.lng)+'</a>'); }
    else { g+=lgRow('Location','<span class="text-muted">Not captured</span>'); }
  }
  g+=lgRow('Remarks',(d.remark&&String(d.remark).trim()!=='')?lgEsc(d.remark):'<span class="text-muted">No remarks</span>');
  var m='';
  if(d.image_url) m+='<div class="lg-media"><label>Photo</label><a href="'+d.image_url+'" target="_blank"><img src="'+d.image_url+'" alt="photo"></a></div>';
  if(d.voice_url) m+='<div class="lg-media"><label>Voice</label><audio controls preload="none" src="'+d.voice_url+'"></audio></div>';
  if(d.video_url) m+='<div class="lg-media"><label>Video</label><video controls preload="none" src="'+d.video_url+'"></video></div>';
  if(!m) m='<div class="text-muted" style="font-size:12px;">No attachments for this entry.</div>';
  document.getElementById('ledgerMediaBody').innerHTML='<div class="lg-grid">'+g+'</div><div class="lg-media-wrap">'+m+'</div>';
  document.getElementById('ledgerMediaModal').classList.add('show');
}
function closeLedgerMedia(){ document.getElementById('ledgerMediaModal').classList.remove('show'); setTimeout(function(){ document.getElementById('ledgerMediaBody').innerHTML=''; },250); }

/* ---- passbook header ---- */
function headerInfo(){ var a=LG&&LG.account?LG.account:{}; return { name:a.name||($('#myInput').val()||'').split('_')[0], bank:a.bank_name||'', acno:a.purchaser_account_no||'', from:$('#fromDate').val(), to:$('#toDate').val() }; }

/* ---- PRINT (per section) ---- */
function printSection(k){
  var sec=secData(k); var cfg=SECTIONS[k];
  if(!sec||!sec.rows.length){ alert('Nothing to print in this section.'); return; }
  var c=computeBalances(sec), h=headerInfo();
  var rowsHtml='<tr class="op"><td></td><td class="pp-part"><b>Opening Balance</b></td><td></td><td class="r"></td><td class="r"></td><td class="r"><b>'+balLabel(c.opening)+'</b></td></tr>';
  var isCash=(k==='cash');
  c.rows.forEach(function(x,idx){
    var d=x.d, hasRmk=(d.remark&&String(d.remark).trim()!=='');
    var dateCell, midCell;
    if(isCash){
      var tm=lgTimeOf(d.added_on);
      dateCell='<span class="pp-name">'+lgEsc(fmtDate(d.date))+'</span>'+(tm?'<span class="pp-sub">'+lgEsc(tm)+'</span>':'');
      var sub=[sourceText(d.source_label)]; if(d.added_by_name)sub.push('by '+d.added_by_name);
      var att=[]; if(d.image_url)att.push('Photo'); if(d.voice_url)att.push('Voice'); if(d.video_url)att.push('Video'); if(att.length)sub.push('📎 '+att.join('/'));
      midCell='<span class="pp-name">'+(hasRmk?lgEsc(d.remark):'—')+'</span><span class="pp-sub">'+lgEsc(sub.join(' · '))+'</span>';
    } else {
      dateCell=lgEsc(fmtDate(d.date));
      midCell='<span class="pp-name">'+lgEsc(d.particulars)+'</span>'+(hasRmk?'<span class="pp-rmk"> — '+lgEsc(d.remark)+'</span>':'');
    }
    rowsHtml+='<tr><td class="c">'+(idx+1)+'</td><td>'+dateCell+'</td><td class="pp-part">'+midCell+'</td>' +
      '<td class="r dr">'+(d.debit>0?fmtINR(d.debit):'')+'</td><td class="r cr">'+(d.credit>0?fmtINR(d.credit):'')+'</td><td class="r">'+balLabel(x.bal)+'</td></tr>';
  });
  var totals='<tr class="tot"><td colspan="3" class="r"><b>TOTAL</b></td><td class="r dr"><b>'+fmtINR(c.totDr)+'</b></td><td class="r cr"><b>'+fmtINR(c.totCr)+'</b></td><td></td></tr>' +
    '<tr class="cl"><td colspan="5" class="r"><b>CLOSING BALANCE</b></td><td class="r"><b>'+balLabel(c.closing)+'</b></td></tr>';
  var win=window.open('','_blank','height=900,width=1000');
  if(!win){ alert('Popup blocked! Please allow popups to print.'); return; }
  win.document.write(
    '<html><head><meta charset="utf-8"><title>'+lgEsc(cfg.label)+' - '+lgEsc(h.name)+'</title><style>' +
    '@page{size:A4 landscape;margin:10mm;}*{box-sizing:border-box;}body{font-family:Arial,Helvetica,sans-serif;color:#111;font-size:11px;margin:0;}' +
    '.pp-head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #0c315f;padding-bottom:8px;margin-bottom:6px;}' +
    '.pp-title{font-size:18px;font-weight:800;color:#0c315f;}.pp-title small{display:block;font-size:10px;font-weight:600;color:#666;}' +
    '.pp-acct{text-align:right;font-size:11px;line-height:1.5;}.pp-acct b{font-size:13px;}' +
    '.pp-bal-line{display:flex;justify-content:space-between;font-size:11px;font-weight:700;margin:4px 0 8px;}' +
    'table{width:100%;border-collapse:collapse;}th,td{border:1px solid #b9c6d6;padding:4px 6px;vertical-align:top;}' +
    'th{background:#0c315f;color:#fff;font-size:10px;text-transform:uppercase;text-align:left;}th.r,td.r{text-align:right;white-space:nowrap;}td.c{text-align:center;}' +
    'tbody tr:nth-child(even){background:#f5f8fc;}tr.op td,tr.tot td,tr.cl td{background:#eaf1fb !important;}td.dr{color:#b3261e;}td.cr{color:#137a4f;}' +
    '.pp-name{font-weight:700;}.pp-rmk{color:#555;}.pp-sub{display:block;font-size:9px;color:#7a8699;margin-top:1px;}.pp-foot{margin-top:8px;font-size:9px;color:#888;text-align:right;}' +
    '</style></head><body>' +
    '<div class="pp-head"><div class="pp-title">'+lgEsc(cfg.label)+'<small>Running-balance passbook</small></div>' +
    '<div class="pp-acct"><b>'+lgEsc(h.name)+'</b>'+(h.acno?'<br>A/c: '+lgEsc(h.acno):'')+(h.bank?'<br>'+lgEsc(h.bank):'')+'</div></div>' +
    '<div class="pp-bal-line"><span>Period: '+lgEsc(fmtDate(h.from))+' to '+lgEsc(fmtDate(h.to))+'</span><span>Opening: '+balLabel(c.opening)+'  |  Closing: '+balLabel(c.closing)+'</span></div>' +
    '<table><thead><tr><th>#</th><th>'+(isCash?'Date &amp; Time':'Date')+'</th><th>'+(isCash?'Remark':'Particulars')+'</th><th class="r">'+cfg.prDr+'</th><th class="r">'+cfg.prCr+'</th><th class="r">Balance</th></tr></thead><tbody>'+rowsHtml+totals+'</tbody></table>' +
    '<div class="pp-foot">Printed: '+new Date().toLocaleString('en-GB')+'</div></body></html>'
  );
  win.document.close(); win.focus();
  setTimeout(function(){ win.print(); }, 350);
}

/* ---- PDF (per section, pdfmake) ---- */
function pdfSection(k){
  var sec=secData(k); var cfg=SECTIONS[k];
  if(!sec||!sec.rows.length){ alert('Nothing to export in this section.'); return; }
  if(typeof pdfMake==='undefined'){ alert('PDF library not loaded. Please refresh.'); return; }
  var c=computeBalances(sec), h=headerInfo();
  var th=function(t,r){ return {text:t,style:'th',alignment:r?'right':'left'}; };
  var isCash=(k==='cash');
  var body=[[th('#'),th(isCash?'Date & Time':'Date'),th(isCash?'Remark':'Particulars'),th(cfg.pdfDr,true),th(cfg.pdfCr,true),th('Balance',true)]];
  body.push([{text:''},{text:'Opening Balance',colSpan:2,bold:true,fillColor:'#eaf1fb'},{},{text:'',fillColor:'#eaf1fb'},{text:'',fillColor:'#eaf1fb'},{text:balLabel(c.opening),alignment:'right',bold:true,fillColor:'#eaf1fb'}]);
  c.rows.forEach(function(x,idx){
    var d=x.d, hasRmk=(d.remark&&String(d.remark).trim()!=='');
    var tm=lgTimeOf(d.added_on);
    var dateCell=isCash ? {stack:[{text:fmtDate(d.date)}].concat(tm?[{text:tm,fontSize:6.5,color:'#8190a5'}]:[])} : {text:fmtDate(d.date)};
    var midCell;
    if(isCash){
      var ps=[ hasRmk?{text:d.remark,bold:true}:{text:'—',color:'#bbb'} ];
      var sub=[sourceText(d.source_label)]; if(d.added_by_name)sub.push('by '+d.added_by_name);
      var att=[]; if(d.image_url)att.push('Photo'); if(d.voice_url)att.push('Voice'); if(d.video_url)att.push('Video'); if(att.length)sub.push('['+att.join('/')+']');
      ps.push({text:sub.join(' · '),fontSize:6.5,color:'#8190a5'});
      midCell={stack:ps};
    } else {
      var ps2=[{text:d.particulars||'',bold:true}];
      if(hasRmk) ps2.push({text:d.remark,fontSize:7,color:'#555'});
      midCell={stack:ps2};
    }
    body.push([{text:String(idx+1),alignment:'center'}, dateCell, midCell,
      {text:d.debit>0?fmtINR(d.debit):'',alignment:'right',color:'#b3261e'},
      {text:d.credit>0?fmtINR(d.credit):'',alignment:'right',color:'#137a4f'},
      {text:balLabel(x.bal),alignment:'right'}]);
  });
  body.push([{text:'TOTAL',colSpan:3,alignment:'right',bold:true,fillColor:'#eaf1fb'},{},{},{text:fmtINR(c.totDr),alignment:'right',bold:true,fillColor:'#eaf1fb'},{text:fmtINR(c.totCr),alignment:'right',bold:true,fillColor:'#eaf1fb'},{text:'',fillColor:'#eaf1fb'}]);
  body.push([{text:'CLOSING BALANCE',colSpan:5,alignment:'right',bold:true,fillColor:'#dfeafb'},{},{},{},{},{text:balLabel(c.closing),alignment:'right',bold:true,fillColor:'#dfeafb'}]);
  var doc={ pageOrientation:'landscape', pageSize:'A4', pageMargins:[22,64,22,34],
    header:{ margin:[22,18,22,0], columns:[
      {text:[{text:cfg.label+'\n',fontSize:13,bold:true,color:'#0c315f'},{text:'Running-balance passbook',fontSize:7,color:'#777'}]},
      {text:[{text:(h.name||'')+'\n',fontSize:10,bold:true},{text:(h.acno?'A/c: '+h.acno+'   ':'')+(h.bank||''),fontSize:7,color:'#555'}],alignment:'right'} ] },
    content:[
      { columns:[ {text:'Period: '+fmtDate(h.from)+' to '+fmtDate(h.to),fontSize:8,bold:true}, {text:'Opening: '+balLabel(c.opening)+'   |   Closing: '+balLabel(c.closing),fontSize:8,bold:true,alignment:'right'} ], margin:[0,0,0,6] },
      { table:{ headerRows:1, widths:[16,62,'*',70,70,78], body:body }, layout:{ hLineColor:function(){return '#b9c6d6';}, vLineColor:function(){return '#b9c6d6';}, hLineWidth:function(){return 0.5;}, vLineWidth:function(){return 0.5;}, paddingTop:function(){return 2.5;}, paddingBottom:function(){return 2.5;} } }
    ],
    styles:{ th:{bold:true,fontSize:8,color:'#ffffff',fillColor:'#0c315f'} }, defaultStyle:{fontSize:8},
    footer:function(cur,tot){ return { columns:[ {text:'Printed: '+new Date().toLocaleString('en-GB'),fontSize:6.5,color:'#999',margin:[22,0,0,0]}, {text:'Page '+cur+' of '+tot,alignment:'right',fontSize:6.5,color:'#999',margin:[0,0,22,0]} ] }; }
  };
  var tag=(k==='cash')?'Account_Ledger':'KisanVahi_Ledger';
  var fname=(h.name?h.name.replace(/[^\w-]+/g,'_')+'_':'')+tag+'_'+h.from+'_to_'+h.to+'.pdf';
  pdfMake.createPdf(doc).download(fname);
}

/* ---- fetch ---- */
function setLoading(on){ var $b=$('#ledgerFind'); if(on){ $b.data('label',$b.html()); $b.addClass('is-loading').html('<span class="lg-btn-spin"></span> Searching…'); } else { $b.removeClass('is-loading').html($b.data('label')||'<i class="ti-search"></i> Search'); } }
function loadLedger(){
  if(!hasAccount()){ alert('Please pick an account from the suggestions.'); return; }
  var from=$('#fromDate').val(), to=$('#toDate').val();
  if(!from||!to){ alert('Please select both From and To dates.'); return; }
  if(new Date(from)>new Date(to)){ alert('From date cannot be after To date.'); return; }
  // Persist the current selection in the URL so a refresh / bookmark reloads it.
  try{ var _q=new URLSearchParams(); _q.set('acc',$('#myInput').val()); _q.set('from',from); _q.set('to',to); history.replaceState(null,'',location.pathname+'?'+_q.toString()); }catch(e){}
  $('#cashBody').html(statusRow(SECTIONS.cash.cols,'spinner','Fetching cash ledger…'));
  $('#kvBody').html(statusRow(SECTIONS.kv.cols,'spinner','Fetching KisanVahi…'));
  setLoading(true);
  $.ajax({ url:"<?php echo base_url(); ?>admin/report/ledger", type:"POST", dataType:'json',
    data:{ 'search_name':$('#myInput').val(), 'from_date':from, 'to_date':to },
    success:function(res){
      setLoading(false);
      if(!res||res.status!=='success'){ $('#cashBody').html(statusRow(SECTIONS.cash.cols,'error',(res&&res.msg)?res.msg:'Could not load ledger.')); $('#kvBody').html(statusRow(SECTIONS.kv.cols,'error','Could not load ledger.')); return; }
      var L=res.ledger||{};
      LG={ account:L.account||null, cash:L.cash||{opening:0,rows:[]}, kv:L.kisanvahi||{opening:0,rows:[]} };
      $('title').text(($('#myInput').val()||'').split('_')[0]+' - Ledger');
      renderSection('cash', res.from, res.to);
      renderSection('kv', res.from, res.to);
    },
    error:function(){ setLoading(false); $('#cashBody').html(statusRow(SECTIONS.cash.cols,'error','Could not load ledger. Please try again.')); $('#kvBody').html(statusRow(SECTIONS.kv.cols,'error','Could not load ledger.')); }
  });
}

$('#ledgerFind').click(loadLedger);
$('#lgPrev').click(function(){ shiftRange(-1); });
$('#lgNext').click(function(){ shiftRange(1); });

$(function(){
  var ov=document.getElementById('ledgerMediaModal');
  if(ov) ov.addEventListener('click',function(e){ if(e.target===this) closeLedgerMedia(); });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape') closeLedgerMedia(); });
});

/* ---- account autocomplete ---- */
function autocomplete(inp){
  if(!inp) return;
  var currentFocus, arr=[];
  inp.addEventListener("input",function(){
    $.ajax({ url:"<?php echo base_url(); ?>admin/billing/account_options", type:"POST", dataType:'json', success:function(a){ arr=a||[]; } });
    var a,b,i,val=this.value; closeAllLists(); if(!val)return false; currentFocus=-1;
    a=document.createElement("DIV"); a.setAttribute("id",this.id+"autocomplete-list"); a.setAttribute("class","autocomplete-items"); this.parentNode.appendChild(a);
    for(i=0;i<arr.length;i++){ if(arr[i].name.substr(0,val.length).toUpperCase()==val.toUpperCase()){
      b=document.createElement("DIV");
      b.innerHTML="<strong>"+arr[i].name.substr(0,val.length)+"</strong>"+arr[i].name.substr(val.length);
      b.innerHTML+="<input type='hidden' value='"+arr[i].name+'_'+arr[i].account_id+"'>";
      b.addEventListener("click",function(){ inp.value=this.getElementsByTagName("input")[0].value; closeAllLists(); loadLedger(); });
      a.appendChild(b);
    } }
  });
  inp.addEventListener("keydown",function(e){
    var x=document.getElementById(this.id+"autocomplete-list"); if(x)x=x.getElementsByTagName("div");
    if(e.keyCode==40){currentFocus++;addActive(x);} else if(e.keyCode==38){currentFocus--;addActive(x);} else if(e.keyCode==13){e.preventDefault(); if(currentFocus>-1&&x)x[currentFocus].click();}
  });
  function addActive(x){ if(!x)return false; removeActive(x); if(currentFocus>=x.length)currentFocus=0; if(currentFocus<0)currentFocus=(x.length-1); x[currentFocus].classList.add("autocomplete-active"); }
  function removeActive(x){ for(var i=0;i<x.length;i++)x[i].classList.remove("autocomplete-active"); }
  function closeAllLists(elmnt){ var x=document.getElementsByClassName("autocomplete-items"); for(var i=0;i<x.length;i++){ if(elmnt!=x[i]&&elmnt!=inp)x[i].parentNode.removeChild(x[i]); } }
  document.addEventListener("keydown",function(e){ closeAllLists(e.target); });
}
// Account field now uses the shared party picker (data-acc-picker) enhanced by
// assets/js/acc_picker.js — same dropdown as everywhere else. It keeps #myInput
// as the hidden "Name_<id>" value holder and fires change on select; load then.
$('#myInput').on('change', function () { if (hasAccount()) loadLedger(); });

// Refresh/bookmark retention: if the page loaded with ?acc=Name_<id> (seeded into
// the input server-side), auto-load that account's ledger for the saved dates.
$(function () { if ((($('#myInput').val() || '').indexOf('_') !== -1)) { setTimeout(loadLedger, 80); } });

/* ---- ledger row search + autocomplete suggestions (no reload) ---- */
(function(){
  var TYPE_ICON  = { Remark:'sg-remark', Particulars:'sg-particulars', Voucher:'sg-voucher', User:'sg-user' };
  var TYPE_GLYPH = { Remark:'&#9998;', Particulars:'&#9776;', Voucher:'#', User:'&#64;' };
  function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
  function dt(sec){ var id=sec==='cash'?'#cashTable':'#kvTable'; return ($.fn.DataTable && $.fn.DataTable.isDataTable(id)) ? $(id).DataTable() : null; }
  function rowsOf(sec){ return (window.LG && LG[sec] && LG[sec].rows) ? LG[sec].rows : []; }
  function applyFilter(sec,val){ var t=dt(sec); if(t){ t.search(val).draw(); } }

  function buildSuggest(sec,q){
    var rows=rowsOf(sec), ql=q.toLowerCase(), seen={}, counts={}, out=[];
    function add(type,val){
      val=(val==null?'':String(val)).trim(); if(!val) return;
      if(val.toLowerCase().indexOf(ql)===-1) return;
      var key=type+'|'+val.toLowerCase();
      if(seen[key]!==undefined){ counts[key]++; return; }
      seen[key]=1; counts[key]=1; out.push({type:type,val:val,key:key});
    }
    rows.forEach(function(d){ add('Remark',d.remark); if(d.added_by_name) add('User',d.added_by_name); });
    out.forEach(function(o){ o.cnt=counts[o.key]; });
    out.sort(function(a,b){
      var ap=a.val.toLowerCase().indexOf(ql)===0?0:1, bp=b.val.toLowerCase().indexOf(ql)===0?0:1;
      if(ap!==bp) return ap-bp; if(b.cnt!==a.cnt) return b.cnt-a.cnt; return a.val.length-b.val.length;
    });
    return out.slice(0,12);
  }
  function hi(val,q){ var i=val.toLowerCase().indexOf(q.toLowerCase()); if(i===-1) return esc(val); return esc(val.slice(0,i))+'<b>'+esc(val.slice(i,i+q.length))+'</b>'+esc(val.slice(i+q.length)); }
  function renderSuggest(sec,q){
    var box=document.getElementById(sec+'Suggest'); if(!box) return;
    if(!q){ box.classList.remove('show'); box.innerHTML=''; return; }
    var items=buildSuggest(sec,q);
    if(!items.length){ box.innerHTML='<div class="lg-sg-empty">No matching remark / entry</div>'; box.classList.add('show'); return; }
    box.innerHTML=items.map(function(o){
      return '<div class="lg-sg-item" data-val="'+esc(o.val)+'">'
        +'<span class="lg-sg-ic '+(TYPE_ICON[o.type]||'sg-particulars')+'">'+(TYPE_GLYPH[o.type]||'&#9776;')+'</span>'
        +'<span class="lg-sg-body"><span class="lg-sg-tp">'+o.type+'</span><span class="lg-sg-tx">'+hi(o.val,q)+'</span></span>'
        +'<span class="lg-sg-cnt">'+o.cnt+'&times;</span></div>';
    }).join('');
    box.classList.add('show');
  }
  function bind(sec){
    var inp=document.getElementById(sec+'Search'); if(!inp) return;
    var wrap=inp.parentNode, box=document.getElementById(sec+'Suggest'), clr=document.getElementById(sec+'SearchClear');
    var timer=null, active=-1;
    function setHas(){ wrap.classList.toggle('has-val', inp.value!==''); }
    function refresh(){ var v=inp.value.trim(); setHas(); applyFilter(sec,v); renderSuggest(sec,v); active=-1; }
    inp.addEventListener('input', function(){ clearTimeout(timer); timer=setTimeout(refresh,120); });
    inp.addEventListener('focus', function(){ if(inp.value.trim()) renderSuggest(sec, inp.value.trim()); });
    function setActive(items){ for(var i=0;i<items.length;i++){ items[i].classList.toggle('active', i===active); if(i===active) items[i].scrollIntoView({block:'nearest'}); } }
    function pick(v){ inp.value=v; setHas(); applyFilter(sec,v); box.classList.remove('show'); inp.focus(); }
    inp.addEventListener('keydown', function(e){
      var items=box.querySelectorAll('.lg-sg-item');
      if(e.key==='ArrowDown'){ e.preventDefault(); if(items.length){ active=(active+1)%items.length; setActive(items); } }
      else if(e.key==='ArrowUp'){ e.preventDefault(); if(items.length){ active=(active-1+items.length)%items.length; setActive(items); } }
      else if(e.key==='Enter'){ if(active>-1&&items[active]){ e.preventDefault(); pick(items[active].getAttribute('data-val')); } else { box.classList.remove('show'); } }
      else if(e.key==='Escape'){ box.classList.remove('show'); }
    });
    box.addEventListener('mousedown', function(e){ var it=e.target.closest('.lg-sg-item'); if(it){ e.preventDefault(); pick(it.getAttribute('data-val')); } });
    if(clr){ clr.addEventListener('click', function(){ inp.value=''; setHas(); applyFilter(sec,''); box.classList.remove('show'); inp.focus(); }); }
    document.addEventListener('click', function(e){ if(!wrap.contains(e.target)) box.classList.remove('show'); });
  }
  bind('cash'); bind('kv');
})();
</script>
