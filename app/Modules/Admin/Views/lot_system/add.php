<?php
$result = isset($result) ? $result : null;
$is_edit = !empty($result);
$lot_result = $is_edit ? $result : null;
$page_title = $is_edit ? 'Edit Lot' : 'Add Lot';
$is_divided = !empty($_POST['lot_divided']) || !empty($result->lot_divided);

if (!function_exists('lot_form_value')) {
    function lot_form_value($field, $result = null, $default = '')
    {
        if (isset($_POST[$field])) {
            return $_POST[$field];
        }
        return isset($result->$field) ? $result->$field : $default;
    }

    function lot_form_date($field, $result = null)
    {
        $value = lot_form_value($field, $result);
        if (empty($value) || $value == '0000-00-00' || $value == '0000-00-00 00:00:00') {
            return '';
        }
        return date('d-m-Y', strtotime($value));
    }

    function lot_form_selected($field, $value, $result = null)
    {
        return (string) lot_form_value($field, $result) === (string) $value ? 'selected' : '';
    }
}
?>

<style>
    .lot-form-page{color:#18243c;padding:24px}.lot-form-shell{margin:0 auto;max-width:1380px}.lot-form-hero{align-items:center;background:#fff;border:1px solid #dce6f2;border-radius:8px;box-shadow:0 16px 38px rgba(24,36,60,.08);display:flex;gap:18px;justify-content:space-between;margin-bottom:18px;padding:22px 24px}.lot-form-hero-copy{align-items:center;display:flex;gap:14px}.lot-form-hero-icon{align-items:center;background:#e8f2ff;border-radius:8px;color:#1769c2;display:flex;font-size:22px;height:52px;justify-content:center;width:52px}.lot-form-title{font-size:25px;font-weight:900;margin:0}.lot-form-subtitle{color:#718096;font-size:13px;font-weight:700;line-height:1.55;margin:6px 0 0}.lot-form-back{background:#edf3fa;border:1px solid #dce6f2;border-radius:8px;color:#516174;font-weight:900;padding:10px 14px}.lot-form-back:hover{background:#e8f2ff;color:#1769c2;text-decoration:none}
    .lot-form-grid{display:grid;gap:18px;grid-template-columns:minmax(0,1fr) 330px}.lot-form-card{background:#fff;border:1px solid #dce6f2;border-radius:8px;box-shadow:0 16px 38px rgba(24,36,60,.08);margin-bottom:18px;overflow:hidden}.lot-form-card-head{align-items:center;border-bottom:1px solid #edf2f7;display:flex;gap:12px;justify-content:space-between;padding:16px 18px}.lot-form-card-head h5{font-size:16px;font-weight:900;margin:0}.lot-form-card-head p{color:#718096;font-size:12px;font-weight:700;margin:5px 0 0}.lot-form-chip{background:#f1f6fc;border-radius:999px;color:#1769c2;font-size:11px;font-weight:900;padding:7px 10px;white-space:nowrap}.lot-form-card-body{padding:18px}.lot-fields{display:grid;gap:14px;grid-template-columns:repeat(3,minmax(0,1fr))}.lot-fields.two{grid-template-columns:repeat(2,minmax(0,1fr))}.lot-field label{color:#516174;display:block;font-size:11px;font-weight:900;margin-bottom:7px;text-transform:uppercase}.lot-field input,.lot-field select,.lot-field textarea{background:#fbfdff;border:1px solid #dce6f2;border-radius:8px;box-shadow:none;color:#18243c;font-weight:800;min-height:40px;width:100%}.lot-field textarea{min-height:96px;resize:vertical}.lot-field .help-block{font-size:11px;font-weight:800;margin:6px 0 0}.lot-required{color:#d64545}.lot-toggle{align-items:center;background:#f8fbff;border:1px solid #e2e9f2;border-radius:8px;display:flex;gap:12px;padding:12px}.lot-toggle input{height:18px;width:18px}.lot-toggle strong{display:block;font-size:13px;font-weight:900}.lot-toggle span{color:#718096;display:block;font-size:12px;font-weight:700;margin-top:3px}
    .lot-summary-card{position:sticky;top:82px}.lot-summary-list{display:grid;gap:10px}.lot-summary-row{background:#f8fbff;border:1px solid #e6edf5;border-radius:8px;padding:12px}.lot-summary-row span{color:#718096;display:block;font-size:11px;font-weight:900;text-transform:uppercase}.lot-summary-row strong{color:#18243c;display:block;font-size:20px;font-weight:900;margin-top:5px}.lot-status-help{background:#fff8e8;border:1px solid #f3d18a;border-radius:8px;color:#725017;font-size:12px;font-weight:800;line-height:1.5;margin-top:12px;padding:12px}.lot-form-actions{align-items:center;display:flex;gap:10px;justify-content:flex-end;margin-top:18px}.lot-submit{background:#1769c2;border:0;border-radius:8px!important;box-shadow:0 10px 22px rgba(23,105,194,.2);color:#fff;font-weight:900;padding:11px 18px}.lot-submit:hover,.lot-submit:focus{background:#0c5aaa;color:#fff}.lot-cancel{background:#edf3fa;border:1px solid #dce6f2;border-radius:8px!important;color:#516174;font-weight:900;padding:10px 16px}.lot-cancel:hover{background:#e8f2ff;color:#1769c2}
    .lot-second-section.is-disabled{opacity:.58}.lot-second-section.is-disabled input,.lot-second-section.is-disabled select{pointer-events:none}.lot-accept-date-wrap.is-hidden{display:none}
    @media(max-width:991px){.lot-form-grid{grid-template-columns:1fr}.lot-summary-card{position:static}.lot-fields,.lot-fields.two{grid-template-columns:1fr 1fr}}@media(max-width:767px){.lot-form-page{padding:14px}.lot-form-hero{align-items:stretch;flex-direction:column}.lot-fields,.lot-fields.two{grid-template-columns:1fr}.lot-form-actions{align-items:stretch;flex-direction:column-reverse}.lot-submit,.lot-cancel{width:100%}}
</style>

<main class="main-content bgc-grey-100 lot-form-page">
    <div id="mainContent">
        <div class="container-fluid lot-form-shell">
            <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>

            <section class="lot-form-hero">
                <div class="lot-form-hero-copy">
                    <span class="lot-form-hero-icon"><i class="fa fa-cubes"></i></span>
                    <div>
                        <h4 class="lot-form-title"><?php echo $page_title; ?></h4>
                        <p class="lot-form-subtitle">Capture lot movement, challan details, truck weights, quality readings and delivery status in one guided form.</p>
                    </div>
                </div>
                <a href="<?php echo base_url('admin/lot_system/listing'); ?>" class="lot-form-back"><i class="fa fa-arrow-left"></i> Back to Lots</a>
            </section>

            <?php echo form_open_multipart(site_url('admin/lot_system/add'), array('id' => 'lotForm', 'autocomplete' => 'off')); ?>
            <div class="lot-form-grid">
                <div>
                    <section class="lot-form-card">
                        <div class="lot-form-card-head">
                            <div><h5>Lot Basics</h5><p>Start with the center, lot number, dispatch date and movement type.</p></div>
                            <span class="lot-form-chip">Step 1</span>
                        </div>
                        <div class="lot-form-card-body">
                            <div class="lot-fields">
                                <div class="lot-field">
                                    <label>Center Name <span class="lot-required">*</span></label>
                                    <select class="form-control" name="center_id" required>
                                        <option value="">Select Center</option>
                                        <?php if (!empty($center_list)) { foreach ($center_list as $center) { ?>
                                            <option value="<?php echo $center->center_id; ?>" <?php echo lot_form_selected('center_id', $center->center_id, $lot_result); ?>><?php echo htmlspecialchars($center->name, ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php }} ?>
                                    </select>
                                    <div class="help-block" style="color:red"><?php echo form_error('center_id'); ?></div>
                                </div>
                                <div class="lot-field">
                                    <label>Lot Number <span class="lot-required">*</span></label>
                                    <input type="number" step="0.01" name="lot_number" value="<?php echo htmlspecialchars(lot_form_value('lot_number', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Enter lot number" required>
                                    <div class="help-block" style="color:red"><?php echo form_error('lot_number'); ?></div>
                                </div>
                                <div class="lot-field">
                                    <label>Dispatch Date <span class="lot-required">*</span></label>
                                    <input type="text" name="dispatch_date" value="<?php echo htmlspecialchars(lot_form_date('dispatch_date', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control dob" placeholder="dd-mm-yyyy" required>
                                    <div class="help-block" style="color:red"><?php echo form_error('dispatch_date'); ?></div>
                                </div>
                            </div>
                            <div style="margin-top:14px">
                                <label class="lot-toggle">
                                    <input type="checkbox" name="lot_divided" value="1" id="lot_divided_status" <?php echo $is_divided ? 'checked' : ''; ?>>
                                    <span><strong>Lot is divided into two truck/challan entries</strong><span>Turn this on only when second movement details are required.</span></span>
                                </label>
                            </div>
                        </div>
                    </section>

                    <section class="lot-form-card">
                        <div class="lot-form-card-head">
                            <div><h5>Truck &amp; Challan 01</h5><p>Primary dispatch details and quality readings.</p></div>
                            <span class="lot-form-chip">Required</span>
                        </div>
                        <div class="lot-form-card-body">
                            <div class="lot-fields">
                                <div class="lot-field"><label>Challan No. 01</label><input type="number" step="0.01" name="movement_challan_one" value="<?php echo htmlspecialchars(lot_form_value('movement_challan_one', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control"></div>
                                <div class="lot-field"><label>Truck 01</label><select class="form-control" name="truck_id_one"><option value="">Select Truck</option><?php if (!empty($get_truck_list)) { foreach ($get_truck_list as $truck) { ?><option value="<?php echo $truck->truck_id; ?>" <?php echo lot_form_selected('truck_id_one', $truck->truck_id, $lot_result); ?>><?php echo htmlspecialchars($truck->truck_number, ENT_QUOTES, 'UTF-8'); ?></option><?php }} ?></select></div>
                                <div class="lot-field"><label>Driver 01</label><select class="form-control" name="driver_id_one"><option value="">Select Driver</option><?php if (!empty($get_driver_list)) { foreach ($get_driver_list as $driver) { ?><option value="<?php echo $driver->driver_id; ?>" <?php echo lot_form_selected('driver_id_one', $driver->driver_id, $lot_result); ?>><?php echo htmlspecialchars($driver->name, ENT_QUOTES, 'UTF-8'); ?></option><?php }} ?></select></div>
                                <div class="lot-field"><label>Challan Bags 01</label><input type="number" step="0.01" name="challan_bags_one" value="<?php echo htmlspecialchars(lot_form_value('challan_bags_one', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control"></div>
                                <div class="lot-field"><label>Challan Weight 01</label><input type="number" step="0.01" name="challan_weight_one" value="<?php echo htmlspecialchars(lot_form_value('challan_weight_one', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control lot-weight"></div>
                                <div class="lot-field"><label>FCI Loaded Weight 01</label><input type="number" step="0.01" name="fci_loaded_weight_one" value="<?php echo htmlspecialchars(lot_form_value('fci_loaded_weight_one', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control lot-loaded"></div>
                                <div class="lot-field"><label>FCI Blank Weight 01</label><input type="number" step="0.01" name="fci_blank_weight_one" value="<?php echo htmlspecialchars(lot_form_value('fci_blank_weight_one', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control lot-blank"></div>
                                <div class="lot-field"><label>Moisture 01</label><input type="number" step="0.01" name="moisture_one" value="<?php echo htmlspecialchars(lot_form_value('moisture_one', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control"></div>
                                <div class="lot-field"><label>Broken 01</label><input type="number" step="0.01" name="broken_one" value="<?php echo htmlspecialchars(lot_form_value('broken_one', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control"></div>
                                <div class="lot-field"><label>Pallerdaar Name 01</label><input type="text" name="pallerdaar_one" value="<?php echo htmlspecialchars(lot_form_value('pallerdaar_one', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control"></div>
                            </div>
                        </div>
                    </section>

                    <section class="lot-form-card lot-second-section" id="lotSecondSection">
                        <div class="lot-form-card-head">
                            <div><h5>Truck &amp; Challan 02</h5><p>Shown only when the lot is divided.</p></div>
                            <span class="lot-form-chip">Optional</span>
                        </div>
                        <div class="lot-form-card-body">
                            <div class="lot-fields">
                                <div class="lot-field"><label>Challan No. 02</label><input type="number" step="0.01" name="movement_challan_two" value="<?php echo htmlspecialchars(lot_form_value('movement_challan_two', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control"></div>
                                <div class="lot-field"><label>Truck 02</label><select class="form-control" name="truck_id_two"><option value="">Select Truck</option><?php if (!empty($get_truck_list)) { foreach ($get_truck_list as $truck) { ?><option value="<?php echo $truck->truck_id; ?>" <?php echo lot_form_selected('truck_id_two', $truck->truck_id, $lot_result); ?>><?php echo htmlspecialchars($truck->truck_number, ENT_QUOTES, 'UTF-8'); ?></option><?php }} ?></select></div>
                                <div class="lot-field"><label>Driver 02</label><select class="form-control" name="driver_id_two"><option value="">Select Driver</option><?php if (!empty($get_driver_list)) { foreach ($get_driver_list as $driver) { ?><option value="<?php echo $driver->driver_id; ?>" <?php echo lot_form_selected('driver_id_two', $driver->driver_id, $lot_result); ?>><?php echo htmlspecialchars($driver->name, ENT_QUOTES, 'UTF-8'); ?></option><?php }} ?></select></div>
                                <div class="lot-field"><label>Challan Bags 02</label><input type="number" step="0.01" name="challan_bags_two" value="<?php echo htmlspecialchars(lot_form_value('challan_bags_two', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control"></div>
                                <div class="lot-field"><label>Challan Weight 02</label><input type="number" step="0.01" name="challan_weight_two" value="<?php echo htmlspecialchars(lot_form_value('challan_weight_two', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control lot-weight"></div>
                                <div class="lot-field"><label>FCI Loaded Weight 02</label><input type="number" step="0.01" name="fci_loaded_weight_two" value="<?php echo htmlspecialchars(lot_form_value('fci_loaded_weight_two', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control lot-loaded"></div>
                                <div class="lot-field"><label>FCI Blank Weight 02</label><input type="number" step="0.01" name="fci_blank_weight_two" value="<?php echo htmlspecialchars(lot_form_value('fci_blank_weight_two', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control lot-blank"></div>
                                <div class="lot-field"><label>Moisture 02</label><input type="number" step="0.01" name="moisture_two" value="<?php echo htmlspecialchars(lot_form_value('moisture_two', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control"></div>
                                <div class="lot-field"><label>Broken 02</label><input type="number" step="0.01" name="broken_two" value="<?php echo htmlspecialchars(lot_form_value('broken_two', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control"></div>
                                <div class="lot-field"><label>Pallerdaar Name 02</label><input type="text" name="pallerdaar_two" value="<?php echo htmlspecialchars(lot_form_value('pallerdaar_two', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control"></div>
                            </div>
                        </div>
                    </section>

                    <section class="lot-form-card">
                        <div class="lot-form-card-head">
                            <div><h5>Delivery Status</h5><p>Set the current lot stage and acceptance details.</p></div>
                            <span class="lot-form-chip">Final Step</span>
                        </div>
                        <div class="lot-form-card-body">
                            <div class="lot-fields two">
                                <div class="lot-field">
                                    <label>Mill Name <span class="lot-required">*</span></label>
                                    <select class="form-control" name="mill_name" required>
                                        <option value="">Select Mill Name</option>
                                        <option value="1" <?php echo lot_form_selected('mill_name', '1', $lot_result); ?>>Maya Industries</option>
                                        <option value="2" <?php echo lot_form_selected('mill_name', '2', $lot_result); ?>>C R Industries</option>
                                    </select>
                                    <div class="help-block" style="color:red"><?php echo form_error('mill_name'); ?></div>
                                </div>
                                <div class="lot-field">
                                    <label>Lot Status <span class="lot-required">*</span></label>
                                    <select class="form-control" name="status" id="lotStatus" required>
                                        <option value="pending" <?php echo lot_form_selected('status', 'pending', $lot_result); ?>>Pending</option>
                                        <option value="hold" <?php echo lot_form_selected('status', 'hold', $lot_result); ?>>Hold</option>
                                        <option value="shipped" <?php echo lot_form_selected('status', 'shipped', $lot_result); ?>>Shipped</option>
                                        <option value="fci_gate" <?php echo lot_form_selected('status', 'fci_gate', $lot_result); ?>>At FCI Gate</option>
                                        <option value="accept" <?php echo lot_form_selected('status', 'accept', $lot_result); ?>>Accepted</option>
                                        <option value="reject" <?php echo lot_form_selected('status', 'reject', $lot_result); ?>>Rejected</option>
                                        <option value="Not-Clear" <?php echo lot_form_selected('status', 'Not-Clear', $lot_result); ?>>Not-Clear</option>
                                    </select>
                                </div>
                                <div class="lot-field lot-accept-date-wrap" id="acceptDateWrap">
                                    <label>Lot Accept Date <span class="lot-required">*</span></label>
                                    <input type="text" name="lot_accept_date" value="<?php echo htmlspecialchars(lot_form_date('lot_accept_date', $lot_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control dob" placeholder="dd-mm-yyyy">
                                    <div class="help-block" style="color:red"><?php echo form_error('lot_accept_date'); ?></div>
                                </div>
                                <div class="lot-field">
                                    <label>Remark</label>
                                    <textarea name="remark" maxlength="1000" class="form-control" placeholder="Add operational note"><?php echo htmlspecialchars(lot_form_value('remark', $lot_result), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    <div class="help-block" style="color:red"><?php echo form_error('remark'); ?></div>
                                </div>
                            </div>
                            <div class="lot-status-help" id="statusHelp">Choose the status that reflects the current physical movement of the lot.</div>
                        </div>
                    </section>

                    <div class="lot-form-actions">
                        <a href="<?php echo base_url('admin/lot_system/listing'); ?>" class="btn lot-cancel">Cancel</a>
                        <button type="submit" class="btn lot-submit"><?php echo $is_edit ? 'Update Lot' : 'Save Lot'; ?></button>
                    </div>
                </div>

                <aside class="lot-form-card lot-summary-card">
                    <div class="lot-form-card-head">
                        <div><h5>Live Summary</h5><p>Auto-calculated from challan and FCI weights.</p></div>
                    </div>
                    <div class="lot-form-card-body">
                        <div class="lot-summary-list">
                            <div class="lot-summary-row"><span>Total Challan Weight</span><strong id="summaryChallan">0.00</strong></div>
                            <div class="lot-summary-row"><span>Total FCI Net Weight</span><strong id="summaryFci">0.00</strong></div>
                            <div class="lot-summary-row"><span>Weight Difference</span><strong id="summaryDiff">0.00</strong></div>
                            <div class="lot-summary-row"><span>Movement Mode</span><strong id="summaryMode"><?php echo $is_divided ? 'Divided' : 'Single'; ?></strong></div>
                        </div>
                    </div>
                </aside>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</main>

<script>
function numberValue(selector){
    var value = parseFloat($(selector).val());
    return isNaN(value) ? 0 : value;
}
function updateLotSummary(){
    var divided = $('#lot_divided_status').prop('checked');
    var challan = numberValue('[name="challan_weight_one"]') + (divided ? numberValue('[name="challan_weight_two"]') : 0);
    var fciOne = numberValue('[name="fci_loaded_weight_one"]') - numberValue('[name="fci_blank_weight_one"]');
    var fciTwo = divided ? (numberValue('[name="fci_loaded_weight_two"]') - numberValue('[name="fci_blank_weight_two"]')) : 0;
    var fci = fciOne + fciTwo;
    $('#summaryChallan').text(challan.toFixed(2));
    $('#summaryFci').text(fci.toFixed(2));
    $('#summaryDiff').text((fci - challan).toFixed(2));
    $('#summaryMode').text(divided ? 'Divided' : 'Single');
}
function toggleSecondTruck(){
    var divided = $('#lot_divided_status').prop('checked');
    $('#lotSecondSection').toggleClass('is-disabled', !divided);
    $('#lotSecondSection').find('input,select').prop('disabled', !divided);
    updateLotSummary();
}
function toggleAcceptDate(){
    var status = $('#lotStatus').val();
    var needsAcceptDate = status === 'accept';
    $('#acceptDateWrap').toggleClass('is-hidden', !needsAcceptDate);
    $('[name="lot_accept_date"]').prop('required', needsAcceptDate);
    var messages = {
        pending: 'Pending means the lot has not yet been dispatched.',
        hold: 'Hold means TD may be printed but dispatch is stopped or waiting.',
        shipped: 'Shipped means the lot has left for FCI.',
        fci_gate: 'FCI Gate means the lot has reached the FCI gate and is awaiting final acceptance.',
        accept: 'Accepted means delivered at FCI. Accept date is required.',
        reject: 'Rejected means the lot was not accepted. Add the reason in remark.',
        'Not-Clear': 'Not-Clear means the lot needs review before final status.'
    };
    $('#statusHelp').text(messages[status] || 'Choose the status that reflects the current physical movement of the lot.');
}
$(function() {
    $('.dob').datepicker({dateFormat: 'dd-mm-yy', setDate: new Date()});
    $('#lot_divided_status').on('change', toggleSecondTruck);
    $('#lotStatus').on('change', toggleAcceptDate);
    $('.lot-weight,.lot-loaded,.lot-blank').on('keyup change', updateLotSummary);
    toggleSecondTruck();
    toggleAcceptDate();
    updateLotSummary();
});
</script>
