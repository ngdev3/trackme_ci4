<?php
$is_edit = !empty($result);
$form_action = $is_edit ? base_url('admin/attendance/employee_edit/' . ID_encode($result->employee_id)) : base_url('admin/attendance/employee_add');
$employee_code = $is_edit ? $result->employee_code : set_value('employee_code');
$employee_name = $is_edit ? $result->employee_name : set_value('employee_name');
$mobile = $is_edit ? $result->mobile : set_value('mobile');
$designation = $is_edit ? $result->designation : set_value('designation');
$joining_date = $is_edit && !empty($result->joining_date) ? date('Y-m-d', strtotime($result->joining_date)) : set_value('joining_date');
$salary = $is_edit ? $result->salary : set_value('salary');
$address = $is_edit ? $result->address : set_value('address');
$status = $is_edit ? $result->status : set_value('status', 'Active');
?>

<style>
    .emp-form{--ink:var(--tm-ink,#18243c);--line:var(--tm-line,#dce6f2);--brand:var(--tm-brand,#1769c2);--brand-dark:var(--tm-brand-dark,#0c315f)}.emp-form-shell{padding:0!important;border:0!important;background:transparent!important}.emp-form-card{overflow:hidden;border:1px solid var(--line);border-radius:8px;background:#fff;box-shadow:0 18px 42px rgba(24,36,60,.08)}.emp-form-head{display:flex;justify-content:space-between;gap:16px;align-items:center;padding:20px 22px;color:#fff;background:linear-gradient(135deg,var(--brand-dark),color-mix(in srgb,var(--brand) 58%,#101827))}.emp-form-head h1{margin:0;color:#fff;font-size:26px;font-weight:850}.emp-form-body{padding:20px 22px}.emp-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.emp-field label{display:block;margin:0 0 7px;color:var(--ink);font-size:12px;font-weight:900}.emp-field.is-wide{grid-column:span 2}.emp-field .form-control{height:44px;border:1px solid var(--line);border-radius:8px;background:#fbfdff;box-shadow:none}.emp-field textarea.form-control{min-height:96px}.emp-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px;padding-top:18px;border-top:1px solid var(--line)}.emp-btn{min-height:42px;display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:8px;font-weight:900;text-decoration:none!important;border:1px solid var(--brand);background:var(--brand);color:#fff}.emp-btn.light{border-color:#fff;background:#fff;color:var(--brand-dark)!important}.emp-error{display:block;margin-top:6px;color:#e5484d;font-size:12px;font-weight:800}@media(max-width:991px){.emp-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:640px){.emp-grid{grid-template-columns:1fr}.emp-field.is-wide{grid-column:auto}.emp-form-head,.emp-actions{align-items:stretch;flex-direction:column}}
</style>

<main class="main-content bgc-grey-100 emp-form">
    <div id="mainContent">
        <div class="container-fluid">
            <?= get_flashdata(); ?>
            <div class="emp-form-shell bgc-white bd bdrs-3 p-20 mB-20">
                <section class="emp-form-card">
                    <div class="emp-form-head">
                        <h1><?= $is_edit ? 'Edit Employee' : 'Add Employee'; ?></h1>
                        <a href="<?= base_url('admin/attendance/employee_listing'); ?>" class="emp-btn light"><i class="fa fa-list"></i> Employee Listing</a>
                    </div>
                    <div class="emp-form-body">
                        <?php echo form_open_multipart($form_action); ?>
                        <div class="emp-grid">
                            <div class="emp-field"><label>Employee Code</label><input type="text" name="employee_code" class="form-control" value="<?= htmlspecialchars($employee_code); ?>"><span class="emp-error"><?= form_error('employee_code'); ?></span></div>
                            <div class="emp-field is-wide"><label>Employee Name *</label><input type="text" name="employee_name" class="form-control" value="<?= htmlspecialchars($employee_name); ?>"><span class="emp-error"><?= form_error('employee_name'); ?></span></div>
                            <div class="emp-field"><label>Mobile</label><input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($mobile); ?>"><span class="emp-error"><?= form_error('mobile'); ?></span></div>
                            <div class="emp-field"><label>Designation</label><input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($designation); ?>"><span class="emp-error"><?= form_error('designation'); ?></span></div>
                            <div class="emp-field"><label>Joining Date</label><input type="text" name="joining_date" class="form-control date-picker" value="<?= htmlspecialchars($joining_date); ?>" placeholder="YYYY-MM-DD"><span class="emp-error"><?= form_error('joining_date'); ?></span></div>
                            <div class="emp-field"><label>Salary</label><input type="number" step="0.01" name="salary" class="form-control" value="<?= htmlspecialchars($salary); ?>"><span class="emp-error"><?= form_error('salary'); ?></span></div>
                            <div class="emp-field"><label>Status *</label><select name="status" class="form-control"><option value="Active" <?= $status == 'Active' ? 'selected' : ''; ?>>Active</option><option value="Inactive" <?= $status == 'Inactive' ? 'selected' : ''; ?>>Inactive</option></select><span class="emp-error"><?= form_error('status'); ?></span></div>
                            <div class="emp-field is-wide"><label>Address</label><textarea name="address" class="form-control"><?= htmlspecialchars($address); ?></textarea><span class="emp-error"><?= form_error('address'); ?></span></div>
                        </div>
                        <div class="emp-actions">
                            <a href="<?= base_url('admin/attendance/employee_listing'); ?>" class="emp-btn light"><i class="fa fa-arrow-left"></i> Back</a>
                            <button type="submit" class="emp-btn"><i class="fa fa-save"></i> <?= $is_edit ? 'Update Employee' : 'Save Employee'; ?></button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<script>
    function normalizeAttendanceIsoDate(value) {
        value = $.trim(value || '');
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return value;
        }

        var slashDate = value.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (slashDate) {
            return slashDate[3] + '-' + ('0' + slashDate[1]).slice(-2) + '-' + ('0' + slashDate[2]).slice(-2);
        }

        var dashDate = value.match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);
        if (dashDate) {
            return dashDate[3] + '-' + ('0' + dashDate[2]).slice(-2) + '-' + ('0' + dashDate[1]).slice(-2);
        }

        return value;
    }

    function initAttendanceIsoDatePicker() {
        if (!$.fn.datepicker) {
            return;
        }

        $('.date-picker').each(function () {
            var $field = $(this);
            $field.attr('placeholder', 'YYYY-MM-DD');

            try {
                $field.datepicker('destroy');
            } catch (e) {}

            $field.datepicker({
                format: 'yyyy-mm-dd',
                dateFormat: 'yy-mm-dd',
                autoclose: true,
                todayHighlight: true,
                onSelect: function () {
                    this.value = normalizeAttendanceIsoDate(this.value);
                }
            });

            $field.val(normalizeAttendanceIsoDate($field.val()));
        });
    }

    $(document).ready(function () {
        initAttendanceIsoDatePicker();
        setTimeout(initAttendanceIsoDatePicker, 300);

        $(document).on('input blur change changeDate hide', '.date-picker', function () {
            var field = this;
            setTimeout(function () {
                field.value = normalizeAttendanceIsoDate(field.value);
            }, 0);
            setTimeout(function () {
                field.value = normalizeAttendanceIsoDate(field.value);
            }, 80);
        });

        $('form').on('submit', function () {
            $('.date-picker').each(function () {
                this.value = normalizeAttendanceIsoDate(this.value);
            });
        });
    });
</script>
