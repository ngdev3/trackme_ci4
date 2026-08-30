<?php
$first_name = isset($user_data->first_name) ? $user_data->first_name : '';
$last_name = isset($user_data->last_name) ? $user_data->last_name : '';
$email = isset($user_data->email) ? $user_data->email : '';
$mobile = isset($user_data->mobile) ? $user_data->mobile : '';
$pan_number = isset($user_data->pan_number) ? $user_data->pan_number : '';
$address = isset($user_data->address) ? $user_data->address : '';
$full_name = trim($first_name . ' ' . $last_name);
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
if ($initials === '') {
    $initials = 'U';
}
$profile_image = isset($user_data->profile_image) ? $user_data->profile_image : '';
$avatar_url    = $profile_image ? base_url('uploads/profile_image/' . $profile_image) : '';
?>

<style>
    .profile-page {
        padding: 24px;
    }

    .profile-shell {
        max-width: 1160px;
        margin: 0 auto;
    }

    .profile-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .profile-title {
        margin: 0;
        color: #1f2937;
        font-size: 24px;
        font-weight: 700;
    }

    .profile-subtitle {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 13px;
    }

    .profile-status {
        border: 1px solid #dbeafe;
        border-radius: 8px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 700;
        padding: 9px 12px;
        text-transform: uppercase;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: 320px minmax(0, 1fr);
        gap: 18px;
        align-items: start;
    }

    .profile-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
    }

    .profile-summary {
        overflow: hidden;
    }

    .summary-band {
        height: 96px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
    }

    .summary-body {
        padding: 0 22px 22px;
    }

    .avatar-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .profile-avatar {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 96px;
        height: 96px;
        margin-top: -48px;
        border: 4px solid #fff;
        border-radius: 50%;
        background: #111827;
        color: #fff;
        font-size: 30px;
        font-weight: 800;
        letter-spacing: 1px;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.2);
        overflow: hidden;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .avatar-edit {
        position: absolute;
        right: -2px;
        bottom: -2px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        margin: 0;
        border: 3px solid #fff;
        border-radius: 50%;
        background: #0f766e;
        color: #fff;
        font-size: 13px;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(15, 118, 110, 0.3);
        transition: background 0.2s;
        z-index: 2;
    }

    .avatar-edit:hover {
        background: #0d665f;
    }

    .avatar-change-btn {
        margin-top: 12px;
        padding: 7px 14px;
        border: 1px solid #d9e2ec;
        border-radius: 20px;
        background: #fff;
        color: #0f766e;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.2s;
    }

    .avatar-change-btn:hover {
        border-color: #0f766e;
        background: #f0fdfa;
    }

    .avatar-hint {
        margin-top: 8px;
        min-height: 16px;
        font-size: 11px;
        font-weight: 700;
        text-align: center;
    }

    .avatar-hint.ok { color: #0f766e; }
    .avatar-hint.err { color: #dc2626; }
    .avatar-hint.busy { color: #64748b; }

    .summary-name {
        margin: 14px 0 4px;
        color: #111827;
        font-size: 21px;
        font-weight: 800;
    }

    .summary-email {
        margin: 0;
        color: #64748b;
        font-size: 13px;
        word-break: break-word;
    }

    .summary-list {
        margin-top: 22px;
        border-top: 1px solid #eef2f7;
    }

    .summary-item {
        display: flex;
        gap: 12px;
        padding: 14px 0;
        border-bottom: 1px solid #eef2f7;
    }

    .summary-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 34px;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: #f1f5f9;
        color: #0f766e;
        font-size: 16px;
        font-weight: 700;
    }

    .summary-label {
        margin: 0;
        color: #94a3b8;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .summary-value {
        margin: 3px 0 0;
        color: #334155;
        font-size: 13px;
        line-height: 1.4;
        word-break: break-word;
    }

    .form-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 20px 22px;
        border-bottom: 1px solid #eef2f7;
    }

    .form-card-title {
        margin: 0;
        color: #111827;
        font-size: 18px;
        font-weight: 800;
    }

    .form-card-note {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 12px;
    }

    .profile-form {
        padding: 22px;
    }

    .profile-form .form-row {
        margin-right: -8px;
        margin-left: -8px;
    }

    .profile-form .form-group {
        padding-right: 8px;
        padding-left: 8px;
        margin-bottom: 18px;
    }

    .profile-form label {
        color: #334155;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0;
    }

    .profile-form .form-control {
        height: 43px;
        border: 1px solid #d9e2ec;
        border-radius: 6px;
        color: #111827;
        font-size: 13px;
        box-shadow: none;
    }

    .profile-form .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .profile-form .form-control[readonly] {
        background: #f8fafc;
        color: #64748b;
    }

    .profile-form textarea.form-control {
        min-height: 96px;
        resize: vertical;
        line-height: 1.5;
        padding-top: 10px;
    }

    .field-hint {
        display: block;
        margin-top: 5px;
        color: #94a3b8;
        font-size: 11px;
    }

    .profile-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 4px;
        padding-top: 18px;
        border-top: 1px solid #eef2f7;
    }

    .btn-profile-save {
        min-width: 130px;
        height: 42px;
        border: 0;
        border-radius: 6px;
        background: #0f766e;
        color: #fff;
        font-weight: 800;
        box-shadow: 0 8px 16px rgba(15, 118, 110, 0.22);
    }

    .btn-profile-save:hover,
    .btn-profile-save:focus {
        background: #0d665f;
        color: #fff;
    }

    .btn-profile-reset {
        height: 42px;
        border: 1px solid #d9e2ec;
        border-radius: 6px;
        background: #fff;
        color: #475569;
        font-weight: 700;
        padding: 0 16px;
    }

    .text-danger {
        display: block;
        min-height: 16px;
        margin-top: 5px;
        font-size: 11px;
    }

    @media (max-width: 991px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }

        .profile-header {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 575px) {
        .profile-page {
            padding: 14px;
        }

        .form-card-header,
        .profile-form,
        .summary-body {
            padding-right: 16px;
            padding-left: 16px;
        }

        .profile-actions {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .btn-profile-save,
        .btn-profile-reset {
            width: 100%;
        }
    }
</style>

<main class="main-content bgc-grey-100">
    <div id="mainContent" class="profile-page">
        <div class="profile-shell">
            <div class="profile-header">
                <div>
                    <h4 class="profile-title">My Profile</h4>
                    <p class="profile-subtitle">Manage your personal details used across the admin panel.</p>
                </div>
                <div class="profile-status">Admin Account</div>
            </div>

            <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>
            <div id="errormsg" class="text-danger"></div>
            <div id="errormsg1" class="text-danger"></div>

            <div class="profile-grid">
                <aside class="profile-card profile-summary">
                    <div class="summary-band"></div>
                    <div class="summary-body">
                        <div class="avatar-wrap">
                            <div class="profile-avatar" id="avatarBox">
                                <?php if ($avatar_url): ?>
                                    <img id="avatarImg" src="<?= $avatar_url; ?>" alt="Profile photo">
                                <?php else: ?>
                                    <span id="avatarInitials"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                <label class="avatar-edit" for="profilePhotoInput" title="Change photo"><i class="ti-camera"></i></label>
                            </div>
                            <input type="file" id="profilePhotoInput" accept="image/png,image/jpeg,image/jpg,image/gif" hidden>
                            <button type="button" class="avatar-change-btn" id="avatarChangeBtn"><i class="ti-upload"></i> Change Photo</button>
                            <div class="avatar-hint" id="avatarHint"></div>
                        </div>
                        <h5 class="summary-name"><?= htmlspecialchars($full_name ?: 'User Profile', ENT_QUOTES, 'UTF-8'); ?></h5>
                        <p class="summary-email"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>

                        <div class="summary-list">
                            <div class="summary-item">
                                <div class="summary-icon">M</div>
                                <div>
                                    <p class="summary-label">Mobile</p>
                                    <p class="summary-value"><?= htmlspecialchars($mobile ?: 'Not added', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-icon">P</div>
                                <div>
                                    <p class="summary-label">PAN</p>
                                    <p class="summary-value"><?= htmlspecialchars($pan_number ?: 'Not added', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-icon">A</div>
                                <div>
                                    <p class="summary-label">Address</p>
                                    <p class="summary-value"><?= htmlspecialchars($address ?: 'Not added', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <section class="profile-card">
                    <div class="form-card-header">
                        <div>
                            <h5 class="form-card-title">Contact Information</h5>
                            <p class="form-card-note">Fields marked with * are required.</p>
                        </div>
                    </div>

                    <?php echo form_open(current_url(), ['id' => 'teamForm', 'class' => 'profile-form']); ?>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="frst_nme">First Name *</label>
                                <input type="text" value="<?= htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" id="frst_nme" name="first_name" placeholder="First Name" maxlength="40">
                                <small class="text-danger"><?= form_error('first_name'); ?></small>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="last_nme">Last Name *</label>
                                <input type="text" value="<?= htmlspecialchars($last_name, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" id="last_nme" name="last_name" placeholder="Last Name" maxlength="40">
                                <small class="text-danger"><?= form_error('last_name'); ?></small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="email_id">Email *</label>
                                <input type="email" readonly value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" name="email" id="email_id" placeholder="Email">
                                <span class="field-hint">Email is connected to login and cannot be changed here.</span>
                                <small class="text-danger"><?= form_error('email'); ?></small>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="mo">Mobile No. *</label>
                                <input type="text" value="<?= htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" name="mobile" id="mo" placeholder="Mobile No." maxlength="10">
                                <span class="field-hint">Use a valid 10-digit Indian mobile number.</span>
                                <small class="text-danger"><?= form_error('mobile'); ?></small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="pan_number">PAN Number *</label>
                                <input type="text" value="<?= htmlspecialchars($pan_number, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" name="pan_number" id="pan_number" placeholder="ABCDE1234F" maxlength="10">
                                <small class="text-danger"><?= form_error('pan_number'); ?></small>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="address">Address *</label>
                                <textarea class="form-control" name="address" id="address" placeholder="Address"><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <small class="text-danger"><?= form_error('address'); ?></small>
                            </div>
                        </div>

                        <div class="profile-actions">
                            <button type="reset" class="btn-profile-reset">Reset</button>
                            <button type="submit" class="btn-profile-save">Update Profile</button>
                        </div>
                    <?php echo form_close(); ?>
                </section>
            </div>
        </div>
    </div>
</main>

<script>
$(document).ready(function () {
    $("#teamForm").on("submit", function (e) {
        let isValid = true;

        $(".text-danger").text("");

        let firstName = $("#frst_nme").val().trim();
        let lastName = $("#last_nme").val().trim();
        let mobile = $("#mo").val().trim();
        let pan = $("#pan_number").val().trim();
        let address = $("#address").val().trim();

        if (firstName === "") {
            $("#frst_nme").siblings(".text-danger").text("First name is required");
            isValid = false;
        }

        if (lastName === "") {
            $("#last_nme").siblings(".text-danger").text("Last name is required");
            isValid = false;
        }

        if (!/^[6-9]\d{9}$/.test(mobile)) {
            $("#mo").siblings(".text-danger").text("Enter valid 10-digit mobile number");
            isValid = false;
        }

        if (!/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(pan)) {
            $("#pan_number").siblings(".text-danger").text("Invalid PAN format (ABCDE1234F)");
            isValid = false;
        }

        if (address === "") {
            $("#address").siblings(".text-danger").text("Address is required");
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });

    $("#pan_number").on("input", function () {
        this.value = this.value.toUpperCase();
    });

    /* ---------- profile photo upload ---------- */
    var UPLOAD_URL = "<?php echo base_url('admin/profile/changeImage'); ?>";
    var IMG_BASE = "<?php echo base_url('uploads/profile_image/'); ?>";

    function avatarHint(msg, cls) {
        var h = $("#avatarHint");
        h.removeClass('ok err busy').addClass(cls).text(msg);
    }

    $("#avatarChangeBtn").on("click", function () { $("#profilePhotoInput").click(); });

    $("#profilePhotoInput").on("change", function () {
        var file = this.files && this.files[0];
        if (!file) return;

        if (!/^image\/(png|jpe?g|gif)$/i.test(file.type)) {
            avatarHint("Please choose a PNG, JPG or GIF image.", "err");
            this.value = "";
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            avatarHint("Image must be under 5 MB.", "err");
            this.value = "";
            return;
        }

        var fd = new FormData();
        fd.append("userfile", file);

        avatarHint("Uploading...", "busy");
        $("#avatarChangeBtn").prop("disabled", true);

        $.ajax({
            url: UPLOAD_URL, type: "POST", data: fd,
            processData: false, contentType: false, dataType: "json",
            success: function (res) {
                $("#avatarChangeBtn").prop("disabled", false);
                if (res && res.status === "success" && res.file_name) {
                    var url = IMG_BASE + res.file_name + "?t=" + Date.now();
                    var box = document.getElementById("avatarBox");
                    var img = document.getElementById("avatarImg");
                    if (img) {
                        img.src = url;
                    } else {
                        var ini = document.getElementById("avatarInitials");
                        if (ini) ini.parentNode.removeChild(ini);
                        var im = document.createElement("img");
                        im.id = "avatarImg"; im.alt = "Profile photo"; im.src = url;
                        box.insertBefore(im, box.firstChild);
                    }
                    $(".profile-block img").attr("src", url); // update top-nav avatar
                    avatarHint("Photo updated successfully.", "ok");
                } else {
                    avatarHint((res && res.error_msg) ? res.error_msg : "Upload failed. Try again.", "err");
                }
            },
            error: function () {
                $("#avatarChangeBtn").prop("disabled", false);
                avatarHint("Upload failed. Try again.", "err");
            }
        });
        this.value = "";
    });
});
</script>
