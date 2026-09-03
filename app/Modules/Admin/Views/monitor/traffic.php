<?php
include __DIR__ . '/_head.php';
$esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$sm = isset($summary) ? $summary : array('total' => 0, 'today' => 0, 'period' => 0, 'unique_pages' => 0, 'active_users' => 0);
$maxPage = 1; foreach ($top_pages as $p) { $maxPage = max($maxPage, (int) $p->total); }
$f = $filters;
$expQs = http_build_query(array('from' => $f['from'], 'to' => $f['to'], 'user' => $f['user'] ? $f['user'] : ''));
?>
<div class="main-content mon-scope">
    <div class="mon-shell">
        <?php include __DIR__ . '/_tabs.php'; ?>

        <div class="mon-kpis">
            <div class="mon-kpi"><div class="mon-kpi-ic ic-blue"><i class="ti-eye"></i></div><div class="mon-kpi-t"><span>Views (period)</span><strong><?= number_format((int) $sm['period']) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-green"><i class="ti-calendar"></i></div><div class="mon-kpi-t"><span>Today</span><strong><?= number_format((int) $sm['today']) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-slate"><i class="ti-layout"></i></div><div class="mon-kpi-t"><span>Unique Pages</span><strong><?= number_format((int) $sm['unique_pages']) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-violet"><i class="ti-users"></i></div><div class="mon-kpi-t"><span>Active Users</span><strong><?= number_format((int) $sm['active_users']) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-amber"><i class="ti-server"></i></div><div class="mon-kpi-t"><span>All-time Views</span><strong><?= number_format((int) $sm['total']) ?></strong></div></div>
            <div class="mon-kpi" style="align-items:center; justify-content:center;"><a class="mon-btn" href="<?= base_url('admin/monitor/export/traffic') . '?' . $expQs ?>"><i class="ti-download"></i> Export CSV</a></div>
        </div>

        <div class="mon-grid-2">
            <div class="mon-panel">
                <div class="mon-panel-h">
                    <b>Page Visits</b>
                    <span style="display:inline-flex;align-items:center;gap:8px;">
                        <label style="font-size:11px;font-weight:800;color:#516174;text-transform:uppercase;margin:0;">Session</label>
                        <select id="mon-session" style="border:1px solid #dce6f2;border-radius:8px;padding:5px 9px;font-weight:700;color:#18243c;background:#fbfdff;">
                            <option value="">All visits</option>
                            <option value="in">Logged-in only</option>
                            <option value="guest">Guest / bounced only</option>
                        </select>
                    </span>
                </div>
                <div class="mon-panel-b">
                    <table id="mon-traffic" class="table mon-tbl">
                        <thead><tr><th>#</th><th>User / Action</th><th>URL</th><th>When</th><th>IP</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="mon-panel">
                <div class="mon-panel-h"><b>Top Pages</b></div>
                <div class="mon-panel-b">
                    <?php if (empty($top_pages)): ?><div class="mon-empty">No data.</div><?php else: ?>
                        <ul class="mon-list">
                            <?php foreach ($top_pages as $p): ?>
                                <li>
                                    <div style="flex:1; min-width:0;">
                                        <div title="<?= $esc($p->url) ?>" style="font-weight:700; color:#334155; font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= $esc($p->url ?: 'Unknown') ?></div>
                                        <div class="mon-bar" style="margin-top:5px;"><i style="width:<?= (int) round($p->total / $maxPage * 100) ?>%"></i></div>
                                    </div>
                                    <span class="mon-badge" style="flex:none;"><?= number_format((int) $p->total) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mon-panel">
            <div class="mon-panel-h"><b>Most Active Users</b></div>
            <div class="mon-panel-b">
                <?php if (empty($user_activity)): ?><div class="mon-empty">No data.</div><?php else: ?>
                    <table class="table mon-tbl">
                        <thead><tr><th>User</th><th>Visits</th><th>Distinct Actions</th><th>Last Seen</th></tr></thead>
                        <tbody>
                            <?php foreach ($user_activity as $u): ?>
                                <tr>
                                    <td><b><?= $esc($u->user_name) ?></b></td>
                                    <td><?= number_format((int) $u->visits) ?></td>
                                    <td><?= number_format((int) $u->actions) ?></td>
                                    <td class="mon-when"><?= !empty($u->last_seen) ? date('d M Y h:i A', strtotime($u->last_seen)) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script>
    $(function () {
        var f = { from: "<?= $esc($f['from']) ?>", to: "<?= $esc($f['to']) ?>", user: "<?= (int) $f['user'] ?>" };
        var dt = $('#mon-traffic').DataTable({
            processing: true, serverSide: true, order: [], pageLength: 25,
            lengthMenu: [[25, 50, 100], [25, 50, 100]],
            language: { search: 'Search:', emptyTable: 'No page visits in this period', zeroRecords: 'No matching visits' },
            columnDefs: [{ targets: '_all', orderable: false }],
            ajax: {
                url: "<?= base_url('admin/monitor/traffic_data') ?>", type: 'post',
                data: function (d) { d.from = f.from; d.to = f.to; d.user = f.user; d.session = $('#mon-session').val(); },
                error: function () { $('#mon-traffic_processing').hide(); }
            }
        });
        $('#mon-session').on('change', function () { dt.ajax.reload(); });
    });
</script>
