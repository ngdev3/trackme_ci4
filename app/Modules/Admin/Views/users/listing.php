<link href="<?php echo base_url();?>assets/global/css/components-rounded.css" id="style_components" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css"/>
<?php
$QUERY_STRING = http_build_query($_GET ?? []);
$us = isset($user_stats) ? $user_stats : array('total' => 0, 'active' => 0, 'inactive' => 0, 'deleted' => 0, 'roles' => 0);
$curStatus = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
$chip = function ($val) use ($curStatus) { return $curStatus === $val ? ' on' : ''; };
?>

<style>
    .usr-scope { color: #18243c; }
    .usr-shell { max-width: 1480px; margin: 0 auto; min-width: 0; }
    .usr-hero { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
        padding: 20px 24px; margin-bottom: 14px; border-radius: 14px; color: #fff;
        background: radial-gradient(circle at 88% -30%, rgba(120,170,255,.5), transparent 38%), linear-gradient(125deg, #0f2748, #1d4ed8 58%, #3b1e6e);
        box-shadow: 0 18px 42px rgba(16,32,72,.28); }
    .usr-hero-l { display: flex; align-items: center; gap: 14px; position: relative; z-index: 1; }
    .usr-hero-ic { width: 50px; height: 50px; border-radius: 13px; display: grid; place-items: center; font-size: 21px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.24); }
    .usr-title { margin: 0; font-size: 22px; font-weight: 900; }
    .usr-title small { display: block; font-size: 12px; font-weight: 700; color: rgba(235,242,255,.85); margin-top: 3px; }
    .usr-add-btn { display: inline-flex; align-items: center; gap: 8px; min-height: 42px; padding: 0 18px; border-radius: 10px;
        background: #fff; color: #1740b5 !important; font-weight: 800; font-size: 13px; text-decoration: none; position: relative; z-index: 1;
        box-shadow: 0 10px 22px rgba(0,0,0,.16); }
    .usr-add-btn:hover { background: #eef3ff; text-decoration: none; }
    .usr-kpis { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 12px; margin-bottom: 14px; }
    .usr-kpi { display: flex; align-items: center; gap: 11px; padding: 14px 15px; border: 1px solid #e3e9f2; border-radius: 13px; background: #fff; box-shadow: 0 10px 26px rgba(24,36,60,.06); }
    .usr-kpi-ic { width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; font-size: 17px; color: #fff; flex: none; }
    .usr-kpi-t span { display: block; font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: #7a8aa0; }
    .usr-kpi-t strong { display: block; margin-top: 2px; font-size: 20px; font-weight: 900; color: #18243c; }
    .ic-blue { background: linear-gradient(135deg,#2563eb,#1746a2); } .ic-green { background: linear-gradient(135deg,#1f9d70,#0c7048); }
    .ic-amber { background: linear-gradient(135deg,#e08a12,#9a5b06); } .ic-red { background: linear-gradient(135deg,#e5484d,#a11722); }
    .ic-violet { background: linear-gradient(135deg,#7c3aed,#55208f); }
    .usr-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
    .usr-chips { display: flex; gap: 7px; flex-wrap: wrap; }
    .usr-chip { display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; border-radius: 999px; font-size: 12.5px; font-weight: 800;
        color: #475569; background: #fff; border: 1px solid #e3e9f2; text-decoration: none; transition: all .14s ease; }
    .usr-chip:hover { color: #1d4ed8; border-color: #b7cdf2; text-decoration: none; }
    .usr-chip.on { background: #1d4ed8; color: #fff; border-color: #1d4ed8; box-shadow: 0 8px 20px rgba(29,78,216,.24); }
    .usr-chip b { font-weight: 900; }
    .usr-chip .cnt { font-size: 11px; padding: 0 7px; border-radius: 999px; background: rgba(0,0,0,.06); }
    .usr-chip.on .cnt { background: rgba(255,255,255,.22); }
    .usr-card { border: 1px solid #e3e9f2; border-radius: 14px; background: #fff; box-shadow: 0 12px 30px rgba(24,36,60,.06); padding: 16px 18px; }
    .usr-card .dataTables_wrapper { padding-top: 4px; }
    table#employee-grid-buyer { width: 100% !important; }
    table#employee-grid-buyer thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .03em; color: #64748b; font-weight: 800;
        border-bottom: 2px solid #eef2f7; padding: 9px 6px; white-space: nowrap; background: #f8fbff; }
    table#employee-grid-buyer tbody td { font-size: 13px; vertical-align: middle; border-top: 1px solid #f1f5f9; padding: 8px 6px; }
    table#employee-grid-buyer tbody tr:hover { background: #f7faff; }
    .users-actions { text-align: center; }
    .users-actions .btn { margin: 1px; }
    .u-id { display: flex; align-items: center; gap: 11px; }
    .u-avatar { flex: none; width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; color: #fff; font-weight: 800; font-size: 14px; letter-spacing: .5px; box-shadow: 0 4px 10px rgba(24,36,60,.16); }
    .u-id-text { display: flex; flex-direction: column; min-width: 0; }
    .u-name { font-weight: 800; color: #18243c; font-size: 13.5px; line-height: 1.25; }
    .u-mail { font-size: 11.5px; color: #64748b; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 230px; }
    .u-mail i, .u-sub i, .u-line i { opacity: .6; margin-right: 3px; }
    .u-acc { font-size: 10.5px; font-weight: 800; color: #94a3b8; letter-spacing: .03em; margin-top: 1px; }
    .u-stack { display: flex; flex-direction: column; gap: 2px; }
    .u-line { font-size: 13px; font-weight: 700; color: #334155; }
    .u-line i { color: #94a3b8; }
    .u-sub { font-size: 11.5px; color: #7a8aa0; font-weight: 600; }
    .u-muted { color: #b6c0cf; font-weight: 600; }
    .u-badge { display: inline-block; align-self: flex-start; max-width: 220px; padding: 3px 10px; border-radius: 7px; background: #eef3ff; color: #2148b7; font-size: 11.5px; font-weight: 800; border: 1px solid #dbe6fb; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .u-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 999px; font-size: 11.5px; font-weight: 800; }
    .u-pill i { font-size: 9px; }
    .u-pill.on { background: #e7f7ee; color: #12824a; }
    .u-pill.off { background: #fff3e0; color: #b26a00; }
    .u-pill.del { background: #fdeaf0; color: #a3164b; }
    @media (max-width: 1200px) { .usr-kpis { grid-template-columns: repeat(3,1fr); } }
    @media (max-width: 640px)  { .usr-kpis { grid-template-columns: repeat(2,1fr); } .u-mail { max-width: 150px; } }
</style>

<div id="msgShow"></div>
<main class="main-content usr-scope">
    <div id="mainContent">
        <div class="container-fluid usr-shell">

            <section class="usr-hero">
                <div class="usr-hero-l">
                    <div class="usr-hero-ic"><i class="fa fa-users"></i></div>
                    <div>
                        <h1 class="usr-title">Users
                            <small>Manage staff accounts, roles, status, default firm &amp; permissions</small>
                        </h1>
                    </div>
                </div>
                <a href="<?php echo base_url('admin/users/add'); ?>" id="back-btn" class="usr-add-btn">
                    <i class="fa fa-plus"></i> Add User
                </a>
            </section>

            <div class="usr-kpis">
                <div class="usr-kpi"><div class="usr-kpi-ic ic-blue"><i class="fa fa-users"></i></div><div class="usr-kpi-t"><span>Total Users</span><strong><?= number_format((int) $us['total']) ?></strong></div></div>
                <div class="usr-kpi"><div class="usr-kpi-ic ic-green"><i class="fa fa-check-circle"></i></div><div class="usr-kpi-t"><span>Active</span><strong><?= number_format((int) $us['active']) ?></strong></div></div>
                <div class="usr-kpi"><div class="usr-kpi-ic ic-amber"><i class="fa fa-pause-circle"></i></div><div class="usr-kpi-t"><span>Inactive</span><strong><?= number_format((int) $us['inactive']) ?></strong></div></div>
                <div class="usr-kpi"><div class="usr-kpi-ic ic-red"><i class="fa fa-trash"></i></div><div class="usr-kpi-t"><span>Deleted</span><strong><?= number_format((int) $us['deleted']) ?></strong></div></div>
                <div class="usr-kpi"><div class="usr-kpi-ic ic-violet"><i class="fa fa-shield"></i></div><div class="usr-kpi-t"><span>Roles / Types</span><strong><?= number_format((int) $us['roles']) ?></strong></div></div>
            </div>

            <div class="usr-bar">
                <div class="usr-chips">
                    <a class="usr-chip<?= $chip('') ?>" href="<?php echo base_url('admin/users/listing'); ?>"><i class="fa fa-list"></i> <b>All</b> <span class="cnt"><?= number_format((int) $us['total']) ?></span></a>
                    <a class="usr-chip<?= $chip('Active') ?>" href="<?php echo base_url('admin/users/listing?status=Active'); ?>"><i class="fa fa-check"></i> Active <span class="cnt"><?= number_format((int) $us['active']) ?></span></a>
                    <a class="usr-chip<?= $chip('Inactive') ?>" href="<?php echo base_url('admin/users/listing?status=Inactive'); ?>"><i class="fa fa-pause"></i> Inactive <span class="cnt"><?= number_format((int) $us['inactive']) ?></span></a>
                    <a class="usr-chip<?= $chip('Delete') ?>" href="<?php echo base_url('admin/users/listing?status=Delete'); ?>"><i class="fa fa-trash"></i> Deleted <span class="cnt"><?= number_format((int) $us['deleted']) ?></span></a>
                </div>
            </div>

            <div class="usr-card">
                <table class="table table-striped table-bordered" id="employee-grid-buyer" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Role &amp; Firm</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
                <div class="usr-legend" style="margin-top:12px;color:#7a8aa0;font-size:12px;font-weight:700;">
                    Row actions: View · Edit · Permissions · Activate/Deactivate · Delete/Restore.
                </div>
            </div>
        </div>
    </div>
</main>

<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>

<script>
    var BASE = "<?php echo base_url(); ?>";

    $('#employee-grid-buyer').dataTable({
        "bStateSave": false,
        "processing": true,
        "serverSide": true,
        "responsive": true,
        "columns": [
            { "orderable": false }, { "orderable": false }, { "orderable": false },
            { "orderable": false }, { "orderable": false }, { "orderable": false }, { "orderable": false }
        ],
        "lengthMenu": [[25, 35, -1], [25, 35, "All"]],
        "iDisplayLength": 25,
        "pageLength": 35,
        "pagingType": "bootstrap_full_number",
        "language": { "search": "Search: ", "lengthMenu": "_MENU_ Records", "paginate": { "previous": "Prev", "next": "Next", "last": "Last", "first": "First" } },
        "ajax": {
            url: BASE + "admin/users/view_all?<?php echo $QUERY_STRING; ?>",
            type: "post",
            error: function () { $("#employee-grid_processing").css("display", "none"); }
        },
        "order": []
    });

    // Activate / deactivate
    $(document).on('click', '.usr-toggle', function () {
        var id = $(this).data('id'), flag = $(this).data('flag');
        $.ajax({
            url: BASE + "admin/users/updateUserStatus", type: "POST", dataType: 'json',
            data: { id: id, status: flag },
            success: function () { location.reload(); },
            error: function () { alert('Error updating status'); }
        });
    });

    // Delete / restore
    $(document).on('click', '.usr-del', function () {
        var id = $(this).data('id'), status = $(this).data('status');
        var msg = (status === 'Delete') ? 'Delete this user?' : 'Restore this user?';
        if (!confirm(msg)) { return; }
        $.ajax({
            url: BASE + "admin/users/delete", type: "POST", dataType: 'json',
            data: { id: id, status: status },
            success: function () { location.reload(); },
            error: function () { alert('Error'); }
        });
    });
</script>
