<link href="<?php echo base_url();?>assets/global/css/components-rounded.css" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css"/>
<?php
$logs = isset($logs) ? $logs : array();
$st   = isset($stats) ? $stats : array('total'=>0,'genuine'=>0,'invalid'=>0,'cancelled'=>0,'invoices'=>0,'ips'=>0);
$cur  = isset($cur) ? $cur : '';
$esc  = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$chip = function ($v) use ($cur) { return $cur === $v ? ' on' : ''; };
?>
<style>
    .vl-scope { color: #18243c; }
    .vl-shell { max-width: 1360px; margin: 0 auto; }
    .vl-hero { position: relative; overflow: hidden; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
        padding: 18px 22px; margin-bottom: 14px; border-radius: 14px; color: #fff;
        background: radial-gradient(circle at 90% -30%, rgba(120,170,255,.5), transparent 40%), linear-gradient(125deg, #0f2748, #1d4ed8 60%, #2f9e6f);
        box-shadow: 0 18px 42px rgba(16,32,72,.26); }
    .vl-hero-ic { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; font-size: 20px; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.26); }
    .vl-hero h1 { margin: 0; font-size: 21px; font-weight: 900; }
    .vl-hero small { display: block; font-size: 12px; font-weight: 700; color: rgba(235,242,255,.85); margin-top: 3px; }
    .vl-kpis { display: grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap: 11px; margin-bottom: 14px; }
    .vl-kpi { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px solid #e3e9f2; border-radius: 12px; background: #fff; box-shadow: 0 10px 24px rgba(24,36,60,.06); }
    .vl-kpi-ic { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; font-size: 15px; color: #fff; flex: none; }
    .vl-kpi-t span { display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: #7a8aa0; }
    .vl-kpi-t strong { display: block; margin-top: 1px; font-size: 18px; font-weight: 900; color: #18243c; }
    .ic-b{background:linear-gradient(135deg,#2563eb,#1746a2)} .ic-g{background:linear-gradient(135deg,#1f9d70,#0c7048)}
    .ic-r{background:linear-gradient(135deg,#e5484d,#a11722)} .ic-a{background:linear-gradient(135deg,#e08a12,#9a5b06)}
    .ic-s{background:linear-gradient(135deg,#47566d,#2a3547)} .ic-c{background:linear-gradient(135deg,#0ea5e9,#0369a1)}
    .vl-bar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
    .vl-chip { display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px; border-radius: 999px; font-size: 12.5px; font-weight: 800; color: #475569; background: #fff; border: 1px solid #e3e9f2; text-decoration: none; }
    .vl-chip.on { background: #1d4ed8; color: #fff; border-color: #1d4ed8; }
    .vl-card { border: 1px solid #e3e9f2; border-radius: 14px; background: #fff; box-shadow: 0 12px 30px rgba(24,36,60,.06); padding: 16px 18px; }
    table#vl-grid { width: 100% !important; }
    table#vl-grid thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .03em; color: #64748b; font-weight: 800; border-bottom: 2px solid #eef2f7; padding: 9px 8px; white-space: nowrap; background: #f8fbff; }
    table#vl-grid tbody td { font-size: 12.5px; vertical-align: middle; border-top: 1px solid #f1f5f9; padding: 8px 8px; }
    .vl-vd { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 10.5px; font-weight: 800; text-transform: uppercase; }
    .vd-genuine { background: #dcfce7; color: #15803d; } .vd-invalid { background: #fee2e2; color: #b91c1c; } .vd-cancelled { background: #fef3c7; color: #92610a; }
    .vl-ip { font-family: ui-monospace, Menlo, monospace; font-weight: 700; color: #0369a1; }
    .vl-src { font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; }
    @media (max-width:1100px){ .vl-kpis{grid-template-columns:repeat(3,1fr);} }
    @media (max-width:640px){ .vl-kpis{grid-template-columns:repeat(2,1fr);} }
</style>

<main class="main-content vl-scope">
    <div id="mainContent">
        <div class="container-fluid vl-shell">
            <?= function_exists('get_flashdata') ? get_flashdata() : '' ?>

            <section class="vl-hero">
                <div class="vl-hero-ic"><i class="fa fa-qrcode"></i></div>
                <div><h1>Invoice Verification Log <small>Every time an invoice QR / verify link was scanned — who, when, from where &amp; the result</small></h1></div>
            </section>

            <div class="vl-kpis">
                <div class="vl-kpi"><div class="vl-kpi-ic ic-b"><i class="fa fa-list"></i></div><div class="vl-kpi-t"><span>Total Checks</span><strong><?= number_format((int)$st['total']) ?></strong></div></div>
                <div class="vl-kpi"><div class="vl-kpi-ic ic-g"><i class="fa fa-check"></i></div><div class="vl-kpi-t"><span>Genuine</span><strong><?= number_format((int)$st['genuine']) ?></strong></div></div>
                <div class="vl-kpi"><div class="vl-kpi-ic ic-r"><i class="fa fa-times"></i></div><div class="vl-kpi-t"><span>Failed / Invalid</span><strong><?= number_format((int)$st['invalid']) ?></strong></div></div>
                <div class="vl-kpi"><div class="vl-kpi-ic ic-a"><i class="fa fa-ban"></i></div><div class="vl-kpi-t"><span>Cancelled</span><strong><?= number_format((int)$st['cancelled']) ?></strong></div></div>
                <div class="vl-kpi"><div class="vl-kpi-ic ic-s"><i class="fa fa-file-text-o"></i></div><div class="vl-kpi-t"><span>Invoices Checked</span><strong><?= number_format((int)$st['invoices']) ?></strong></div></div>
                <div class="vl-kpi"><div class="vl-kpi-ic ic-c"><i class="fa fa-globe"></i></div><div class="vl-kpi-t"><span>Unique IPs</span><strong><?= number_format((int)$st['ips']) ?></strong></div></div>
            </div>

            <div class="vl-bar">
                <a class="vl-chip<?= $chip('') ?>" href="<?= base_url('admin/invoice/verify_logs') ?>"><i class="fa fa-list"></i> All</a>
                <a class="vl-chip<?= $chip('genuine') ?>" href="<?= base_url('admin/invoice/verify_logs?verdict=genuine') ?>"><i class="fa fa-check"></i> Genuine</a>
                <a class="vl-chip<?= $chip('invalid') ?>" href="<?= base_url('admin/invoice/verify_logs?verdict=invalid') ?>"><i class="fa fa-times"></i> Invalid</a>
                <a class="vl-chip<?= $chip('cancelled') ?>" href="<?= base_url('admin/invoice/verify_logs?verdict=cancelled') ?>"><i class="fa fa-ban"></i> Cancelled</a>
            </div>

            <div class="vl-card">
                <table class="table table-striped table-bordered" id="vl-grid" style="width:100%">
                    <thead>
                        <tr><th>#</th><th>When</th><th>Invoice</th><th>Party</th><th>Result</th><th>IP</th><th>Source</th><th>Device</th></tr>
                    </thead>
                    <tbody>
                        <?php $i=0; foreach ($logs as $l): $i++;
                            $vd = strtolower((string)$l->verdict);
                            $vdc = in_array($vd, array('genuine','invalid','cancelled'), true) ? $vd : 'invalid';
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td style="white-space:nowrap;"><?= !empty($l->verified_at) ? date('d M Y, h:i A', strtotime($l->verified_at)) : '—' ?></td>
                            <td><b><?= $esc($l->invoice_id ?: ('#'.$l->bos_id)) ?></b><?= $l->fy ? ' <small style="color:#94a3b8;">FY '.$esc($l->fy).'</small>' : '' ?><?= $l->product_name ? '<br><small style="color:#94a3b8;">'.$esc($l->product_name).'</small>' : '' ?></td>
                            <td><?= $esc($l->contact_person_name ?: '—') ?></td>
                            <td><span class="vl-vd vd-<?= $vdc ?>"><?= $esc(ucfirst($vd)) ?></span></td>
                            <td><span class="vl-ip"><?= $esc($l->ip_address ?: '—') ?></span></td>
                            <td><span class="vl-src"><?= $esc($l->source ?: 'web') ?></span></td>
                            <td style="max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#94a3b8; font-size:11px;" title="<?= $esc($l->user_agent) ?>"><?= $esc($l->user_agent ?: '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script>
    $(function () {
        $('#vl-grid').DataTable({
            order: [[0, 'asc']], pageLength: 25, lengthMenu: [[25,50,100,-1],[25,50,100,'All']],
            columnDefs: [{ orderable: false, targets: [7] }],
            language: { search: 'Search:', emptyTable: 'No invoice has been verified yet.' }
        });
    });
</script>
