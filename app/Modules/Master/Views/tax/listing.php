<link href="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') ?>" rel="stylesheet">

<div style="padding:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0;">Tax Master</h1>
            <p style="color:#718096;font-size:12px;margin:4px 0 0;">GST rates (CGST / SGST / GST %) · <?= (int) $total ?> active</p>
        </div>
        <button class="btn btn-primary" id="tx-add"><i class="fa fa-plus"></i> Add Tax Rate</button>
    </div>
    <div class="bgc-white" style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <table id="tx-grid" class="table table-striped table-hover" style="width:100%;">
            <thead><tr><th>#</th><th>CGST</th><th>SGST</th><th>GST</th><th>Status</th><th>Action</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="txModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title" id="txModalTitle">Add Tax Rate</h4></div>
    <div class="modal-body">
        <div id="txErr" class="alert alert-danger" style="display:none;"></div>
        <input type="hidden" id="tx-id" value="0">
        <div class="row">
            <div class="col-sm-6"><div class="form-group"><label>CGST % <span style="color:#d63d5c;">*</span></label><input type="number" step="0.01" id="tx-cgst" class="form-control" placeholder="2.5"></div></div>
            <div class="col-sm-6"><div class="form-group"><label>SGST % <span style="color:#d63d5c;">*</span></label><input type="number" step="0.01" id="tx-sgst" class="form-control" placeholder="2.5"></div></div>
        </div>
        <div class="form-group"><label>GST % <small style="color:#94a3b8;">(blank = CGST + SGST)</small></label><input type="number" step="0.01" id="tx-gst" class="form-control" placeholder="5"></div>
        <div class="form-group"><label>Status</label><select id="tx-status" class="form-control"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="tx-save">Save</button></div>
</div></div></div>

<script src="<?= base_url('assets/global/plugins/datatables/media/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') ?>"></script>
<script>
    (function () {
        var U = { data: "<?= site_url('master/tax/listing_data') ?>", save: "<?= site_url('master/tax/save') ?>", del: "<?= site_url('master/tax/delete') ?>", row: "<?= site_url('master/tax/row') ?>/" };
        var t = jQuery('#tx-grid').DataTable({ processing: true, serverSide: true, order: [], ajax: { url: U.data, type: 'POST' }, columnDefs: [{ orderable: false, targets: [0, 1, 2, 3, 4, 5] }] });
        function reload() { t.ajax.reload(null, false); } function err(m) { jQuery('#txErr').text(m).show(); }
        function open(r) {
            jQuery('#txErr').hide().text('');
            if (r) { jQuery('#txModalTitle').text('Edit Tax Rate'); jQuery('#tx-id').val(r.tax_id); jQuery('#tx-cgst').val(r.cgst); jQuery('#tx-sgst').val(r.sgst); jQuery('#tx-gst').val(r.gst); jQuery('#tx-status').val(r.status === 'Inactive' ? 'Inactive' : 'Active'); }
            else { jQuery('#txModalTitle').text('Add Tax Rate'); jQuery('#tx-id').val('0'); jQuery('#tx-cgst,#tx-sgst,#tx-gst').val(''); jQuery('#tx-status').val('Active'); }
            jQuery('#txModal').modal('show');
        }
        jQuery('#tx-add').on('click', function () { open(null); });
        jQuery('#tx-save').on('click', function () {
            jQuery('#txErr').hide(); var b = jQuery(this).prop('disabled', true).text('Saving…');
            jQuery.post(U.save, { id: jQuery('#tx-id').val(), cgst: jQuery('#tx-cgst').val(), sgst: jQuery('#tx-sgst').val(), gst: jQuery('#tx-gst').val(), status: jQuery('#tx-status').val() }, null, 'json')
                .done(function (res) { if (res && res.status === 'success') { jQuery('#txModal').modal('hide'); reload(); } else err((res && res.message) || 'Save failed.'); })
                .fail(function () { err('Save failed.'); }).always(function () { b.prop('disabled', false).text('Save'); });
        });
        jQuery('#tx-grid tbody').on('click', '.tx-edit', function () { jQuery.getJSON(U.row + jQuery(this).data('id'), function (res) { if (res && res.status === 'success') open(res.data); }); });
        jQuery('#tx-grid tbody').on('click', '.tx-del', function () {
            if (!confirm('Delete this tax rate?')) return;
            jQuery.post(U.del, { id: jQuery(this).data('id') }, null, 'json').done(function (res) { if (res && res.status === 'success') reload(); else alert((res && res.message) || 'Delete failed.'); });
        });
    })();
</script>
