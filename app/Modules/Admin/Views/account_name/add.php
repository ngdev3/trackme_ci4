<?php
// ---- Modern Account Master form ----
$account_types      = isset($account_types) ? $account_types : array();
$acc_groups         = isset($acc_groups) ? $acc_groups : array();
$can_override_group = !empty($can_override_group);
$firms              = isset($firms) ? $firms : array();
$states             = isset($states) ? $states : array();
$cities             = isset($cities) ? $cities : array();
$gst_states         = isset($gst_states) ? $gst_states : array();

$is_edit   = !empty($result);
$sel_type  = set_value('account_type', @$result->account_type);
$sel_group = (int) set_value('account_group_id', @$result->account_group_id);
$sel_firm  = (int) set_value('linked_template_id', isset($linked_template_id) ? $linked_template_id : '');
$v_state   = set_value('state', @$result->state);
$v_scode   = set_value('state_code', @$result->state_code);
$v_city    = set_value('city', @$result->city);

// Registration type: gst (firm) / pan / unregistered (not a firm).
$v_reg = set_value('registration_type', isset($result->registration_type) ? $result->registration_type : '');
if ($v_reg === '' || !in_array($v_reg, array('gst', 'pan', 'unregistered'), true)) {
    if ($is_edit) {
        $gstv = trim((string) @$result->purchaser_gst_no);
        $panv = trim((string) @$result->pan_card);
        $v_reg = $gstv !== '' ? 'gst' : ($panv !== '' ? 'pan' : 'unregistered');
    } else {
        $v_reg = 'gst';
    }
}

// JS lookups
$js_group_by_code = array();
foreach ($acc_groups as $g) { $js_group_by_code[$g->code] = array('id' => (int) $g->id, 'name' => $g->name); }

// State master + GST-code match (case-insensitive name → 2-digit code).
$gst_by_lower = array();
foreach ($gst_states as $code => $sname) { $gst_by_lower[strtolower(trim($sname))] = $code; }
$js_states = array();
foreach ($states as $s) {
    $code = isset($gst_by_lower[strtolower(trim($s->name))]) ? $gst_by_lower[strtolower(trim($s->name))] : '';
    $js_states[] = array('id' => (int) $s->state_id, 'name' => $s->name, 'code' => $code);
}
$js_cities = array();
foreach ($cities as $c) { $js_cities[] = array('name' => $c->name, 'state_id' => (int) $c->state_id); }

// Is the saved state already present in the master option list? (case-insensitive)
$state_in_master = false;
foreach ($js_states as $s) { if ($v_state !== '' && strcasecmp($v_state, $s['name']) === 0) { $state_in_master = true; break; } }
?>
<style>
.acf-wrap { max-width: 1080px; margin: 0 auto; color: #18243c; }
.acf-title { font-size: 21px; font-weight: 900; margin: 6px 0 2px; }
.acf-title small { display: block; font-size: 12px; font-weight: 700; color: #8190a5; margin-top: 2px; }
.acf-card { border: 1px solid #dce6f2; border-radius: 14px; background: #fff; box-shadow: 0 12px 30px rgba(24,36,60,.06); padding: 20px 22px; margin-top: 16px; }
.acf-sec-h { display: flex; align-items: center; gap: 9px; font-size: 13px; font-weight: 900; text-transform: uppercase; letter-spacing: .05em; color: #1769c2; margin: 2px 0 14px; }
.acf-sec-h i { width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; background: linear-gradient(135deg,#eaf3ff,#dbeafe); color: #1769c2; font-size: 13px; }
.acf-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px 18px; }
.acf-grid.c3 { grid-template-columns: repeat(3, 1fr); }
@media (max-width: 780px) { .acf-grid, .acf-grid.c3 { grid-template-columns: 1fr; } }
.acf-f { display: flex; flex-direction: column; }
.acf-f.span2 { grid-column: span 2; }
@media (max-width: 780px) { .acf-f.span2 { grid-column: auto; } }
.acf-f label { font-size: 11px; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; color: #64748b; margin-bottom: 6px; }
.acf-f label .req { color: #e5484d; }
.acf-f input[type=text], .acf-f select, .acf-f textarea {
    width: 100%; min-height: 44px; border: 1px solid #dce6f2; border-radius: 10px; background: #fbfdff;
    padding: 0 12px; font-size: 14px; font-weight: 600; color: #18243c; transition: border-color .15s, box-shadow .15s;
}
.acf-f textarea { min-height: 70px; padding: 10px 12px; resize: vertical; }
.acf-f input:focus, .acf-f select:focus, .acf-f textarea:focus { outline: none; border-color: #1769c2; box-shadow: 0 0 0 4px rgba(23,105,194,.12); background: #fff; }
.acf-f input[readonly] { background: #eef3fa; color: #516174; cursor: default; }
.acf-hint { font-size: 11px; color: #8190a5; margin-top: 5px; font-weight: 600; }
.acf-err { color: #e5484d; font-size: 12px; margin-top: 5px; font-weight: 700; }
/* Select2 v3 tidy-up to match the inputs */
.acf-f .select2-container { width: 100% !important; }
.acf-f .select2-container .select2-choice { min-height: 44px; line-height: 44px; border: 1px solid #dce6f2; border-radius: 10px; background: #fbfdff; font-weight: 600; color: #18243c; padding-left: 12px; }
.acf-f .select2-container .select2-choice .select2-arrow { border-left: 1px solid #dce6f2; background: #eef3fa; border-radius: 0 10px 10px 0; }
.acf-gst-verify { display: flex; gap: 10px; align-items: end; }
.acf-btn { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 0 18px; border-radius: 10px; font-weight: 800; border: 0; cursor: pointer; }
.acf-btn-primary { color: #fff; background: linear-gradient(135deg,#1769c2,#0c315f); }
.acf-btn-ghost { background: #eef3fa; color: #0c315f; border: 1px solid #dce6f2; text-decoration: none; }
.acf-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
#gstinParseBox { margin-top: 10px; padding: 11px 13px; border-radius: 10px; background: #f8fafc; border: 1px solid #dce6f2; font-size: 12px; }
.acf-loader { width: 22px; height: 22px; }
.acf-loader.hide { display: none; }
.acf-addnew { font-size: 11px; font-weight: 800; color: #1769c2; text-decoration: none; text-transform: none; letter-spacing: 0; margin-left: 4px; }
.acf-addnew:hover { text-decoration: underline; }
.acf-addbox { display: none; gap: 6px; margin-bottom: 8px; }
.acf-addbox.open { display: flex; }
.acf-addbox input { flex: 1 1 auto; min-width: 0; min-height: 40px; border: 1px solid #1769c2; border-radius: 9px; padding: 0 11px; font-size: 14px; font-weight: 600; }
.acf-addbox .acf-btn { min-height: 40px; padding: 0 13px; }
.acf-reg { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
@media (max-width: 780px) { .acf-reg { grid-template-columns: 1fr; } }
.acf-reg-opt { display: flex; align-items: center; gap: 10px; margin: 0; padding: 11px 13px; border: 1px solid #dce6f2; border-radius: 10px; background: #fbfdff; cursor: pointer; font-weight: 600; }
.acf-reg-opt input { margin: 0; }
.acf-reg-opt span { display: flex; flex-direction: column; line-height: 1.25; }
.acf-reg-opt span small { color: #8190a5; font-size: 11px; font-weight: 600; }
.acf-reg-opt.on { border-color: #1769c2; background: #eef5ff; box-shadow: 0 0 0 3px rgba(23,105,194,.10); }
.acf-f.dim { opacity: .5; }
</style>

<main class="main-content bgc-grey-100"><div id="mainContent"><div class="container-fluid"><div class="acf-wrap">
    <div class="acf-title"><?= $is_edit ? 'Edit Account' : 'Add Account' ?><small>Party / ledger master &middot; C R Industries</small></div>
    <?= get_flashdata(); ?>

    <?php if (empty($states) && function_exists('erp_is_super_admin') && erp_is_super_admin()): ?>
        <div class="acf-card" style="border-color:#ffe0a6;background:#fff8e9;padding:14px 18px;">
            <strong>State / City master is empty.</strong> Seed all Indian states + major cities into the dropdowns (idempotent).
            <a class="acf-btn acf-btn-primary" style="margin-left:8px;min-height:36px;" href="<?php echo base_url('admin/account_name/seed_locations'); ?>">Seed State &amp; City Master</a>
        </div>
    <?php endif; ?>

    <?php echo form_open_multipart('', array('id' => 'ciatyform_id')); ?>

    <!-- ============ Registration / GST / PAN ============ -->
    <div class="acf-card">
        <div class="acf-sec-h"><i class="ti-id-badge"></i> Registration &amp; Tax Identity</div>
        <div class="acf-f" style="margin-bottom:14px;">
            <label>Registration Type <span class="req">*</span></label>
            <div class="acf-reg">
                <label class="acf-reg-opt"><input type="radio" name="registration_type" value="gst" <?= $v_reg === 'gst' ? 'checked' : '' ?>> <span><b>GST Registered</b><small>Firm with a GSTIN</small></span></label>
                <label class="acf-reg-opt"><input type="radio" name="registration_type" value="pan" <?= $v_reg === 'pan' ? 'checked' : '' ?>> <span><b>PAN Only</b><small>No GST, has PAN</small></span></label>
                <label class="acf-reg-opt"><input type="radio" name="registration_type" value="unregistered" <?= $v_reg === 'unregistered' ? 'checked' : '' ?>> <span><b>Unregistered</b><small>Individual / not a firm</small></span></label>
            </div>
        </div>
        <div class="acf-grid">
            <div class="acf-f" id="gstField">
                <label for="purchaser_gst_no">GST No. <span class="req" id="gstReq">*</span></label>
                <div class="acf-gst-verify">
                    <?php echo form_input(array('type'=>'text','name'=>'purchaser_gst_no','id'=>'purchaser_gst_no','maxlength'=>'15','class'=>'','placeholder'=>'15-digit GSTIN','value'=>set_value('purchaser_gst_no', @$result->purchaser_gst_no))); ?>
                    <button type="button" id="getgstData" class="acf-btn acf-btn-primary" style="white-space:nowrap;">Verify</button>
                    <img id="loaders" src="<?php echo base_url('assets/images/preview.gif'); ?>" alt="loading" class="acf-loader hide">
                </div>
                <?php echo form_error('purchaser_gst_no', '<div class="acf-err">', '</div>'); ?>
                <div id="gstinParseBox" style="display:none;">
                    <div><span id="gstinBadge" class="label"></span> <strong id="gstinMessage"></strong></div>
                    <div style="margin-top:6px;color:#516174;">State: <strong id="gstinState">-</strong> | PAN: <strong id="gstinPan">-</strong> | Registration: <strong id="gstinReg">-</strong></div>
                </div>
            </div>
            <div class="acf-f" id="panField">
                <label for="pan_card">PAN Card <span class="req" id="panReq" style="display:none;">*</span></label>
                <?php echo form_input(array('type'=>'text','name'=>'pan_card','id'=>'pan_card','maxlength'=>'10','class'=>'','placeholder'=>'PAN Card','value'=>set_value('pan_card', @$result->pan_card))); ?>
                <?php echo form_error('pan_card', '<div class="acf-err">', '</div>'); ?>
                <div class="acf-hint" id="unregHint" style="display:none;">Individual / not a firm — GST &amp; PAN are optional.</div>
            </div>
        </div>
    </div>

    <!-- ============ Party & classification ============ -->
    <div class="acf-card">
        <div class="acf-sec-h"><i class="ti-user"></i> Party &amp; Classification</div>
        <div class="acf-grid">
            <div class="acf-f span2">
                <label for="account_name">Account Name <span class="req">*</span></label>
                <?php echo form_input(array('type'=>'text','name'=>'account_name','id'=>'account_name','maxlength'=>'255','class'=>'','placeholder'=>'Account / party name','value'=>set_value('account_name', @$result->name))); ?>
                <?php echo form_error('account_name', '<div class="acf-err">', '</div>'); ?>
            </div>
            <div class="acf-f">
                <label for="account_type">Account Type <span class="req">*</span></label>
                <select id="account_type" class="" name="account_type">
                    <option value="">-- Select Account Type --</option>
                    <?php foreach ($account_types as $tv => $tl): ?>
                        <option value="<?= html_escape($tv) ?>" <?= ($sel_type === $tv ? 'selected' : '') ?>><?= html_escape($tl) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="acf-hint">The ledger group is assigned automatically.</div>
                <?php echo form_error('account_type', '<div class="acf-err">', '</div>'); ?>
            </div>
            <div class="acf-f">
                <label for="account_group_display">Ledger Group <span class="req">*</span></label>
                <?php if ($can_override_group && !empty($acc_groups)): ?>
                    <select id="account_group_id" class="" name="account_group_id">
                        <option value="">(Auto from Account Type)</option>
                        <?php foreach ($acc_groups as $g): ?>
                            <option value="<?= (int) $g->id ?>" data-code="<?= html_escape($g->code) ?>" <?= ($sel_group === (int) $g->id ? 'selected' : '') ?>><?= html_escape($g->label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="acf-hint">You may override the auto-assigned group.</div>
                <?php else: ?>
                    <input type="text" id="account_group_display" value="" placeholder="Auto-assigned" readonly>
                    <div class="acf-hint">Assigned automatically (override needs edit permission).</div>
                <?php endif; ?>
            </div>
            <div class="acf-f span2" id="sisterFirmRow" style="display:none;">
                <label for="linked_template_id">Linked Sister Firm</label>
                <select id="linked_template_id" class="" name="linked_template_id">
                    <option value="">-- Select Firm --</option>
                    <?php foreach ($firms as $f): ?>
                        <option value="<?= (int) $f->template_id ?>" <?= ($sel_firm === (int) $f->template_id ? 'selected' : '') ?>><?= html_escape($f->template_name) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="acf-hint">Tag which firm this Trade-Party ledger represents (for inter-firm reconciliation).</div>
            </div>
            <div class="acf-f">
                <label for="contact_person_name">Contact Person</label>
                <?php echo form_input(array('type'=>'text','name'=>'contact_person_name','id'=>'contact_person_name','maxlength'=>'255','class'=>'','placeholder'=>'Contact person','value'=>set_value('contact_person_name', @$result->contact_person_name))); ?>
            </div>
            <div class="acf-f">
                <label for="is_Kisan">Is Kisan?</label>
                <select id="is_Kisan" class="" name="is_Kisan">
                    <option value="true" <?= (@$result->is_Kisan === 'true' ? 'selected' : '') ?>>Yes</option>
                    <option value="false" <?= (@$result->is_Kisan === 'true' ? '' : 'selected') ?>>No</option>
                </select>
            </div>
            <div class="acf-f">
                <label for="email_id">Email Id</label>
                <?php echo form_input(array('type'=>'text','name'=>'email_id','id'=>'email_id','maxlength'=>'255','class'=>'','placeholder'=>'Email','value'=>set_value('email_id', @$result->email_id))); ?>
            </div>
        </div>
    </div>

    <!-- ============ Address / location (advanced dropdowns) ============ -->
    <div class="acf-card">
        <div class="acf-sec-h"><i class="ti-location-pin"></i> Address &amp; Location</div>
        <div class="acf-grid c3">
            <div class="acf-f">
                <label for="state">State <span class="req">*</span> <a href="javascript:void(0)" class="acf-addnew" data-target="state">+ New</a></label>
                <div class="acf-addbox" id="addbox-state">
                    <input type="text" id="new-state" placeholder="New state name">
                    <button type="button" class="acf-btn acf-btn-primary acf-addgo" data-kind="state">Add</button>
                    <button type="button" class="acf-btn acf-btn-ghost acf-addcancel" data-target="state">Cancel</button>
                </div>
                <select id="state" name="state" data-placeholder="Search state">
                    <option value="">Select state</option>
                    <?php foreach ($js_states as $s): ?>
                        <option value="<?= html_escape($s['name']) ?>" data-code="<?= html_escape($s['code']) ?>" data-state-id="<?= (int) $s['id'] ?>" <?= ($v_state !== '' && strcasecmp($v_state, $s['name']) === 0 ? 'selected' : '') ?>><?= html_escape($s['name']) ?></option>
                    <?php endforeach; ?>
                    <?php if ($v_state !== '' && !$state_in_master): ?>
                        <option value="<?= html_escape($v_state) ?>" selected><?= html_escape($v_state) ?></option>
                    <?php endif; ?>
                </select>
                <?php echo form_error('state', '<div class="acf-err">', '</div>'); ?>
            </div>
            <div class="acf-f">
                <label for="state_code">State Code <span class="req">*</span></label>
                <select id="state_code" name="state_code" data-placeholder="Search code">
                    <option value="">Select code</option>
                    <?php foreach ($gst_states as $code => $sname): ?>
                        <option value="<?= html_escape($code) ?>" <?= ($v_scode !== '' && $v_scode === $code ? 'selected' : '') ?>><?= html_escape($code . ' — ' . $sname) ?></option>
                    <?php endforeach; ?>
                    <?php if ($v_scode !== '' && !isset($gst_states[$v_scode])): ?>
                        <option value="<?= html_escape($v_scode) ?>" selected><?= html_escape($v_scode) ?></option>
                    <?php endif; ?>
                </select>
                <?php echo form_error('state_code', '<div class="acf-err">', '</div>'); ?>
            </div>
            <div class="acf-f">
                <label for="city">City <span class="req">*</span> <a href="javascript:void(0)" class="acf-addnew" data-target="city">+ New</a></label>
                <div class="acf-addbox" id="addbox-city">
                    <input type="text" id="new-city" placeholder="New city name">
                    <button type="button" class="acf-btn acf-btn-primary acf-addgo" data-kind="city">Add</button>
                    <button type="button" class="acf-btn acf-btn-ghost acf-addcancel" data-target="city">Cancel</button>
                </div>
                <select id="city" name="city" data-placeholder="Search city">
                    <option value="">Select city</option>
                    <?php if ($v_city !== ''): ?>
                        <option value="<?= html_escape($v_city) ?>" selected><?= html_escape($v_city) ?></option>
                    <?php endif; ?>
                </select>
                <div class="acf-hint">Filtered by the selected state. Type to search or add a new one.</div>
                <?php echo form_error('city', '<div class="acf-err">', '</div>'); ?>
            </div>
            <div class="acf-f">
                <label for="pin_code">Pin Code <span class="req">*</span></label>
                <?php echo form_input(array('type'=>'text','name'=>'pin_code','id'=>'pin_code','maxlength'=>'10','class'=>'','placeholder'=>'Pin code','value'=>set_value('pin_code', @$result->pin_code))); ?>
                <?php echo form_error('pin_code', '<div class="acf-err">', '</div>'); ?>
            </div>
            <div class="acf-f span2">
                <label for="purchaser_address">Address <span class="req">*</span></label>
                <?php echo form_textarea(array('name'=>'purchaser_address','id'=>'purchaser_address','maxlength'=>'255','class'=>'','placeholder'=>'Full address','value'=>set_value('purchaser_address', @$result->purchaser_address))); ?>
                <?php echo form_error('purchaser_address', '<div class="acf-err">', '</div>'); ?>
            </div>
        </div>
    </div>

    <!-- ============ Banking ============ -->
    <div class="acf-card">
        <div class="acf-sec-h"><i class="ti-wallet"></i> Banking</div>
        <div class="acf-grid c3">
            <div class="acf-f">
                <label for="bank_name">Bank Name</label>
                <?php echo form_input(array('type'=>'text','name'=>'bank_name','id'=>'bank_name','maxlength'=>'255','class'=>'','placeholder'=>'Bank name','value'=>set_value('bank_name', @$result->bank_name))); ?>
            </div>
            <div class="acf-f">
                <label for="ifsc_code">IFSC Code</label>
                <?php echo form_input(array('type'=>'text','name'=>'ifsc_code','id'=>'ifsc_code','maxlength'=>'20','class'=>'','placeholder'=>'IFSC','value'=>set_value('ifsc_code', @$result->ifsc_code))); ?>
            </div>
            <div class="acf-f">
                <label for="purchaser_account_no">Account No.</label>
                <?php echo form_input(array('type'=>'text','name'=>'purchaser_account_no','id'=>'purchaser_account_no','maxlength'=>'40','class'=>'','placeholder'=>'Account number','value'=>set_value('purchaser_account_no', @$result->purchaser_account_no))); ?>
            </div>
        </div>
    </div>

    <!-- ============ Options ============ -->
    <div class="acf-card">
        <div class="acf-sec-h"><i class="ti-settings"></i> Options</div>
        <div class="acf-grid">
            <div class="acf-f">
                <label for="status">Status <span class="req">*</span></label>
                <select id="status" class="" name="status">
                    <option value="Active" <?= (@$result->status === 'Inactive' ? '' : 'selected') ?>>Active</option>
                    <option value="Inactive" <?= (@$result->status === 'Inactive' ? 'selected' : '') ?>>Inactive</option>
                </select>
                <?php echo form_error('status', '<div class="acf-err">', '</div>'); ?>
            </div>
            <div class="acf-f">
                <label>TDS</label>
                <label style="font-weight:700;color:#18243c;display:flex;align-items:center;gap:8px;min-height:44px;">
                    <input type="checkbox" name="buyer_deducts_tds_194q" value="1" <?php echo set_checkbox('buyer_deducts_tds_194q', '1', !empty($result) && (int) @$result->buyer_deducts_tds_194q === 1); ?>>
                    Buyer deducts TDS under 194Q
                </label>
                <div class="acf-hint">Enable for buyers who deduct TDS. Takes priority over TCS.</div>
            </div>
        </div>
    </div>

    <div class="acf-actions">
        <a href="<?php echo base_url('admin/account_name/listing'); ?>" class="acf-btn acf-btn-ghost">Cancel</a>
        <button type="submit" class="acf-btn acf-btn-primary"><i class="ti-check"></i> <?= $is_edit ? 'Update Account' : 'Save Account' ?></button>
    </div>

    <?php echo form_close(); ?>
</div></div></div></main>

<script>
var ACC_TYPE_GROUP = {
    'customer':'TRADE_PARTIES','supplier':'TRADE_PARTIES','customer_supplier':'TRADE_PARTIES',
    'sister_firm':'TRADE_PARTIES','farmer':'FARMER_ACCOUNTS','transporter':'TRANSPORTERS',
    'commission_agent':'COMMISSION_AGENTS','employee':'EMPLOYEES','bank':'BANK_ACCOUNTS',
    'cash':'CASH_IN_HAND','expense':'INDIRECT_EXPENSES','income':'INDIRECT_INCOME',
    'fixed_asset':'FIXED_ASSETS','loan':'LOANS_LIABILITY','capital':'CAPITAL','other':'SUSPENSE'
};
var ACC_GROUP_BY_CODE = <?php echo json_encode($js_group_by_code); ?>;
var ACC_STATES        = <?php echo json_encode($js_states); ?>;   // [{id,name,code}]
var ACC_CITIES        = <?php echo json_encode($js_cities); ?>;   // [{name,state_id}]
var ACC_GST_STATES    = <?php echo json_encode($gst_states); ?>;  // {code:name}

/* ---- Select2 v3-safe init / combo helpers ---- */
function acfInitSelect2($sel, opts){
    if (!window.jQuery || !$.fn.select2 || !$sel.length) return;
    try { $sel.select2('destroy'); } catch (e) {}
    $sel.select2($.extend({ width: '100%', allowClear: true, placeholder: $sel.data('placeholder') || 'Select' }, opts || {}));
}
function acfEnsureOption($sel, val, text){
    if (val === '' || val === null || typeof val === 'undefined') return;
    var found = false;
    $sel.find('option').each(function(){ if (String(this.value) === String(val)) { found = true; } });
    if (!found) { $sel.append(new Option(text || val, val, false, false)); }
}
function acfSetCombo($sel, val, text){
    acfEnsureOption($sel, val, text);
    $sel.val(val);
    acfInitSelect2($sel);              // reflect any newly added option (v3-safe)
}

/* ---- City list rebuild filtered by state_id ---- */
function acfRebuildCities(stateId, keepVal){
    var $city = $('#city');
    var cur = (typeof keepVal !== 'undefined') ? keepVal : $city.val();
    var html = '<option value="">Select city</option>';
    var seen = {};
    ACC_CITIES.forEach(function(c){
        if (!stateId || String(c.state_id) === String(stateId)) {
            if (!seen[c.name]) { seen[c.name] = 1; html += '<option value="' + $('<i>').text(c.name).html() + '">' + $('<i>').text(c.name).html() + '</option>'; }
        }
    });
    $city.html(html);
    if (cur) { acfEnsureOption($city, cur, cur); $city.val(cur); }
    acfInitSelect2($city);
}

/* ---- Account Type -> group + sister-firm ---- */
function accSyncGroup(){
    var t = $('#account_type').val();
    var code = ACC_TYPE_GROUP[t] || '';
    var g = ACC_GROUP_BY_CODE[code] || null;
    var disp = document.getElementById('account_group_display');
    if (disp) { disp.value = g ? g.name : ''; }
    var $sel = $('#account_group_id');
    if ($sel.length && (!$sel.val() || $sel.attr('data-auto') === '1')) {
        $sel.val(g ? String(g.id) : '');
        $sel.attr('data-auto', '1');
        acfInitSelect2($sel);
    }
    $('#sisterFirmRow').css('display', t === 'sister_firm' ? '' : 'none');
}

/* ---- State <-> State Code sync ---- */
function accStateChanged(){
    var $opt = $('#state').find('option:selected');
    var code = $opt.data('code') || '';
    var sid  = $opt.data('state-id') || '';
    if (code) { acfSetCombo($('#state_code'), code, code + ' — ' + (ACC_GST_STATES[code] || '')); }
    acfRebuildCities(sid, $('#city').val());
}
function accStateCodeChanged(){
    var code = $('#state_code').val();
    var name = ACC_GST_STATES[code] || '';
    if (name) {
        // match against existing state option (by name), else add it
        var matched = null;
        $('#state').find('option').each(function(){ if (this.value.toLowerCase() === name.toLowerCase()) { matched = this.value; } });
        acfSetCombo($('#state'), matched || name, name);
        // rebuild cities for the matched state's id (if any)
        var sid = '';
        ACC_STATES.forEach(function(s){ if (s.name.toLowerCase() === name.toLowerCase()) { sid = s.id; } });
        acfRebuildCities(sid, $('#city').val());
    }
}

$(document).ready(function(){
    // init select2 on the plain selects
    acfInitSelect2($('#account_type'));
    acfInitSelect2($('#account_group_id'));
    acfInitSelect2($('#linked_template_id'));
    acfInitSelect2($('#is_Kisan'));
    acfInitSelect2($('#status'));
    acfInitSelect2($('#state'));
    acfInitSelect2($('#state_code'));

    // initial city list based on any preselected state
    var initOpt = $('#state').find('option:selected');
    acfRebuildCities(initOpt.data('state-id') || '', '<?= html_escape($v_city) ?>');

    $('#account_type').on('change', accSyncGroup);
    $('#account_group_id').on('change', function(){ $(this).removeAttr('data-auto'); });
    $('#state').on('change', accStateChanged);
    $('#state_code').on('change', accStateCodeChanged);
    accSyncGroup();

    // registration type (GST / PAN / Unregistered)
    $('input[name=registration_type]').on('change', acfApplyReg);
    acfApplyReg();
    $('#ciatyform_id').on('submit', acfValidateReg);

    // ---- inline "+ New" state / city ----
    $('.acf-addnew').on('click', function(){
        var t = $(this).data('target');
        $('#addbox-' + t).addClass('open');
        $('#new-' + t).focus();
    });
    $('.acf-addcancel').on('click', function(){
        var t = $(this).data('target');
        $('#addbox-' + t).removeClass('open');
        $('#new-' + t).val('');
    });
    $('.acf-addgo').on('click', function(){
        var kind = $(this).data('kind');
        if (kind === 'state') { acfAddState(); } else { acfAddCity(); }
    });
    $('#new-state, #new-city').on('keydown', function(e){
        if (e.which === 13) { e.preventDefault(); if (this.id === 'new-state') { acfAddState(); } else { acfAddCity(); } }
    });
});

function acfAddState(){
    var name = $.trim($('#new-state').val());
    if (name === '') { $('#new-state').focus(); return; }
    $.post("<?php echo base_url('admin/account_name/quick_add_state'); ?>", { name: name }, function(res){
        if (!res || res.status === 'error') { alert((res && res.message) || 'Could not add state.'); return; }
        var $state = $('#state');
        // ensure option carries the data attributes so downstream sync works
        var exists = false;
        $state.find('option').each(function(){ if (this.value.toLowerCase() === name.toLowerCase()) { exists = true; $(this).attr('data-state-id', res.id).attr('data-code', res.code || ''); } });
        if (!exists) { $state.append($('<option>', { value: name, text: name, 'data-state-id': res.id, 'data-code': res.code || '' })); }
        ACC_STATES.push({ id: res.id, name: name, code: res.code || '' });
        $state.val(name);
        acfInitSelect2($state);
        accStateChanged();
        $('#addbox-state').removeClass('open');
        $('#new-state').val('');
    }, 'json').fail(function(){ alert('Could not add state.'); });
}

function acfAddCity(){
    var name = $.trim($('#new-city').val());
    if (name === '') { $('#new-city').focus(); return; }
    var $opt = $('#state').find('option:selected');
    var stateName = $('#state').val() || '';
    var stateId = $opt.data('state-id') || '';
    if (!stateName) { alert('Select a state first.'); return; }
    $.post("<?php echo base_url('admin/account_name/quick_add_city'); ?>", { name: name, state_name: stateName, state_id: stateId }, function(res){
        if (!res || res.status === 'error') { alert((res && res.message) || 'Could not add city.'); return; }
        ACC_CITIES.push({ name: name, state_id: res.state_id });
        // reflect the new state_id on the selected state option (if it was created)
        if (res.state_id && !stateId) { $opt.attr('data-state-id', res.state_id); }
        acfSetCombo($('#city'), name, name);
        $('#addbox-city').removeClass('open');
        $('#new-city').val('');
    }, 'json').fail(function(){ alert('Could not add city.'); });
}

/* ---- Registration type: GST / PAN / Unregistered ---- */
function acfApplyReg(){
    var reg = $('input[name=registration_type]:checked').val() || 'gst';
    $('.acf-reg-opt').removeClass('on');
    $('input[name=registration_type]:checked').closest('.acf-reg-opt').addClass('on');
    $('#gstReq').toggle(reg === 'gst');
    $('#panReq').toggle(reg === 'pan');
    $('#unregHint').toggle(reg === 'unregistered');
}
function acfValidateReg(e){
    var reg = $('input[name=registration_type]:checked').val() || 'gst';
    if (reg === 'gst' && $.trim($('#purchaser_gst_no').val()) === '') {
        alert('GST No is required for a GST-registered firm.'); $('#purchaser_gst_no').focus(); e.preventDefault(); return false;
    }
    if (reg === 'pan' && $.trim($('#pan_card').val()) === '') {
        alert('PAN Card is required for a PAN-only party.'); $('#pan_card').focus(); e.preventDefault(); return false;
    }
}

/* ---- GSTIN live parse ---- */
var gstinParseTimer = null;
function renderGstinParse(res){
    if (!res || !res.gstin) { $('#gstinParseBox').hide(); return; }
    var valid = !!res.valid;
    $('#gstinParseBox').show();
    $('#gstinBadge').removeClass('label-success label-danger label-default')
        .addClass(valid ? 'label-success' : (res.status === 'empty' ? 'label-default' : 'label-danger'))
        .text(valid ? 'VALID' : String(res.status || 'INVALID').toUpperCase());
    $('#gstinMessage').text(res.message || '');
    $('#gstinState').text((res.state_code || '-') + (res.state_name ? ' - ' + res.state_name : ''));
    $('#gstinPan').text(res.pan_number || '-');
    $('#gstinReg').text((res.registration_number || '-') + (res.registration_label ? ' (' + res.registration_label + ')' : ''));
    if (valid) {
        if (res.state_code) { acfSetCombo($('#state_code'), res.state_code, res.state_code + ' — ' + (ACC_GST_STATES[res.state_code] || res.state_name || '')); }
        if (res.state_name) { acfSetCombo($('#state'), res.state_name, res.state_name); }
        if (res.pan_number) { $('#pan_card').val(res.pan_number); }
        accStateCodeChanged();
    }
}
function parseGstinLive(){
    var gstin = $('#purchaser_gst_no').val();
    clearTimeout(gstinParseTimer);
    gstinParseTimer = setTimeout(function(){
        if ($.trim(gstin) === '') { $('#gstinParseBox').hide(); return; }
        $.post("<?php echo base_url('admin/gstin/parse'); ?>", { gstin: gstin }, renderGstinParse, 'json');
    }, 250);
}
$('#purchaser_gst_no').on('keyup change blur', parseGstinLive);
$(document).ready(parseGstinLive);

/* ---- GST full verify (appyflow) ---- */
$('#getgstData').on('click', function(){
    $('#loaders').removeClass('hide');
    var gstNo = $('#purchaser_gst_no').val().trim();
    if (gstNo === '' || gstNo.length !== 15) { alert('Please enter a valid 15-digit GST number'); $('#loaders').addClass('hide'); return false; }
    $.ajax({
        url: "https://appyflow.in/api/verifyGST", method: "GET", dataType: "json",
        data: { key_secret: "JK1KGeb0tlUHOQw7GFAQ4hRrID02", gstNo: gstNo },
        success: function(response){
            $('#loaders').addClass('hide');
            if (response.error === true) { alert(response.message || 'Invalid GST Number'); return; }
            var info = response.taxpayerInfo || {};
            var addr = (info.pradr && info.pradr.addr) ? info.pradr.addr : {};
            var scode = info.gstin ? info.gstin.substring(0, 2) : '';
            if (scode) { acfSetCombo($('#state_code'), scode, scode + ' — ' + (ACC_GST_STATES[scode] || '')); }
            $('#account_name').val(info.tradeNam || info.legalNam || '');
            var cityName = addr.dst || addr.loc || '';
            $('#contact_person_name').val(addr.bnm || info.tradeNam || '');
            $('#pin_code').val(addr.pncd || '');
            $('#is_Kisan').val('false'); acfInitSelect2($('#is_Kisan'));
            $('#purchaser_address').val(
                (addr.bno ? addr.bno + ', ' : '') + (addr.st ? addr.st + ', ' : '') +
                (addr.loc ? addr.loc + ', ' : '') + (addr.dst ? addr.dst + ', ' : '') + (addr.stcd || '')
            );
            var stName = addr.stcd || (ACC_GST_STATES[scode] || '');
            if (stName) { acfSetCombo($('#state'), stName, stName); }
            accStateCodeChanged();
            if (cityName) { acfSetCombo($('#city'), cityName, cityName); }
        },
        error: function(){ $('#loaders').addClass('hide'); alert('Unable to verify GST. Please try again later.'); }
    });
});
</script>
