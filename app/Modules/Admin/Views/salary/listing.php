<link href="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') ?>" rel="stylesheet">
<div style="padding:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div><h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0;">Salary Module</h1>
        <p style="color:#718096;font-size:12px;margin:4px 0 0;">Employee salaries · firm <?= esc(fy()->firm_name ?? '') ?></p></div>
        <button class="btn btn-primary" id="sl-add"><i class="fa fa-plus"></i> Add Salary</button>
    </div>
    <div class="table-responsive" style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <table id="sl-grid" class="table table-striped table-hover" style="width:100%;">
            <thead><tr><th>#</th><th>Employee</th><th>Amount</th><th>From</th><th>To</th><th>Status</th><th>Action</th></tr></thead><tbody></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="slModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title" id="slTitle">Add Salary</h4></div>
    <div class="modal-body">
        <div id="slErr" class="alert alert-danger" style="display:none;"></div>
        <input type="hidden" id="sl-id" value="0">
        <div class="form-group"><label>Employee <span style="color:#d63d5c;">*</span></label>
            <select id="sl-user" class="form-control"><option value="">— select employee —</option>
                <?php foreach ($users as $u): ?><option value="<?= (int) $u->id ?>"><?= esc(trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))) ?: ('User #' . $u->id) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Salary Amount <span style="color:#d63d5c;">*</span></label><input type="number" step="0.01" id="sl-amount" class="form-control"></div>
        <div class="row"><div class="col-sm-6"><div class="form-group"><label>From Date</label><input type="date" id="sl-start" class="form-control"></div></div>
        <div class="col-sm-6"><div class="form-group"><label>To Date</label><input type="date" id="sl-end" class="form-control"></div></div></div>
        <div class="form-group"><label>Status</label><select id="sl-status" class="form-control"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="sl-save">Save</button></div>
</div></div></div>
<script src="<?= base_url('assets/global/plugins/datatables/media/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') ?>"></script>
<script>
(function(){
    var U={data:"<?= site_url('admin/salary_module/view_all') ?>",save:"<?= site_url('admin/salary_module/save') ?>",del:"<?= site_url('admin/salary_module/delete') ?>",row:"<?= site_url('admin/salary_module/row') ?>/"};
    var t=jQuery('#sl-grid').DataTable({processing:true,serverSide:true,order:[],ajax:{url:U.data,type:'POST'},columnDefs:[{orderable:false,targets:'_all'}]});
    function rl(){t.ajax.reload(null,false);} function er(m){jQuery('#slErr').text(m).show();}
    function op(r){jQuery('#slErr').hide();if(r){jQuery('#slTitle').text('Edit Salary');jQuery('#sl-id').val(r.id);jQuery('#sl-user').val(r.user_id);jQuery('#sl-amount').val(r.salary_amount);jQuery('#sl-start').val(r.start_date);jQuery('#sl-end').val(r.end_date);jQuery('#sl-status').val(r.status==='Inactive'?'Inactive':'Active');}else{jQuery('#slTitle').text('Add Salary');jQuery('#sl-id').val('0');jQuery('#sl-user').val('');jQuery('#sl-amount,#sl-start,#sl-end').val('');jQuery('#sl-status').val('Active');}jQuery('#slModal').modal('show');}
    jQuery('#sl-add').on('click',function(){op(null);});
    jQuery('#sl-save').on('click',function(){jQuery('#slErr').hide();var b=jQuery(this).prop('disabled',true).text('Saving…');jQuery.post(U.save,{id:jQuery('#sl-id').val(),user_id:jQuery('#sl-user').val(),salary_amount:jQuery('#sl-amount').val(),start_date:jQuery('#sl-start').val(),end_date:jQuery('#sl-end').val(),status:jQuery('#sl-status').val()},null,'json').done(function(res){if(res&&res.status==='success'){jQuery('#slModal').modal('hide');rl();}else er((res&&res.message)||'Save failed.');}).fail(function(){er('Save failed.');}).always(function(){b.prop('disabled',false).text('Save');});});
    jQuery('#sl-grid tbody').on('click','.sl-edit',function(){jQuery.getJSON(U.row+jQuery(this).data('id'),function(res){if(res&&res.status==='success')op(res.data);});});
    jQuery('#sl-grid tbody').on('click','.sl-del',function(){if(!confirm('Delete this salary record?'))return;jQuery.post(U.del,{id:jQuery(this).data('id')},null,'json').done(function(res){if(res&&res.status==='success')rl();else alert((res&&res.message)||'Delete failed.');});});
})();
</script>
