<link href="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') ?>" rel="stylesheet">
<div style="padding:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div><h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0;">FCI Driver</h1>
        <p style="color:#718096;font-size:12px;margin:4px 0 0;">Drivers (global)</p></div>
        <button class="btn btn-primary" id="dr-add"><i class="fa fa-plus"></i> Add Driver</button>
    </div>
    <div class="table-responsive" style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <table id="dr-grid" class="table table-striped table-hover" style="width:100%;">
            <thead><tr><th>#</th><th>Name</th><th>Mobile</th><th>Status</th><th>Action</th></tr></thead><tbody></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="drModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title" id="drTitle">Add Driver</h4></div>
    <div class="modal-body">
        <div id="drErr" class="alert alert-danger" style="display:none;"></div>
        <input type="hidden" id="dr-id" value="0">
        <div class="form-group"><label>Name <span style="color:#d63d5c;">*</span></label><input type="text" id="dr-name" class="form-control"></div>
        <div class="form-group"><label>Mobile</label><input type="text" id="dr-mobile" class="form-control"></div>
        <div class="form-group"><label>Status</label><select id="dr-status" class="form-control"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="dr-save">Save</button></div>
</div></div></div>
<script src="<?= base_url('assets/global/plugins/datatables/media/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') ?>"></script>
<script>
(function(){
    var U={data:"<?= site_url('admin/driver_module/view_all') ?>",save:"<?= site_url('admin/driver_module/save') ?>",del:"<?= site_url('admin/driver_module/delete') ?>",row:"<?= site_url('admin/driver_module/row') ?>/"};
    var t=jQuery('#dr-grid').DataTable({processing:true,serverSide:true,order:[],ajax:{url:U.data,type:'POST'},columnDefs:[{orderable:false,targets:'_all'}]});
    function rl(){t.ajax.reload(null,false);} function er(m){jQuery('#drErr').text(m).show();}
    function op(r){jQuery('#drErr').hide();if(r){jQuery('#drTitle').text('Edit Driver');jQuery('#dr-id').val(r.driver_id);jQuery('#dr-name').val(r.name);jQuery('#dr-mobile').val(r.mobile_number);jQuery('#dr-status').val(r.status==='Inactive'?'Inactive':'Active');}else{jQuery('#drTitle').text('Add Driver');jQuery('#dr-id').val('0');jQuery('#dr-name,#dr-mobile').val('');jQuery('#dr-status').val('Active');}jQuery('#drModal').modal('show');}
    jQuery('#dr-add').on('click',function(){op(null);});
    jQuery('#dr-save').on('click',function(){jQuery('#drErr').hide();var b=jQuery(this).prop('disabled',true).text('Saving…');jQuery.post(U.save,{id:jQuery('#dr-id').val(),name:jQuery('#dr-name').val(),mobile_number:jQuery('#dr-mobile').val(),status:jQuery('#dr-status').val()},null,'json').done(function(res){if(res&&res.status==='success'){jQuery('#drModal').modal('hide');rl();}else er((res&&res.message)||'Save failed.');}).fail(function(){er('Save failed.');}).always(function(){b.prop('disabled',false).text('Save');});});
    jQuery('#dr-grid tbody').on('click','.dr-edit',function(){jQuery.getJSON(U.row+jQuery(this).data('id'),function(res){if(res&&res.status==='success')op(res.data);});});
    jQuery('#dr-grid tbody').on('click','.dr-del',function(){if(!confirm('Delete this driver?'))return;jQuery.post(U.del,{id:jQuery(this).data('id')},null,'json').done(function(res){if(res&&res.status==='success')rl();else alert((res&&res.message)||'Delete failed.');});});
})();
</script>
