<?php
/**
 * Deposit Entry — redesigned (Task 20)
 * Shared by admin/account/deposite (add) and admin/account/edit/<id> (edit).
 *
 * Core fields are always visible; bill/invoice details are optional and hidden
 * behind a toggle. Khata Entry No, Bill No and Mill Name are kept as hidden,
 * auto-carried fields so the backend + existing data/reports are unaffected.
 * Image / voice / video attachments reuse the existing aa_rokad media columns.
 */
$is_edit       = isset($result) && !empty($result);
// Per-page context: deposite() and expenditure() pass these so the same view serves both
// with the correct default Type and labels. Falls back to Deposit for any caller that omits them.
$entry_label   = isset($entry_label) ? $entry_label : 'Deposit';
$default_type  = isset($default_type) ? $default_type : 'deposit';
$sel_type      = $is_edit ? (string) @$result->type_of_account : $default_type;
$image_path    = $is_edit ? trim((string) @$result->image_path) : '';
$voice_path    = $is_edit ? trim((string) @$result->voice_note_path) : '';
$video_path    = $is_edit ? trim((string) @$result->video_note_path) : '';
// Auto-open the bill section on edit when any bill field already has a value.
$bill_has_data = $is_edit && (
    trim((string) @$result->party_account_no) !== '' ||
    trim((string) @$result->party_invoice_no) !== '' ||
    trim((string) @$result->quantity) !== '' ||
    trim((string) @$result->rate) !== '' ||
    trim((string) @$result->truck_no) !== ''
);
// Attachments are hidden behind a toggle (like the bill section); auto-open on
// edit only when the entry already has an image / voice / video attached.
$attach_has_data = $is_edit && ($image_path !== '' || $voice_path !== '' || $video_path !== '');
?>
<style>
  * { box-sizing: border-box; }

  .account-entry-page { color: var(--tm-ink, #18243c); }
  .account-entry-page .entry-shell { max-width: 1100px; margin: 0 auto; }

  .account-entry-hero {
    position: relative;
    margin: 4px 0 18px;
    padding: 18px 20px 18px 68px;
    border: 1px solid rgba(var(--tm-brand-rgb, 23, 105, 194), .13);
    border-radius: 12px;
    background:
      linear-gradient(135deg, rgba(255, 255, 255, .98), rgba(255, 255, 255, .9)),
      radial-gradient(circle at 92% 0, rgba(31, 157, 112, .13), transparent 34%);
    box-shadow: 0 14px 34px rgba(24, 36, 60, .08);
  }
  .account-entry-hero:before {
    content: "\e63c";
    position: absolute; left: 20px; top: 50%;
    width: 36px; height: 36px;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 9px; color: #fff;
    background: linear-gradient(135deg, #1f9d70, #13734f);
    box-shadow: 0 10px 22px rgba(31, 157, 112, .22);
    font-family: themify; transform: translateY(-50%);
  }
  .account-entry-hero h4 { margin: 0; font-size: 23px; font-weight: 900; }
  .account-entry-hero p { margin: 5px 0 0; color: var(--tm-muted, #718096); font-size: 13px; font-weight: 700; }

  .entry-card {
    margin-bottom: 18px;
    border: 1px solid var(--tm-line, #dce6f2);
    border-radius: 12px;
    background: rgba(255, 255, 255, .97);
    box-shadow: 0 16px 38px rgba(24, 36, 60, .07);
    /* visible (not hidden) so the Account Name autocomplete dropdown can extend
       past the card edge instead of being clipped / overlapping fields below. */
    overflow: visible;
    animation: cardIn .35s ease both;
  }
  @keyframes cardIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

  .entry-card > .card-head {
    display: flex; align-items: center; gap: 10px;
    padding: 15px 20px;
    border-bottom: 1px solid var(--tm-line, #dce6f2);
    background: linear-gradient(180deg, #fff, rgba(var(--tm-brand-rgb, 23, 105, 194), .035));
    border-radius: 12px 12px 0 0;   /* keep rounded top corners without overflow:hidden */
    font-size: 15px; font-weight: 900;
  }
  .entry-card > .card-head .ti, .entry-card > .card-head i { color: var(--tm-brand, #1769c2); }
  .entry-card > .card-head small { margin-left: auto; font-size: 11px; font-weight: 700; color: var(--tm-muted, #718096); }
  .entry-card > .card-body { padding: 20px; }

  .account-entry-page .form-row {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 14px; margin: 0;
  }
  .account-entry-page .form-group { margin-bottom: 14px; }
  .account-entry-page .form-group[class*="col-md-"] { width: auto !important; max-width: none !important; flex: none !important; padding: 0; }
  .account-entry-page .col-md-3 { grid-column: span 3; }
  .account-entry-page .col-md-4 { grid-column: span 4; }
  .account-entry-page .col-md-6 { grid-column: span 6; }
  .account-entry-page .col-md-12 { grid-column: span 12; }

  .account-entry-page label { margin-bottom: 7px; color: var(--tm-muted, #718096); font-size: 12px; font-weight: 900; letter-spacing: .03em; display: block; }

  .account-entry-page .form-control,
  .account-entry-page input[type=text],
  .account-entry-page input[type=number],
  .account-entry-page select,
  .account-entry-page textarea {
    width: 100%; min-height: 44px;
    border: 1px solid var(--tm-line, #dce6f2) !important;
    border-radius: 9px !important;
    color: var(--tm-ink, #18243c);
    background: #fbfdff !important;
    box-shadow: none; font-size: 14px; font-weight: 700;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
  }
  .account-entry-page textarea.form-control { min-height: 96px; resize: vertical; }
  .account-entry-page .form-control:focus,
  .account-entry-page input:focus, .account-entry-page select:focus, .account-entry-page textarea:focus {
    border-color: var(--tm-brand, #1769c2) !important; background: #fff !important;
    box-shadow: 0 0 0 4px rgba(var(--tm-brand-rgb, 23, 105, 194), .12) !important; outline: 0;
  }
  .account-entry-page .help-block { margin-top: 5px; color: #e5484d !important; font-size: 12px; font-weight: 800; }
  .account-entry-page .client-error { margin-top: 5px; color: #e5484d; font-size: 12px; font-weight: 800; }
  .account-entry-page .is-invalid { border-color: #e5484d !important; background: #fffafa !important; box-shadow: 0 0 0 4px rgba(229, 72, 77, .1) !important; }

  .autocomplete { position: relative; display: block; }
  .autocomplete-items {
    position: absolute; z-index: 100; top: calc(100% + 6px); left: 0; right: 0;
    max-height: 290px; overflow: auto;
    border: 1px solid var(--tm-line, #dce6f2); border-radius: 10px; background: #fff;
    box-shadow: 0 18px 38px rgba(24, 36, 60, .18);
    transform-origin: top center;
    animation: acDrop .22s cubic-bezier(.2, .8, .25, 1) both;
  }
  @keyframes acDrop { from { opacity: 0; transform: translateY(-8px) scale(.98); } to { opacity: 1; transform: none; } }
  .autocomplete-items .ac-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 13px; cursor: pointer;
    border-bottom: 1px solid var(--tm-line, #dce6f2); font-weight: 700;
    opacity: 0; transform: translateY(-4px);
    animation: acItem .24s ease forwards;
    transition: background .15s ease, color .15s ease, padding-left .15s ease;
  }
  @keyframes acItem { to { opacity: 1; transform: none; } }
  .autocomplete-items .ac-item:last-child { border-bottom: 0; }
  .autocomplete-items .ac-item:hover, .autocomplete-active {
    color: var(--tm-brand-dark, #0c315f) !important;
    background: var(--tm-brand-soft, #eaf3ff) !important;
    padding-left: 17px;
  }
  .autocomplete-items .ac-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .autocomplete-items .ac-name b { color: var(--tm-brand, #1769c2); background: rgba(var(--tm-brand-rgb, 23, 105, 194), .12); border-radius: 3px; }
  .autocomplete-items .ac-id {
    flex: none; font-size: 11px; font-weight: 800; color: var(--tm-muted, #718096);
    background: #eef2f7; border-radius: 6px; padding: 2px 8px;
  }
  .autocomplete-items .ac-id b { color: var(--tm-brand, #1769c2); background: transparent; }
  .autocomplete-items .ac-empty { padding: 14px 13px; color: var(--tm-muted, #9aa6b6); font-weight: 700; cursor: default; }

  /* Optional bill toggle */
  .bill-toggle {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px; margin-bottom: 18px;
    border: 1px dashed rgba(var(--tm-brand-rgb, 23, 105, 194), .35);
    border-radius: 11px; background: rgba(var(--tm-brand-rgb, 23, 105, 194), .04);
    cursor: pointer; user-select: none; transition: background .18s ease, border-color .18s ease;
  }
  .bill-toggle:hover { background: rgba(var(--tm-brand-rgb, 23, 105, 194), .07); }
  .bill-toggle input { width: 20px; height: 20px; min-height: 0; margin: 0; accent-color: var(--tm-brand, #1769c2); }
  .bill-toggle .bt-text strong { display: block; font-size: 14px; font-weight: 900; }
  .bill-toggle .bt-text span { font-size: 12px; font-weight: 700; color: var(--tm-muted, #718096); }

  /* Smooth reveal for the optional section */
  .collapsey { overflow: hidden; max-height: 0; opacity: 0; transition: max-height .4s ease, opacity .3s ease, margin .3s ease; }
  .collapsey.open { max-height: 1600px; opacity: 1; }

  /* Media uploaders */
  .media-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
  .media-box {
    border: 1px solid var(--tm-line, #dce6f2); border-radius: 11px; padding: 14px;
    background: #fbfdff; transition: border-color .18s ease, box-shadow .18s ease;
  }
  .media-box:hover { border-color: rgba(var(--tm-brand-rgb, 23, 105, 194), .4); box-shadow: 0 8px 22px rgba(24,36,60,.06); }
  .media-box .mb-title { font-size: 13px; font-weight: 900; margin-bottom: 10px; display: flex; align-items: center; gap: 7px; }
  .media-box .mb-title i { color: var(--tm-brand, #1769c2); }
  .media-preview { margin-bottom: 10px; }
  .media-preview img { max-width: 100%; max-height: 150px; border-radius: 9px; cursor: zoom-in; border: 1px solid var(--tm-line, #dce6f2); display: block; }
  .media-preview audio, .media-preview video { width: 100%; border-radius: 9px; }
  .media-preview video { max-height: 170px; background: #000; }
  .media-empty { font-size: 12px; font-weight: 700; color: var(--tm-muted, #9aa6b6); padding: 18px 8px; text-align: center; border: 1px dashed var(--tm-line, #dce6f2); border-radius: 9px; }
  .media-box input[type=file] { font-size: 12px; font-weight: 700; padding: 8px; min-height: 0; }
  .media-remove { display: inline-flex; align-items: center; gap: 6px; margin-top: 9px; font-size: 12px; font-weight: 800; color: #e5484d; cursor: pointer; }
  .media-remove input { width: 16px; height: 16px; min-height: 0; margin: 0; accent-color: #e5484d; }
  .media-hint { margin-top: 7px; font-size: 11px; font-weight: 700; color: var(--tm-muted, #9aa6b6); }

  /* Lightbox */
  .tm-lightbox {
    position: fixed; inset: 0; z-index: 9999; display: none;
    align-items: center; justify-content: center; padding: 30px;
    background: rgba(8, 14, 24, .82); backdrop-filter: blur(2px);
    animation: lbIn .2s ease;
  }
  .tm-lightbox.open { display: flex; }
  @keyframes lbIn { from { opacity: 0; } to { opacity: 1; } }
  .tm-lightbox img { max-width: 92vw; max-height: 88vh; border-radius: 10px; box-shadow: 0 30px 80px rgba(0,0,0,.5); }
  .tm-lightbox .lb-close { position: absolute; top: 18px; right: 24px; color: #fff; font-size: 34px; font-weight: 700; cursor: pointer; line-height: 1; }

  .account-entry-actions {
    display: flex; justify-content: flex-end; gap: 10px;
    margin-top: 4px; padding-top: 16px; border-top: 1px solid var(--tm-line, #dce6f2);
  }
  .account-entry-actions .btn { min-height: 44px; min-width: 120px; border-radius: 9px !important; font-weight: 900; }
  .account-entry-actions .btn-light { background: #eef2f7; color: #43506a; border: 1px solid var(--tm-line, #dce6f2); }

  @media (max-width: 991px) {
    .account-entry-page .form-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .account-entry-page .col-md-3, .account-entry-page .col-md-4, .account-entry-page .col-md-6 { grid-column: span 1; }
    .media-grid { grid-template-columns: 1fr; }
  }
  @media (max-width: 575px) {
    .account-entry-hero { padding: 16px 14px 16px 58px; }
    .account-entry-hero h4 { font-size: 19px; }
    .account-entry-page .form-row { grid-template-columns: 1fr; }
    .account-entry-actions { flex-direction: column; }
    .account-entry-actions .btn, .account-entry-actions a { width: 100%; }
  }
</style>

<main id="myclsid" class="main-content bgc-grey-100 account-entry-page">
  <div id="mainContent">
    <div class="container-fluid entry-shell">

      <div class="account-entry-hero">
        <h4><?php echo $is_edit ? ('Edit ' . $entry_label . ' Entry') : ($entry_label . ' Entry'); ?></h4>
        <p>Record the <?php echo strtolower($entry_label); ?> amount and, optionally, related bill/invoice details and attachments.</p>
      </div>

      <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>

      <?php echo form_open_multipart('', array('id' => 'ciatyform_id')); ?>

        <!-- Hidden auto-carried fields (kept for backend + reports; not user-facing) -->
        <?php
          $khata_val  = $is_edit ? @$result->rokad_entry_no : strtotime(date('d-m-y'));
          $bill_val   = $is_edit ? @$result->challan_no : '';
          $mill_val   = $is_edit ? @$result->mill_id : '';
        ?>
        <input type="hidden" name="khata_entry_no" id="khata_entry_id" value="<?php echo esc($khata_val); ?>">
        <input type="hidden" name="challan_no" id="bill_no" value="<?php echo esc($bill_val); ?>">
        <input type="hidden" name="mill_id" value="<?php echo esc($mill_val); ?>">

        <!-- ============ CORE DEPOSIT DETAILS ============ -->
        <div class="entry-card">
          <div class="card-head"><i class="fa fa-money"></i> <?php echo $entry_label; ?> Details</div>
          <div class="card-body">
            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Billing Date *</label>
                <?php
                  // Show the stored date (Y-m-d) in the datepicker's dd-mm-yyyy
                  // format so it validates on edit without being re-picked.
                  $name = !empty(@$result->rokad_date) ? date('d-m-Y', strtotime($result->rokad_date)) : '';
                  $postvalue = @$_SESSION['billing_date'];
                  // Optional prefill (dd-mm-yyyy) when adding from the Rokad Parcha page.
                  $prefill_date = service('request')->getGet('d');
                  if (!$prefill_date || !preg_match('/^\d{2}-\d{2}-\d{4}$/', $prefill_date)) { $prefill_date = ''; }
                  // Edit -> stored date; Add-from-parcha -> the ?d= date; plain Add -> last used.
                  $date_value = !empty($name) ? $name : (!empty($prefill_date) ? $prefill_date : $postvalue);
                  echo form_input(array('id' => 'datepicker', 'name' => 'billing_date', 'maxlength' => '25', 'class' => 'form-control', 'placeholder' => 'Billing Date', 'value' => $date_value));
                ?>
                <div class="help-block"><?php echo form_error('billing_date'); ?></div>
              </div>

              <div class="form-group col-md-6">
                <label>Account Name *</label>
                <div class="autocomplete">
                  <?php
                    $name = @$result->account_name;
                    $postvalue = @$_POST['account_name'];
                    echo form_input(array('autofocus' => 'autofocus', 'autocomplete' => 'off', 'name' => 'account_name', 'maxlength' => '100', 'class' => 'form-control', 'id' => 'myInput', 'placeholder' => 'Start typing an account name…', 'value' => !empty($postvalue) ? $postvalue : $name));
                  ?>
                </div>
                <div class="help-block"><?php echo form_error('account_name'); ?></div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-4">
                <label>Type *</label>
                <select class="form-control" name="type_of_account">
                  <option <?php if ($sel_type == 'deposit') echo 'selected'; ?> value="deposit">Deposit (जमा)</option>
                  <option <?php if ($sel_type == 'expenses') echo 'selected'; ?> value="expenses">Account (नाम)</option>
                </select>
              </div>
              <div class="form-group col-md-4">
                <label>Karch Amount *</label>
                <?php
                  $name = @$result->karch_amount;
                  $postvalue = @$_POST['karch_amount'];
                  echo form_input(array('type' => 'number', 'step' => '0.01', 'min' => '0', 'name' => 'karch_amount', 'maxlength' => '25', 'class' => 'form-control', 'id' => 'karch_amount', 'placeholder' => 'Amount', 'value' => !empty($postvalue) ? $postvalue : $name));
                ?>
                <div class="help-block"><?php echo form_error('karch_amount'); ?></div>
              </div>
              <div class="form-group col-md-4">
                <label>Status *</label>
                <select class="form-control" name="status">
                  <option <?php if (@$result->status == 'Active') echo 'selected'; ?> value="Active">Active</option>
                  <option <?php if (@$result->status == 'Inactive') echo 'selected'; ?> value="Inactive">Inactive</option>
                </select>
              </div>
            </div>

            <style>
              .pay-modes { display: flex; flex-wrap: wrap; gap: 8px; }
              .pay-pill { display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; border: 1px solid #dbe4ef; border-radius: 999px; background: #f4f7fb; color: #48566b; font-size: 13px; font-weight: 700; cursor: pointer; transition: background .16s ease, border-color .16s ease, color .16s ease, box-shadow .16s ease, transform .12s ease; }
              .pay-pill:hover { border-color: #b9cbe4; background: #eaf1fb; }
              .pay-pill:active { transform: translateY(1px); }
              .pay-pill.active { background: #1f6fe0; border-color: #1f6fe0; color: #fff; box-shadow: 0 6px 14px rgba(31,111,224,.25); }
              .pay-pill i { font-size: 13px; }
            </style>
            <div class="form-row">
              <div class="form-group col-md-12">
                <label>Paid by</label>
                <?php
                  $pay_mode = $is_edit ? trim((string) @$result->payment_mode) : '';
                  if (!empty($_POST['payment_mode'])) { $pay_mode = $_POST['payment_mode']; }
                  if ($pay_mode === '') { $pay_mode = 'Cash'; }
                  $pay_modes = array(
                    'Cash'          => 'fa-money',
                    'UPI'           => 'fa-mobile',
                    'Bank Transfer' => 'fa-university',
                    'Cheque'        => 'fa-file-text-o',
                    'Card'          => 'fa-credit-card',
                    'Other'         => 'fa-ellipsis-h',
                  );
                ?>
                <input type="hidden" name="payment_mode" id="payment_mode" value="<?php echo esc($pay_mode); ?>">
                <div class="pay-modes" id="payModes">
                  <?php foreach ($pay_modes as $pm => $icn): ?>
                    <button type="button" class="pay-pill<?php echo strcasecmp($pay_mode, $pm) === 0 ? ' active' : ''; ?>" data-mode="<?php echo esc($pm); ?>">
                      <i class="fa <?php echo $icn; ?>"></i> <?php echo esc($pm); ?>
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <script>
              (function () {
                var box = document.getElementById('payModes');
                if (!box) return;
                box.addEventListener('click', function (e) {
                  var btn = e.target.closest ? e.target.closest('.pay-pill') : null;
                  if (!btn) return;
                  Array.prototype.forEach.call(box.querySelectorAll('.pay-pill'), function (b) { b.classList.remove('active'); });
                  btn.classList.add('active');
                  document.getElementById('payment_mode').value = btn.getAttribute('data-mode');
                });
              })();
            </script>

            <div class="form-row">
              <div class="form-group col-md-12">
                <label>Remark</label>
                <?php
                  $name = @$result->remark;
                  $postvalue = @$_POST['remark'];
                  echo form_textarea(array('rows' => '2', 'name' => 'remark', 'maxlength' => '1000', 'class' => 'form-control', 'placeholder' => 'Remark (optional)', 'value' => !empty($postvalue) ? $postvalue : $name));
                ?>
                <div class="help-block"><?php echo form_error('remark'); ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- ============ OPTIONAL BILL / INVOICE ============ -->
        <label class="bill-toggle" for="bill_toggle">
          <input type="checkbox" id="bill_toggle" <?php echo $bill_has_data ? 'checked' : ''; ?>>
          <span class="bt-text">
            <strong>This entry is related to an Invoice / Bill</strong>
            <span>Show party, invoice, quantity, rate &amp; truck fields (all optional).</span>
          </span>
        </label>

        <div class="collapsey <?php echo $bill_has_data ? 'open' : ''; ?>" id="bill_section">
          <div class="entry-card" style="animation:none">
            <div class="card-head"><i class="fa fa-file-text-o"></i> Bill / Invoice Details <small>Optional</small></div>
            <div class="card-body">
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label>Party Account Name</label>
                  <div class="autocomplete">
                    <?php
                      $name = @$result->party_account_no;
                      $postvalue = @$_POST['party_account_no'];
                      echo form_input(array('autocomplete' => 'off', 'name' => 'party_account_no', 'maxlength' => '100', 'class' => 'form-control', 'id' => 'myInput02', 'placeholder' => 'Select party account…', 'value' => !empty($postvalue) ? $postvalue : $name));
                    ?>
                  </div>
                  <div class="client-error-holder"></div>
                </div>
                <div class="form-group col-md-3">
                  <label>Invoice No</label>
                  <?php
                    $name = @$result->party_invoice_no;
                    $postvalue = @$_POST['party_invoice_no'];
                    echo form_input(array('name' => 'party_invoice_no', 'maxlength' => '100', 'class' => 'form-control', 'placeholder' => 'Invoice No', 'value' => !empty($postvalue) ? $postvalue : $name));
                  ?>
                </div>
                <div class="form-group col-md-3">
                  <label>Quantity</label>
                  <?php
                    $name = @$result->quantity;
                    $postvalue = @$_POST['quantity'];
                    echo form_input(array('type' => 'number', 'step' => '0.01', 'min' => '0', 'name' => 'quantity', 'maxlength' => '100', 'class' => 'form-control', 'placeholder' => 'Quantity', 'value' => !empty($postvalue) ? $postvalue : $name));
                  ?>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-3">
                  <label>Rate</label>
                  <?php
                    $name = @$result->rate;
                    $postvalue = @$_POST['rate'];
                    echo form_input(array('type' => 'number', 'step' => '0.01', 'min' => '0', 'name' => 'rate', 'maxlength' => '25', 'class' => 'form-control', 'placeholder' => 'Rate', 'value' => !empty($postvalue) ? $postvalue : $name));
                  ?>
                </div>
                <div class="form-group col-md-3">
                  <label>Truck No</label>
                  <?php
                    $name = @$result->truck_no;
                    $postvalue = @$_POST['truck_no'];
                    echo form_input(array('name' => 'truck_no', 'maxlength' => '100', 'class' => 'form-control', 'placeholder' => 'Truck No', 'value' => !empty($postvalue) ? $postvalue : $name));
                  ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ============ ATTACHMENTS (hidden until enabled) ============ -->
        <label class="bill-toggle" for="attach_toggle">
          <input type="checkbox" id="attach_toggle" <?php echo $attach_has_data ? 'checked' : ''; ?>>
          <span class="bt-text">
            <strong>Add attachments</strong>
            <span>Optional — image, voice &amp; video. Tick to show the upload options.</span>
          </span>
        </label>

        <div class="collapsey <?php echo $attach_has_data ? 'open' : ''; ?>" id="attach_section">
          <div class="entry-card" style="animation:none">
            <div class="card-head"><i class="fa fa-paperclip"></i> Attachments <small>Image, voice &amp; video — optional</small></div>
          <div class="card-body">
            <div class="media-grid">

              <!-- IMAGE -->
              <div class="media-box">
                <div class="mb-title"><i class="fa fa-picture-o"></i> Image / Picture</div>
                <div class="media-preview" id="image_preview">
                  <?php if ($image_path): ?>
                    <img src="<?php echo base_url($image_path); ?>" alt="Deposit image" class="zoomable">
                  <?php else: ?>
                    <div class="media-empty" data-empty>No image attached</div>
                  <?php endif; ?>
                </div>
                <input type="file" name="image_file" id="image_file" accept="image/*" class="form-control">
                <div class="media-hint">JPG, PNG, WEBP, GIF · max 5 MB. Click the image to zoom.</div>
                <?php if ($image_path): ?>
                  <label class="media-remove"><input type="checkbox" name="remove_image" value="1"> Remove current image</label>
                <?php endif; ?>
              </div>

              <!-- VOICE -->
              <div class="media-box">
                <div class="mb-title"><i class="fa fa-microphone"></i> Voice Recording</div>
                <div class="media-preview" id="voice_preview">
                  <?php if ($voice_path): ?>
                    <audio controls preload="none" src="<?php echo base_url($voice_path); ?>"></audio>
                  <?php else: ?>
                    <div class="media-empty" data-empty>No voice recording</div>
                  <?php endif; ?>
                </div>
                <input type="file" name="voice_file" id="voice_file" accept="audio/*" class="form-control">
                <div class="media-hint">WEBM, MP3, M4A, AAC, OGG, WAV · max 15 MB.</div>
                <?php if ($voice_path): ?>
                  <label class="media-remove"><input type="checkbox" name="remove_voice" value="1"> Remove current voice</label>
                <?php endif; ?>
              </div>

              <!-- VIDEO -->
              <div class="media-box">
                <div class="mb-title"><i class="fa fa-video-camera"></i> Video Recording</div>
                <div class="media-preview" id="video_preview">
                  <?php if ($video_path): ?>
                    <video controls preload="none" src="<?php echo base_url($video_path); ?>"></video>
                  <?php else: ?>
                    <div class="media-empty" data-empty>No video recording</div>
                  <?php endif; ?>
                </div>
                <input type="file" name="video_file" id="video_file" accept="video/*" class="form-control">
                <div class="media-hint">MP4, WEBM, MOV, AVI, 3GP, MKV · max 60 MB.</div>
                <?php if ($video_path): ?>
                  <label class="media-remove"><input type="checkbox" name="remove_video" value="1"> Remove current video</label>
                <?php endif; ?>
              </div>

            </div>
          </div>
          </div>
        </div>

        <div class="account-entry-actions">
          <a href="<?php echo base_url('admin/dashboard/'); ?>"><button type="button" class="btn btn-light">Cancel</button></a>
          <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Update Entry' : ('Save ' . $entry_label); ?></button>
        </div>

      </form>
    </div>
  </div>
</main>

<!-- Image zoom lightbox -->
<div class="tm-lightbox" id="tm_lightbox"><span class="lb-close">&times;</span><img src="" alt="preview"></div>

<script>
  var TM_IS_EDIT = <?php echo $is_edit ? 'true' : 'false'; ?>;

  /* ---------- account search: by NAME or ID, substring match, cached + animated ----------
     The list is fetched once and reused (the old code re-fetched on every keystroke and
     filtered the previous, stale response). Stored value stays "name_id" so the backend
     parsing (explode('_') / /_(\d+)$/) is unchanged. */
  var ACCOUNT_CACHE = null, ACCOUNT_LOADING = null;

  function loadAccounts() {
    if (ACCOUNT_CACHE) return $.Deferred().resolve(ACCOUNT_CACHE).promise();
    if (ACCOUNT_LOADING) return ACCOUNT_LOADING;
    ACCOUNT_LOADING = $.ajax({
      url: "<?php echo base_url(); ?>admin/billing/account_options",
      type: "POST", dataType: 'json'
    }).then(function (data) {
      ACCOUNT_CACHE = $.isArray(data) ? data : [];
      return ACCOUNT_CACHE;
    });
    return ACCOUNT_LOADING;
  }

  function acEscapeHtml(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
  function acEscapeRe(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
  function acHighlight(text, q) {
    var safe = acEscapeHtml(text);
    if (!q) return safe;
    return safe.replace(new RegExp('(' + acEscapeRe(q) + ')', 'ig'), '<b>$1</b>');
  }

  function autocomplete(inp) {
    if (!inp) return;
    var currentFocus = -1;

    inp.addEventListener("input", function () {
      var val = this.value.trim();
      closeAllLists();
      if (!val) return;
      currentFocus = -1;
      loadAccounts().then(function (arr) {
        if (inp.value.trim() !== val) return;           // field changed while loading
        var q = val.toLowerCase();
        var matches = arr.filter(function (a) {
          var name = (a.name || '').toLowerCase();
          var id = String(a.account_id == null ? '' : a.account_id);
          return name.indexOf(q) !== -1 || id.indexOf(q) !== -1;
        });
        // Prefer name-prefix matches first, then the rest; cap the list.
        matches.sort(function (a, b) {
          var an = (a.name || '').toLowerCase().indexOf(q) === 0 ? 0 : 1;
          var bn = (b.name || '').toLowerCase().indexOf(q) === 0 ? 0 : 1;
          return an - bn;
        });
        renderList(matches.slice(0, 50), val);
      });
    });

    inp.addEventListener("keydown", function (e) {
      var list = document.getElementById(this.id + "autocomplete-list");
      var x = list ? list.getElementsByClassName("ac-item") : null;
      if (e.keyCode == 40) { currentFocus++; addActive(x); }
      else if (e.keyCode == 38) { currentFocus--; addActive(x); }
      else if (e.keyCode == 13) { if (x && x.length && currentFocus > -1) { e.preventDefault(); x[currentFocus].click(); } }
      else if (e.keyCode == 27) { closeAllLists(); }
    });

    function addActive(x) {
      if (!x || !x.length) return;
      removeActive(x);
      if (currentFocus >= x.length) currentFocus = 0;
      if (currentFocus < 0) currentFocus = x.length - 1;
      x[currentFocus].classList.add("autocomplete-active");
      x[currentFocus].scrollIntoView({ block: 'nearest' });
    }
    function removeActive(x) { for (var i = 0; i < x.length; i++) x[i].classList.remove("autocomplete-active"); }

    function renderList(matches, val) {
      var box = document.createElement("DIV");
      box.id = inp.id + "autocomplete-list";
      box.className = "autocomplete-items";
      inp.parentNode.appendChild(box);

      if (!matches.length) {
        var none = document.createElement("DIV");
        none.className = "ac-empty";
        none.textContent = 'No account matches "' + val + '"';
        box.appendChild(none);
        return;
      }

      matches.forEach(function (item, i) {
        var row = document.createElement("DIV");
        row.className = "ac-item";
        row.style.animationDelay = Math.min(i * 16, 200) + 'ms';
        row.innerHTML =
          '<span class="ac-name">' + acHighlight(item.name || '', val) + '</span>' +
          '<span class="ac-id">#' + acHighlight(item.account_id || '', val) + '</span>';
        row.setAttribute('data-value', (item.name || '') + '_' + (item.account_id || ''));
        row.addEventListener("click", function () {
          inp.value = this.getAttribute('data-value');
          closeAllLists();
          $(inp).trigger('change');
        });
        box.appendChild(row);
      });
    }

    function closeAllLists(elmnt) {
      var x = document.getElementsByClassName("autocomplete-items");
      for (var i = x.length - 1; i >= 0; i--) {
        if (elmnt != x[i] && elmnt != inp) x[i].parentNode.removeChild(x[i]);
      }
    }
    document.addEventListener("click", function (e) { closeAllLists(e.target); });
  }
  autocomplete(document.getElementById("myInput"));
  autocomplete(document.getElementById("myInput02"));

  /* ---------- datepicker (constrained to the financial year) ---------- */
  $(function () {
    var fy = "<?php echo fy()->FY; ?>".split("-");
    var startYear = parseInt(fy[0]); var endYear = parseInt(fy[1]);
    $("#datepicker").datepicker({
      dateFormat: "dd-mm-yy",
      minDate: new Date(startYear, 3, 1),
      maxDate: new Date(endYear, 2, 31)
    });
    // Default to today only when the field wasn't already pre-filled
    // (edit value, last-used, or a date passed from the Rokad Parcha page).
    if (!TM_IS_EDIT && !$.trim($("#datepicker").val())) { $("#datepicker").datepicker("setDate", new Date()); }
  });

  /* ---------- auto bill number (add only; never overwrite on edit) ---------- */
  if (!TM_IS_EDIT) {
    $.ajax({
      url: "<?php echo base_url(); ?>admin/billing/billingCyle",
      type: "POST", dataType: 'json',
      success: function (a) {
        var khata_id = $('#khata_entry_id').val();
        $('#bill_no').val(khata_id + "/" + a);
      },
      error: function () {}
    });
  }

  /* ---------- optional bill section toggle ---------- */
  $('#bill_toggle').on('change', function () {
    $('#bill_section').toggleClass('open', this.checked);
  });

  /* ---------- optional attachments section toggle (hidden by default) ---------- */
  $('#attach_toggle').on('change', function () {
    $('#attach_section').toggleClass('open', this.checked);
  });

  /* ---------- media: live preview of newly chosen files ---------- */
  function previewChosenFile(input, holderId, kind) {
    var file = input.files && input.files[0];
    var holder = document.getElementById(holderId);
    if (!file) return;
    var url = URL.createObjectURL(file);
    var html = '';
    if (kind === 'image') html = '<img src="' + url + '" class="zoomable" alt="preview">';
    else if (kind === 'voice') html = '<audio controls src="' + url + '"></audio>';
    else if (kind === 'video') html = '<video controls src="' + url + '"></video>';
    holder.innerHTML = html;
    // Choosing a new file cancels any pending "remove" for that slot.
    $(input).closest('.media-box').find('input[type=checkbox]').prop('checked', false);
  }
  $('#image_file').on('change', function () { previewChosenFile(this, 'image_preview', 'image'); });
  $('#voice_file').on('change', function () { previewChosenFile(this, 'voice_preview', 'voice'); });
  $('#video_file').on('change', function () { previewChosenFile(this, 'video_preview', 'video'); });

  /* ---------- image zoom lightbox ---------- */
  var $lb = $('#tm_lightbox');
  $(document).on('click', '.zoomable', function () { $lb.find('img').attr('src', this.src); $lb.addClass('open'); });
  $lb.on('click', function () { $lb.removeClass('open'); });

  /* ---------- client-side validation (only the fields that are truly required) ---------- */
  function showErr($f, msg) { $f.addClass('is-invalid'); $f.closest('.form-group').find('.client-error').remove(); $f.after('<div class="client-error">' + msg + '</div>'); }
  function isPositive(v) { return v !== '' && !isNaN(v) && parseFloat(v) > 0; }
  function isAutoSel(v) { return /^[^_]+_\d+$/.test(v); }
  function parseDmy(v) {
    var p = v.split('-'); if (p.length !== 3) return null;
    var d = parseInt(p[0], 10), m = parseInt(p[1], 10) - 1, y = parseInt(p[2], 10);
    var dt = new Date(y, m, d);
    if (dt.getFullYear() !== y || dt.getMonth() !== m || dt.getDate() !== d) return null;
    return dt;
  }

  function validateDepositForm() {
    var ok = true;
    var fy = "<?php echo fy()->FY; ?>".split("-");
    var fyStart = new Date(parseInt(fy[0], 10), 3, 1);
    var fyEnd = new Date(parseInt(fy[1], 10), 2, 31);
    $('.client-error').remove(); $('.is-invalid').removeClass('is-invalid');

    var $date = $('[name="billing_date"]'); var dv = $.trim($date.val()); var d = parseDmy(dv);
    if (dv === '') { showErr($date, 'Please select Billing Date'); ok = false; }
    else if (!d) { showErr($date, 'Billing Date must be dd-mm-yyyy'); ok = false; }
    else if (d < fyStart || d > fyEnd) { showErr($date, 'Billing Date must be within the current financial year'); ok = false; }

    var $acc = $('[name="account_name"]');
    if ($.trim($acc.val()) === '') { showErr($acc, 'Please enter Account Name'); ok = false; }

    var $amt = $('[name="karch_amount"]');
    if (!isPositive($.trim($amt.val()))) { showErr($amt, 'Karch Amount must be greater than 0'); ok = false; }

    // Optional bill fields: only validate when the section is enabled AND a value is present.
    if ($('#bill_toggle').is(':checked')) {
      var $party = $('[name="party_account_no"]'); var pv = $.trim($party.val());
      if (pv !== '' && !isAutoSel(pv)) { showErr($party, 'Select Party Account Name from the suggestion list'); ok = false; }
      var $qty = $('[name="quantity"]'); var qv = $.trim($qty.val());
      if (qv !== '' && !isPositive(qv)) { showErr($qty, 'Quantity must be greater than 0'); ok = false; }
      var $rate = $('[name="rate"]'); var rv = $.trim($rate.val());
      if (rv !== '' && !isPositive(rv)) { showErr($rate, 'Rate must be greater than 0'); ok = false; }
    }

    if (!ok) {
      var $first = $('.is-invalid').first();
      if ($first.length) { $('html, body').animate({ scrollTop: $first.offset().top - 120 }, 250); $first.focus(); }
    }
    return ok;
  }

  $('#ciatyform_id').on('submit', function (e) { if (!validateDepositForm()) e.preventDefault(); });
  $('#ciatyform_id').on('input change', 'input, select, textarea', function () {
    $(this).removeClass('is-invalid'); $(this).closest('.form-group').find('.client-error').remove();
  });
  $('[name="truck_no"], [name="party_invoice_no"]').on('input', function () { this.value = this.value.toUpperCase(); });
</script>
