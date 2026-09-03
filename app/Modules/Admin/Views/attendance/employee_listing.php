<?php helper(['url']); ?>
<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css" />
<style>
    .emp-page{--ink:var(--tm-ink,#18243c);--line:var(--tm-line,#dce6f2);--brand:var(--tm-brand,#1769c2);--brand-dark:var(--tm-brand-dark,#0c315f);--brand-soft:var(--tm-brand-soft,#eaf3ff)}
    .emp-shell{padding:0!important;border:0!important;background:transparent!important;box-shadow:none!important}
    .emp-hero{display:flex;justify-content:space-between;gap:18px;align-items:center;margin-bottom:18px;padding:22px;border-radius:12px;color:#fff;background:linear-gradient(135deg,var(--brand-dark),var(--brand));box-shadow:0 18px 44px rgba(24,36,60,.15)}
    .emp-hero h1{margin:0 0 6px;color:#fff;font-size:26px;font-weight:850}.emp-hero p{margin:0;color:rgba(242,248,255,.85);font-size:13px}
    .emp-btn{min-height:42px;display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:9px;color:var(--brand-dark)!important;background:#fff;font-weight:900;text-decoration:none!important}
    .emp-card{overflow:hidden;border:1px solid var(--line);border-radius:12px;background:#fff;box-shadow:0 18px 42px rgba(24,36,60,.08)}
    .emp-wrap{width:100%;max-width:100%;padding:16px;overflow-x:auto;-webkit-overflow-scrolling:touch}
    .emp-page th{background:var(--brand-soft);color:var(--brand-dark);font-size:12px;text-transform:uppercase}
    .emp-page th,.emp-page td{padding:12px!important;vertical-align:middle!important}
    @media(max-width:640px){.emp-hero{align-items:stretch;flex-direction:column}}
</style>

<main class="main-content bgc-grey-100 emp-page">
    <div id="mainContent">
        <div class="container-fluid">
            <?= get_flashdata(); ?>
            <div class="emp-shell bgc-white bd bdrs-3 p-20 mB-20">
                <section class="emp-hero">
                    <div>
                        <h1>Employee Management</h1>
                        <p>Create employees here before marking daily attendance.</p>
                    </div>
                    <a href="<?= base_url('admin/attendance/employee_add'); ?>" class="emp-btn"><i class="fa fa-plus"></i> Add Employee</a>
                </section>
                <section class="emp-card">
                    <div class="emp-wrap">
                        <table class="table table-striped table-bordered" id="employee-grid" style="width:100%">
                            <thead>
                                <tr>
                                    <th>S.No.</th><th>Code</th><th>Employee Name</th><th>Mobile</th>
                                    <th>Designation</th><th>Joining Date</th><th>Status</th><th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script>
    $('#employee-grid').dataTable({
        "processing": true, "serverSide": true, "scrollX": true, "autoWidth": false, "pageLength": 25,
        "lengthMenu": [[25, 50, 100, -1], [25, 50, 100, "All"]],
        "language": { "search": "Search employees:", "lengthMenu": "_MENU_ Records" },
        "columnDefs": [{ "targets": [0, 7], "orderable": false, "searchable": false }],
        "ajax": { url: "<?= base_url(); ?>admin/attendance/employee_view_all", type: "post" },
        "order": []
    });
</script>
