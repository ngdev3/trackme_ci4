<?php
helper(['url', 'form']);
$result  = $result ?? null;
$is_edit = !empty($result);
$paddy_result = $is_edit ? $result : null;
$page_title = $is_edit ? 'Edit Paddy Lot' : 'Add Paddy Lot';

if (!function_exists('paddy_form_value')) {
    function paddy_form_value($field, $result = null, $default = '')
    {
        if (isset($_POST[$field])) {
            return $_POST[$field];
        }
        return isset($result->$field) ? $result->$field : $default;
    }
}

if (!function_exists('paddy_form_date')) {
    function paddy_form_date($field, $result = null)
    {
        $value = paddy_form_value($field, $result);
        if (empty($value) || $value == '0000-00-00' || $value == '0000-00-00 00:00:00') {
            return '';
        }
        return date('d-m-Y', strtotime($value));
    }
}

if (!function_exists('paddy_form_selected')) {
    function paddy_form_selected($field, $value, $result = null)
    {
        return (string) paddy_form_value($field, $result) === (string) $value ? 'selected' : '';
    }
}
?>

<style>
    .paddy-form-page{color:#18243c;padding:24px}.paddy-form-shell{margin:0 auto;max-width:1320px}.paddy-form-hero{align-items:center;background:#fff;border:1px solid #dce6f2;border-radius:8px;box-shadow:0 16px 38px rgba(24,36,60,.08);display:flex;gap:18px;justify-content:space-between;margin-bottom:18px;padding:22px 24px}.paddy-form-hero-copy{align-items:center;display:flex;gap:14px}.paddy-form-hero-icon{align-items:center;background:#e7f6ed;border-radius:8px;color:#18794e;display:flex;font-size:22px;height:52px;justify-content:center;width:52px}.paddy-form-title{font-size:25px;font-weight:900;margin:0}.paddy-form-subtitle{color:#718096;font-size:13px;font-weight:700;line-height:1.55;margin:6px 0 0}.paddy-form-back{background:#edf3fa;border:1px solid #dce6f2;border-radius:8px;color:#516174;font-weight:900;padding:10px 14px}.paddy-form-back:hover{background:#e7f6ed;color:#18794e;text-decoration:none}
    .paddy-form-grid{display:grid;gap:18px;grid-template-columns:minmax(0,1fr) 330px}.paddy-form-card{background:#fff;border:1px solid #dce6f2;border-radius:8px;box-shadow:0 16px 38px rgba(24,36,60,.08);margin-bottom:18px;overflow:hidden}.paddy-form-card-head{align-items:center;border-bottom:1px solid #edf2f7;display:flex;gap:12px;justify-content:space-between;padding:16px 18px}.paddy-form-card-head h5{font-size:16px;font-weight:900;margin:0}.paddy-form-card-head p{color:#718096;font-size:12px;font-weight:700;margin:5px 0 0}.paddy-form-chip{background:#e7f6ed;border-radius:999px;color:#18794e;font-size:11px;font-weight:900;padding:7px 10px;white-space:nowrap}.paddy-form-card-body{padding:18px}.paddy-fields{display:grid;gap:14px;grid-template-columns:repeat(3,minmax(0,1fr))}.paddy-fields.two{grid-template-columns:repeat(2,minmax(0,1fr))}.paddy-field label{color:#516174;display:block;font-size:11px;font-weight:900;margin-bottom:7px;text-transform:uppercase}.paddy-field input,.paddy-field select,.paddy-field textarea{background:#fbfdff;border:1px solid #dce6f2;border-radius:8px;box-shadow:none;color:#18243c;font-weight:800;min-height:40px;width:100%}.paddy-field textarea{min-height:100px;resize:vertical}.paddy-field .help-block{font-size:11px;font-weight:800;margin:6px 0 0}.paddy-required{color:#d64545}
    .paddy-select-wrap{position:relative}.paddy-select-wrap:after{color:#18794e;content:"\f107";font-family:FontAwesome;font-size:14px;pointer-events:none;position:absolute;right:13px;top:37px}.paddy-select{appearance:none;padding-right:34px!important}.paddy-field input:focus,.paddy-field select:focus,.paddy-field textarea:focus{border-color:#76c893;box-shadow:0 0 0 3px rgba(24,121,78,.12);outline:none}
    .paddy-summary-card{position:sticky;top:82px}.paddy-summary-list{display:grid;gap:10px}.paddy-summary-row{background:#f8fbff;border:1px solid #e6edf5;border-radius:8px;padding:12px}.paddy-summary-row span{color:#718096;display:block;font-size:11px;font-weight:900;text-transform:uppercase}.paddy-summary-row strong{color:#18243c;display:block;font-size:20px;font-weight:900;margin-top:5px}.paddy-status-help{background:#fff8e8;border:1px solid #f3d18a;border-radius:8px;color:#725017;font-size:12px;font-weight:800;line-height:1.5;margin-top:12px;padding:12px}.paddy-form-actions{align-items:center;display:flex;gap:10px;justify-content:flex-end;margin-top:18px}.paddy-submit{background:#18794e;border:0;border-radius:8px!important;box-shadow:0 10px 22px rgba(24,121,78,.2);color:#fff;font-weight:900;padding:11px 18px}.paddy-submit:hover,.paddy-submit:focus{background:#12693f;color:#fff}.paddy-cancel{background:#edf3fa;border:1px solid #dce6f2;border-radius:8px!important;color:#516174;font-weight:900;padding:10px 16px}.paddy-cancel:hover{background:#e7f6ed;color:#18794e}.paddy-accept-date-wrap.is-hidden{display:none}
    @media(max-width:991px){.paddy-form-grid{grid-template-columns:1fr}.paddy-summary-card{position:static}.paddy-fields,.paddy-fields.two{grid-template-columns:1fr 1fr}}@media(max-width:767px){.paddy-form-page{padding:14px}.paddy-form-hero{align-items:stretch;flex-direction:column}.paddy-fields,.paddy-fields.two{grid-template-columns:1fr}.paddy-form-actions{align-items:stretch;flex-direction:column-reverse}.paddy-submit,.paddy-cancel{width:100%}}
</style>

<main class="main-content bgc-grey-100 paddy-form-page">
    <div id="mainContent">
        <div class="container-fluid paddy-form-shell">
            <?php
            $flash_success = session()->getFlashdata('success');
            $flash_error   = session()->getFlashdata('error');
            if (!empty($flash_success)) { echo '<div class="alert alert-success">' . esc($flash_success) . '</div>'; }
            if (!empty($flash_error)) { echo '<div class="alert alert-danger">' . esc($flash_error) . '</div>'; }
            ?>

            <section class="paddy-form-hero">
                <div class="paddy-form-hero-copy">
                    <span class="paddy-form-hero-icon"><i class="fa fa-leaf"></i></span>
                    <div><h4 class="paddy-form-title"><?php echo $page_title; ?></h4><p class="paddy-form-subtitle">Capture paddy center, lot number, bags, quantity, mill and acceptance status with live operational summary.</p></div>
                </div>
                <a href="<?php echo base_url('admin/PaddyLotsystem/listing'); ?>" class="paddy-form-back"><i class="fa fa-arrow-left"></i> Back to Paddy Lots</a>
            </section>

            <?php echo form_open_multipart('', array('id' => 'paddyForm', 'autocomplete' => 'off')); ?>
            <div class="paddy-form-grid">
                <div>
                    <section class="paddy-form-card">
                        <div class="paddy-form-card-head"><div><h5>Paddy Lot Basics</h5><p>Start with lot identity and dispatch details.</p></div><span class="paddy-form-chip">Step 1</span></div>
                        <div class="paddy-form-card-body">
                            <div class="paddy-fields">
                                <div class="paddy-field paddy-select-wrap">
                                    <label>Center Name <span class="paddy-required">*</span></label>
                                    <select class="form-control paddy-select" name="center_id" required>
                                        <option value="">Select Center</option>
                                        <?php if (!empty($center_list)) { foreach ($center_list as $center) { ?>
                                            <option value="<?php echo $center->center_id; ?>" <?php echo paddy_form_selected('center_id', $center->center_id, $paddy_result); ?>><?php echo htmlspecialchars($center->name, ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php }} ?>
                                    </select>
                                    <div class="help-block" style="color:red"><?php echo form_error('center_id'); ?></div>
                                </div>
                                <div class="paddy-field">
                                    <label>Lot Number <span class="paddy-required">*</span></label>
                                    <input type="number" step="0.01" name="lot_number" value="<?php echo htmlspecialchars(paddy_form_value('lot_number', $paddy_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Enter paddy lot number" required>
                                    <div class="help-block" style="color:red"><?php echo form_error('lot_number'); ?></div>
                                </div>
                                <div class="paddy-field">
                                    <label>Dispatch Date <span class="paddy-required">*</span></label>
                                    <input type="text" name="dispatch_date" value="<?php echo htmlspecialchars(paddy_form_date('dispatch_date', $paddy_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control dob" placeholder="dd-mm-yyyy" required>
                                    <div class="help-block" style="color:red"><?php echo form_error('dispatch_date'); ?></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="paddy-form-card">
                        <div class="paddy-form-card-head"><div><h5>Bags &amp; Quantity</h5><p>Record paddy packing and total quantity.</p></div><span class="paddy-form-chip">Movement</span></div>
                        <div class="paddy-form-card-body">
                            <div class="paddy-fields">
                                <div class="paddy-field">
                                    <label>Type of Bags</label>
                                    <input type="text" name="type_of_bags" value="<?php echo htmlspecialchars(paddy_form_value('type_of_bags', $paddy_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="e.g. Jute, PP, Gunny">
                                    <div class="help-block" style="color:red"><?php echo form_error('type_of_bags'); ?></div>
                                </div>
                                <div class="paddy-field">
                                    <label>Total Bags</label>
                                    <input type="number" step="0.01" name="total_bags" value="<?php echo htmlspecialchars(paddy_form_value('total_bags', $paddy_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control paddy-calc" placeholder="Total bags">
                                    <div class="help-block" style="color:red"><?php echo form_error('total_bags'); ?></div>
                                </div>
                                <div class="paddy-field">
                                    <label>Quantity</label>
                                    <input type="number" step="0.01" name="quantity" value="<?php echo htmlspecialchars(paddy_form_value('quantity', $paddy_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control paddy-calc" placeholder="Quantity">
                                    <div class="help-block" style="color:red"><?php echo form_error('quantity'); ?></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="paddy-form-card">
                        <div class="paddy-form-card-head"><div><h5>Status &amp; Mill</h5><p>Set the processing destination and paddy lot stage.</p></div><span class="paddy-form-chip">Final Step</span></div>
                        <div class="paddy-form-card-body">
                            <div class="paddy-fields two">
                                <div class="paddy-field paddy-select-wrap">
                                    <label>Mill Name <span class="paddy-required">*</span></label>
                                    <select class="form-control paddy-select" name="mill_name" required>
                                        <option value="">Select Mill Name</option>
                                        <option value="1" <?php echo paddy_form_selected('mill_name', '1', $paddy_result); ?>>Maya Industries</option>
                                        <option value="2" <?php echo paddy_form_selected('mill_name', '2', $paddy_result); ?>>C R Industries</option>
                                    </select>
                                    <div class="help-block" style="color:red"><?php echo form_error('mill_name'); ?></div>
                                </div>
                                <div class="paddy-field paddy-select-wrap">
                                    <label>Lot Status <span class="paddy-required">*</span></label>
                                    <select class="form-control paddy-select" name="status" id="paddyStatus" required>
                                        <option value="pending" <?php echo paddy_form_selected('status', 'pending', $paddy_result); ?>>Pending</option>
                                        <option value="hold" <?php echo paddy_form_selected('status', 'hold', $paddy_result); ?>>Hold</option>
                                        <option value="accept" <?php echo paddy_form_selected('status', 'accept', $paddy_result); ?>>Accepted</option>
                                        <option value="reject" <?php echo paddy_form_selected('status', 'reject', $paddy_result); ?>>Rejected</option>
                                        <option value="Not-Clear" <?php echo paddy_form_selected('status', 'Not-Clear', $paddy_result); ?>>Not-Clear</option>
                                    </select>
                                </div>
                                <div class="paddy-field paddy-accept-date-wrap" id="acceptDateWrap">
                                    <label>Lot Accept Date <span class="paddy-required">*</span></label>
                                    <input type="text" name="lot_accept_date" value="<?php echo htmlspecialchars(paddy_form_date('lot_accept_date', $paddy_result), ENT_QUOTES, 'UTF-8'); ?>" class="form-control dob" placeholder="dd-mm-yyyy">
                                    <div class="help-block" style="color:red"><?php echo form_error('lot_accept_date'); ?></div>
                                </div>
                                <div class="paddy-field">
                                    <label>Remark</label>
                                    <textarea name="remark" maxlength="1000" class="form-control" placeholder="Add paddy lot note"><?php echo htmlspecialchars(paddy_form_value('remark', $paddy_result), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    <div class="help-block" style="color:red"><?php echo form_error('remark'); ?></div>
                                </div>
                            </div>
                            <div class="paddy-status-help" id="statusHelp">Choose the status that reflects the paddy lot stage.</div>
                        </div>
                    </section>

                    <div class="paddy-form-actions">
                        <a href="<?php echo base_url('admin/PaddyLotsystem/listing'); ?>" class="btn paddy-cancel">Cancel</a>
                        <button type="submit" class="btn paddy-submit"><?php echo $is_edit ? 'Update Paddy Lot' : 'Save Paddy Lot'; ?></button>
                    </div>
                </div>

                <aside class="paddy-form-card paddy-summary-card">
                    <div class="paddy-form-card-head"><div><h5>Live Paddy Summary</h5><p>Updates as you type.</p></div></div>
                    <div class="paddy-form-card-body">
                        <div class="paddy-summary-list">
                            <div class="paddy-summary-row"><span>Total Bags</span><strong id="summaryBags">0</strong></div>
                            <div class="paddy-summary-row"><span>Total Quantity</span><strong id="summaryQuantity">0.00</strong></div>
                            <div class="paddy-summary-row"><span>Quantity / Bag</span><strong id="summaryPerBag">0.00</strong></div>
                            <div class="paddy-summary-row"><span>Current Status</span><strong id="summaryStatus">Pending</strong></div>
                        </div>
                    </div>
                </aside>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</main>

<script>
function paddyNumber(name){
    var value = parseFloat($('[name="'+name+'"]').val());
    return isNaN(value) ? 0 : value;
}
function paddyStatusLabel(value){
    var labels = {accept:'Accepted',pending:'Pending',hold:'Hold',reject:'Rejected','Not-Clear':'Not Clear'};
    return labels[value] || value;
}
function updatePaddySummary(){
    var bags = paddyNumber('total_bags');
    var quantity = paddyNumber('quantity');
    $('#summaryBags').text(bags.toFixed(0));
    $('#summaryQuantity').text(quantity.toFixed(2));
    $('#summaryPerBag').text(bags > 0 ? (quantity / bags).toFixed(2) : '0.00');
    $('#summaryStatus').text(paddyStatusLabel($('#paddyStatus').val()));
}
function toggleAcceptDate(){
    var status = $('#paddyStatus').val();
    var needsAcceptDate = status === 'accept';
    $('#acceptDateWrap').toggleClass('is-hidden', !needsAcceptDate);
    $('[name="lot_accept_date"]').prop('required', needsAcceptDate);
    var messages = {
        pending: 'Pending means the paddy lot is still waiting for completion.',
        hold: 'Hold means this lot needs attention before acceptance.',
        accept: 'Accepted means final paddy receipt is complete. Accept date is required.',
        reject: 'Rejected means the paddy lot was not accepted. Add the reason in remark.',
        'Not-Clear': 'Not-Clear means the paddy lot needs review before final status.'
    };
    $('#statusHelp').text(messages[status] || 'Choose the status that reflects the paddy lot stage.');
    updatePaddySummary();
}
$(function(){
    $('.dob').datepicker({dateFormat:'dd-mm-yy',setDate:new Date()});
    $('.paddy-calc').on('keyup change', updatePaddySummary);
    $('#paddyStatus').on('change', toggleAcceptDate);
    toggleAcceptDate();
    updatePaddySummary();
});
</script>
