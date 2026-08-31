<link href="<?= base_url(); ?>assets/global/css/components-rounded.css" id="style_components" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css" />

<div id="msgShow"></div>
<main class="main-content bgc-grey-100">
    <div id="mainContent">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="bgc-white bd bdrs-3 p-20 mB-20">
                        <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
                        <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>
                        <div class="gap-10 peers">
                            <div class="peer">
                                <h4 class="c-grey-900 mB-20 pull-left">Tasks</h4>
                                <a href="<?= base_url('task/task/add'); ?>" id="back-btn" class="btn cur-p btn-primary pull-right">ADD TASK</a>
                            </div>
                            <form method="get" id="filter_id" class="pull-right">
                                <?php $st = $_GET['status'] ?? ''; ?>
                                <select class="form-control custom_filter" name="status">
                                    <option value=""> All Status </option>
                                    <option value="open" <?= $st === 'open' ? 'selected' : ''; ?>> Open </option>
                                    <option value="in_progress" <?= $st === 'in_progress' ? 'selected' : ''; ?>> In Progress </option>
                                    <option value="done" <?= $st === 'done' ? 'selected' : ''; ?>> Done </option>
                                    <option value="closed" <?= $st === 'closed' ? 'selected' : ''; ?>> Closed </option>
                                </select>
                            </form>
                        </div>

                        <table class="table table-striped table-bordered" id="task-grid">
                            <thead>
                                <tr>
                                    <th width="5%">S.No.</th>
                                    <th width="28%">Title</th>
                                    <th width="12%">Status</th>
                                    <th width="10%">Priority</th>
                                    <th width="15%">Assignee</th>
                                    <th width="8%">Comments</th>
                                    <th width="12%">Created</th>
                                    <th width="10%">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js"></script>
<script src="<?= base_url(); ?>assets/admin/pages/scripts/table-managed.js"></script>

<script>
    var BASE = "<?= base_url(); ?>", QS = "<?= esc($_SERVER['QUERY_STRING'] ?? '', 'js'); ?>";

    function delete_task(id) {
        if (id !== '' && confirm('Are you sure you want to delete this task?')) {
            $.ajax({
                type: "POST",
                url: BASE + "task/task/delete",
                data: { id: id },
                success: function () { window.location.href = BASE + "task/task"; }
            });
        }
        return false;
    }

    var table = $('#task-grid');
    table.dataTable({
        "bStateSave": false,
        "processing": true,
        "serverSide": true,
        "lengthMenu": [[5, 15, 20, -1], [5, 15, 20, "All"]],
        "pageLength": 15,
        "pagingType": "bootstrap_full_number",
        "language": { "search": "My search: ", "lengthMenu": "_MENU_ Records", "paginate": { "previous": "Prev", "next": "Next", "last": "Last", "first": "First" } },
        "ajax": {
            url: BASE + "task/task/view_all?" + QS,
            type: "post",
            error: function () { $("#task-grid_processing").css("display", "none"); }
        },
        "columnDefs": [
            { "targets": [0, 5, 7], "orderable": false, "searchable": false }
        ],
        "order": []
    });

    $(".custom_filter").on("change", function () { $("#filter_id").submit(); });
</script>
