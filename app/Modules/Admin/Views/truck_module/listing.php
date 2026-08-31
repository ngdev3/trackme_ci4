<link href="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') ?>" rel="stylesheet">
<div style="padding:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div><h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0;">FCI Truck</h1>
        <p style="color:#718096;font-size:12px;margin:4px 0 0;">Trucks (global)</p></div>
        <button class="btn btn-primary" id="tk-add"><i class="fa fa-plus"></i> Add Truck</button>
    </div>
    <div class="table-responsive" style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <table id="tk-grid" class="table table-striped table-hover" style="width:100%;">
            <thead><tr><th>#</th><th>Truck No</th><th>Chassis</th><th>Transport</th><th>Status</th><th>Action</th></tr></thead><tbody></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="tkModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title" id="tkTitle">Add Truck</h4></div>
    <div class="modal-body">
        <div id="tkErr" class="alert alert-danger" style="display:none;"></div>
        <input type="hidden" id="tk-id" value="0">
        <div class="form-group"><label>Truck Number <span style="color:#d63d5c;">*</span></label><input type="text" id="tk-tno" class="form-control"></div>
        <div class="form-group"><label>Chassis Number</label><input type="text" id="tk-chas" class="form-control"></div>
        <div class="form-group"><label>Transport Name</label><input type="text" id="tk-trans" class="form-control"></div>
        <div class="form-group"><label>Status</label><select id="tk-status" class="form-control"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="tk-save">Save</button></div>
</div></div></div>
<script src="<?= base_url('assets/global/plugins/datatables/media/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') ?>"></script>
<script>
(function(){
    var U={data:"<?= site_url('admin/truck_module/view_all') ?>",save:"<?= site_url('admin/truck_module/save') ?>",del:"<?= site_url('admin/truck_module/delete') ?>",row:"<?= site_url('admin/truck_module/row') ?>/"};
    var t=jQuery('#tk-grid').DataTable({processing:true,serverSide:true,order:[],ajax:{url:U.data,type:'POST'},columnDefs:[{orderable:false,targets:'_all'}]});
    function rl(){t.ajax.reload(null,false);} function er(m){jQuery('#tkErr').text(m).show();}
    function op(r){jQuery('#tkErr').hide();if(r){jQuery('#tkTitle').text('Edit Truck');jQuery('#tk-id').val(r.truck_id);jQuery('#tk-tno').val(r.truck_number);jQuery('#tk-chas').val(r.chassis_number);jQuery('#tk-trans').val(r.transport_name);jQuery('#tk-status').val(r.status==='Inactive'?'Inactive':'Active');}else{jQuery('#tkTitle').text('Add Truck');jQuery('#tk-id').val('0');jQuery('#tk-tno,#tk-chas,#tk-trans').val('');jQuery('#tk-status').val('Active');}jQuery('#tkModal').modal('show');}
    jQuery('#tk-add').on('click',function(){op(null);});
    jQuery('#tk-save').on('click',function(){jQuery('#tkErr').hide();var b=jQuery(this).prop('disabled',true).text('Saving…');jQuery.post(U.save,{id:jQuery('#tk-id').val(),truck_number:jQuery('#tk-tno').val(),chassis_number:jQuery('#tk-chas').val(),transport_name:jQuery('#tk-trans').val(),status:jQuery('#tk-status').val()},null,'json').done(function(res){if(res&&res.status==='success'){jQuery('#tkModal').modal('hide');rl();}else er((res&&res.message)||'Save failed.');}).fail(function(){er('Save failed.');}).always(function(){b.prop('disabled',false).text('Save');});});
    jQuery('#tk-grid tbody').on('click','.tk-edit',function(){jQuery.getJSON(U.row+jQuery(this).data('id'),function(res){if(res&&res.status==='success')op(res.data);});});
    jQuery('#tk-grid tbody').on('click','.tk-del',function(){if(!confirm('Delete this truck?'))return;jQuery.post(U.del,{id:jQuery(this).data('id')},null,'json').done(function(res){if(res&&res.status==='success')rl();else alert((res&&res.message)||'Delete failed.');});});
})();
</script>
