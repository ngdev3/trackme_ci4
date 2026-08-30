<link href="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') ?>" rel="stylesheet">

<div style="padding:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0;">HSN Code Master</h1>
            <p style="color:#718096;font-size:12px;margin:4px 0 0;">Global commodity/HSN master · feeds invoice &amp; stock pickers · <?= (int) $total ?> active</p>
        </div>
        <button class="btn btn-primary" id="hsn-add"><i class="fa fa-plus"></i> Add HSN</button>
    </div>

    <div class="bgc-white" style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <table id="hsn-grid" class="table table-striped table-hover" style="width:100%;">
            <thead>
                <tr><th>#</th><th>HSN Code</th><th>Commodity</th><th>Mapped Account</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Add / Edit modal -->
<div class="modal fade" id="hsnModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="hsnModalTitle">Add HSN Code</h4>
            </div>
            <div class="modal-body">
                <div id="hsnErr" class="alert alert-danger" style="display:none;"></div>
                <input type="hidden" id="hsn-id" value="0">
                <div class="form-group">
                    <label>HSN Code <span style="color:#d63d5c;">*</span></label>
                    <input type="text" id="hsn-code" class="form-control" placeholder="e.g. 1006" maxlength="20">
                </div>
                <div class="form-group">
                    <label>Commodity / Product Name <span style="color:#d63d5c;">*</span></label>
                    <input type="text" id="hsn-name" class="form-control" placeholder="e.g. Basmati Rice">
                </div>
                <div class="form-group">
                    <label>Mapped Account</label>
                    <input type="text" id="hsn-account" class="form-control" placeholder="Account name / id (optional)">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="hsn-status" class="form-control">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="hsn-save">Save</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/global/plugins/datatables/media/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') ?>"></script>
<script>
    (function () {
        var URLS = {
            data:  "<?= site_url('admin/hsn/listing_data') ?>",
            save:  "<?= site_url('admin/hsn/save') ?>",
            del:   "<?= site_url('admin/hsn/delete') ?>",
            row:   "<?= site_url('admin/hsn/row') ?>/"
        };
        var table = jQuery('#hsn-grid').DataTable({
            processing: true, serverSide: true, searching: true, order: [],
            ajax: { url: URLS.data, type: 'POST' },
            columnDefs: [{ orderable: false, targets: [0, 3, 4, 5] }]
        });
        function reload() { table.ajax.reload(null, false); }
        function showErr(m) { jQuery('#hsnErr').text(m).show(); }

        function openModal(row) {
            jQuery('#hsnErr').hide().text('');
            if (row) {
                jQuery('#hsnModalTitle').text('Edit HSN Code');
                jQuery('#hsn-id').val(row.id);
                jQuery('#hsn-code').val(row.hsn_code);
                jQuery('#hsn-name').val(row.product_name);
                jQuery('#hsn-account').val(row.map_account || '');
                jQuery('#hsn-status').val(row.status === 'Inactive' ? 'Inactive' : 'Active');
            } else {
                jQuery('#hsnModalTitle').text('Add HSN Code');
                jQuery('#hsn-id').val('0');
                jQuery('#hsn-code, #hsn-name, #hsn-account').val('');
                jQuery('#hsn-status').val('Active');
            }
            jQuery('#hsnModal').modal('show');
        }

        jQuery('#hsn-add').on('click', function () { openModal(null); });

        jQuery('#hsn-save').on('click', function () {
            jQuery('#hsnErr').hide();
            var btn = jQuery(this).prop('disabled', true).text('Saving…');
            jQuery.post(URLS.save, {
                id: jQuery('#hsn-id').val(),
                hsn_code: jQuery('#hsn-code').val(),
                product_name: jQuery('#hsn-name').val(),
                map_account: jQuery('#hsn-account').val(),
                status: jQuery('#hsn-status').val()
            }, null, 'json').done(function (res) {
                if (res && res.status === 'success') {
                    jQuery('#hsnModal').modal('hide'); reload();
                } else { showErr((res && res.message) || 'Save failed.'); }
            }).fail(function () { showErr('Save failed (server error).'); })
              .always(function () { btn.prop('disabled', false).text('Save'); });
        });

        jQuery('#hsn-grid tbody').on('click', '.hsn-edit', function () {
            jQuery.getJSON(URLS.row + jQuery(this).data('id'), function (res) {
                if (res && res.status === 'success') { openModal(res.data); }
            });
        });

        jQuery('#hsn-grid tbody').on('click', '.hsn-del', function () {
            var id = jQuery(this).data('id'), name = jQuery(this).data('name');
            if (!confirm('Delete HSN ' + name + '? It will be removed from the pickers.')) return;
            jQuery.post(URLS.del, { id: id }, null, 'json').done(function (res) {
                if (res && res.status === 'success') { reload(); }
                else { alert((res && res.message) || 'Delete failed.'); }
            }).fail(function () { alert('Delete failed.'); });
        });
    })();
</script>
