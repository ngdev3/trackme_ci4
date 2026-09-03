<?php
include __DIR__ . '/_head.php';
$esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$f = $filters;
$expQs = http_build_query(array('from' => $f['from'], 'to' => $f['to'], 'user' => $f['user'] ? $f['user'] : ''));
?>
<div class="main-content mon-scope">
    <div class="mon-shell">
        <?php include __DIR__ . '/_tabs.php'; ?>
        <div class="mon-panel">
            <div class="mon-panel-h">
                <b>Login History</b>
                <a class="mon-btn" href="<?= base_url('admin/monitor/export/logins') . '?' . $expQs ?>"><i class="ti-download"></i> Export CSV</a>
            </div>
            <div class="mon-panel-b">
                <table id="mon-logins" class="table mon-tbl">
                    <thead><tr><th>User</th><th>IP</th><th>Device / Agent</th><th>When</th></tr></thead>
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
        $('#mon-logins').DataTable({
            processing: true, serverSide: true, order: [], pageLength: 25,
            lengthMenu: [[25, 50, 100], [25, 50, 100]],
            language: { search: 'Search:', emptyTable: 'No logins in this period', zeroRecords: 'No matching logins' },
            columnDefs: [{ targets: '_all', orderable: false }],
            ajax: {
                url: "<?= base_url('admin/monitor/logins_data') ?>", type: 'post',
                data: function (d) { d.from = f.from; d.to = f.to; d.user = f.user; },
                error: function () { $('#mon-logins_processing').hide(); }
            }
        });
    });
</script>
