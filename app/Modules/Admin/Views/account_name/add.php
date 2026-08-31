<?php
/** Add Account (admin/account_name/add) — functional CI4 port. Saves to
 *  aa_account_name; ledger provisioning + GST auto-parse land with accounting. */
helper(['url', 'form']);
$v = fn($f, $d = '') => esc(old($f, $d));
?>
<style>
  .an-page{color:#1e2a3d;padding:22px}.an-shell{margin:0 auto;max-width:1000px}
  .an-hero{align-items:center;background:linear-gradient(135deg,#1769c2,#0f4e97);border-radius:14px;box-shadow:0 16px 40px rgba(23,105,194,.22);color:#fff;display:flex;gap:14px;margin-bottom:16px;padding:18px 22px}
  .an-hero-ic{align-items:center;background:rgba(255,255,255,.16);border-radius:12px;display:flex;font-size:20px;height:46px;justify-content:center;width:46px}
  .an-hero h4{font-size:20px;font-weight:900;margin:0}.an-hero p{font-size:12.5px;margin:2px 0 0;opacity:.9}
  .an-card{background:#fff;border:1px solid #e5ecf5;border-radius:14px;box-shadow:0 14px 34px rgba(24,36,60,.07);padding:22px;margin-bottom:16px}
  .an-card h5{font-size:13px;font-weight:900;color:#1769c2;text-transform:uppercase;letter-spacing:.03em;margin:0 0 14px;border-bottom:1px solid #eef2f7;padding-bottom:8px}
  .an-grid{display:grid;gap:14px;grid-template-columns:repeat(3,1fr)}
  .an-grid .full{grid-column:1/-1}.an-grid .half{grid-column:span 2}
  .an-page label{display:block;font-size:11px;font-weight:800;color:#516174;text-transform:uppercase;letter-spacing:.02em;margin-bottom:5px}
  .an-page input,.an-page select,.an-page textarea{width:100%;min-height:42px;border:1.5px solid #dce6f2;border-radius:9px;padding:9px 12px;background:#fbfdff;font-weight:700;color:#1e2a3d}
  .an-page input:focus,.an-page select:focus,.an-page textarea:focus{border-color:#1769c2;background:#fff;box-shadow:0 0 0 4px rgba(23,105,194,.12);outline:0}
  .an-page textarea{min-height:70px;resize:vertical}
  .an-req{color:#e11d48}
  .an-actions{display:flex;gap:10px;flex-wrap:wrap}
  .an-actions .btn{min-height:44px;min-width:150px;border-radius:9px;font-weight:800}
  .an-check{display:inline-flex;align-items:center;gap:8px;font-weight:800;font-size:13px}
  @media(max-width:820px){.an-grid{grid-template-columns:1fr 1fr}.an-grid .half{grid-column:1/-1}}
  @media(max-width:560px){.an-page{padding:14px}.an-grid{grid-template-columns:1fr}}
</style>

<main class="main-content bgc-grey-100 an-page">
  <div id="mainContent">
    <div class="container-fluid an-shell">

      <section class="an-hero">
        <span class="an-hero-ic"><i class="ti-user"></i></span>
        <div><h4>Add Account</h4><p>Create a trade party / farmer ledger account.</p></div>
      </section>

      <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

      <?= form_open(base_url('admin/account_name/add'), ['id' => 'accForm', 'autocomplete' => 'off']) ?>

        <div class="an-card">
          <h5>Identity</h5>
          <div class="an-grid">
            <div class="half"><label>Account Name <span class="an-req">*</span></label><input type="text" name="account_name" value="<?= $v('account_name') ?>" maxlength="255" required></div>
            <div>
              <label>Registration Type</label>
              <select name="registration_type" id="regType">
                <?php $rt = old('registration_type', 'gst'); foreach (['gst' => 'GST Registered', 'pan' => 'PAN (Unregistered)', 'unregistered' => 'Unregistered'] as $k => $lbl): ?>
                  <option value="<?= $k ?>" <?= $rt === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div><label>GSTIN <span class="an-req gst-req">*</span></label><input type="text" name="purchaser_gst_no" id="gstNo" value="<?= $v('purchaser_gst_no') ?>" maxlength="15" style="text-transform:uppercase"></div>
            <div><label>PAN</label><input type="text" name="pan_card" value="<?= $v('pan_card') ?>" maxlength="10" style="text-transform:uppercase"></div>
            <div>
              <label>Account Type</label>
              <input type="text" name="account_type" list="accTypes" value="<?= $v('account_type') ?>" placeholder="e.g. Sundry Debtor / Creditor">
              <datalist id="accTypes">
                <option value="Sundry Debtor"></option><option value="Sundry Creditor"></option><option value="Bank Account"></option>
                <option value="Cash"></option><option value="Expense"></option><option value="Income"></option><option value="Farmer"></option>
              </datalist>
            </div>
            <div><label>Is Kisan (Farmer)?</label><div style="padding-top:8px"><label class="an-check"><input type="checkbox" name="is_Kisan" value="1" style="width:18px;height:18px" <?= old('is_Kisan') ? 'checked' : '' ?>> This is a farmer account</label></div></div>
          </div>
        </div>

        <div class="an-card">
          <h5>Address & Contact</h5>
          <div class="an-grid">
            <div><label>State</label><input type="text" name="state" value="<?= $v('state') ?>"></div>
            <div><label>State Code</label><input type="text" name="state_code" value="<?= $v('state_code') ?>" maxlength="2" placeholder="auto from GSTIN"></div>
            <div><label>City</label><input type="text" name="city" value="<?= $v('city') ?>"></div>
            <div><label>Pin Code</label><input type="text" name="pin_code" value="<?= $v('pin_code') ?>" maxlength="6"></div>
            <div class="half"><label>Address</label><input type="text" name="purchaser_address" value="<?= $v('purchaser_address') ?>"></div>
            <div><label>Contact Person</label><input type="text" name="contact_person_name" value="<?= $v('contact_person_name') ?>"></div>
            <div><label>Contact Number</label><input type="text" name="contact_person_number" value="<?= $v('contact_person_number') ?>" maxlength="15"></div>
            <div><label>Email</label><input type="email" name="email_id" value="<?= $v('email_id') ?>"></div>
          </div>
        </div>

        <div class="an-card">
          <h5>Bank Details (optional)</h5>
          <div class="an-grid">
            <div><label>Bank Name</label><input type="text" name="bank_name" value="<?= $v('bank_name') ?>"></div>
            <div><label>IFSC</label><input type="text" name="ifsc_code" value="<?= $v('ifsc_code') ?>" maxlength="11" style="text-transform:uppercase"></div>
            <div><label>Account No.</label><input type="text" name="purchaser_account_no" value="<?= $v('purchaser_account_no') ?>"></div>
            <div>
              <label>Status</label>
              <select name="status"><?php $st = old('status', 'Active'); ?>
                <option value="Active" <?= $st === 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= $st === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>
          </div>
        </div>

        <div class="an-actions">
          <button type="submit" class="btn btn-primary"><i class="ti-check"></i> Save Account</button>
          <a href="<?= base_url('admin/account_name/listing') ?>" class="btn btn-default">Cancel</a>
        </div>
      <?= form_close() ?>
    </div>
  </div>
</main>

<script>
  (function(){
    var reg = document.getElementById('regType'), gst = document.getElementById('gstNo'),
        req = document.querySelector('.gst-req');
    function sync(){
      var isGst = reg.value === 'gst';
      if (req) req.style.display = isGst ? '' : 'none';
      gst.required = isGst;
      gst.closest('div').style.opacity = (reg.value === 'unregistered') ? '.55' : '1';
    }
    reg.addEventListener('change', sync); sync();
    gst.addEventListener('input', function(){ this.value = this.value.toUpperCase(); });
  })();
</script>
