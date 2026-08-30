<link href="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') ?>" rel="stylesheet">

<div style="padding:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0;">State Master</h1>
            <p style="color:#718096;font-size:12px;margin:4px 0 0;">Global lookup · <?= (int) $total ?> active · feeds the City master</p>
        </div>
        <button class="btn btn-primary" id="st-add"><i class="fa fa-plus"></i> Add State</button>
    </div>
    <div class="bgc-white" style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <table id="st-grid" class="table table-striped table-hover" style="width:100%;">
            <thead><tr><th>#</th><th>State</th><th>Status</th><th>Action</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="stModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title" id="stModalTitle">Add State</h4></div>
    <div class="modal-body">
        <div id="stErr" class="alert alert-danger" style="display:none;"></div>
        <input type="hidden" id="st-id" value="0">
        <div class="form-group"><label>State Name <span style="color:#d63d5c;">*</span></label><input type="text" id="st-name" class="form-control" placeholder="e.g. Uttar Pradesh"></div>
        <div class="form-group"><label>Status</label><select id="st-status" class="form-control"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="st-save">Save</button></div>
</div></div></div>

<script src="<?= base_url('assets/global/plugins/datatables/media/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') ?>"></script>
<script>
    (function () {
        var U = { data: "<?= site_url('master/state/listing_data') ?>", save: "<?= site_url('master/state/save') ?>", del: "<?= site_url('master/state/delete') ?>", row: "<?= site_url('master/state/row') ?>/" };
        var t = jQuery('#st-grid').DataTable({ processing: true, serverSide: true, order: [], ajax: { url: U.data, type: 'POST' }, columnDefs: [{ orderable: false, targets: [0, 2, 3] }] });
        function reload() { t.ajax.reload(null, false); } function err(m) { jQuery('#stErr').text(m).show(); }
        function open(r) {
            jQuery('#stErr').hide().text('');
            if (r) { jQuery('#stModalTitle').text('Edit State'); jQuery('#st-id').val(r.state_id); jQuery('#st-name').val(r.name); jQuery('#st-status').val(r.status === 'Inactive' ? 'Inactive' : 'Active'); }
            else { jQuery('#stModalTitle').text('Add State'); jQuery('#st-id').val('0'); jQuery('#st-name').val(''); jQuery('#st-status').val('Active'); }
            jQuery('#stModal').modal('show');
        }
        jQuery('#st-add').on('click', function () { open(null); });
        jQuery('#st-save').on('click', function () {
            jQuery('#stErr').hide(); var b = jQuery(this).prop('disabled', true).text('Saving…');
            jQuery.post(U.save, { id: jQuery('#st-id').val(), name: jQuery('#st-name').val(), status: jQuery('#st-status').val() }, null, 'json')
                .done(function (res) { if (res && res.status === 'success') { jQuery('#stModal').modal('hide'); reload(); } else err((res && res.message) || 'Save failed.'); })
                .fail(function () { err('Save failed.'); }).always(function () { b.prop('disabled', false).text('Save'); });
        });
        jQuery('#st-grid tbody').on('click', '.st-edit', function () { jQuery.getJSON(U.row + jQuery(this).data('id'), function (res) { if (res && res.status === 'success') open(res.data); }); });
        jQuery('#st-grid tbody').on('click', '.st-del', function () {
            var id = jQuery(this).data('id'), name = jQuery(this).data('name');
            if (!confirm('Delete state ' + name + '?')) return;
            jQuery.post(U.del, { id: id }, null, 'json').done(function (res) { if (res && res.status === 'success') reload(); else alert((res && res.message) || 'Delete failed.'); });
        });
    })();
</script>
