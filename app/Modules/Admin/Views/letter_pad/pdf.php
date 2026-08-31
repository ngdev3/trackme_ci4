<?php
/**
 * Letter Pad letterhead (A4).
 *
 * Rendered in three modes by Letter_pad controller:
 *   $is_pdf = true   -> mPDF source. Header/footer repeat per page via named
 *                       htmlpageheader/htmlpagefooter blocks referenced from
 *                       @page CSS; the double page border is an opaque PNG
 *                       @page background ($page_border data URI). mPDF (not
 *                       dompdf) because it shapes Hindi/Devanagari correctly
 *                       (OTL) — dompdf drops/mangles Indic text.
 *   $is_pdf = false  -> browser preview / print view. Header/footer live in a
 *                       table thead/tfoot so browsers repeat them per printed
 *                       page; $auto_print fires window.print() on load.
 *
 * All firm details come from aa_template + firm_name ($firm) — nothing hardcoded.
 * $letter->content is already sanitized by the controller.
 */
$is_pdf = !empty($is_pdf);
$auto_print = !empty($auto_print);
$qr = isset($qr) ? $qr : '';
$page_border = isset($page_border) ? $page_border : '';
$letter_no = isset($letter->letter_no) ? trim((string) $letter->letter_no) : '';

$letter_date = (!empty($letter->letter_date) && $letter->letter_date !== '0000-00-00')
    ? date('d M Y', strtotime($letter->letter_date)) : date('d M Y');

$bank_bits = array();
if (!empty($firm->bank_name)) { $bank_bits[] = 'Bank: ' . html_escape($firm->bank_name); }
if (!empty($firm->bank_number)) { $bank_bits[] = 'A/c No: ' . html_escape($firm->bank_number); }
if (!empty($firm->bank_ifsc)) { $bank_bits[] = 'IFSC: ' . html_escape($firm->bank_ifsc); }

/* ---- Header block (shared by both modes) ---- */
ob_start(); ?>
<table class="lp-head-table" cellpadding="0" cellspacing="0">
    <tr>
        <td class="lp-head-side lp-head-left">
            <?php if (!empty($firm->gst_no)): ?><div><span class="lp-meta-label">GST:</span> <?= html_escape($firm->gst_no) ?></div><?php endif; ?>
            <?php if (!empty($firm->fssai_no)): ?><div><span class="lp-meta-label">FSSAI:</span> <?= html_escape($firm->fssai_no) ?></div><?php endif; ?>
            <div><span class="lp-meta-label">Letter No:</span> <?= $letter_no !== '' ? html_escape($letter_no) : '<span class="lp-meta-dots">(assigned on save)</span>' ?></div>
        </td>
        <td class="lp-head-main">
            <?php if (!empty($logo)): ?><img class="lp-logo" src="<?= $logo ?>" alt="Logo"><br><?php endif; ?>
            <div class="lp-firm-name"><?= html_escape($firm->firm_name) ?></div>
        </td>
        <td class="lp-head-side lp-head-right">
            <?php if (!empty($firm->mandi_license_mandi)): ?><div><span class="lp-meta-label">Mandi:</span> <?= html_escape($firm->mandi_license_mandi) ?></div><?php endif; ?>
            <?php if (!empty($firm->mandi_license_mill)): ?><div><span class="lp-meta-label">Mill Mandi:</span> <?= html_escape($firm->mandi_license_mill) ?></div><?php endif; ?>
            <div><span class="lp-meta-label">Date:</span> <?= $letter_date ?></div>
            <?php if (!empty($qr)): ?>
                <div class="lp-qr-wrap"><img class="lp-qr" src="<?= $qr ?>" alt="QR"><div class="lp-qr-caption">Scan to verify</div></div>
            <?php endif; ?>
            <?php if ($is_pdf): ?><div class="lp-sheet-mark">Sheet {PAGENO} of {nbpg}</div><?php endif; ?>
        </td>
    </tr>
</table>
<div class="lp-head-rule"></div>
<?php $header_html = ob_get_clean();

/* ---- Footer block (shared by both modes) ---- */
ob_start(); ?>
<div class="lp-foot-rule"></div>
<?php if (!empty($firm->address)): ?>
    <div class="lp-foot-line"><span class="lp-meta-label">Address:</span> <?= html_escape($firm->address) ?></div>
<?php endif; ?>
<?php if (!empty($firm->company_email)): ?>
    <div class="lp-foot-line"><span class="lp-meta-label">Email:</span> <?= html_escape(trim($firm->company_email)) ?></div>
<?php endif; ?>
<?php if (!empty($bank_bits)): ?>
    <div class="lp-foot-bank"><?= implode(' &nbsp;|&nbsp; ', $bank_bits) ?></div>
<?php endif; ?>
<?php if ($is_pdf): ?>
    <table class="lp-foot-meta" cellpadding="0" cellspacing="0"><tr>
        <td class="lp-foot-wm"><?= !empty($letter->watermark_code) ? 'WM-' . html_escape($letter->watermark_code) : '' ?></td>
        <td class="lp-foot-verifycell"><?= $letter_no !== '' ? 'Letter No. ' . html_escape($letter_no) . ' &mdash; verify authenticity by scanning the QR code in the header.' : '' ?></td>
        <td class="lp-foot-page">Page {PAGENO} of {nbpg}</td>
    </tr></table>
<?php elseif ($letter_no !== ''): ?>
    <div class="lp-foot-verify">Letter No. <?= html_escape($letter_no) ?> &mdash; verify authenticity by scanning the QR code in the header.</div>
<?php endif; ?>
<?php $footer_html = ob_get_clean();

/* ---- Letter body (shared by both modes) ---- */
ob_start(); ?>
<?php
$recipient_txt = isset($letter->recipient) ? trim((string) $letter->recipient) : '';
if ($recipient_txt !== ''): ?>
    <div class="lp-recipient"><?php if (!preg_match('/^to[\s,]/i', $recipient_txt)): ?>To,<br><?php endif; ?><?= nl2br(html_escape($recipient_txt)) ?></div>
<?php endif; ?>

<?php if (!empty($letter->subject)): ?>
    <div class="lp-subject"><span class="lp-subject-label">Subject:</span> <?= html_escape($letter->subject) ?></div>
<?php endif; ?>

<div class="lp-body"><?= $letter->content ?></div>

<table class="lp-sign-row" cellpadding="0" cellspacing="0">
    <tr>
        <td class="lp-sign-spacer">&nbsp;</td>
        <!-- Rubber-stamp style block: fixed narrow cell so the "For-" line and
             the designation stay close together, like the physical stamp -->
        <td class="lp-stamp">
            <div class="lp-stamp-for">For- <?= html_escape($firm->firm_name) ?></div>
            <div class="lp-stamp-space">&nbsp;</div>
            <?php /* align attr: mPDF ignores text-align CSS in this cell */ ?>
            <?php if (!empty($letter->signature_name)): ?>
                <div class="lp-stamp-name" align="right"><?= html_escape($letter->signature_name) ?></div>
            <?php endif; ?>
            <div class="lp-stamp-desig" align="right"><?= !empty($letter->signature_designation) ? html_escape($letter->signature_designation) : 'Authorised Signatory' ?></div>
        </td>
    </tr>
</table>
<?php $body_html = ob_get_clean(); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= html_escape($firm->firm_name) ?> — <?= html_escape(!empty($letter->title) ? $letter->title : 'Letter') ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            color: #1b2a41;
            font-family: "DejaVu Sans", "Segoe UI", Arial, sans-serif;
            font-size: 12px;
            line-height: 1.55;
        }

        /* ---- Letterhead header ---- */
        .lp-head-table { width: 100%; border-collapse: collapse; }
        .lp-head-side { width: 27%; vertical-align: top; color: #33445f; font-size: 9.5px; font-weight: bold; line-height: 1.6; }
        .lp-head-left { text-align: left; }
        .lp-head-right { text-align: right; }
        .lp-meta-label { color: #0c315f; }
        .lp-meta-dots { color: #8a97aa; font-weight: normal; font-style: italic; }
        .lp-head-main { text-align: center; vertical-align: middle; }
        .lp-logo { max-width: 52px; max-height: 52px; margin-bottom: 2px; }
        .lp-firm-name { color: #0c315f; font-size: 23px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .lp-qr-wrap { margin-top: 4px; }
        .lp-qr { width: 50px; height: 50px; }
        .lp-qr-caption { color: #6b7890; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .lp-sheet-mark { margin-top: 2px; color: #2238b5; font-size: 7.5px; font-weight: bold; }
        .lp-head-rule { margin-top: 6px; border-top: 2.6px solid #0c315f; border-bottom: 1px solid #b8892c; height: 2px; }

        /* ---- Footer ---- */
        .lp-foot-rule { border-top: 1px solid #b8892c; border-bottom: 2.2px solid #0c315f; height: 2px; margin-bottom: 6px; }
        .lp-foot-line { color: #33445f; font-size: 9.5px; font-weight: bold; text-align: center; }
        .lp-foot-bank { color: #33445f; font-size: 9.5px; font-weight: bold; text-align: center; }
        .lp-foot-verify { margin-top: 3px; color: #6b7890; font-size: 8.5px; text-align: center; }
        .lp-recipient { margin: 0 0 14px; text-align: left; line-height: 1.5; }
        .lp-subject { margin: 0 0 16px; color: #0c315f; font-size: 13px; font-weight: bold; text-align: center; }
        .lp-subject .lp-subject-label { color: #33445f; }
        .lp-body { min-height: 320px; text-align: justify; word-wrap: break-word; }
        .lp-body table { border-collapse: collapse; width: 100%; }
        .lp-body table td, .lp-body table th { border: 1px solid #8ea3bd; padding: 5px 7px; }
        .lp-body img { max-width: 100%; }
        .lp-body p { margin: 0 0 8px; }
        .lp-sign-row { width: 100%; border-collapse: collapse; margin-top: 34px; page-break-inside: avoid; }
        .lp-sign-spacer { }
        /* Rubber-stamp block, matched to the physical stamp: the cell shrinks
           to the width of the "For- FIRM" line (width:1% + nowrap), so the
           designation right-aligns under the END of the firm name, with only
           a small vertical gap — exactly like the inked stamp. */
        .lp-stamp { width: 1%; padding-right: 14px; color: #2238b5; vertical-align: top; white-space: nowrap; }
        .lp-stamp-for { text-align: left; font-size: 14.5px; font-weight: bold; letter-spacing: .3px; }
        .lp-stamp-space { height: 24px; }
        .lp-stamp-name { text-align: right; font-size: 11px; font-weight: bold; }
        .lp-stamp-desig { margin-top: 3px; text-align: right; font-size: 13px; font-weight: bold; letter-spacing: .3px; }

        <?php if ($is_pdf): ?>
        /* ==== PDF (mPDF): named header/footer repeat on every page; the
                double page border is an opaque PNG @page background.
                Top margin must clear the header (logo + firm name + QR +
                sheet marker ≈ 36mm below the 12mm header start). ==== */
        @page {
            margin: 52mm 16mm 38mm 16mm;
            margin-header: 12mm;
            margin-footer: 11mm;
            <?php if (!empty($page_border)): ?>background-image: url("<?= $page_border ?>"); background-image-resize: 6;<?php endif; ?>
            header: html_lphead;
            footer: html_lpfoot;
        }
        /* freesans ships with the bundled mPDF and, with autoScriptToLang,
           Devanagari runs auto-switch to freeserif with proper OTL shaping. */
        body { font-family: freesans, sans-serif; }
        .lp-foot-meta { width: 100%; margin-top: 3px; border-collapse: collapse; }
        .lp-foot-wm { width: 20%; color: #9ea8b6; font-size: 8px; text-align: left; }
        .lp-foot-verifycell { color: #6b7890; font-size: 8.5px; text-align: center; }
        .lp-foot-page { width: 20%; color: #6b7890; font-size: 8.5px; text-align: right; }
        /* mPDF ignores the width:1% + nowrap shrink-to-fit trick (it wraps
           the cell one character per line) — give the stamp a fixed width. */
        .lp-stamp { width: 270px; white-space: normal; }
        <?php else: ?>
        /* ==== Browser preview / print ==== */
        body { background: #eef2f7; }
        .lp-sheet { width: 210mm; min-height: 297mm; margin: 18px auto; padding: 8mm 14mm; background: #fff; border: 2.4px double #0c315f; box-shadow: 0 18px 44px rgba(15, 23, 42, .18); }
        /* Fixed table height = sheet inner height, so on a short letter the
           tbody stretches and the footer sits at the BOTTOM of the sheet;
           long letters simply grow past it (height acts as min-height). */
        .lp-print-table { width: 100%; height: calc(297mm - 16mm); border-collapse: collapse; }
        .lp-print-table > thead > tr > td, .lp-print-table > tbody > tr > td, .lp-print-table > tfoot > tr > td { padding: 0; }
        .lp-print-table > thead > tr > td { height: 1px; padding-bottom: 14px; }
        .lp-print-table > tbody > tr > td { vertical-align: top; }
        .lp-print-table > tfoot > tr > td { height: 1px; padding-top: 14px; vertical-align: bottom; }
        .lp-preview-bar { position: fixed; top: 0; left: 0; right: 0; z-index: 50; display: flex; align-items: center; justify-content: space-between; padding: 10px 18px; color: #fff; background: #0c315f; font-size: 13px; font-weight: bold; }
        .lp-preview-bar button { border: 0; border-radius: 6px; padding: 8px 16px; color: #0c315f; background: #fff; font-weight: bold; cursor: pointer; }
        body.lp-has-bar { padding-top: 46px; }
        @media print {
            body { background: #fff; padding-top: 0 !important; }
            .lp-preview-bar { display: none; }
            .lp-sheet { width: auto; min-height: 0; margin: 0; padding: 0; border: 0; box-shadow: none; }
        }
        <?php endif; ?>
    </style>
</head>
<body class="<?= $is_pdf ? '' : 'lp-has-bar' ?>">
<?php if ($is_pdf): ?>
    <htmlpageheader name="lphead"><?= $header_html ?></htmlpageheader>
    <htmlpagefooter name="lpfoot"><?= $footer_html ?></htmlpagefooter>
    <?= $body_html ?>
<?php else: ?>
    <div class="lp-preview-bar">
        <span><?= $auto_print ? 'Print letter' : 'Letter preview' ?> — <?= html_escape($firm->firm_name) ?></span>
        <button type="button" onclick="window.print()"><i></i>Print</button>
    </div>
    <div class="lp-sheet">
        <table class="lp-print-table">
            <thead><tr><td><?= $header_html ?></td></tr></thead>
            <tbody><tr><td><?= $body_html ?></td></tr></tbody>
            <tfoot><tr><td><?= $footer_html ?></td></tr></tfoot>
        </table>
    </div>
    <?php if ($auto_print): ?>
        <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 350); });</script>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
