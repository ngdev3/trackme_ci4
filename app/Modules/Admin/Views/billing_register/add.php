<link href="<?php echo base_url(); ?>assets/global/css/components-rounded.css" rel="stylesheet" type="text/css"/>
<?php
// Faithful CI4 port of admin/billing_register/add. Inline validation-error and
// flashdata renderers stand in for CI3's form_error()/get_flashdata().
$err = function ($field) use ($validation) {
    return ($validation && $validation->hasError($field))
        ? '<div class="err">' . esc($validation->getError($field)) . '</div>' : '';
};
?>
<style>
    .bra { padding: 22px; color: #182135; }
    .bra-shell { max-width: 1100px; margin: 0 auto; }
    .bra-hero { padding: 22px 24px; border-radius: 14px 14px 0 0; color: #fff;
        background: radial-gradient(circle at 88% -20%, rgba(240,160,32,.4), transparent 34%), linear-gradient(125deg, #0c315f, #174777 55%, #221638); }
    .bra-hero h1 { margin: 0; font-size: 23px; font-weight: 850; }
    .bra-hero p { margin: 6px 0 0; font-size: 13px; color: rgba(238,247,255,.82); font-weight: 600; }
    .bra-card { background: #fff; border: 1px solid #e3e9f2; border-top: 0; border-radius: 0 0 14px 14px; box-shadow: 0 16px 40px rgba(20,32,56,.08); }
    .bra-grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 16px; padding: 22px 24px; }
    .bra-col-2 { grid-column: span 2; }
    .bra-col-full { grid-column: 1 / -1; }
    .bra .field label { display: block; margin-bottom: 6px; font-size: 12px; font-weight: 800; color: #34425c; }
    .bra .field .req { color: #d6354f; }
    .bra .form-control { width: 100%; min-height: 44px; padding: 10px 13px; border: 1px solid #dce6f2; border-radius: 10px; background: #fbfdff; font-weight: 600; color: #18243c;
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease; }
    .bra .form-control:focus { border-color: #1769c2; background: #fff; box-shadow: 0 0 0 4px rgba(23,105,194,.12); outline: 0; }
    .bra textarea.form-control { min-height: 90px; resize: vertical; }
    .bra .err { margin-top: 5px; color: #d6354f; font-size: 12px; font-weight: 700; }
    .bra-bar { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #e3e9f2; background: #fbfdff; border-radius: 0 0 14px 14px; }
    .bra-bar .btn { min-height: 44px; padding: 11px 22px; border-radius: 11px; font-weight: 850; transition: transform .16s ease, box-shadow .16s ease; }
    .bra-bar .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(20,32,56,.16); }
    .bra-bar .btn-primary { border: 0; background: linear-gradient(135deg, #1769c2, #0c315f); }
    .bra .select2-container { width: 100% !important; }
    @media (max-width: 800px){ .bra-grid { grid-template-columns: 1fr; } .bra-col-2 { grid-column: auto; } }
</style>

<main class="main-content bgc-grey-100 bra">
    <div id="mainContent">
        <div class="container-fluid bra-shell">
            <section class="bra-hero">
                <h1>Add Billing Entry</h1>
                <p>Record a cash / kisan billing entry into the register (aa_billing).</p>
            </section>
            <div class="bra-card">
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success" style="margin:16px 24px 0;"><?= esc(session()->getFlashdata('success')); ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger" style="margin:16px 24px 0;"><?= esc(session()->getFlashdata('error')); ?></div>
                <?php endif; ?>
                <?php echo form_open(current_url(), ['id' => 'brgForm']); ?>
                    <div class="bra-grid">
                        <div class="field">
                            <label>Billing Date <span class="req">*</span></label>
                            <input type="text" name="billing_date" id="billing_date" class="form-control" placeholder="dd-mm-yyyy" value="<?= set_value('billing_date'); ?>">
                            <?= $err('billing_date'); ?>
                        </div>

                        <div class="field">
                            <label>Billing Type <span class="req">*</span></label>
                            <select name="billing_type" class="form-control">
                                <option value="cash"  <?= set_select('billing_type', 'cash', true); ?>>Cash</option>
                                <option value="kisan" <?= set_select('billing_type', 'kisan'); ?>>Kisan</option>
                            </select>
                            <?= $err('billing_type'); ?>
                        </div>

                        <div class="field">
                            <label>Account Type <span class="req">*</span></label>
                            <select name="type_of_account" class="form-control">
                                <option value="deposit"  <?= set_select('type_of_account', 'deposit', true); ?>>Deposit</option>
                                <option value="expenses" <?= set_select('type_of_account', 'expenses'); ?>>Expenses</option>
                                <option value="balance"  <?= set_select('type_of_account', 'balance'); ?>>Balance</option>
                            </select>
                            <?= $err('type_of_account'); ?>
                        </div>

                        <div class="field bra-col-2">
                            <label>Purchaser Account <span class="req">*</span></label>
                            <select name="purchaser_account" id="purchaser_account" class="form-control brg-select2">
                                <option value="">-- Select account --</option>
                                <?php foreach ($accounts as $a): ?>
                                    <option value="<?= esc($a->account_id); ?>" <?= set_select('purchaser_account', (string) $a->account_id); ?>>
                                        <?= esc($a->name) . ' (#' . esc($a->account_id) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= $err('purchaser_account'); ?>
                        </div>

                        <div class="field">
                            <label>Final Amount <span class="req">*</span></label>
                            <input type="number" step="0.01" min="0" name="final_amount" class="form-control" placeholder="0.00" value="<?= set_value('final_amount'); ?>">
                            <?= $err('final_amount'); ?>
                        </div>

                        <div class="field">
                            <label>Khata Entry No</label>
                            <input type="text" name="khata_entry_no" class="form-control" placeholder="Khata entry no" value="<?= set_value('khata_entry_no'); ?>">
                        </div>

                        <div class="field">
                            <label>Challan No</label>
                            <input type="text" name="challan_no" class="form-control" placeholder="Challan no" value="<?= set_value('challan_no'); ?>">
                        </div>

                        <div class="field">
                            <label>Rate</label>
                            <input type="text" name="rate" class="form-control" placeholder="Rate" value="<?= set_value('rate'); ?>">
                        </div>

                        <div class="field">
                            <label>Total Weight</label>
                            <input type="text" name="total_weight" class="form-control" placeholder="Total weight" value="<?= set_value('total_weight'); ?>">
                        </div>

                        <div class="field">
                            <label>Total Katti</label>
                            <input type="text" name="total_katti" class="form-control" placeholder="Total katti" value="<?= set_value('total_katti'); ?>">
                        </div>

                        <div class="field bra-col-full">
                            <label>Remark</label>
                            <textarea name="remark" class="form-control" placeholder="Optional note"><?= set_value('remark'); ?></textarea>
                        </div>
                    </div>

                    <div class="bra-bar">
                        <a href="<?= base_url('admin/billing_register/listing'); ?>" class="btn btn-default">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Save Entry</button>
                    </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</main>

<script>
    $(function () {
        $("#billing_date").datepicker({ dateFormat: "dd-mm-yy", setDate: new Date() });
        if (!$("#billing_date").val()) { $("#billing_date").datepicker("setDate", new Date()); }
        if ($.fn.select2) { $(".brg-select2").select2({ placeholder: "-- Select account --" }); }
    });
</script>
