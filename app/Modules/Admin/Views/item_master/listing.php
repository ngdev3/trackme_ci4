<link href="<?php echo base_url();?>assets/global/css/components-rounded.css" id="style_components" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css"/>
<?php
$items = isset($items) ? $items : array();
$st    = isset($stats) ? $stats : array('active' => 0, 'inactive' => 0, 'trashed' => 0, 'live' => 0, 'with_hsn' => 0);
$cur   = isset($cur) ? $cur : '';
$can_edit = isset($can_edit) ? (bool) $can_edit : false;   // super admin only
$chip  = function ($v) use ($cur) { return $cur === $v ? ' on' : ''; };
$esc   = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
?>
<style>
    .im-scope { color: #18243c; }
    .im-shell { max-width: 1380px; margin: 0 auto; min-width: 0; }
    .im-hero { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
        padding: 20px 24px; margin-bottom: 14px; border-radius: 14px; color: #fff;
        background: radial-gradient(circle at 88% -30%, rgba(120,170,255,.5), transparent 38%), linear-gradient(125deg, #0f2748, #1d4ed8 58%, #3b1e6e);
        box-shadow: 0 18px 42px rgba(16,32,72,.28); }
    .im-hero-l { display: flex; align-items: center; gap: 14px; position: relative; z-index: 1; }
    .im-hero-ic { width: 50px; height: 50px; border-radius: 13px; display: grid; place-items: center; font-size: 21px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.24); }
    .im-title { margin: 0; font-size: 22px; font-weight: 900; }
    .im-title small { display: block; font-size: 12px; font-weight: 700; color: rgba(235,242,255,.85); margin-top: 3px; }
    .im-add-btn { display: inline-flex; align-items: center; gap: 8px; min-height: 42px; padding: 0 18px; border-radius: 10px;
        background: #fff; color: #1740b5 !important; font-weight: 800; font-size: 13px; text-decoration: none; position: relative; z-index: 1; box-shadow: 0 10px 22px rgba(0,0,0,.16); }
    .im-add-btn:hover { background: #eef3ff; text-decoration: none; }

    .im-kpis { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 12px; margin-bottom: 14px; }
    .im-kpi { display: flex; align-items: center; gap: 11px; padding: 14px 15px; border: 1px solid #e3e9f2; border-radius: 13px; background: #fff; box-shadow: 0 10px 26px rgba(24,36,60,.06); }
    .im-kpi-ic { width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; font-size: 17px; color: #fff; flex: none; }
    .im-kpi-t span { display: block; font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: #7a8aa0; }
    .im-kpi-t strong { display: block; margin-top: 2px; font-size: 20px; font-weight: 900; color: #18243c; }
    .ic-blue { background: linear-gradient(135deg,#2563eb,#1746a2); } .ic-green { background: linear-gradient(135deg,#1f9d70,#0c7048); }
    .ic-amber { background: linear-gradient(135deg,#e08a12,#9a5b06); } .ic-red { background: linear-gradient(135deg,#e5484d,#a11722); } .ic-violet { background: linear-gradient(135deg,#7c3aed,#55208f); }

    .im-bar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
    .im-chip { display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; border-radius: 999px; font-size: 12.5px; font-weight: 800; color: #475569; background: #fff; border: 1px solid #e3e9f2; text-decoration: none; transition: all .14s ease; }
    .im-chip:hover { color: #1d4ed8; border-color: #b7cdf2; text-decoration: none; }
    .im-chip.on { background: #1d4ed8; color: #fff; border-color: #1d4ed8; box-shadow: 0 8px 20px rgba(29,78,216,.24); }
    .im-chip .cnt { font-size: 11px; padding: 0 7px; border-radius: 999px; background: rgba(0,0,0,.06); }
    .im-chip.on .cnt { background: rgba(255,255,255,.22); }

    .im-card { border: 1px solid #e3e9f2; border-radius: 14px; background: #fff; box-shadow: 0 12px 30px rgba(24,36,60,.06); padding: 16px 18px; }
    table#im-grid { width: 100% !important; }
    table#im-grid thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .03em; color: #64748b; font-weight: 800; border-bottom: 2px solid #eef2f7; padding: 10px 9px; white-space: nowrap; background: #f8fbff; }
    table#im-grid tbody td { font-size: 13px; vertical-align: middle; border-top: 1px solid #f1f5f9; padding: 9px 9px; }
    table#im-grid tbody tr:hover { background: #f7faff; }
    .im-name { font-weight: 800; color: #14213d; }
    .im-unit { display: inline-block; padding: 2px 9px; border-radius: 999px; background: #eef2f8; color: #334155; font-size: 11.5px; font-weight: 800; }
    .im-hsn { font-family: ui-monospace, Menlo, monospace; font-weight: 700; color: #0369a1; }
    .im-badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; }
    .im-b-active { background: #dcfce7; color: #0c6b2e; } .im-b-inactive { background: #fef3c7; color: #9a5b06; } .im-b-delete { background: #fee2e2; color: #b42318; }
    .im-actions .btn { margin: 2px; border-radius: 8px; }
    @media (max-width: 1100px) { .im-kpis { grid-template-columns: repeat(3,1fr); } }
    @media (max-width: 640px)  { .im-kpis { grid-template-columns: repeat(2,1fr); } }
</style>

<div id="msgShow"></div>
<main class="main-content im-scope">
    <div id="mainContent">
        <div class="container-fluid im-shell">
            <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>

            <section class="im-hero">
                <div class="im-hero-l">
                    <div class="im-hero-ic"><i class="fa fa-cubes"></i></div>
                    <div><h1 class="im-title">Item Master <small>Products / items available for the Stock module</small></h1></div>
                </div>
                <?php if ($can_edit): ?>
                <a href="<?php echo base_url('admin/item_master/add'); ?>" class="im-add-btn"><i class="fa fa-plus"></i> Add Item</a>
                <?php else: ?>
                <span class="im-add-btn" style="background:rgba(255,255,255,.16);color:#fff !important;box-shadow:none;cursor:default;"><i class="fa fa-eye"></i> View only</span>
                <?php endif; ?>
            </section>

            <div class="im-kpis">
                <div class="im-kpi"><div class="im-kpi-ic ic-blue"><i class="fa fa-cubes"></i></div><div class="im-kpi-t"><span>Total Items</span><strong><?= number_format((int) $st['live']) ?></strong></div></div>
                <div class="im-kpi"><div class="im-kpi-ic ic-green"><i class="fa fa-check-circle"></i></div><div class="im-kpi-t"><span>Active</span><strong><?= number_format((int) $st['active']) ?></strong></div></div>
                <div class="im-kpi"><div class="im-kpi-ic ic-amber"><i class="fa fa-pause-circle"></i></div><div class="im-kpi-t"><span>Inactive</span><strong><?= number_format((int) $st['inactive']) ?></strong></div></div>
                <div class="im-kpi"><div class="im-kpi-ic ic-violet"><i class="fa fa-hashtag"></i></div><div class="im-kpi-t"><span>With HSN</span><strong><?= number_format((int) $st['with_hsn']) ?></strong></div></div>
                <div class="im-kpi"><div class="im-kpi-ic ic-red"><i class="fa fa-trash"></i></div><div class="im-kpi-t"><span>Trash</span><strong><?= number_format((int) $st['trashed']) ?></strong></div></div>
            </div>

            <div class="im-bar">
                <a class="im-chip<?= $chip('') ?>" href="<?php echo base_url('admin/item_master/listing'); ?>"><i class="fa fa-list"></i> All <span class="cnt"><?= number_format((int) $st['live']) ?></span></a>
                <a class="im-chip<?= $chip('Active') ?>" href="<?php echo base_url('admin/item_master/listing?status=Active'); ?>"><i class="fa fa-check"></i> Active <span class="cnt"><?= number_format((int) $st['active']) ?></span></a>
                <a class="im-chip<?= $chip('Inactive') ?>" href="<?php echo base_url('admin/item_master/listing?status=Inactive'); ?>"><i class="fa fa-pause"></i> Inactive <span class="cnt"><?= number_format((int) $st['inactive']) ?></span></a>
                <a class="im-chip<?= $chip('Delete') ?>" href="<?php echo base_url('admin/item_master/listing?status=Delete'); ?>"><i class="fa fa-trash"></i> Trash <span class="cnt"><?= number_format((int) $st['trashed']) ?></span></a>
            </div>

            <div class="im-card">
                <table class="table table-striped table-bordered" id="im-grid" style="width:100%">
                    <thead>
                        <tr><th>#</th><th>Item Name</th><th>Unit</th><th>HSN Code</th><th>Status</th><th>Added By</th><th>Added On</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php $i = 0; foreach ($items as $it): $i++;
                            $isTrash = ($it->status === 'Delete');
                            $bcls = $it->status === 'Active' ? 'im-b-active' : ($it->status === 'Inactive' ? 'im-b-inactive' : 'im-b-delete');
                            $eid  = ID_encode($it->id);
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><span class="im-name"><?= $esc($it->product_name) ?></span></td>
                            <td><span class="im-unit"><?= $esc($it->unit ?: '-') ?></span></td>
                            <td><?= $it->hsn_code !== '' && $it->hsn_code !== null ? '<span class="im-hsn">' . $esc($it->hsn_code) . '</span>' : '<span style="color:#b0b8c6;">—</span>' ?></td>
                            <td><span class="im-badge <?= $bcls ?>"><?= $esc($it->status) ?></span></td>
                            <td><?= $esc(trim((string) $it->added_by_name) !== '' ? $it->added_by_name : '—') ?></td>
                            <td style="white-space:nowrap;"><?= !empty($it->added_date) ? date('d M Y', strtotime($it->added_date)) : '—' ?></td>
                            <td class="im-actions text-nowrap">
                                <?php if (!$can_edit): ?>
                                    <span style="color:#94a3b8;font-weight:700;font-size:12px;"><i class="fa fa-eye"></i> View only</span>
                                <?php elseif ($isTrash): ?>
                                    <a class="btn btn-warning btn-sm" title="Restore" href="javascript:void(0)" onclick="imDelete(<?= (int) $it->id ?>,'restore')"><i class="fa fa-undo"></i></a>
                                <?php else: ?>
                                    <a class="btn btn-primary btn-sm" title="Edit" href="<?php echo base_url('admin/item_master/edit/' . $eid); ?>"><i class="fa fa-pencil"></i></a>
                                    <?php if ($it->status === 'Active'): ?>
                                        <a class="btn btn-secondary btn-sm" title="Set Inactive" href="javascript:void(0)" onclick="imToggle(<?= (int) $it->id ?>,'Inactive')"><i class="fa fa-times"></i></a>
                                    <?php else: ?>
                                        <a class="btn btn-success btn-sm" title="Set Active" href="javascript:void(0)" onclick="imToggle(<?= (int) $it->id ?>,'Active')"><i class="fa fa-check"></i></a>
                                    <?php endif; ?>
                                    <a class="btn btn-danger btn-sm" title="Delete" href="javascript:void(0)" onclick="imDelete(<?= (int) $it->id ?>,'delete')"><i class="fa fa-trash"></i></a>
                                <?php endif; ?>
                            </td>
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
        $('#im-grid').DataTable({
            order: [[1, 'asc']],
            pageLength: 25,
            lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
            columnDefs: [{ orderable: false, targets: [7] }],
            language: { search: 'Search:', emptyTable: 'No items yet — click “Add Item”.', zeroRecords: 'No matching items' }
        });
    });

    function imAjax(url, data, done) {
        $.ajax({ url: "<?php echo base_url(); ?>" + url, type: 'POST', dataType: 'json', data: data,
            success: function (r) { if (r && r.status === 'success') { done(); } else { imErr(); } },
            error: function () { imErr(); } });
    }
    function imErr() {
        if (window.showToast) { showToast('Something went wrong', 'error'); } else { alert('Something went wrong'); }
    }
    function imDelete(id, action) {
        var title = action === 'restore' ? 'Restore this item?' : 'Delete this item?';
        var sub = action === 'restore' ? 'It will be set back to Active.' : 'It will move to Trash and can be restored later.';
        var go = function () { imAjax('admin/item_master/delete', { id: id, action: action }, function () { location.reload(); }); };
        if (window.showConfirm) { showConfirm(title, sub, go); } else if (confirm(title)) { go(); }
    }
    function imToggle(id, status) {
        imAjax('admin/item_master/updateStatus', { id: id, status: status }, function () { location.reload(); });
    }
</script>
