<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<link href="<?php echo base_url(); ?>assets/global/css/components-rounded.css" id="style_components" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css" />

<style>
    /* Wrapped in the theme's .main-content so it clears the fixed header + the
       absolute "Advanced features" note (padding-top handled responsively there). */
    .et-scope { color: #18243c; }
    .et-shell { max-width: 1420px; margin: 0 auto; }

    /* Hero */
    .et-hero { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap;
        padding: 22px 26px; margin-bottom: 18px; border-radius: 14px; color: #fff;
        background: radial-gradient(circle at 90% -30%, rgba(120,170,255,.5), transparent 36%), linear-gradient(125deg, #12325b, #1d4ed8 55%, #3b1e6e);
        box-shadow: 0 20px 46px rgba(16,32,72,.28); }
    .et-hero::after { content: ""; position: absolute; right: -90px; top: -120px; width: 300px; height: 300px; border: 1px solid rgba(255,255,255,.14); border-radius: 50%; box-shadow: 0 0 0 34px rgba(255,255,255,.04); pointer-events: none; }
    .et-hero-l { display: flex; align-items: center; gap: 16px; position: relative; z-index: 1; }
    .et-hero-ic { width: 52px; height: 52px; border-radius: 14px; display: grid; place-items: center; font-size: 22px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.24); }
    .et-title { margin: 0; font-size: 23px; font-weight: 900; }
    .et-title small { display: block; font-size: 12.5px; font-weight: 700; color: rgba(235,242,255,.85); margin-top: 4px; }
    .et-firm { position: relative; z-index: 1; display: inline-flex; align-items: center; gap: 8px; padding: 9px 15px; border-radius: 10px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.26); font-weight: 800; font-size: 13px; }

    /* KPI */
    .et-kpis { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 14px; margin-bottom: 18px; }
    .et-kpi { display: flex; align-items: center; gap: 13px; padding: 15px 18px; border: 1px solid #e3e9f2; border-radius: 13px; background: #fff; box-shadow: 0 12px 30px rgba(24,36,60,.06); transition: transform .16s ease, box-shadow .16s ease; }
    .et-kpi:hover { transform: translateY(-2px); box-shadow: 0 16px 36px rgba(24,36,60,.1); }
    .et-kpi-ic { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; font-size: 18px; color: #fff; flex: none; }
    .et-kpi-t span { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #7a8aa0; }
    .et-kpi-t strong { display: block; margin-top: 3px; font-size: 21px; font-weight: 900; color: #18243c; }
    .ic-blue { background: linear-gradient(135deg, #2563eb, #1746a2); }
    .ic-violet { background: linear-gradient(135deg, #7c3aed, #55208f); }
    .ic-green { background: linear-gradient(135deg, #1f9d70, #0c7048); }
    .ic-amber { background: linear-gradient(135deg, #e08a12, #9a5b06); }
    .ic-slate { background: linear-gradient(135deg, #47566d, #2a3547); }

    /* Panels */
    .et-panel { border: 1px solid #e3e9f2; border-radius: 14px; background: #fff; box-shadow: 0 14px 34px rgba(24,36,60,.07); margin-bottom: 18px; }

    /* Retention strip */
    .et-ret { display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap; padding: 15px 20px; }
    .et-ret-l { display: flex; align-items: center; gap: 13px; min-width: 260px; }
    .et-ret-ic { width: 42px; height: 42px; border-radius: 11px; display: grid; place-items: center; font-size: 18px; color: #fff; flex: none; background: linear-gradient(135deg, #0ea5e9, #0369a1); }
    .et-ret-t b { display: block; font-size: 14px; font-weight: 900; color: #0f172a; }
    .et-ret-t span { display: block; font-size: 12px; font-weight: 600; color: #7a8aa0; margin-top: 2px; max-width: 520px; }
    .et-ret-r { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .et-ret-r select.form-control { min-height: 42px; width: auto; min-width: 190px; border: 1px solid #dce6f2; border-radius: 10px; background: #fbfdff; font-weight: 700; font-size: 13px; }
    .et-ret-note { font-size: 12px; font-weight: 700; color: #475569; }
    .et-ret-note b { color: #0369a1; }
    .et-filter { padding: 18px 20px; }
    .et-filter-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px 16px; align-items: end; }
    .et-field label { display: block; font-size: 11px; font-weight: 900; text-transform: uppercase; color: #718096; margin-bottom: 6px; letter-spacing: .02em; }
    .et-field .form-control, .et-field select.form-control { min-height: 42px; border: 1px solid #dce6f2; border-radius: 10px; background: #fbfdff; font-weight: 700; font-size: 13px; color: #223; width: 100%; }
    .et-field .form-control:focus { border-color: #1769c2; box-shadow: 0 0 0 3px rgba(23,105,194,.12); outline: none; }
    .et-chk { display: inline-flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 800; color: #475569; min-height: 42px; padding: 0 4px; cursor: pointer; }
    .et-chk input { width: 16px; height: 16px; }
    .et-filter-actions { display: flex; gap: 10px; margin-top: 16px; }
    .et-btn { display: inline-flex; align-items: center; gap: 8px; min-height: 42px; padding: 0 20px; border-radius: 10px; font-weight: 800; font-size: 13px; border: 1px solid transparent; cursor: pointer; }
    .et-btn-apply { background: #1d4ed8; color: #fff; }
    .et-btn-apply:hover { background: #1740b5; }
    .et-btn-reset { background: #f1f5f9; color: #475569; border-color: #dce6f2; }
    .et-btn-reset:hover { background: #e6edf6; }

    .et-tablewrap { padding: 8px 18px 18px; }
    table#et-grid { width: 100% !important; }
    table#et-grid thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .03em; color: #64748b; font-weight: 800; border-bottom: 2px solid #eef2f7; padding: 10px 8px; }
    table#et-grid tbody td { font-size: 13px; vertical-align: middle; border-top: 1px solid #f1f5f9; padding: 10px 8px; }
    .et-entry-link { display: inline-flex; align-items: center; gap: 5px; font-weight: 800; color: #1d4ed8; text-decoration: none; padding: 3px 9px; border: 1px solid #dbe4f3; border-radius: 8px; background: #f5f8ff; transition: background .14s ease, border-color .14s ease; }
    .et-entry-link:hover { background: #e6efff; border-color: #b7cdf2; color: #1740b5; }
    .et-entry-link i { font-size: 11px; opacity: .75; }
    .et-mod { display: block; font-weight: 800; color: #0f172a; }
    .et-slug { display: block; font-size: 11px; color: #94a3b8; }
    .et-act { display: inline-block; font-size: 11px; font-weight: 800; padding: 2px 9px; border-radius: 999px; text-transform: uppercase; }
    .et-a-create { background: #dcfce7; color: #15803d; } .et-a-update { background: #dbeafe; color: #1d4ed8; } .et-a-delete { background: #fee2e2; color: #b91c1c; }
    .et-src { display: inline-block; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 6px; }
    .et-src-web { background: #eff6ff; color: #1d4ed8; } .et-src-app { background: #ecfdf5; color: #047857; } .et-src-sys { background: #f5f3ff; color: #6d28d9; }
    .et-ip { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; color: #334155; }
    .et-loc { font-weight: 700; color: #0ea5e9; white-space: nowrap; } .et-loc i { margin-right: 3px; }
    .et-acc { display: block; font-size: 10px; color: #94a3b8; }
    .et-user { font-weight: 700; color: #334155; }
    .et-when { display: block; font-weight: 700; color: #334155; }
    .et-ua { display: block; font-size: 10px; color: #94a3b8; }

    @media (max-width: 1100px) { .et-kpis { grid-template-columns: repeat(2, 1fr); } .et-filter-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px)  { .et-kpis { grid-template-columns: 1fr; } .et-filter-grid { grid-template-columns: 1fr; } }
</style>

<div class="main-content et-scope">
    <div class="et-shell">

        <!-- Hero -->
        <div class="et-hero">
            <div class="et-hero-l">
                <div class="et-hero-ic"><i class="ti-location-pin"></i></div>
                <div>
                    <h1 class="et-title">Entry Trace &middot; Audit Log
                        <small>Who created / edited / deleted each entry &mdash; with IP address &amp; location</small>
                    </h1>
                </div>
            </div>
            <?php $et_fy = fy(); ?>
            <?php if (is_object($et_fy)): ?>
            <div class="et-firm"><i class="ti-home"></i> <?= html_escape((isset($et_fy->template_name) ? $et_fy->template_name : (isset($et_fy->firm_name) ? $et_fy->firm_name : '')) . (isset($et_fy->FY) ? '  (FY ' . $et_fy->FY . ')' : '')) ?></div>
            <?php endif; ?>
        </div>

        <!-- KPIs -->
        <div class="et-kpis">
            <div class="et-kpi"><div class="et-kpi-ic ic-blue"><i class="ti-layers"></i></div><div class="et-kpi-t"><span>Records</span><strong id="kpiTotal">0</strong></div></div>
            <div class="et-kpi"><div class="et-kpi-ic ic-violet"><i class="ti-mobile"></i></div><div class="et-kpi-t"><span>From App</span><strong id="kpiApp">0</strong></div></div>
            <div class="et-kpi"><div class="et-kpi-ic ic-green"><i class="ti-location-pin"></i></div><div class="et-kpi-t"><span>With Location</span><strong id="kpiGeo">0</strong></div></div>
            <div class="et-kpi"><div class="et-kpi-ic ic-amber"><i class="ti-world"></i></div><div class="et-kpi-t"><span>Unique IPs</span><strong id="kpiIp">0</strong></div></div>
            <div class="et-kpi"><div class="et-kpi-ic ic-slate"><i class="ti-user"></i></div><div class="et-kpi-t"><span>Users</span><strong id="kpiUser">0</strong></div></div>
        </div>

        <!-- Retention policy -->
        <?php
            $et_ret   = isset($retention_days) ? (int) $retention_days : 0;
            $et_prune = isset($prunable_count) ? (int) $prunable_count : 0;
            $et_presets = array(0 => 'Keep all history', 15 => '15 days', 30 => '30 days', 60 => '60 days', 90 => '90 days', 180 => '180 days', 365 => '1 year (365 days)');
        ?>
        <div class="et-panel et-ret">
            <div class="et-ret-l">
                <div class="et-ret-ic"><i class="ti-time"></i></div>
                <div class="et-ret-t">
                    <b>Data Retention</b>
                    <span>Cap how much audit history is kept to avoid overload &mdash; older rows auto-delete once a day.</span>
                </div>
            </div>
            <div class="et-ret-r">
                <select class="form-control" id="etRetDays">
                    <?php foreach ($et_presets as $v => $lbl): ?>
                        <option value="<?= (int) $v ?>" <?= $et_ret === (int) $v ? 'selected' : '' ?>><?= html_escape($lbl) ?></option>
                    <?php endforeach; ?>
                    <?php if (!array_key_exists($et_ret, $et_presets)): ?>
                        <option value="<?= $et_ret ?>" selected><?= $et_ret ?> days (custom)</option>
                    <?php endif; ?>
                </select>
                <button type="button" class="et-btn et-btn-apply" id="etRetSave"><i class="ti-save"></i> Save</button>
                <span class="et-ret-note" id="etRetNote">
                    <?php if ($et_ret > 0): ?>
                        Keeping last <b><?= $et_ret ?></b> days<?= $et_prune > 0 ? ' &middot; <b>' . number_format($et_prune) . '</b> older row(s) will be purged' : '' ?>.
                    <?php else: ?>
                        Currently keeping <b>all</b> history (no auto-delete).
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <!-- Filters -->
        <div class="et-panel">
            <form id="etFilter" class="et-filter" onsubmit="return false;">
                <div class="et-filter-grid">
                    <div class="et-field">
                        <label>Module</label>
                        <select class="form-control" name="f_module">
                            <option value="all">All modules</option>
                            <?php foreach ($modules as $m): ?>
                                <option value="<?= html_escape($m->module) ?>"><?= html_escape(isset($module_labels[$m->module]) ? $module_labels[$m->module] : $m->module) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="et-field">
                        <label>Action</label>
                        <select class="form-control" name="f_action">
                            <option value="all">All</option>
                            <option value="create">Create</option>
                            <option value="update">Update</option>
                            <option value="delete">Delete</option>
                        </select>
                    </div>
                    <div class="et-field">
                        <label>Source</label>
                        <select class="form-control" name="f_source">
                            <option value="all">All</option>
                            <option value="Web">Web</option>
                            <option value="App">App</option>
                            <option value="System">System</option>
                        </select>
                    </div>
                    <div class="et-field">
                        <label>User</label>
                        <select class="form-control" name="f_user">
                            <option value="all">All users</option>
                            <?php foreach ($users as $u): $lbl = trim($u->full_name) !== '' ? $u->full_name : ($u->user_name ?: ('#' . $u->user_id)); ?>
                                <option value="<?= (int) $u->user_id ?>"><?= html_escape($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="et-field">
                        <label>IP contains</label>
                        <input type="text" class="form-control" name="f_ip" placeholder="e.g. 103.42">
                    </div>
                    <div class="et-field">
                        <label>From date</label>
                        <input type="text" class="form-control et-from" name="f_from" placeholder="dd-mm-yyyy" autocomplete="off">
                    </div>
                    <div class="et-field">
                        <label>To date</label>
                        <input type="text" class="form-control et-to" name="f_to" placeholder="dd-mm-yyyy" autocomplete="off">
                    </div>
                    <div class="et-field">
                        <label>&nbsp;</label>
                        <label class="et-chk"><input type="checkbox" name="f_geo" value="1"> Only with location</label>
                    </div>
                </div>
                <div class="et-filter-actions">
                    <button type="button" class="et-btn et-btn-apply" id="etApply"><i class="ti-filter"></i> Apply Filters</button>
                    <button type="button" class="et-btn et-btn-reset" id="etReset"><i class="ti-reload"></i> Reset</button>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="et-panel">
            <div class="et-tablewrap">
                <table id="et-grid" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Entry</th>
                            <th>Action</th>
                            <th>User</th>
                            <th>Source</th>
                            <th>IP Address</th>
                            <th>Location</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>

<script>
    var BASE = "<?php echo base_url(); ?>";

    $(function () {
        function toYmd(v) {
            v = (v || '').trim(); if (!v) return '';
            var p = v.split('-'); return (p.length === 3) ? (p[2] + '-' + p[1] + '-' + p[0]) : v;
        }
        try {
            $(".et-from").datepicker({ dateFormat: "dd-mm-yy", onSelect: function (d) { $(".et-to").datepicker("option", "minDate", d); } });
            $(".et-to").datepicker({ dateFormat: "dd-mm-yy", onSelect: function (d) { $(".et-from").datepicker("option", "maxDate", d); } });
        } catch (e) { }

        function updateKpis(s) {
            if (!s) return;
            $('#kpiTotal').text(Number(s.total || 0).toLocaleString('en-IN'));
            $('#kpiApp').text(Number(s.app_cnt || 0).toLocaleString('en-IN'));
            $('#kpiGeo').text(Number(s.geo_cnt || 0).toLocaleString('en-IN'));
            $('#kpiIp').text(Number(s.ip_cnt || 0).toLocaleString('en-IN'));
            $('#kpiUser').text(Number(s.user_cnt || 0).toLocaleString('en-IN'));
        }

        var table = $('#et-grid').DataTable({
            "bStateSave": false, "processing": true, "serverSide": true,
            "lengthMenu": [[25, 50, 100, 200], [25, 50, 100, 200]],
            "iDisplayLength": 25, "pageLength": 25,
            "pagingType": "bootstrap_full_number",
            "language": { "search": "Search:", "processing": "Loading...", "emptyTable": "No trace records yet", "zeroRecords": "No matching records", "paginate": { "previous": "Prev", "next": "Next", "last": "Last", "first": "First" } },
            "columnDefs": [{ "targets": "_all", "orderable": false }],
            "ajax": {
                url: BASE + "admin/entry_trace/listing_data",
                type: "post",
                data: function (d) {
                    d.f_module = $('select[name=f_module]').val();
                    d.f_action = $('select[name=f_action]').val();
                    d.f_source = $('select[name=f_source]').val();
                    d.f_user   = $('select[name=f_user]').val();
                    d.f_ip     = $('input[name=f_ip]').val();
                    d.f_geo    = $('input[name=f_geo]').is(':checked') ? '1' : '';
                    d.f_from   = toYmd($('input[name=f_from]').val());
                    d.f_to     = toYmd($('input[name=f_to]').val());
                },
                dataSrc: function (json) {
                    if (json && json.stats) updateKpis(json.stats);
                    return json.data || [];
                },
                error: function () { $("#et-grid_processing").css("display", "none"); }
            },
            "order": []
        });

        // All filtering happens in place via AJAX — the page never reloads.
        function reloadGrid() { table.ajax.reload(); }

        $('#etApply').on('click', reloadGrid);
        $('#etFilter').on('submit', function (e) { e.preventDefault(); reloadGrid(); });

        // Live filtering: dropdowns, the geo toggle and the date fields apply the
        // moment they change — no need to press "Apply Filters".
        $('select[name=f_module], select[name=f_action], select[name=f_source], select[name=f_user]').on('change', reloadGrid);
        $('input[name=f_geo]').on('change', reloadGrid);
        $('input[name=f_from], input[name=f_to]').on('change', reloadGrid);

        // Live "IP contains" search — debounced so we reload once the user pauses.
        var ipTimer = null;
        $('input[name=f_ip]').on('keyup', function () {
            clearTimeout(ipTimer);
            ipTimer = setTimeout(reloadGrid, 350);
        });

        $('#etReset').on('click', function () {
            $('select[name=f_module], select[name=f_action], select[name=f_source], select[name=f_user]').val('all');
            $('input[name=f_ip], input[name=f_from], input[name=f_to]').val('');
            $('input[name=f_geo]').prop('checked', false);
            reloadGrid();
        });

        // ---- Retention: save the window, prune immediately, refresh grid ----
        $('#etRetSave').on('click', function () {
            var $btn = $(this), days = parseInt($('#etRetDays').val(), 10) || 0;
            $btn.prop('disabled', true);
            $.ajax({
                url: BASE + 'admin/entry_trace/save_retention',
                type: 'post', dataType: 'json', data: { retention_days: days },
                success: function (r) {
                    if (r && r.status === 'success') {
                        var msg;
                        if (r.retention_days > 0) {
                            msg = 'Keeping last <b>' + r.retention_days + '</b> days';
                            if (r.prunable_count > 0) { msg += ' &middot; <b>' + Number(r.prunable_count).toLocaleString('en-IN') + '</b> older row(s) remain to purge'; }
                            msg += '.';
                        } else {
                            msg = 'Currently keeping <b>all</b> history (no auto-delete).';
                        }
                        $('#etRetNote').html(msg);
                        if (window.showToast) showToast(r.message || 'Retention updated.', 'success');
                        table.ajax.reload();   // reflect any rows just pruned
                    } else if (window.showToast) {
                        showToast((r && r.message) || 'Could not save retention.', 'error');
                    }
                },
                error: function () { if (window.showToast) showToast('Could not save retention.', 'error'); },
                complete: function () { $btn.prop('disabled', false); }
            });
        });
    });
</script>
