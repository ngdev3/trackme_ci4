<?php
include __DIR__ . '/_head.php';
$esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$st = isset($stats) ? $stats : array('failures' => 0, 'success' => 0, 'ips' => 0, 'emails' => 0);
$f = $filters;
?>
<div class="main-content mon-scope">
    <div class="mon-shell">
        <?php include __DIR__ . '/_tabs.php'; ?>

        <div class="mon-kpis" style="grid-template-columns:repeat(4,1fr);">
            <div class="mon-kpi"><div class="mon-kpi-ic ic-amber"><i class="ti-alert"></i></div><div class="mon-kpi-t"><span>Failed Attempts</span><strong><?= number_format((int) $st['failures']) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-green"><i class="ti-check-box"></i></div><div class="mon-kpi-t"><span>Successful Logins</span><strong><?= number_format((int) $st['success']) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-blue"><i class="ti-world"></i></div><div class="mon-kpi-t"><span>Source IPs (failed)</span><strong><?= number_format((int) $st['ips']) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-slate"><i class="ti-email"></i></div><div class="mon-kpi-t"><span>Emails Tried</span><strong><?= number_format((int) $st['emails']) ?></strong></div></div>
        </div>

        <div class="mon-panel" style="margin-bottom:14px;">
            <div class="mon-panel-b" style="font-size:12px;color:#475569;">
                <i class="ti-info-alt"></i> Every failed admin login is recorded here for review. This is a monitoring log only — no one is ever blocked or locked out.
            </div>
        </div>

        <!-- Repeat offenders -->
        <?php if (!empty($top_ips)): ?>
        <div class="mon-panel">
            <div class="mon-panel-h"><b>Top Source IPs (failed attempts)</b><span class="mon-badge"><?= $esc($f['from']) ?> → <?= $esc($f['to']) ?></span></div>
            <div class="mon-panel-b">
                <table class="table mon-tbl">
                    <thead><tr><th>IP Address</th><th>Failed Attempts</th><th>Emails Tried</th><th>Last Attempt</th></tr></thead>
                    <tbody>
                        <?php foreach ($top_ips as $t): ?>
                            <tr>
                                <td><span class="mon-ip"><?= $esc($t->ip_address) ?></span></td>
                                <td><?= number_format((int) $t->fails) ?></td>
                                <td><?= number_format((int) $t->emails) ?></td>
                                <td class="mon-when"><?= !empty($t->last_try) ? date('d M Y h:i A', strtotime($t->last_try)) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Full failed-attempt log -->
        <div class="mon-panel">
            <div class="mon-panel-h"><b>Failed Login Attempts</b><span class="mon-badge">every rejected login in this period</span></div>
            <div class="mon-panel-b">
                <table id="mon-failed" class="table mon-tbl">
                    <thead><tr><th>When</th><th>Email Tried</th><th>IP</th><th>Reason</th><th>Device / Agent</th></tr></thead>
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
        $('#mon-failed').DataTable({
            processing: true, serverSide: true, order: [], pageLength: 25,
            lengthMenu: [[25, 50, 100], [25, 50, 100]],
            language: { search: 'Search:', emptyTable: 'No failed login attempts in this period 🎉', zeroRecords: 'No matching attempts' },
            columnDefs: [{ targets: '_all', orderable: false }],
            ajax: {
                url: "<?= base_url('admin/monitor/login_security_data') ?>", type: 'post',
                data: function (d) { d.from = f.from; d.to = f.to; d.user = f.user; },
                error: function () { $('#mon-failed_processing').hide(); }
            }
        });
    });
</script>
