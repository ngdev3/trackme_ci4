<?php
$is_edit = !empty($result);
$v = function ($field, $default = '') use ($result) {
    $CI = &get_instance();
    $posted = $CI->input->post($field);
    if ($posted !== null) {
        return $posted; // re-render after a failed validation keeps what was typed
    }
    return (!empty($result) && isset($result->{$field}) && $result->{$field} !== null) ? $result->{$field} : $default;
};
$sel_template = $v('template_id');
$letter_date = $v('letter_date', date('Y-m-d'));
if ($letter_date === '0000-00-00') { $letter_date = date('Y-m-d'); }

// Letter date window: today .. +7 days (an existing letter's stored date stays selectable on edit).
$date_min = date('Y-m-d');
$date_max = date('Y-m-d', strtotime('+7 days'));
if ($is_edit && !empty($result->letter_date) && $result->letter_date !== '0000-00-00') {
    if ($result->letter_date < $date_min) { $date_min = $result->letter_date; }
    if ($result->letter_date > $date_max) { $date_max = $result->letter_date; }
}

// Default signatory = the logged-in user (their stamp style: RAJAT GUPTA / PARTNER).
$me = currentuserinfo();
$default_sign_name = strtoupper(trim((isset($me->first_name) ? $me->first_name : '') . ' ' . (isset($me->last_name) ? $me->last_name : '')));
?>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">

<style>
    .lp-form-page{color:#18243c;padding:24px}.lp-form-shell{margin:0 auto;max-width:1320px}
    .lp-hero{align-items:center;background:#fff;border:1px solid #dce6f2;border-radius:8px;box-shadow:0 16px 38px rgba(24,36,60,.08);display:flex;gap:18px;justify-content:space-between;margin-bottom:18px;padding:22px 24px}
    .lp-hero-copy{align-items:center;display:flex;gap:14px}.lp-hero-icon{align-items:center;background:#e8f2ff;border-radius:8px;color:#1769c2;display:flex;font-size:20px;height:48px;justify-content:center;width:48px}
    .lp-title{color:#18243c;font-size:25px;font-weight:900;margin:0}.lp-subtitle{color:#718096;font-size:13px;font-weight:700;line-height:1.55;margin:6px 0 0}
    .lp-back-btn{align-items:center;background:#fff;border:1px solid #d8e1eb;border-radius:8px!important;color:#52657a;display:inline-flex;font-weight:900;gap:8px;min-height:42px;padding:10px 16px}.lp-back-btn:hover{background:#eaf3ff;color:#1769c2}
    .lp-grid{display:grid;gap:18px;grid-template-columns:minmax(0,1fr) 360px}
    .lp-panel{background:#fff;border:1px solid #dce6f2;border-radius:8px;box-shadow:0 16px 38px rgba(24,36,60,.08);padding:20px}
    .lp-panel h5{color:#18243c;font-size:16px;font-weight:900;margin:0 0 4px}.lp-panel .lp-panel-sub{color:#718096;font-size:12px;font-weight:700;margin:0 0 16px}
    .lp-no-chip{background:#f1f6fc;border:1px solid #cfe1f5;border-radius:6px;color:#1769c2;display:inline-block;font-family:Consolas,monospace;font-size:12px;font-weight:700;margin-left:8px;padding:4px 9px;vertical-align:middle}
    .lp-field{margin-bottom:15px}.lp-field label{color:#516174;display:block;font-size:11px;font-weight:900;margin-bottom:6px;text-transform:uppercase}
    .lp-field label .lp-req{color:#c53030}
    .lp-field input[type=text],.lp-field input[type=date],.lp-field select,.lp-field textarea{background:#fbfdff;border:1px solid #dce6f2;border-radius:8px;color:#18243c;font-weight:700;min-height:42px;padding:9px 12px;width:100%}
    .lp-field textarea{resize:vertical}
    .lp-field input:focus,.lp-field select:focus,.lp-field textarea:focus{border-color:#1769c2;box-shadow:0 0 0 3px rgba(23,105,194,.12);outline:0}
    .lp-field-row{display:grid;gap:14px;grid-template-columns:1fr 1fr}
    .lp-actions-bar{align-items:center;display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}
    .lp-btn{align-items:center;border:0;border-radius:8px!important;cursor:pointer;display:inline-flex;font-weight:900;gap:8px;min-height:44px;padding:10px 18px}
    .lp-btn-primary{background:#1769c2;box-shadow:0 10px 22px rgba(23,105,194,.2);color:#fff}.lp-btn-primary:hover{background:#0c5aaa;color:#fff}
    .lp-btn-ghost{background:#fff;border:1px solid #b9d5f5;color:#1769c2}.lp-btn-ghost:hover{background:#eaf3ff}
    .lp-btn-soft{background:#eef7f1;border:1px solid #bfe3cd;color:#18794e}.lp-btn-soft:hover{background:#def0e5}
    .lp-preview-card{position:sticky;top:90px}
    .lp-preview-sheet{background:#fff;border:2px double #0c315f;border-radius:4px;min-height:280px;padding:16px 14px}
    .lp-preview-head{border-bottom:2px solid #0c315f;padding-bottom:10px;text-align:center}
    .lp-preview-logo{max-height:52px;max-width:52px}
    .lp-preview-name{color:#0c315f;font-size:16px;font-weight:900;letter-spacing:.5px;text-transform:uppercase}
    .lp-preview-addr{color:#33445f;font-size:10.5px;font-weight:700;margin-top:3px}
    .lp-preview-meta{color:#33445f;font-size:10px;font-weight:800;margin-top:3px}
    .lp-preview-bank{border-top:1px solid #dce6f2;color:#516174;font-size:10px;font-weight:800;margin-top:12px;padding-top:8px;text-align:center}
    .lp-preview-empty{color:#9aa7b6;font-size:12px;font-weight:700;font-style:italic;padding:30px 8px;text-align:center}
    .lp-preview-note{color:#718096;font-size:11px;font-weight:700;line-height:1.5;margin:12px 0 0}
    .lp-verify-card{border:1px solid #cfe1f5;border-radius:8px;margin-top:14px;overflow:hidden}
    .lp-verify-head{background:#f1f6fc;border-bottom:1px solid #cfe1f5;color:#1769c2;font-size:11px;font-weight:900;padding:9px 12px;text-transform:uppercase}
    .lp-verify-body{display:flex;gap:12px;padding:12px}
    .lp-verify-qr{border:1px solid #dce6f2;border-radius:6px;cursor:zoom-in;flex:0 0 86px;height:86px;width:86px}
    .lp-qr-overlay{align-items:center;background:rgba(9,20,38,.78);bottom:0;cursor:zoom-out;display:none;justify-content:center;left:0;position:fixed;right:0;top:0;z-index:100010}
    .lp-qr-overlay.is-open{display:flex}
    .lp-qr-overlay-card{background:#fff;border-radius:12px;box-shadow:0 30px 80px rgba(0,0,0,.45);padding:22px;text-align:center}
    .lp-qr-overlay-card img{height:min(70vw,340px);image-rendering:pixelated;width:min(70vw,340px)}
    .lp-qr-overlay-no{color:#344a64;font-family:Consolas,monospace;font-size:15px;font-weight:700;margin-top:10px}
    .lp-qr-overlay-hint{color:#8a97aa;font-size:11px;font-weight:700;margin-top:6px}
    .lp-verify-meta{min-width:0}
    .lp-verify-no{background:#f4f6f9;border:1px solid #e3e9f0;border-radius:5px;color:#344a64;display:inline-block;font-family:Consolas,monospace;font-size:12px;font-weight:700;margin-bottom:7px;padding:4px 8px}
    .lp-verify-meta p{color:#516174;font-size:11px;font-weight:700;line-height:1.5;margin:0 0 7px}
    .lp-verify-meta a{color:#1769c2;font-size:11px;font-weight:900}
    .lp-verify-card-pending .lp-verify-body{background:#fbfdff}
    .note-editor.note-frame{border:1px solid #dce6f2;border-radius:8px}
    .note-editor .note-editing-area .note-editable{color:#1b2a41;font-size:13px;line-height:1.6;min-height:320px}
    .lp-validation{background:#fde8e8;border:1px solid #efb3b3;border-radius:8px;color:#b43333;font-weight:700;margin-bottom:16px;padding:12px 16px}
    .lp-validation p{margin:0}
    @media(max-width:1100px){.lp-grid{grid-template-columns:1fr}.lp-preview-card{position:static}}
    @media(max-width:767px){.lp-form-page{padding:14px}.lp-hero{align-items:stretch;flex-direction:column}.lp-field-row{grid-template-columns:1fr}.lp-actions-bar .lp-btn{flex:1 1 100%;justify-content:center}}
</style>

<div id="msgShow"></div>
<main class="main-content bgc-grey-100 lp-form-page">
    <div id="mainContent">
        <div class="container-fluid lp-form-shell">
            <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>

            <section class="lp-hero">
                <div class="lp-hero-copy">
                    <span class="lp-hero-icon"><i class="fa fa-file-text-o"></i></span>
                    <div>
                        <h4 class="lp-title"><?= $is_edit ? 'Edit Letter' : 'Create Letter' ?></h4>
                        <p class="lp-subtitle">Type or paste any content and generate a professional A4 PDF on the selected firm's letter pad.</p>
                    </div>
                </div>
                <a href="<?= base_url('admin/letter_pad/listing') ?>" class="btn lp-back-btn"><i class="fa fa-list"></i> All Letters</a>
            </section>

            <?php if (validation_errors()): ?>
                <div class="lp-validation"><?= validation_errors('<p><i class="fa fa-exclamation-circle"></i> ', '</p>') ?></div>
            <?php endif; ?>

            <form method="post" id="lp-form" action="">
                <input type="hidden" name="after_save" id="lp-after-save" value="list">
                <div class="lp-grid">
                    <section class="lp-panel">
                        <h5>Letter Details
                            <?php if ($is_edit && !empty($result->letter_no)): ?>
                                <span class="lp-no-chip" title="Unique letter number — printed on the letter with a verification QR code"><i class="fa fa-qrcode"></i> <?= html_escape($result->letter_no) ?></span>
                            <?php endif; ?>
                        </h5>
                        <p class="lp-panel-sub">Firm details load automatically from the firm master — nothing is typed twice.<?= $is_edit ? '' : ' A unique letter number + verification QR code are assigned when you save.' ?></p>

                        <div class="lp-field-row">
                            <div class="lp-field">
                                <label>Firm / Letter Pad <span class="lp-req">*</span></label>
                                <select name="template_id" id="lp-template" required>
                                    <option value="">— Select firm —</option>
                                    <?php foreach ($firm_templates as $t): ?>
                                        <option value="<?= (int) $t->template_id ?>" <?= ((string) $sel_template === (string) $t->template_id) ? 'selected' : '' ?>>
                                            <?= html_escape($t->firm_name) ?> (<?= html_escape($t->FY) ?> — <?= html_escape($t->template_name) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="lp-field">
                                <label>Letter Date <span class="lp-req">*</span> <small style="text-transform:none;font-weight:700;color:#8a97aa;">(today to +7 days)</small></label>
                                <input type="date" name="letter_date" value="<?= html_escape($letter_date) ?>" min="<?= $date_min ?>" max="<?= $date_max ?>" required>
                            </div>
                        </div>

                        <div class="lp-field">
                            <label>To / Recipient (Name &amp; Address)</label>
                            <textarea name="recipient" id="lp-recipient" rows="3" placeholder="The Branch Manager,&#10;State Bank of India,&#10;Shahabad, Hardoi (U.P.)"><?= html_escape($v('recipient')) ?></textarea>
                        </div>

                        <div class="lp-field-row">
                            <div class="lp-field">
                                <label>Letter Title / Ref <span class="lp-req">*</span></label>
                                <input type="text" name="title" maxlength="255" value="<?= html_escape($v('title')) ?>" placeholder="e.g. LTR/2026/014 or Bank Request Letter" required>
                            </div>
                            <div class="lp-field">
                                <label>Subject</label>
                                <input type="text" name="subject" maxlength="255" value="<?= html_escape($v('subject')) ?>" placeholder="Subject line printed on the letter">
                            </div>
                        </div>

                        <div class="lp-field">
                            <label>Letter Body <span class="lp-req">*</span></label>
                            <textarea name="content" id="lp-content" style="display:none;"><?= html_escape($v('content')) ?></textarea>
                        </div>

                        <div class="lp-field-row">
                            <div class="lp-field">
                                <label>Signature Name</label>
                                <input type="text" name="signature_name" maxlength="255" value="<?= html_escape($v('signature_name', $default_sign_name)) ?>" placeholder="Blank = Authorised Signatory">
                            </div>
                            <div class="lp-field">
                                <label>Signature Designation</label>
                                <input type="text" name="signature_designation" maxlength="255" value="<?= html_escape($v('signature_designation', 'PARTNER')) ?>" placeholder="e.g. PARTNER / MANAGER">
                            </div>
                        </div>

                        <div class="lp-actions-bar">
                            <button type="button" class="lp-btn lp-btn-ghost" id="lp-preview-btn"><i class="fa fa-eye"></i> Preview</button>
                            <button type="submit" class="lp-btn lp-btn-primary" data-after="list"><i class="fa fa-file-pdf-o"></i> <?= $is_edit ? 'Update & Regenerate PDF' : 'Generate PDF & Save' ?></button>
                        </div>
                    </section>

                    <aside class="lp-panel lp-preview-card">
                        <h5>Letter Pad Preview</h5>
                        <p class="lp-panel-sub">Header &amp; footer as they will print on every page.</p>
                        <div class="lp-preview-sheet" id="lp-preview-sheet">
                            <div class="lp-preview-empty">Select a firm to preview its letter pad.</div>
                        </div>
                        <p class="lp-preview-note"><i class="fa fa-info-circle"></i> Firm name, address, GST, email and bank details are fetched live from the firm master. A logo appears automatically when <code>uploads/firm_logo/&lt;firm-id&gt;.png|jpg</code> exists.</p>

                        <?php if ($is_edit && !empty($result->letter_no)): ?>
                            <div class="lp-verify-card">
                                <div class="lp-verify-head"><i class="fa fa-qrcode"></i> Letter Verification</div>
                                <div class="lp-verify-body">
                                    <?php if (!empty($letter_qr)): ?>
                                        <img class="lp-verify-qr" id="lp-qr-img" src="<?= $letter_qr ?>" alt="Verification QR" title="Click to zoom">
                                    <?php endif; ?>
                                    <div class="lp-verify-meta">
                                        <div class="lp-verify-no"><?= html_escape($result->letter_no) ?></div>
                                        <p>This number + QR code print on the letterhead of every page. Anyone can scan it to confirm the letter is genuine.</p>
                                        <?php if (!empty($verify_url)): ?>
                                            <a href="<?= html_escape($verify_url) ?>" target="_blank" rel="noopener"><i class="fa fa-external-link"></i> Open verification page</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php elseif (!$is_edit): ?>
                            <div class="lp-verify-card lp-verify-card-pending">
                                <div class="lp-verify-head"><i class="fa fa-qrcode"></i> Letter Verification</div>
                                <div class="lp-verify-body">
                                    <div class="lp-verify-meta">
                                        <p>A unique <b>Letter Number</b> and a <b>verification QR code</b> will be assigned automatically when you save this letter.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </aside>
                </div>
            </form>
        </div>
    </div>

    <?php if ($is_edit && !empty($letter_qr)): ?>
        <div class="lp-qr-overlay" id="lp-qr-overlay" title="Click to close">
            <div class="lp-qr-overlay-card">
                <img src="<?= $letter_qr ?>" alt="Verification QR (large)">
                <div class="lp-qr-overlay-no"><?= html_escape($result->letter_no) ?></div>
                <div class="lp-qr-overlay-hint">Scan with any phone camera to open the verification page &bull; click anywhere to close</div>
            </div>
        </div>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script>
$(function () {
    $('#lp-content').summernote({
        placeholder: 'Type your letter here, or paste from MS Word / Google Docs — formatting is preserved.',
        height: 340,
        tabsize: 4,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph', 'height']],
            ['insert', ['table', 'hr', 'link']],
            ['history', ['undo', 'redo']],
            ['view', ['fullscreen', 'codeview']]
        ],
        fontSizes: ['10', '11', '12', '13', '14', '16', '18', '20', '24', '28'],
        lineHeights: ['1.0', '1.2', '1.4', '1.5', '1.6', '1.8', '2.0', '2.5', '3.0']
    });

    // Track which save button was pressed (list / download / print).
    $('#lp-form button[type=submit]').on('click', function () {
        $('#lp-after-save').val($(this).data('after') || 'list');
    });

    // Live letter-pad preview: fetch selected firm details from the master.
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
    function loadFirmPreview() {
        var tid = $('#lp-template').val();
        var $sheet = $('#lp-preview-sheet');
        if (!tid) {
            $sheet.html('<div class="lp-preview-empty">Select a firm to preview its letter pad.</div>');
            return;
        }
        $sheet.html('<div class="lp-preview-empty"><i class="fa fa-spinner fa-spin"></i> Loading firm details…</div>');
        $.getJSON('<?= base_url('admin/letter_pad/firm_details') ?>/' + tid, function (res) {
            if (!res || res.status !== 'success' || !res.data) {
                $sheet.html('<div class="lp-preview-empty">Firm details could not be loaded.</div>');
                return;
            }
            var d = res.data, meta = [], bank = [];
            if (d.gst_no) { meta.push('GSTIN: ' + esc(d.gst_no)); }
            if (d.fssai_no) { meta.push('FSSAI: ' + esc(d.fssai_no)); }
            if (d.company_email) { meta.push(esc(String(d.company_email).trim())); }
            if (d.bank_name) { bank.push('Bank: ' + esc(d.bank_name)); }
            if (d.bank_number) { bank.push('A/c: ' + esc(d.bank_number)); }
            if (d.bank_ifsc) { bank.push('IFSC: ' + esc(d.bank_ifsc)); }
            var html = '<div class="lp-preview-head">'
                + (d.logo ? '<img class="lp-preview-logo" src="' + d.logo + '" alt=""><br>' : '')
                + '<div class="lp-preview-name">' + esc(d.firm_name) + '</div>'
                + (d.address ? '<div class="lp-preview-addr">' + esc(d.address) + '</div>' : '')
                + (meta.length ? '<div class="lp-preview-meta">' + meta.join(' | ') + '</div>' : '')
                + '</div>'
                + (bank.length ? '<div class="lp-preview-bank">' + bank.join(' | ') + '</div>' : '');
            $sheet.html(html);
        }).fail(function () {
            $sheet.html('<div class="lp-preview-empty">Firm details could not be loaded.</div>');
        });
    }
    $('#lp-template').on('change', loadFirmPreview);
    if ($('#lp-template').val()) { loadFirmPreview(); }

    // QR zoom: click the small QR to open a large scannable overlay.
    $('#lp-qr-img').on('click', function () { $('#lp-qr-overlay').addClass('is-open'); });
    $('#lp-qr-overlay').on('click', function () { $(this).removeClass('is-open'); });
    $(document).on('keyup', function (e) { if (e.key === 'Escape') { $('#lp-qr-overlay').removeClass('is-open'); } });

    // Full-page preview in a new tab (POST, nothing saved).
    $('#lp-preview-btn').on('click', function () {
        if (!$('#lp-template').val()) { alert('Please select a firm/letter pad first.'); return; }
        $('#lp-content').val($('#lp-content').summernote('code')); // sync editor -> textarea
        var $form = $('#lp-form');
        var oldAction = $form.attr('action');
        $form.attr({ action: '<?= base_url('admin/letter_pad/preview') ?>', target: '_blank' });
        $form[0].submit();
        $form.attr('action', oldAction || '').removeAttr('target');
    });
});
</script>
