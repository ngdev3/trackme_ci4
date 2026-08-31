<?php helper(['url', 'form']); ?>
<main class="main-content bgc-grey-100">
    <div id="mainContent">
        <div class="container-fluid">
            <div class="row">
                <div class="masonry-item col-md-12">
                    <div class="bgc-white p-20 bd">
                        <h4 class="c-grey-900"><?php echo ! empty($result) ? 'Edit Credential' : 'Add Credential'; ?></h4>
                        <?= get_flashdata(); ?>

                        <div class="mT-30">
                            <?php echo form_open(current_url(), ['id' => 'credential_form']); ?>
                            <div class="form-row row">
                                <div class="form-group col-md-6">
                                    <label>Entry Name *</label>
                                    <?php echo form_input(['name' => 'password_name', 'maxlength' => '150', 'class' => 'form-control', 'placeholder' => 'Example: Company GST Login', 'value' => set_value('password_name', @$result->password_name)]); ?>
                                    <div class="help-block" style="color:red"><?php echo form_error('password_name'); ?></div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Category *</label>
                                    <?php echo form_input(['name' => 'password_type', 'list' => 'credential_categories', 'maxlength' => '100', 'class' => 'form-control', 'placeholder' => 'Choose or enter any category', 'value' => set_value('password_type', @$result->password_type)]); ?>
                                    <datalist id="credential_categories">
                                        <option value="GST"></option><option value="Mandi"></option><option value="Income Tax"></option><option value="Bank"></option>
                                        <option value="Laptop"></option><option value="Email"></option><option value="Website"></option><option value="Software"></option><option value="Other"></option>
                                    </datalist>
                                    <div class="help-block" style="color:red"><?php echo form_error('password_type'); ?></div>
                                </div>
                            </div>

                            <div class="form-row row">
                                <div class="form-group col-md-6">
                                    <label>Service / Organisation</label>
                                    <?php echo form_input(['name' => 'bank_name', 'maxlength' => '255', 'class' => 'form-control', 'placeholder' => 'Bank, department, website, device, etc.', 'value' => set_value('bank_name', @$result->bank_name)]); ?>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>URL</label>
                                    <?php echo form_input(['type' => 'url', 'name' => 'bank_url', 'maxlength' => '500', 'class' => 'form-control', 'placeholder' => 'https://example.com', 'value' => set_value('bank_url', @$result->bank_url)]); ?>
                                    <div class="help-block" style="color:red"><?php echo form_error('bank_url'); ?></div>
                                </div>
                            </div>

                            <div class="form-row row">
                                <div class="form-group col-md-6">
                                    <label>Username / Email / ID</label>
                                    <?php echo form_input(['name' => 'user_login_id', 'maxlength' => '150', 'class' => 'form-control', 'placeholder' => 'Login ID, email address, GSTIN, etc.', 'value' => set_value('user_login_id', @$result->user_login_id)]); ?>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Secondary ID / Reference Number</label>
                                    <?php echo form_input(['name' => 'corp_id', 'maxlength' => '150', 'class' => 'form-control', 'placeholder' => 'Customer ID, Corp ID, device serial, etc.', 'value' => set_value('corp_id', @$result->corp_id)]); ?>
                                </div>
                            </div>

                            <div class="form-row row">
                                <div class="form-group col-md-6">
                                    <label>Password / Secret</label>
                                    <div class="input-group">
                                        <?php echo form_input(['type' => 'password', 'name' => 'login_password', 'maxlength' => '255', 'class' => 'form-control secret-field', 'placeholder' => 'Password, PIN, key, or other secret', 'autocomplete' => 'new-password', 'value' => set_value('login_password', @$result->login_password)]); ?>
                                        <span class="input-group-btn"><button class="btn btn-default toggle-secret" type="button" title="Show or hide"><i class="fa fa-eye"></i></button></span>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Secondary Password / PIN</label>
                                    <div class="input-group">
                                        <?php echo form_input(['type' => 'password', 'name' => 'transaction_password', 'maxlength' => '255', 'class' => 'form-control secret-field', 'placeholder' => 'Optional secondary secret', 'autocomplete' => 'new-password', 'value' => set_value('transaction_password', @$result->transaction_password)]); ?>
                                        <span class="input-group-btn"><button class="btn btn-default toggle-secret" type="button" title="Show or hide"><i class="fa fa-eye"></i></button></span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row row">
                                <div class="form-group col-md-6">
                                    <label>Primary Expiry Date</label>
                                    <?php echo form_input(['type' => 'date', 'name' => 'login_password_expiry_date', 'class' => 'form-control', 'value' => set_value('login_password_expiry_date', @$result->login_password_expiry_date)]); ?>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Secondary Expiry Date</label>
                                    <?php echo form_input(['type' => 'date', 'name' => 'transaction_password_expiry_date', 'class' => 'form-control', 'value' => set_value('transaction_password_expiry_date', @$result->transaction_password_expiry_date)]); ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Notes / Additional Details</label>
                                <textarea name="notes" class="form-control" rows="4" placeholder="Store any other useful details here"><?php echo html_escape(set_value('notes', @$result->notes)); ?></textarea>
                            </div>

                            <div class="form-row row">
                                <div class="form-group col-md-4">
                                    <label>Status *</label>
                                    <?php $selected_status = set_value('status', ! empty($result->status) ? $result->status : 'Active'); ?>
                                    <select class="form-control" name="status">
                                        <option value="Active" <?php echo $selected_status === 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo $selected_status === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-8">
                                    <?php $is_common = $_POST['is_common'] ?? (@$result->is_common); ?>
                                    <label>Firm Scope</label>
                                    <div><label><input type="checkbox" name="is_common" value="1" <?php echo ! empty($is_common) ? 'checked' : ''; ?>> Common for all firms</label></div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Credential</button>
                            <a href="<?php echo base_url('admin/bank_password/listing'); ?>" class="btn btn-default">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
$(function () {
    $('.toggle-secret').on('click', function () {
        var input = $(this).closest('.input-group').find('.secret-field');
        input.attr('type', input.attr('type') === 'password' ? 'text' : 'password');
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });
});
</script>
