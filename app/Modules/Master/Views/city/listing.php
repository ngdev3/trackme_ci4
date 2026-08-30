<link href="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') ?>" rel="stylesheet">

<div style="padding:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0;">City Master</h1>
            <p style="color:#718096;font-size:12px;margin:4px 0 0;">Global lookup · <?= (int) $total ?> active · used by accounts &amp; invoices</p>
        </div>
        <button class="btn btn-primary" id="city-add"><i class="fa fa-plus"></i> Add City</button>
    </div>

    <div class="bgc-white" style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <table id="city-grid" class="table table-striped table-hover" style="width:100%;">
            <thead><tr><th>#</th><th>City</th><th>State</th><th>Status</th><th>Action</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="cityModal" tabindex="-1" role="dialog"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title" id="cityModalTitle">Add City</h4>
    </div>
    <div class="modal-body">
        <div id="cityErr" class="alert alert-danger" style="display:none;"></div>
        <input type="hidden" id="city-id" value="0">
        <div class="form-group">
            <label>City Name <span style="color:#d63d5c;">*</span></label>
            <input type="text" id="city-name" class="form-control" placeholder="e.g. Hardoi">
        </div>
        <div class="form-group">
            <label>State <span style="color:#d63d5c;">*</span></label>
            <select id="city-state" class="form-control">
                <option value="">— select state —</option>
                <?php foreach ($states as $s): ?>
                    <option value="<?= (int) $s->state_id ?>"><?= esc($s->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select id="city-status" class="form-control"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="city-save">Save</button>
    </div>
</div></div></div>

<script src="<?= base_url('assets/global/plugins/datatables/media/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') ?>"></script>
<script>
    (function () {
        var U = {
            data: "<?= site_url('master/city/listing_data') ?>", save: "<?= site_url('master/city/save') ?>",
            del: "<?= site_url('master/city/delete') ?>", row: "<?= site_url('master/city/row') ?>/"
        };
        var table = jQuery('#city-grid').DataTable({
            processing: true, serverSide: true, order: [],
            ajax: { url: U.data, type: 'POST' },
            columnDefs: [{ orderable: false, targets: [0, 3, 4] }]
        });
        function reload() { table.ajax.reload(null, false); }
        function err(m) { jQuery('#cityErr').text(m).show(); }
        function open(r) {
            jQuery('#cityErr').hide().text('');
            if (r) {
                jQuery('#cityModalTitle').text('Edit City');
                jQuery('#city-id').val(r.city_id); jQuery('#city-name').val(r.name);
                jQuery('#city-state').val(r.state_id); jQuery('#city-status').val(r.status === 'Inactive' ? 'Inactive' : 'Active');
            } else {
                jQuery('#cityModalTitle').text('Add City');
                jQuery('#city-id').val('0'); jQuery('#city-name').val(''); jQuery('#city-state').val(''); jQuery('#city-status').val('Active');
            }
            jQuery('#cityModal').modal('show');
        }
        jQuery('#city-add').on('click', function () { open(null); });
        jQuery('#city-save').on('click', function () {
            jQuery('#cityErr').hide();
            var b = jQuery(this).prop('disabled', true).text('Saving…');
            jQuery.post(U.save, {
                id: jQuery('#city-id').val(), name: jQuery('#city-name').val(),
                state_id: jQuery('#city-state').val(), status: jQuery('#city-status').val()
            }, null, 'json').done(function (res) {
                if (res && res.status === 'success') { jQuery('#cityModal').modal('hide'); reload(); }
                else { err((res && res.message) || 'Save failed.'); }
            }).fail(function () { err('Save failed.'); }).always(function () { b.prop('disabled', false).text('Save'); });
        });
        jQuery('#city-grid tbody').on('click', '.city-edit', function () {
            jQuery.getJSON(U.row + jQuery(this).data('id'), function (res) { if (res && res.status === 'success') open(res.data); });
        });
        jQuery('#city-grid tbody').on('click', '.city-del', function () {
            var id = jQuery(this).data('id'), name = jQuery(this).data('name');
            if (!confirm('Delete city ' + name + '?')) return;
            jQuery.post(U.del, { id: id }, null, 'json').done(function (res) {
                if (res && res.status === 'success') reload(); else alert((res && res.message) || 'Delete failed.');
            });
        });
    })();
</script>
