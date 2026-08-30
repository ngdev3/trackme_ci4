<link href="<?php echo base_url(); ?>assets/global/css/components-rounded.css" id="style_components" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css" />
<?php
$QUERY_STRING = $_SERVER['QUERY_STRING'] ?? '';
$c = isset($counts) ? $counts : null;
$total  = $c && isset($c->total)  ? (int) $c->total  : 0;
$unread = $c && isset($c->unread) ? (int) $c->unread : 0;
$read   = $c && isset($c->read)   ? (int) $c->read   : 0;
?>

<style>
    .ntf-list-page { padding: 24px; color: #18243c; }
    .ntf-list-shell { max-width: 1320px; margin: 0 auto; }

    .ntf-hero {
        display: flex; align-items: center; justify-content: space-between; gap: 18px;
        margin-bottom: 18px; padding: 22px 24px;
        border: 1px solid #dce6f2; border-radius: 8px;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, .98), rgba(255, 255, 255, .92)),
            radial-gradient(circle at 94% 0, rgba(23, 105, 194, .13), transparent 34%);
        box-shadow: 0 16px 38px rgba(24, 36, 60, .08);
    }
    .ntf-title { margin: 0; color: #18243c; font-size: 25px; font-weight: 900; }
    .ntf-subtitle { margin: 6px 0 0; color: #718096; font-size: 13px; font-weight: 700; }
    .ntf-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .ntf-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        min-height: 42px; border-radius: 8px !important; font-weight: 900; padding: 10px 16px; transition: all .18s ease;
    }
    .ntf-btn-primary { border: 0; background: #1769c2; color: #fff; box-shadow: 0 10px 22px rgba(23, 105, 194, .2); }
    .ntf-btn-primary:hover, .ntf-btn-primary:focus { background: #0c5aaa; color: #fff; }

    .ntf-panel { border: 1px solid #dce6f2; border-radius: 8px; background: #fff; box-shadow: 0 16px 38px rgba(24, 36, 60, .08); }

    .ntf-snapshot-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
    .ntf-snapshot { padding: 16px; border: 1px solid #dce6f2; border-radius: 8px; background: #fff; box-shadow: 0 12px 26px rgba(24, 36, 60, .06); }
    .ntf-snapshot-label { margin: 0; color: #718096; font-size: 11px; font-weight: 900; text-transform: uppercase; }
    .ntf-snapshot-value { margin: 8px 0 0; font-size: 26px; font-weight: 900; }
    .ntf-snapshot-value.is-total { color: #18243c; }
    .ntf-snapshot-value.is-unread { color: #1769c2; }
    .ntf-snapshot-value.is-read { color: #1f9d70; }

    .ntf-table-panel { overflow: hidden; }
    .ntf-table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 18px 20px; border-bottom: 1px solid #edf2f7; }
    .ntf-table-title { margin: 0; color: #18243c; font-size: 17px; font-weight: 900; }
    .ntf-table-note { margin: 4px 0 0; color: #718096; font-size: 12px; font-weight: 700; }

    .ntf-table-wrap { position: relative; padding: 18px 20px 20px; }
    .ntf-table { width: 100% !important; margin-bottom: 0 !important; border-collapse: separate !important; border-spacing: 0; }
    .ntf-table thead th { border: 0 !important; border-bottom: 1px solid #dce6f2 !important; background: #f7fafc; color: #516174; font-size: 12px; font-weight: 900; text-transform: uppercase; white-space: nowrap; }
    .ntf-table tbody td { border-top: 1px solid #edf2f7 !important; color: #26374f; font-size: 13px; font-weight: 700; vertical-align: middle !important; }
    .ntf-table tbody td:nth-child(2) { text-align: left; }
    .ntf-table tbody tr:hover td { background: #fbfdff; }
    .ntf-table .badge { font-size: 11px; padding: 4px 10px; border-radius: 20px; }

    .ntf-act { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 7px; color: #fff !important; font-size: 13px; }
    .ntf-act-read { background: #1f9d70; }
    .ntf-act-unread { background: #a0aec0; }

    .ntf-table-wrap .dataTables_filter input, .ntf-table-wrap .dataTables_length select { min-height: 36px; border: 1px solid #dce6f2; border-radius: 8px; background: #fbfdff; color: #18243c; box-shadow: none; }
    .ntf-table-wrap .dataTables_length, .ntf-table-wrap .dataTables_filter, .ntf-table-wrap .dataTables_info, .ntf-table-wrap .dataTables_paginate { color: #516174; font-size: 12px; font-weight: 800; }
    .ntf-table-wrap .dataTables_filter { text-align: right; }
    .ntf-table-wrap .dataTables_filter input { width: 220px; padding: 6px 11px; }
    .ntf-table-wrap .dataTables_paginate { padding-top: 8px; text-align: right; }
    .ntf-table-wrap .dataTables_paginate .pagination { display: inline-flex; gap: 6px; margin: 0; }
    .ntf-table-wrap .dataTables_paginate .pagination > li > a, .ntf-table-wrap .dataTables_paginate .pagination > li > span { min-width: 36px; min-height: 36px; padding: 8px 11px; border: 1px solid #dce6f2; border-radius: 8px !important; background: #fff; color: #26374f; font-weight: 900; }
    .ntf-table-wrap .dataTables_paginate .pagination > .active > a, .ntf-table-wrap .dataTables_paginate .pagination > .active > span { border-color: #1769c2; background: #1769c2; color: #fff; }
    .ntf-table-wrap .dataTables_processing, #employee-grid-buyer_processing { position: absolute !important; top: 50% !important; left: 50% !important; z-index: 5; width: auto !important; min-width: 190px; padding: 13px 18px !important; transform: translate(-50%, -50%); border: 1px solid #b9d5f5; border-radius: 8px; background: #fff; color: #1769c2; font-weight: 900; box-shadow: 0 18px 38px rgba(24, 36, 60, .16); }

    @media (max-width: 767px) {
        .ntf-list-page { padding: 14px; }
        .ntf-hero, .ntf-table-toolbar { flex-direction: column; align-items: stretch; }
        .ntf-snapshot-grid { grid-template-columns: 1fr; }
        .ntf-table-wrap { overflow-x: auto; }
    }
</style>

<div id="msgShow"></div>
<main class="main-content bgc-grey-100 ntf-list-page">
    <div id="mainContent">
        <div class="container-fluid ntf-list-shell">
            <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>

            <section class="ntf-hero">
                <div>
                    <h4 class="ntf-title">Notifications</h4>
                    <p class="ntf-subtitle">Activity across the app &mdash; what was created, updated or accessed, and by whom.</p>
                </div>
                <div class="ntf-actions">
                    <a href="<?php echo base_url('admin/notification/mark_all_read'); ?>" class="btn ntf-btn ntf-btn-primary">
                        <i class="fa fa-check-double"></i> Mark all as read
                    </a>
                </div>
            </section>

            <section class="ntf-snapshot-grid">
                <div class="ntf-snapshot">
                    <p class="ntf-snapshot-label">Total</p>
                    <p class="ntf-snapshot-value is-total"><?php echo number_format($total); ?></p>
                </div>
                <div class="ntf-snapshot">
                    <p class="ntf-snapshot-label">Unread</p>
                    <p class="ntf-snapshot-value is-unread"><?php echo number_format($unread); ?></p>
                </div>
                <div class="ntf-snapshot">
                    <p class="ntf-snapshot-label">Read</p>
                    <p class="ntf-snapshot-value is-read"><?php echo number_format($read); ?></p>
                </div>
            </section>

            <section class="ntf-panel ntf-table-panel">
                <div class="ntf-table-toolbar">
                    <div>
                        <h5 class="ntf-table-title">Activity Log</h5>
                        <p class="ntf-table-note">Toggle the read / unread state using the action button.</p>
                    </div>
                </div>

                <div class="ntf-table-wrap">
                    <table class="table table-striped table-bordered ntf-table" style="text-align:center;" id="employee-grid-buyer">
                        <thead>
                            <tr>
                                <th>S.No.</th>
                                <th>Activity</th>
                                <th>Status</th>
                                <th>By</th>
                                <th>Date &amp; Time</th>
                                <th>When</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </section>
        </div>
    </div>
</main>

<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script src="<?= base_url(); ?>assets/admin/pages/scripts/table-managed.js"></script>

<script>
    function clickToSet(id, na) {
        $.ajax({
            url: "<?php echo base_url(); ?>admin/notification/updatenotificationStatus",
            type: "POST",
            dataType: 'json',
            data: { 'id': id, 'status': (na === 'checked' ? 'checked' : 'Notchecked') },
            success: function () {
                if (typeof jAlert === 'function') { jAlert('Updated successfully'); }
                setTimeout(function () { location.reload(); }, 700);
            },
            error: function () { alert("Error"); }
        });
    }

    // inline per-row buttons rendered by the CI4 feed
    $(document).on('click', '.ntf-toggle', function () {
        clickToSet($(this).data('id'), $(this).data('flag'));
    });

    var table = $('#employee-grid-buyer');
    table.dataTable({
        "bStateSave": false,
        "processing": true,
        "serverSide": true,
        "lengthMenu": [[25, 35, -1], [25, 35, "All"]],
        "iDisplayLength": 25,
        "pageLength": 25,
        "pagingType": "bootstrap_full_number",
        "language": {
            "search": "Search:",
            "lengthMenu": "_MENU_ Records",
            "processing": "Loading notifications...",
            "emptyTable": "No notifications found",
            "zeroRecords": "No matching notifications found",
            "paginate": { "previous": "Prev", "next": "Next", "last": "Last", "first": "First" }
        },
        "columnDefs": [{ "targets": "_all", "orderable": false }],
        "ajax": {
            url: "<?php echo base_url(); ?>admin/notification/view_all?<?php echo $QUERY_STRING; ?>",
            type: "post",
            error: function () { $("#employee-grid_processing").css("display", "none"); }
        },
        "order": []
    });

    $(document).ready(function () {
        setTimeout(function () {
            TableManaged.init();
            $(".form-group-custom").removeClass('hide');
        }, 1000);
    });
</script>
