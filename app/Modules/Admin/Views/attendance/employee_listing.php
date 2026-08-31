<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css" />

<style>
    .attendance-actions{display:inline-flex;gap:8px;white-space:nowrap}.attendance-action-btn{width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(var(--tm-brand-rgb,23,105,194),.22);border-radius:8px;color:var(--tm-brand,#1769c2)!important;background:#fff;text-decoration:none!important}.attendance-action-btn:hover{color:#fff!important;background:var(--tm-brand,#1769c2)}.attendance-action-delete:hover{background:#e5484d;border-color:#e5484d}.attendance-action-status.is-active{color:#178a52!important;border-color:rgba(23,138,82,.24);background:#eefaf4}.attendance-action-status.is-inactive{color:#9a6700!important;border-color:rgba(154,103,0,.24);background:#fff8e5}
    .emp-page{--ink:var(--tm-ink,#18243c);--muted:var(--tm-muted,#718096);--line:var(--tm-line,#dce6f2);--brand:var(--tm-brand,#1769c2);--brand-dark:var(--tm-brand-dark,#0c315f);--brand-soft:var(--tm-brand-soft,#eaf3ff)}
    .emp-shell{padding:0!important;border:0!important;background:transparent!important;box-shadow:none!important}.emp-hero{display:flex;justify-content:space-between;gap:18px;align-items:center;margin-bottom:18px;padding:22px;border-radius:8px;color:#fff;background:linear-gradient(135deg,var(--brand-dark),color-mix(in srgb,var(--brand) 62%,#101827));box-shadow:0 18px 44px rgba(24,36,60,.15)}.emp-hero h1{margin:0 0 6px;color:#fff;font-size:28px;font-weight:850}.emp-hero p{margin:0;color:rgba(242,248,255,.8)}.emp-btn{min-height:42px;display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:8px;color:var(--brand-dark)!important;background:#fff;font-weight:900;text-decoration:none!important}.emp-card{overflow:hidden;border:1px solid var(--line);border-radius:8px;background:#fff;box-shadow:0 18px 42px rgba(24,36,60,.08)}.emp-wrap{width:100%;max-width:100%;padding:16px;overflow-x:auto;-webkit-overflow-scrolling:touch}.emp-page .dataTables_wrapper{width:100%}.emp-page .dataTables_scroll{width:100%;overflow:hidden}.emp-page .dataTables_scrollBody{overflow-x:auto!important;-webkit-overflow-scrolling:touch}.emp-page .dataTables_scrollHeadInner,.emp-page .dataTables_scrollHeadInner table,.emp-page .dataTables_scrollBody table{min-width:1040px!important}.emp-page .dataTables_wrapper .row:first-child{display:grid;grid-template-columns:minmax(180px,auto) minmax(260px,420px);align-items:center;justify-content:space-between;gap:14px;margin:0 0 14px}.emp-page .dataTables_wrapper .row:first-child:before,.emp-page .dataTables_wrapper .row:first-child:after{display:none}.emp-page .dataTables_wrapper .row:first-child>[class*="col-"]{width:auto;float:none;padding:0}.emp-page .dataTables_length,.emp-page .dataTables_filter{color:var(--muted);font-size:12px;font-weight:800}.emp-page .dataTables_filter{text-align:right}.emp-page .dataTables_filter label{display:inline-flex;align-items:center;gap:8px;justify-content:flex-end;margin:0}.emp-page .dataTables_filter input,.emp-page .dataTables_length select{height:40px;margin-left:7px;border:1px solid var(--line)!important;border-radius:8px!important;background:#fff;box-shadow:none}.emp-page table{min-width:1040px;width:100%!important;table-layout:fixed}.emp-page th{background:var(--brand-soft);color:var(--brand-dark);font-size:12px;text-transform:uppercase}.emp-page th,.emp-page td{padding:13px!important;vertical-align:middle!important}.emp-page th:first-child,.emp-page td:first-child{width:80px;text-align:center}.emp-page th:last-child,.emp-page td:last-child{width:150px;text-align:center}
    @media(max-width:767px){.emp-page{overflow-x:hidden}.emp-hero{align-items:stretch;flex-direction:column}.emp-wrap{margin-right:-12px;padding-right:12px;overflow-x:scroll;touch-action:pan-x}.emp-page .dataTables_wrapper .row:first-child{grid-template-columns:1fr}.emp-page .dataTables_filter{text-align:left}.emp-page .dataTables_filter label{justify-content:flex-start;flex-wrap:wrap}.emp-page .dataTables_filter input{width:100%;max-width:100%;margin:8px 0 0}.emp-page .dataTables_scrollBody{border-bottom:1px solid var(--line)}}
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
                        <table class="table table-striped table-bordered" id="employee-attendance-grid">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Code</th>
                                    <th>Employee Name</th>
                                    <th>Mobile</th>
                                    <th>Designation</th>
                                    <th>Joining Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
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
    $('#employee-attendance-grid').dataTable({
        "processing": true,
        "serverSide": true,
        "scrollX": true,
        "autoWidth": false,
        "pageLength": 35,
        "lengthMenu": [[25, 35, 50, -1], [25, 35, 50, "All"]],
        "language": {"search": "Search employees:", "lengthMenu": "_MENU_ Records"},
        "columnDefs": [{"targets": [0, 7], "orderable": false, "searchable": false}],
        "ajax": {url: "<?= base_url(); ?>admin/attendance/employee_view_all", type: "post"},
        "order": []
    });
</script>
