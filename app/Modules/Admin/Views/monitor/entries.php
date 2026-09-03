<?php
include __DIR__ . '/_head.php';
$esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$f = $filters;
$expQs = http_build_query(array('from' => $f['from'], 'to' => $f['to'], 'user' => $f['user'] ? $f['user'] : ''));
?>
<div class="main-content mon-scope">
    <div class="mon-shell">
        <?php include __DIR__ . '/_tabs.php'; ?>

        <div class="mon-kpis">
            <div class="mon-kpi"><div class="mon-kpi-ic ic-blue"><i class="ti-layers"></i></div><div class="mon-kpi-t"><span>Records</span><strong id="ekTotal">0</strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-violet"><i class="ti-mobile"></i></div><div class="mon-kpi-t"><span>From App</span><strong id="ekApp">0</strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-green"><i class="ti-location-pin"></i></div><div class="mon-kpi-t"><span>With Location</span><strong id="ekGeo">0</strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-amber"><i class="ti-world"></i></div><div class="mon-kpi-t"><span>Unique IPs</span><strong id="ekIp">0</strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-slate"><i class="ti-user"></i></div><div class="mon-kpi-t"><span>Users</span><strong id="ekUser">0</strong></div></div>
            <div class="mon-kpi" style="align-items:center; justify-content:center;"><a class="mon-btn" href="<?= base_url('admin/monitor/export/entries') . '?' . $expQs ?>"><i class="ti-download"></i> Export CSV</a></div>
        </div>

        <div class="mon-panel">
            <div class="mon-panel-b" style="display:grid; grid-template-columns:repeat(6,1fr); gap:12px; align-items:end;">
                <div><label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#718096;">Module</label>
                    <select class="form-control" id="fModule"><option value="all">All modules</option>
                        <?php foreach ($modules as $m): ?><option value="<?= $esc($m->module) ?>"><?= $esc(isset($module_labels[$m->module]) ? $module_labels[$m->module] : $m->module) ?></option><?php endforeach; ?>
                    </select></div>
                <div><label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#718096;">Action</label>
                    <select class="form-control" id="fAction"><option value="all">All</option><option value="create">Create</option><option value="update">Update</option><option value="delete">Delete</option></select></div>
                <div><label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#718096;">Source</label>
                    <select class="form-control" id="fSource"><option value="all">All</option><option value="Web">Web</option><option value="App">App</option><option value="System">System</option></select></div>
                <div><label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#718096;">IP contains</label>
                    <input type="text" class="form-control" id="fIp" placeholder="e.g. 103.42"></div>
                <div><label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#718096;">&nbsp;</label>
                    <label style="display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:800;color:#475569;min-height:40px;"><input type="checkbox" id="fGeo" value="1"> Only with location</label></div>
                <div><label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#718096;">&nbsp;</label>
                    <button class="mon-btn" id="eApply" style="width:100%;justify-content:center;"><i class="ti-filter"></i> Filter</button></div>
            </div>
        </div>

        <div class="mon-panel">
            <div class="mon-panel-b">
                <table id="mon-entries" class="table mon-tbl">
                    <thead><tr><th>Module</th><th>Entry</th><th>Action</th><th>User</th><th>Source</th><th>IP</th><th>Location</th><th>When</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script>
    $(function () {
        var f = { from: "<?= $esc($f['from']) ?>", to: "<?= $esc($f['to']) ?>", user: "<?= (int) $f['user'] ?>" };
        function setK(s) { if (!s) return; $('#ekTotal').text((s.total||0).toLocaleString('en-IN')); $('#ekApp').text((s.app_cnt||0).toLocaleString('en-IN')); $('#ekGeo').text((s.geo_cnt||0).toLocaleString('en-IN')); $('#ekIp').text((s.ip_cnt||0).toLocaleString('en-IN')); $('#ekUser').text((s.user_cnt||0).toLocaleString('en-IN')); }
        var t = $('#mon-entries').DataTable({
            processing: true, serverSide: true, order: [], pageLength: 25,
            lengthMenu: [[25, 50, 100, 200], [25, 50, 100, 200]],
            language: { search: 'Search:', emptyTable: 'No entry-audit records in this period', zeroRecords: 'No matching records' },
            columnDefs: [{ targets: '_all', orderable: false }],
            ajax: {
                url: "<?= base_url('admin/monitor/entries_data') ?>", type: 'post',
                data: function (d) {
                    d.f_module = $('#fModule').val(); d.f_action = $('#fAction').val(); d.f_source = $('#fSource').val();
                    d.f_ip = $('#fIp').val(); d.f_geo = $('#fGeo').is(':checked') ? '1' : '';
                    d.f_user = f.user || 'all'; d.f_from = f.from; d.f_to = f.to;
                },
                dataSrc: function (j) { if (j && j.stats) setK(j.stats); return j.data || []; },
                error: function () { $('#mon-entries_processing').hide(); }
            }
        });
        $('#eApply').on('click', function () { t.ajax.reload(); });
        $('#fModule,#fAction,#fSource,#fGeo').on('change', function () { t.ajax.reload(); });
        var ipt; $('#fIp').on('keyup', function () { clearTimeout(ipt); ipt = setTimeout(function () { t.ajax.reload(); }, 350); });
    });
</script>
