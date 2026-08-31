<link href="<?php echo base_url(); ?>assets/global/css/components-rounded.css" id="style_components" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css" />
<?php
$QUERY_STRING = $_SERVER['QUERY_STRING'];
$g   = function ($k) { return isset($_GET[$k]) ? htmlspecialchars($_GET[$k], ENT_QUOTES, 'UTF-8') : ''; };
$sel = function ($k, $v) { return (isset($_GET[$k]) && $_GET[$k] == $v) ? 'selected' : ''; };
$sm  = isset($summary) ? $summary : array('count' => 0, 'amount' => 0, 'deposit' => 0, 'expense' => 0);
?>

<style>
    .de-page { padding: 24px; color: #18243c; }
    .de-shell { max-width: 1420px; margin: 0 auto; }

    /* Hero */
    .de-hero { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap;
        padding: 24px 26px; margin-bottom: 18px; border-radius: 14px; color: #fff;
        background: radial-gradient(circle at 90% -30%, rgba(255,120,120,.5), transparent 36%), linear-gradient(125deg, #5b1620, #7a1d29 45%, #2a1230);
        box-shadow: 0 20px 46px rgba(60,16,24,.28); }
    .de-hero::after { content: ""; position: absolute; right: -90px; top: -120px; width: 300px; height: 300px; border: 1px solid rgba(255,255,255,.14); border-radius: 50%; box-shadow: 0 0 0 34px rgba(255,255,255,.04); pointer-events: none; }
    .de-hero-l { display: flex; align-items: center; gap: 16px; position: relative; z-index: 1; }
    .de-hero-ic { width: 52px; height: 52px; border-radius: 14px; display: grid; place-items: center; font-size: 22px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.24); }
    .de-title { margin: 0; font-size: 23px; font-weight: 900; }
    .de-title small { display: block; font-size: 12.5px; font-weight: 700; color: rgba(255,235,235,.82); margin-top: 4px; }
    .de-back { position: relative; z-index: 1; display: inline-flex; align-items: center; gap: 8px; min-height: 42px; padding: 10px 16px; border-radius: 10px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.28); color: #fff !important; font-weight: 800; font-size: 13px; text-decoration: none; transition: transform .16s ease, background .16s ease; }
    .de-back:hover { transform: translateY(-2px); background: rgba(255,255,255,.24); color: #fff !important; }

    /* KPI */
    .de-kpis { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; margin-bottom: 18px; }
    .de-kpi { display: flex; align-items: center; gap: 13px; padding: 16px 18px; border: 1px solid #e3e9f2; border-radius: 13px; background: #fff; box-shadow: 0 12px 30px rgba(24,36,60,.06); transition: transform .16s ease, box-shadow .16s ease; }
    .de-kpi:hover { transform: translateY(-2px); box-shadow: 0 16px 36px rgba(24,36,60,.1); }
    .de-kpi-ic { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; font-size: 18px; color: #fff; flex: none; }
    .de-kpi-t span { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #7a8aa0; }
    .de-kpi-t strong { display: block; margin-top: 3px; font-size: 21px; font-weight: 900; color: #18243c; }
    .ic-red { background: linear-gradient(135deg, #e5484d, #a11722); }
    .ic-slate { background: linear-gradient(135deg, #47566d, #2a3547); }
    .ic-green { background: linear-gradient(135deg, #1f9d70, #0c7048); }
    .ic-amber { background: linear-gradient(135deg, #e08a12, #9a5b06); }

    .de-panel { border: 1px solid #e3e9f2; border-radius: 14px; background: #fff; box-shadow: 0 14px 34px rgba(24,36,60,.07); margin-bottom: 18px; overflow: hidden; }
    .de-filter { padding: 18px 20px; }
    .de-filter-grid { display: grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap: 12px; align-items: end; }
    .de-field label { display: block; font-size: 11px; font-weight: 900; text-transform: uppercase; color: #718096; margin-bottom: 6px; }
    .de-field .form-control { min-height: 42px; border: 1px solid #dce6f2; border-radius: 10px; background: #fbfdff; font-weight: 700; }
    .de-field .form-control:focus { border-color: #1769c2; box-shadow: 0 0 0 3px rgba(23,105,194,.12); }
    .de-filter-actions { display: flex; gap: 8px; margin-top: 12px; max-width: 340px; }
    .de-filter-actions .btn { flex: 1; min-height: 42px; border-radius: 10px !important; font-weight: 800; border: 0; display: inline-flex; align-items: center; justify-content: center; gap: 7px; }
    .de-search { background: #1769c2; color: #fff; }
    .de-search:hover { background: #12569e; color: #fff; }
    .de-reset { background: #fff; border: 1px solid #dce6f2 !important; color: #516174; }
    .de-pdf { background: #b42318; color: #fff; }
    .de-pdf:hover { background: #97180f; color: #fff; }

    /* Table */
    .de-table-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 16px 20px 4px; }
    .de-table-head h3 { margin: 0; font-size: 16px; font-weight: 900; }
    .de-table-head span { font-size: 12px; font-weight: 700; color: #8190a5; }
    .de-table-wrap { padding: 8px 18px 18px; position: relative; }
    #employee-grid-buyer { width: 100% !important; font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #employee-grid-buyer thead th { background: #2a3145; color: #fff; white-space: nowrap; vertical-align: middle; border: 0 !important; font-weight: 800; font-size: 11.5px; text-transform: uppercase; letter-spacing: .03em; padding: 11px 10px; }
    #employee-grid-buyer tbody td { vertical-align: middle; border-color: #eef2f7 !important; padding: 9px 10px; }
    #employee-grid-buyer tbody tr:hover { background: #fbfdff; }

    .de-entry b { display: block; color: #1769c2; font-weight: 900; }
    .de-entry span { font-size: 11px; color: #8190a5; font-weight: 700; }
    .de-party { display: flex; align-items: center; gap: 9px; text-align: left; }
    .de-av { width: 32px; height: 32px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-weight: 900; font-size: 13px; flex: none; box-shadow: 0 2px 6px rgba(24,36,60,.18); }
    .de-party-t { min-width: 0; }
    .de-party-t b { display: block; font-weight: 800; color: #26374f; max-width: 190px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .de-party-t span { font-size: 10.5px; color: #8190a5; font-weight: 700; }
    .de-pill { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 800; padding: 3px 9px; border-radius: 20px; white-space: nowrap; }
    .de-pill-web { background: #e6f0fb; color: #1769c2; }
    .de-pill-app { background: #e8f7ee; color: #1f9d70; }
    .de-pill-dep { background: #e7f7ef; color: #12805a; border: 1px solid #bce8d4; }
    .de-pill-exp { background: #fdecef; color: #d6354f; border: 1px solid #f4c5cf; }
    .de-amt { font-weight: 900; font-size: 13.5px; white-space: nowrap; }
    .de-amt.pos { color: #12805a; }
    .de-amt.neg { color: #d6354f; }
    .de-who { text-align: left; line-height: 1.25; }
    .de-who b { display: block; font-weight: 800; color: #26374f; font-size: 12px; }
    .de-who span { font-size: 10.5px; color: #8190a5; font-weight: 700; }
    .de-who-danger b { color: #b3202c; }
    .de-reason { display: inline-block; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11.5px; font-weight: 700; color: #8a5a00; background: #fdf3dc; border: 1px solid #f2dfae; padding: 3px 9px; border-radius: 8px; vertical-align: middle; }
    .de-actions { display: inline-flex; gap: 5px; }
    .de-act { width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: #fff !important; font-size: 12px; transition: transform .14s ease, box-shadow .14s ease; }
    .de-act:hover { transform: translateY(-2px); box-shadow: 0 5px 12px rgba(24,36,60,.2); }
    .de-act-view { background: #1769c2; }
    .de-act-restore { background: #1f9d70; }

    /* Native selects — force a consistent height/arrow across all filters */
    .de-field select.form-control { height: 42px; -webkit-appearance: none; -moz-appearance: none; appearance: none;
        background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20width%3D'12'%20height%3D'8'%20viewBox%3D'0%200%2012%208'%3E%3Cpath%20fill%3D'%2358708c'%20d%3D'M6%208L0%200h12z'%2F%3E%3C%2Fsvg%3E");
        background-repeat: no-repeat; background-position: right 12px center; padding-right: 30px; }

    /* animated modal (shared style with parcha) */
    .rk-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.55); z-index: 12000; display: flex; align-items: flex-start; justify-content: center; padding: 40px 14px; overflow-y: auto; opacity: 0; visibility: hidden; transition: opacity .25s ease, visibility .25s ease; }
    .rk-overlay.show { opacity: 1; visibility: visible; }
    .rk-modal { width: 580px; max-width: 100%; background: #fff; border-radius: 14px; box-shadow: 0 24px 60px rgba(15,23,42,.35); overflow: hidden; transform: translateY(-22px) scale(.96); opacity: 0; transition: transform .3s cubic-bezier(.2,.8,.2,1), opacity .3s ease; }
    .rk-overlay.show .rk-modal { transform: translateY(0) scale(1); opacity: 1; }
    .rk-head { display: flex; align-items: center; justify-content: space-between; padding: 15px 18px; background: linear-gradient(120deg, #5b1620, #7a1d29); color: #fff; }
    .rk-head h5 { margin: 0; font-size: 15px; font-weight: 900; }
    .rk-close { background: transparent; border: 0; color: #fff; font-size: 22px; cursor: pointer; line-height: 1; }
    .rk-body { padding: 16px 18px; max-height: 70vh; overflow-y: auto; }
    .rk-sec { font-size: 11px; font-weight: 900; text-transform: uppercase; color: #718096; margin: 4px 0 8px; }
    .rk-sec.danger { color: #e5484d; }
    .rk-grid { display: grid; grid-template-columns: 140px 1fr; border: 1px solid #edf2f7; border-radius: 10px; overflow: hidden; margin-bottom: 14px; }
    .rk-grid .rk-k, .rk-grid .rk-v { padding: 9px 12px; font-size: 13px; border-bottom: 1px solid #edf2f7; }
    .rk-grid .rk-k { background: #f7fafc; color: #718096; font-weight: 800; }
    .rk-grid .rk-v { color: #26374f; font-weight: 700; word-break: break-word; }
    .rk-media-wrap { display: grid; gap: 12px; margin-bottom: 14px; }
    .rk-media label { display: block; font-size: 11px; font-weight: 900; text-transform: uppercase; color: #718096; margin-bottom: 6px; }
    .rk-media img { max-width: 100%; border-radius: 8px; border: 1px solid #e7eef6; }
    .rk-media audio, .rk-media video { width: 100%; }
    .rk-foot { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 18px 16px; border-top: 1px solid #edf2f7; }
    .rk-foot .btn { border: 0; border-radius: 10px; font-weight: 800; padding: 10px 18px; }
    .rk-restore { background: #1f9d70; color: #fff; }
    .rk-restore:disabled { opacity: .6; cursor: not-allowed; }

    @media (max-width: 1100px) { .de-filter-grid { grid-template-columns: repeat(2, 1fr); } .de-kpis { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 767px) { .de-page { padding: 14px; } .de-filter-grid { grid-template-columns: 1fr; } .de-kpis { grid-template-columns: 1fr; } .de-table-wrap { overflow-x: auto; } }
</style>

<div id="msgShow"></div>
<main class="main-content bgc-grey-100 de-page">
    <div id="mainContent">
        <div class="de-shell">
            <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>

            <section class="de-hero">
                <div class="de-hero-l">
                    <span class="de-hero-ic"><i class="ti-trash"></i></span>
                    <h4 class="de-title">Deleted Rokad Entries <small>Soft-deleted cash entries &mdash; review the trail &amp; restore</small></h4>
                </div>
                <a href="<?php echo base_url('admin/report/rokad_parcha'); ?>" class="de-back"><i class="ti-arrow-left"></i> Back to Rokad Parcha</a>
            </section>

            <section class="de-kpis">
                <div class="de-kpi"><span class="de-kpi-ic ic-red"><i class="ti-trash"></i></span><div class="de-kpi-t"><span>Deleted Entries</span><strong id="kpiCount"><?= number_format((int) $sm['count']) ?></strong></div></div>
                <div class="de-kpi"><span class="de-kpi-ic ic-slate"><i class="ti-money"></i></span><div class="de-kpi-t"><span>Total Amount</span><strong id="kpiTotal">&#8377; <?= number_format((float) $sm['amount'], 2) ?></strong></div></div>
                <div class="de-kpi"><span class="de-kpi-ic ic-green"><i class="ti-import"></i></span><div class="de-kpi-t"><span>Deposit Deleted</span><strong id="kpiDep">&#8377; <?= number_format((float) $sm['deposit'], 2) ?></strong></div></div>
                <div class="de-kpi"><span class="de-kpi-ic ic-amber"><i class="ti-export"></i></span><div class="de-kpi-t"><span>Expense Deleted</span><strong id="kpiExp">&#8377; <?= number_format((float) $sm['expense'], 2) ?></strong></div></div>
            </section>

            <section class="de-panel de-filter">
                <form method="get" id="filterForm">
                    <div class="de-filter-grid">
                        <div class="de-field"><label>From Date</label><input type="text" name="from_date" class="form-control de-from" value="<?= $g('from_date') ?>" placeholder="From" autocomplete="off"></div>
                        <div class="de-field"><label>To Date</label><input type="text" name="to_date" class="form-control de-to" value="<?= $g('to_date') ?>" placeholder="To" autocomplete="off"></div>
                        <div class="de-field">
                            <label>Party</label>
                            <select name="party" class="form-control">
                                <option value="none">All Parties</option>
                                <?php foreach ($parties as $p): if ($p->account_no === '') continue; ?>
                                    <option value="<?= htmlspecialchars($p->account_no) ?>" <?= $sel('party', $p->account_no) ?>><?= htmlspecialchars($p->name ?: ('A/c ' . $p->account_no)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="de-field">
                            <label>Deleted By</label>
                            <select name="user" class="form-control">
                                <option value="none">All Users</option>
                                <?php foreach ($users as $u): if (empty($u->deleted_by)) continue; ?>
                                    <option value="<?= htmlspecialchars($u->deleted_by) ?>" <?= $sel('user', $u->deleted_by) ?>><?= htmlspecialchars(trim($u->name) ?: ('#' . $u->deleted_by)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="de-field">
                            <label>Source</label>
                            <select name="source" class="form-control">
                                <option value="none">All</option>
                                <option value="Web" <?= $sel('source', 'Web') ?>>Web</option>
                                <option value="App" <?= $sel('source', 'App') ?>>App</option>
                            </select>
                        </div>
                        <div class="de-field">
                            <label>Type</label>
                            <select name="type" class="form-control">
                                <option value="none">All</option>
                                <option value="deposit" <?= $sel('type', 'deposit') ?>>Deposit</option>
                                <option value="expenses" <?= $sel('type', 'expenses') ?>>Expense</option>
                            </select>
                        </div>
                    </div>
                    <div class="de-filter-actions">
                        <button type="submit" class="btn de-search"><i class="ti-search"></i> Apply Filters</button>
                        <button type="button" id="deReset" class="btn de-reset"><i class="ti-reload"></i> Reset</button>
                        <button type="button" id="dePdf" class="btn de-pdf"><i class="ti-download"></i> Download PDF</button>
                    </div>
                </form>
            </section>

            <section class="de-panel">
                <div class="de-table-head">
                    <h3>Deleted Entries Register</h3>
                    <span>Click <i class="ti-eye"></i> to review an entry, or <i class="ti-back-left"></i> to restore it.</span>
                </div>
                <div class="de-table-wrap">
                    <table class="table table-bordered" style="text-align:center;" id="employee-grid-buyer">
                        <thead>
                            <tr>
                                <th>Entry</th>
                                <th>Party</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Source</th>
                                <th>Created</th>
                                <th>Deleted</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </section>
        </div>
    </div>
</main>

<!-- Detail popup -->
<div class="rk-overlay" id="deModal">
    <div class="rk-modal">
        <div class="rk-head">
            <h5><i class="ti-trash"></i> Deleted Entry Details</h5>
            <button type="button" class="rk-close" onclick="closeDe()">&times;</button>
        </div>
        <div class="rk-body" id="deBody"></div>
        <div class="rk-foot" id="deFoot" style="display:none;">
            <?php if (!empty($can_restore)): ?>
                <button type="button" class="btn rk-restore" id="deRestoreBtn"><i class="ti-back-left"></i> Restore Entry</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script src="<?= base_url(); ?>assets/admin/pages/scripts/table-managed.js"></script>

<script>
    var BASE = "<?php echo base_url(); ?>";
    var CAN_RESTORE = <?php echo !empty($can_restore) ? 'true' : 'false'; ?>;

    $(function () {
        $(".de-from").datepicker({ dateFormat: "dd-mm-yy", onSelect: function (d) { $(".de-to").datepicker("option", "minDate", d); } });
        $(".de-to").datepicker({ dateFormat: "dd-mm-yy", onSelect: function (d) { $(".de-from").datepicker("option", "maxDate", d); } });
    });

    function updateKpis(s) {
        if (!s) return;
        $('#kpiCount').text(Number(s.count || 0).toLocaleString('en-IN'));
        $('#kpiTotal').html('&#8377; ' + fmtAmt(s.amount));
        $('#kpiDep').html('&#8377; ' + fmtAmt(s.deposit));
        $('#kpiExp').html('&#8377; ' + fmtAmt(s.expense));
    }

    var table = $('#employee-grid-buyer').DataTable({
        "bStateSave": false, "processing": true, "serverSide": true,
        "lengthMenu": [[25, 50, 100, -1], [25, 50, 100, "All"]],
        "iDisplayLength": 25, "pageLength": 25,
        "pagingType": "bootstrap_full_number",
        "language": { "search": "Search:", "processing": "Loading...", "emptyTable": "No deleted entries found", "zeroRecords": "No matching deleted entries", "paginate": { "previous": "Prev", "next": "Next", "last": "Last", "first": "First" } },
        "columnDefs": [{ "targets": "_all", "orderable": false }],
        "ajax": {
            url: BASE + "admin/report/deleted_entries_data",
            type: "post",
            data: function (d) {
                d.from_date = $('input[name=from_date]').val();
                d.to_date   = $('input[name=to_date]').val();
                d.party     = $('select[name=party]').val();
                d.user      = $('select[name=user]').val();
                d.source    = $('select[name=source]').val();
                d.type      = $('select[name=type]').val();
            },
            dataSrc: function (json) {
                if (json && json.summary) updateKpis(json.summary);
                return json.data || [];
            },
            error: function () { $("#employee-grid_processing").css("display", "none"); }
        },
        "order": []
    });

    // Apply filters without reloading the page.
    $('#filterForm').on('submit', function (e) { e.preventDefault(); table.ajax.reload(); });

    // Reset clears every filter and reloads the grid in place.
    $('#deReset').on('click', function () {
        $('input[name=from_date], input[name=to_date]').val('');
        $('select[name=party], select[name=user], select[name=source], select[name=type]').val('none');
        table.ajax.reload();
    });

    // Download the current (filtered) list as a PDF — carries the active filters.
    // Uses a temporary <a download> click (not window.open) so popup blockers
    // never swallow it; the PDF streams as an attachment and the page stays put.
    $('#dePdf').on('click', function () {
        var q = $('#filterForm').serialize();
        var url = BASE + 'admin/report/deleted_entries_pdf?' + q;
        var a = document.createElement('a');
        a.href = url; a.target = '_blank'; a.rel = 'noopener';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
    });

    $(document).ready(function () { setTimeout(function () { TableManaged.init(); $(".form-group-custom").removeClass('hide'); }, 800); });

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function fmtAmt(a) { return (parseFloat(a) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function row(k, v) { return '<div class="rk-k">' + k + '</div><div class="rk-v">' + v + '</div>'; }

    var deCurrentId = null;
    function showDeleted(id) {
        deCurrentId = id;
        $('#deBody').html('<div style="padding:30px;text-align:center;color:#94a3b8;">Loading...</div>');
        $('#deFoot').hide();
        $('#deModal').addClass('show');
        $.ajax({
            url: BASE + "admin/report/deleted_entry_detail", type: "POST", dataType: 'json',
            data: { id: id },
            success: function (d) {
                if (!d || d.status === 'error') { $('#deBody').html('<div style="padding:30px;text-align:center;color:#e5484d;">Entry not found.</div>'); return; }
                var isWeb = (String(d.entry_source).toLowerCase() === 'web' || !d.entry_source);
                var typeLabel = (d.type_of_account === 'deposit') ? 'Deposit (जमा)' : 'Expense (नाम)';

                var g = '<div class="rk-sec">Entry</div><div class="rk-grid">';
                g += row('Entry ID', esc(d.rokad_id));
                g += row('Date', esc(d.rokad_date));
                g += row('Party', esc(d.party_name || d.account_name) + (d.account_no ? ' <small>(A/c ' + esc(d.account_no) + ')</small>' : ''));
                g += row('Type', typeLabel);
                g += row('Amount', '&#8377; ' + fmtAmt(d.karch_amount));
                g += row('Source', isWeb ? 'Web' : 'App');
                if (d.payment_mode) g += row('Paid by', esc(d.payment_mode));
                if (d.rokad_entry_no) g += row('Khata Entry No', esc(d.rokad_entry_no));
                if (d.challan_no) g += row('Challan No', esc(d.challan_no));
                if (d.truck_no) g += row('Truck No', esc(d.truck_no));
                if (d.quantity) g += row('Quantity', esc(d.quantity));
                if (d.rate) g += row('Rate', esc(d.rate));
                g += row('Remark', d.remark ? esc(d.remark) : '<span class="text-muted">No remark</span>');
                g += row('Created By', esc((d.created_by_name && d.created_by_name.trim()) ? d.created_by_name : ('#' + d.added_by)));
                g += row('Created On', esc(d.added_type || '-'));
                g += row('IP Address', d.ip ? esc(d.ip) : '<span class="text-muted">Not captured</span>');
                if (d.lat && d.lng) {
                    g += row('Location', '<a href="https://www.google.com/maps?q=' + encodeURIComponent(d.lat + ',' + d.lng) + '" target="_blank" rel="noopener"><i class="ti-location-pin"></i> ' + esc(d.lat) + ', ' + esc(d.lng) + '</a>');
                } else {
                    g += row('Location', '<span class="text-muted">Not captured</span>');
                }
                g += '</div>';

                var m = '';
                if (d.image) m += '<div class="rk-media"><label>Image</label><a href="' + d.image + '" target="_blank"><img src="' + d.image + '"></a></div>';
                if (d.voice) m += '<div class="rk-media"><label>Voice Recording</label><audio controls preload="none" src="' + d.voice + '"></audio></div>';
                if (d.video) m += '<div class="rk-media"><label>Video Recording</label><video controls preload="none" src="' + d.video + '"></video></div>';
                if (m) m = '<div class="rk-sec">Attachments</div><div class="rk-media-wrap">' + m + '</div>';

                var del = '<div class="rk-sec danger">Delete Information</div><div class="rk-grid">';
                del += row('Delete Reason', d.delete_reason ? esc(d.delete_reason) : '-');
                del += row('Deleted By', esc((d.deleted_by_name && d.deleted_by_name.trim()) ? d.deleted_by_name : ('#' + d.deleted_by)));
                del += row('Deleted On', esc(d.deleted_date || '-'));
                del += '</div>';

                $('#deBody').html(g + m + del);
                if (CAN_RESTORE) { $('#deFoot').show(); }
            },
            error: function () { $('#deBody').html('<div style="padding:30px;text-align:center;color:#e5484d;">Error loading entry.</div>'); }
        });
    }
    function closeDe() { $('#deModal').removeClass('show'); setTimeout(function () { $('#deBody').html(''); }, 300); }
    document.getElementById('deModal').addEventListener('click', function (e) { if (e.target === this) closeDe(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDe(); });

    $('#deRestoreBtn').on('click', function () { if (deCurrentId) restoreEntry(deCurrentId); });

    function restoreEntry(id) {
        if (!CAN_RESTORE) { showToast('error', 'You are not authorized to restore entries.'); return; }
        showConfirm('Restore entry', 'Restore this entry? It will reappear in the Rokad Parcha and all reports.', function () {
        $.ajax({
            url: BASE + "admin/report/restore_entry", type: "POST", dataType: 'json',
            data: { id: id },
            success: function (res) {
                if (res && res.status === 'success') {
                    closeDe();
                    showToast('success', res.msg || 'Entry restored.');
                    $('#employee-grid-buyer').DataTable().ajax.reload(null, false);
                } else {
                    showToast('error', res && res.msg ? res.msg : 'Unable to restore.');
                }
            },
            error: function () { showToast('error', 'Error'); }
        });
        }, null, { okText: 'Restore' });
    }
</script>
