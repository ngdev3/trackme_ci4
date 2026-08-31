<link href="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') ?>" rel="stylesheet">
<div style="padding:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div><h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0;">Attendance</h1>
        <p style="color:#718096;font-size:12px;margin:4px 0 0;">firm <?= esc(fy()->firm_name ?? '') ?> · FY <?= esc(fy()->FY ?? '') ?></p></div>
        <button class="btn btn-primary" id="at-add"><i class="fa fa-plus"></i> Mark Attendance</button>
    </div>
    <div class="table-responsive" style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <table id="at-grid" class="table table-striped table-hover" style="width:100%;">
            <thead><tr><th>#</th><th>Person</th><th>Date</th><th>Status</th><th>Check In</th><th>Check Out</th><th>Action</th></tr></thead><tbody></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="atModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title" id="atTitle">Mark Attendance</h4></div>
    <div class="modal-body">
        <div id="atErr" class="alert alert-danger" style="display:none;"></div>
        <input type="hidden" id="at-id" value="0">
        <div class="form-group"><label>Person Name <span style="color:#d63d5c;">*</span></label><input type="text" id="at-person" class="form-control"></div>
        <div class="form-group"><label>Date <span style="color:#d63d5c;">*</span></label><input type="date" id="at-date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
        <div class="form-group"><label>Status</label><select id="at-status" class="form-control"><option>Present</option><option>Absent</option><option>Half Day</option><option>Leave</option></select></div>
        <div class="row"><div class="col-sm-6"><div class="form-group"><label>Check In</label><input type="time" id="at-cin" class="form-control"></div></div>
        <div class="col-sm-6"><div class="form-group"><label>Check Out</label><input type="time" id="at-cout" class="form-control"></div></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="at-save">Save</button></div>
</div></div></div>
<script src="<?= base_url('assets/global/plugins/datatables/media/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') ?>"></script>
<script>
(function(){
    var U={data:"<?= site_url('admin/attendance/view_all') ?>",save:"<?= site_url('admin/attendance/save') ?>",del:"<?= site_url('admin/attendance/delete') ?>",row:"<?= site_url('admin/attendance/row') ?>/"};
    var t=jQuery('#at-grid').DataTable({processing:true,serverSide:true,order:[],ajax:{url:U.data,type:'POST'},columnDefs:[{orderable:false,targets:'_all'}]});
    function rl(){t.ajax.reload(null,false);} function er(m){jQuery('#atErr').text(m).show();}
    function op(r){jQuery('#atErr').hide();if(r){jQuery('#atTitle').text('Edit Attendance');jQuery('#at-id').val(r.attendance_id);jQuery('#at-person').val(r.person_name);jQuery('#at-date').val(r.attendance_date);jQuery('#at-status').val(r.attendance_status);jQuery('#at-cin').val(r.check_in);jQuery('#at-cout').val(r.check_out);}else{jQuery('#atTitle').text('Mark Attendance');jQuery('#at-id').val('0');jQuery('#at-person').val('');jQuery('#at-status').val('Present');jQuery('#at-cin,#at-cout').val('');}jQuery('#atModal').modal('show');}
    jQuery('#at-add').on('click',function(){op(null);});
    jQuery('#at-save').on('click',function(){jQuery('#atErr').hide();var b=jQuery(this).prop('disabled',true).text('Saving…');jQuery.post(U.save,{id:jQuery('#at-id').val(),person_name:jQuery('#at-person').val(),attendance_date:jQuery('#at-date').val(),attendance_status:jQuery('#at-status').val(),check_in:jQuery('#at-cin').val(),check_out:jQuery('#at-cout').val()},null,'json').done(function(res){if(res&&res.status==='success'){jQuery('#atModal').modal('hide');rl();}else er((res&&res.message)||'Save failed.');}).fail(function(){er('Save failed.');}).always(function(){b.prop('disabled',false).text('Save');});});
    jQuery('#at-grid tbody').on('click','.at-edit',function(){jQuery.getJSON(U.row+jQuery(this).data('id'),function(res){if(res&&res.status==='success')op(res.data);});});
    jQuery('#at-grid tbody').on('click','.at-del',function(){if(!confirm('Delete this attendance record?'))return;jQuery.post(U.del,{id:jQuery(this).data('id')},null,'json').done(function(res){if(res&&res.status==='success')rl();else alert((res&&res.message)||'Delete failed.');});});
})();
</script>
