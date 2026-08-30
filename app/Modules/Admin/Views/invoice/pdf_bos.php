<?php if (!empty($invoice_data)) { ?>
<?php
// Optional per-document config so this themed template serves multiple types
// (Bill of Supply, Unregistered BOS, Delivery Challan, Tax Invoice, ...).
$pdf_doc      = isset($pdf_doc) && is_array($pdf_doc) ? $pdf_doc : array();
$doc_module   = isset($pdf_doc['module']) && $pdf_doc['module'] !== '' ? $pdf_doc['module'] : 'invoice';
$doc_verify   = isset($pdf_doc['verify']) ? (bool) $pdf_doc['verify'] : true;   // QR = live verify URL?
// Show GST fields (CGST/SGST/IGST, tax amount, party GSTINs)? Off for non-GST
// documents like the Unregistered Bill of Supply. Defaults to on, but a bill
// with zero GST auto-hides them too.
$doc_show_gst = isset($pdf_doc['show_gst']) ? (bool) $pdf_doc['show_gst'] : true;
// Show vehicle/transport fields (truck no, driver, transport)? Off for the
// Unregistered BOS — there we surface the party State / State Code instead.
$doc_show_vehicle = isset($pdf_doc['show_vehicle']) ? (bool) $pdf_doc['show_vehicle'] : true;
if ($doc_show_gst && (float) (isset($invoice_data['tax_gst_amount']) ? $invoice_data['tax_gst_amount'] : 0) <= 0
	&& isset($pdf_doc['module']) && $pdf_doc['module'] === 'uninvoice') {
	$doc_show_gst = false;
}

$invoiceDate = strtotime($invoice_data['billing_date']);
$invoiceDateFormatted = date('d-m-Y', $invoiceDate);
$stateCode = substr((string) $firm->gst_no, 0, 2);
$invoiceType = ($invoice_data['type_of_invoice'] == 1) ? 'TAX INVOICE' : 'BILL OF SUPPLY';
if (isset($pdf_doc['title']) && $pdf_doc['title'] !== '') { $invoiceType = $pdf_doc['title']; }
$consigneeName = ($invoice_data['delivery_at_account'] != 0) ? $invoice_data['del_name'] : $invoice_data['contact_person_name'];
$consigneeAddress = ($invoice_data['delivery_at_account'] != 0) ? $invoice_data['del_purchaser_address'] : $invoice_data['purchaser_address'];
$consigneeGst = ($invoice_data['delivery_at_account'] != 0) ? $invoice_data['del_purchaser_gst_no'] : $invoice_data['purchaser_gst_no'];
$consigneeState = ($invoice_data['delivery_at_account'] != 0) ? $invoice_data['del_state'] : $invoice_data['state'];
$consigneeStateCode = ($invoice_data['delivery_at_account'] != 0) ? $invoice_data['del_state_code'] : $invoice_data['state_code'];
$title = ' ' . $invoice_data['FY'] . '_INV' . $invoice_data['invoice_id'] . '_' . $invoice_data['contact_person_name'] . '_' . $invoice_data['product_name'];
$gstStatus = !$doc_show_gst ? 'Non-GST / Unregistered' : (((float) $invoice_data['tax_gst_amount'] > 0) ? 'GST Applied' : 'GST Exempt / Nil');
$billStatus = !empty($invoice_data['status']) ? ucfirst(strtolower($invoice_data['status'])) : 'Unknown';
$companyWords = preg_split('/\s+/', trim($firm->name));
$companyInitials = '';
foreach ($companyWords as $word) {
	$companyInitials .= strtoupper(substr($word, 0, 1));
	if (strlen($companyInitials) >= 3) {
		break;
	}
}

/* ------------------------------------------------------------------ */
/* SECURITY: tamper-proof verification seal.                          */
/* A code derived via HMAC-SHA256 over the invoice's key fields using */
/* the app secret. Altering ANY of these fields changes the code, so  */
/* a forged/edited invoice fails verification. A matching QR encodes   */
/* the same authoritative values + code for a quick cross-check.       */
/* ------------------------------------------------------------------ */
helper(['invoice', 'pdf_theme']);

$T = function_exists('pdf_theme_config')
    ? pdf_theme_config(isset($invoice_data['template_id']) ? $invoice_data['template_id'] : 0, $invoice_data['billing_date'], $doc_module)
    : (function_exists('pdf_theme_defaults') ? pdf_theme_defaults() : array('primary'=>'#2f57a6','accent'=>'#e0902a','total'=>'#2f9e6f','title_alt'=>'#17a2b8','seal'=>'#2f7d52','watermark'=>1,'seal_on'=>1));
$sec_code = invoice_seal_code_fmt($invoice_data);   // XXXX-XXXX-XXXX-XXXX (printed)
$sec_raw  = invoice_seal_code_raw($invoice_data);   // 16-hex (in the QR URL)

// QR payload: for the Bill of Supply (invoice) with a real bos_id it encodes the
// live VERIFY URL (scanning opens the public verification page). For other
// document types the online verifier isn't wired, so it encodes a self-describing
// summary instead of a broken link.
$sec_bos_id = isset($invoice_data['bos_id']) ? $invoice_data['bos_id'] : '';
// The record id the verifier loads by. Most types use bos_id; the Tax Invoice
// passes its own PK via $pdf_doc['verify_id'] (its bos_id points at the BOS).
$sec_verify_id = (isset($pdf_doc['verify_id']) && $pdf_doc['verify_id'] !== '') ? $pdf_doc['verify_id'] : $sec_bos_id;
// The verifier "type" for the URL — defaults to the theme module, but a doc can
// override it (e.g. the e-invoice reuses the taxinvoice THEME but verifies via a
// different data path, so it sets verify_type = 'einvoice').
$doc_vtype = (isset($pdf_doc['verify_type']) && $pdf_doc['verify_type'] !== '') ? $pdf_doc['verify_type'] : $doc_module;
// Types wired into the public verifier (each has a session-independent lookup
// that reproduces the seal). Others keep the offline text QR.
$sec_verify_types = array('invoice', 'uninvoice', 'payment_receipt', 'taxinvoice', 'einvoice', 'delivery_challan');
$sec_is_verifiable = ($doc_verify && in_array($doc_vtype, $sec_verify_types, true) && $sec_verify_id !== '');
if ($sec_is_verifiable) {
	$sec_qr_payload = base_url('invoice_verify/check/' . rawurlencode($doc_vtype) . '/' . (function_exists('ID_encode') ? ID_encode($sec_verify_id) : $sec_verify_id) . '/' . $sec_raw);
} else {
	$sec_qr_payload = "C R INDUSTRIES\n" . $invoiceType . "\n"
		. 'No: ' . (isset($invoice_data['FY']) ? $invoice_data['FY'] : '') . '-' . (isset($invoice_data['invoice_id']) ? $invoice_data['invoice_id'] : '') . "\n"
		. 'Party: ' . trim((string) (isset($invoice_data['contact_person_name']) ? $invoice_data['contact_person_name'] : '')) . "\n"
		. 'Total: ' . (isset($invoice_data['total_invoice']) ? $invoice_data['total_invoice'] : '') . "\n"
		. 'Code: ' . $sec_code;
}

// Build an OFFLINE QR PNG (data URI) so dompdf can embed it without any network.
$sec_qr_uri = '';
$__qr_lib = APPPATH . 'ThirdParty/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';
if (is_file($__qr_lib) && function_exists('imagecreatetruecolor')) {
	require_once $__qr_lib;
	try {
		$__qr = new TCPDF2DBarcode($sec_qr_payload, 'QRCODE,M');
		$__a = $__qr->getBarcodeArray();
		if (!empty($__a['bcode']) && !empty($__a['num_cols']) && !empty($__a['num_rows'])) {
			$__s = 5; $__q = 4 * $__s;
			$__w = (int) $__a['num_cols'] * $__s + 2 * $__q;
			$__h = (int) $__a['num_rows'] * $__s + 2 * $__q;
			$__im = imagecreatetruecolor($__w, $__h);
			$__white = imagecolorallocate($__im, 255, 255, 255);
			$__ink = imagecolorallocate($__im, 47, 75, 124);   // matches the light-blue ink
			imagefilledrectangle($__im, 0, 0, $__w - 1, $__h - 1, $__white);
			foreach ($__a['bcode'] as $__r => $__row) {
				foreach ($__row as $__c => $__on) {
					if ($__on) {
						$__x = $__q + $__c * $__s; $__y = $__q + $__r * $__s;
						imagefilledrectangle($__im, $__x, $__y, $__x + $__s - 1, $__y + $__s - 1, $__ink);
					}
				}
			}
			ob_start(); imagepng($__im); $__png = ob_get_clean(); imagedestroy($__im);
			if ($__png) { $sec_qr_uri = 'data:image/png;base64,' . base64_encode($__png); }
		}
	} catch (Exception $e) { $sec_qr_uri = ''; }
}
?>
<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $title; ?></title>
	<style type="text/css">
		/* ===========================================================
		   Colorful theme — royal-blue hero + teal/amber/green accents.
		   Solid fills only (dompdf-reliable).
		   =========================================================== */
		@page { size: A4 portrait; margin: 4mm; }
		* { box-sizing: border-box; }

		body {
			margin: 0; padding: 0; background: #ffffff; color: #26313f;
			font-family: Arial, Helvetica, sans-serif; font-size: 9px; line-height: 1.16;
		}

		.invoice-wrap {
			position: relative; width: 100%; max-width: 980px; height: 286mm; margin: 0 auto;
			background: #ffffff; border: 2px solid #2f57a6; border-radius: 8px; overflow: hidden;
			page-break-inside: avoid;
		}

		.watermark {
			position: absolute; left: 40px; right: 40px; top: 300px;
			text-align: center; z-index: 0;
		}
		.wm-type { color: #e9eef9; font-size: 60px; font-weight: bold; letter-spacing: 6px; }
		.wm-secured { margin-top: 4px; color: #eaf4ee; font-size: 22px; font-weight: bold; letter-spacing: 10px; }
		.wm-code {
			margin-top: 8px; color: #e4ebf7; font-size: 30px; font-weight: bold;
			letter-spacing: 8px; font-family: "Courier New", Courier, monospace;
		}

		/* rainbow side ribbon (stacked solid bands) */
		.side-accent { position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: #2f57a6; z-index: 2; }

		.header-band { width: 100%; border-collapse: collapse; background: #eef3fb; border-bottom: 2px solid #2f57a6; }
		.header-band td { padding: 3px 11px; font-size: 8.4px; font-weight: bold; color: #3a4a63; }

		.top-hero { width: 100%; border-collapse: collapse; background: #2f57a6; color: #ffffff; border-bottom: 4px solid #e0902a; }
		.top-hero td { vertical-align: middle; }
		.hero-brand-cell { padding: 12px 13px 6px; text-align: center; }
		.hero-meta-band { padding: 6px 13px 9px; border-top: 1px solid #4a6fb8; }

		/* Modern app-icon monogram: white card > amber tile > white initials */
		.logo-mark {
			width: 60px; height: 60px; border-radius: 15px; background: #ffffff;
			text-align: center; border: 1px solid #e6c58f;
		}
		.logo-core {
			width: 46px; height: 46px; margin: 7px auto 0; border-radius: 12px;
			background: #e0902a; color: #ffffff; line-height: 46px;
			font-size: 20px; font-weight: 900; letter-spacing: 1.5px;
		}

		.hero-brand-name { margin: 0; color: #ffffff; font-size: 27px; font-weight: 900; letter-spacing: 1.2px; text-transform: uppercase; text-align: center; }
		.hero-brand-address { margin-top: 4px; color: #d6e2f7; font-size: 8.8px; font-weight: bold; text-align: center; }

		.hero-label {
			display: inline-block; padding: 5px 10px; border: 1px solid #f0b968; border-radius: 18px;
			background: #e0902a; color: #ffffff; font-size: 11px; font-weight: bold; text-transform: uppercase;
		}
		.hero-seal {
			display: inline-block; margin-left: 4px; padding: 4px 9px; border-radius: 18px;
			border: 1px solid #7fd0a3; background: #2f9e6f; color: #ffffff; font-size: 8.5px; font-weight: bold; text-transform: uppercase;
		}
		.hero-meta { display: block; color: #d6e2f7; font-size: 8.6px; font-weight: bold; }
		.hero-total { display: inline-block; margin-top: 5px; padding: 4px 11px; border-radius: 6px; background: #ffffff; color: #2f57a6; font-size: 11.5px; font-weight: bold; }

		.document-ribbon { width: 100%; border-collapse: collapse; background: #f0f5ff; border-bottom: 1px solid #cdd9ec; }
		.document-ribbon td { width: 20%; padding: 5px 10px; border-right: 1px solid #d5e0f2; color: #3a4a63; font-size: 8px; font-weight: bold; vertical-align: top; }
		.document-ribbon td:last-child { border-right: 0; }
		.document-ribbon span { display: block; margin-bottom: 2px; color: #2f57a6; font-size: 7.2px; text-transform: uppercase; }

		.section { position: relative; z-index: 1; padding: 5px 9px; page-break-inside: avoid; }

		.stat-strip { width: 100%; border-collapse: collapse; }
		.stat-card { width: 24%; padding: 5px 7px; border: 1px solid #dfe6f0; border-radius: 6px; background: #fbfdff; vertical-align: top; }
		.grid-gap { width: 1.4%; padding: 0; border: 0; background: transparent; }
		.stat-card span { display: block; color: #7b8794; font-size: 7.5px; font-weight: bold; text-transform: uppercase; }
		.stat-card strong { display: block; margin-top: 2px; font-size: 9.8px; font-weight: bold; }
		/* each stat card a different accent */
		.sc-blue  { border-top: 3px solid #2f57a6; } .sc-blue strong  { color: #2f57a6; }
		.sc-teal  { border-top: 3px solid #17a2b8; } .sc-teal strong  { color: #12808f; }
		.sc-amber { border-top: 3px solid #e0902a; } .sc-amber strong { color: #b9721c; }
		.sc-green { border-top: 3px solid #2f9e6f; } .sc-green strong { color: #227a54; }

		.info-grid, .bottom-grid, .closing-grid { width: 100%; border-collapse: collapse; }
		.info-card, .bottom-card, .closing-card { width: 49%; vertical-align: top; border: 1px solid #cdd9ec; border-radius: 6px; background: #ffffff; padding: 0; }
		.grid-gap2 { width: 2%; padding: 0; border: 0; background: transparent; }

		.card-title { padding: 5px 8px; background: #2f57a6; color: #ffffff; font-size: 9.5px; font-weight: bold; text-align: center; }
		.card-title.teal { background: #17a2b8; }

		.detail-table { width: 100%; border-collapse: collapse; }
		.detail-table td { padding: 3px 6px; border-bottom: 1px solid #eef2f8; font-size: 8.3px; font-weight: bold; vertical-align: top; }
		.detail-table tr:last-child td { border-bottom: 0; }
		.label { width: 78px; color: #2f57a6; }

		.items-table { width: 100%; border-collapse: collapse; border: 1px solid #cdd9ec; }
		.items-table th { padding: 5px 4px; background: #2f57a6; border: 1px solid #cdd9ec; color: #ffffff; font-size: 9px; font-weight: bold; text-align: center; }
		.items-table td { padding: 4px 4px; border: 1px solid #dfe6f0; font-size: 9px; text-align: center; vertical-align: middle; }
		.items-table .description { text-align: left; font-weight: bold; color: #24344f; }

		.summary-row td { background: #f0f5ff; font-weight: bold; }
		.bank-cell { text-align: left !important; color: #3a4a63; font-size: 8.3px !important; }
		.total-label { text-align: right !important; color: #2f57a6; font-weight: bold; }

		.grand-total td { background: #2f9e6f; color: #ffffff; font-size: 10.5px; font-weight: bold; }
		.grand-total .total-label { color: #ffffff; }

		.note-line { padding: 5px 8px; border: 1px solid #f0b968; border-left: 4px solid #e0902a; border-radius: 6px; background: #fff6e9; font-size: 8.4px; font-weight: bold; color: #7a531a; }

		.closing-card { height: 58px; padding: 7px 8px; font-size: 8.2px; font-weight: bold; line-height: 1.45; color: #5b6b82; }
		.closing-card strong { display: block; margin-bottom: 3px; color: #2f57a6; font-size: 9px; text-transform: uppercase; }

		.receipt-row { width: 100%; border-collapse: collapse; margin-top: 8px; }
		.receipt-row td { width: 33.33%; padding-top: 11px; border-top: 1px solid #cdd9ec; color: #7b8794; font-size: 8px; text-align: center; }

		.footer-section { padding-top: 3px; }
		.signature-box { height: 86px; text-align: center; padding: 7px; font-weight: bold; color: #46545a; }
		.signature-box img { height: 34px; margin: 7px 0 4px; }
		.signatory-title { margin-top: 4px; color: #2f57a6; font-size: 9.5px; font-weight: bold; }
		.certified-line { color: #7b8794; font-size: 8px; font-weight: bold; }

		/* ---- Security / verification strip ---- */
		.security-strip { width: 100%; border-collapse: collapse; margin: 4px 0 0; }
		.security-box {
			border: 1px solid #cdd9ec; border-left: 4px solid #2f7d52; border-radius: 6px;
			background: #f7fbf9; padding: 7px 9px; vertical-align: middle;
		}
		.sec-qr-cell { width: 74px; text-align: center; vertical-align: middle; padding-right: 8px; }
		.sec-qr-cell img { width: 66px; height: 66px; border: 1px solid #dfe6f0; background: #fff; }
		.sec-title { color: #2f7d52; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: .3px; }
		.sec-code { display: inline-block; margin-top: 3px; padding: 3px 9px; border: 1px solid #bfe0cb; border-radius: 5px; background: #ffffff; color: #24344f; font-size: 12px; font-weight: bold; letter-spacing: 1.5px; font-family: "Courier New", Courier, monospace; }
		.sec-note { margin-top: 4px; color: #5b6b82; font-size: 7.6px; font-weight: bold; line-height: 1.4; }

		.computer-note {
			position: absolute; left: 10px; right: 10px; bottom: 8px; padding: 5px 10px;
			background: #f6f9fd; border: 1px solid #dfe6f0; border-radius: 6px; color: #5b6b82;
			font-size: 8.5px; font-weight: bold; text-align: center;
		}
		.print-copy { color: #2f4b7c; font-weight: bold; }

		@media print { body { background: #ffffff; } .invoice-wrap { border-radius: 0; } }
	</style>
	<?php
	$__d = function_exists('pdf_theme_defaults') ? pdf_theme_defaults() : array();
	$__g = function ($k, $fb) use ($T, $__d) { if (isset($T[$k])) return $T[$k]; if (isset($__d[$k])) return $__d[$k]; return $fb; };
	$__p   = $__g('primary', '#2f57a6');   $__a  = $__g('accent', '#e0902a');
	$__tot = $__g('total', '#2f9e6f');     $__ta = $__g('title_alt', '#17a2b8');
	$__seal= $__g('seal', '#2f7d52');      $__ink = $__g('ink', '#26313f');
	$__mut = $__g('muted', '#7b8794');     $__bd = $__g('border', '#cdd9ec');
	$__hbg = $__g('header_bg', '#eef3fb'); $__htx = $__g('header_text', '#3a4a63');
	$__hero= $__g('hero_text', '#ffffff'); $__tt = $__g('title_text', '#ffffff');
	$__tht = $__g('th_text', '#ffffff');   $__ttx = $__g('total_text', '#ffffff');
	$__wm  = $__g('watermark_col', '#e9eef9');
	?>
	<!-- Theme overrides (super-admin PDF Theme Manager) -->
	<style type="text/css">
		body { color: <?= $__ink ?>; }
		.items-table .description, .items-table td { color: <?= $__ink ?>; }
		.invoice-wrap { border-color: <?= $__p ?>; }
		.side-accent { background: <?= $__p ?>; }
		.header-band { background: <?= $__hbg ?>; border-bottom-color: <?= $__p ?>; }
		.header-band td { color: <?= $__htx ?>; }
		.top-hero { background: <?= $__p ?>; border-bottom-color: <?= $__a ?>; }
		.hero-brand-name, .hero-brand-address, .hero-meta { color: <?= $__hero ?>; }
		.logo-mark { border-color: <?= $__a ?>; }
		.logo-core { background: <?= $__a ?>; }
		.hero-label { background: <?= $__a ?>; border-color: <?= $__a ?>; }
		.hero-total { color: <?= $__p ?>; }
		.document-ribbon { background: <?= $__hbg ?>; } .document-ribbon span { color: <?= $__p ?>; } .document-ribbon td { color: <?= $__htx ?>; }
		.card-title { background: <?= $__p ?>; color: <?= $__tt ?>; }
		.card-title.teal { background: <?= $__ta ?>; }
		.label { color: <?= $__mut ?>; }
		.total-label, .closing-card strong, .signatory-title { color: <?= $__p ?>; }
		.stat-card span, .detail-table td, .closing-card, .certified-line { color: <?= $__mut ?>; }
		.items-table { border-color: <?= $__p ?>; }
		.items-table th { background: <?= $__p ?>; color: <?= $__tht ?>; }
		.items-table td, .info-card, .bottom-card, .closing-card, .stat-card, .detail-table td { border-color: <?= $__bd ?>; }
		.grand-total td { background: <?= $__tot ?>; color: <?= $__ttx ?>; }
		.grand-total .total-label { color: <?= $__ttx ?>; }
		.note-line { border-left-color: <?= $__a ?>; }
		.security-box { border-left-color: <?= $__seal ?>; }
		.sec-title { color: <?= $__seal ?>; }
		.wm-type, .wm-secured, .wm-code { color: <?= $__wm ?>; }
		<?php if (empty($T['watermark'])): ?>.watermark { display: none !important; }<?php endif; ?>
		<?php if (empty($T['seal_on'])): ?>.security-strip, .hero-seal { display: none !important; }<?php endif; ?>
	</style>
</head>

<body>
	<div class="invoice-wrap">
		<div class="side-accent"></div>
		<div class="watermark">
			<div class="wm-type"><?php echo $invoiceType; ?></div>
			<div class="wm-secured">&#183; SECURED &#183;</div>
			<div class="wm-code"><?php echo $sec_code; ?></div>
		</div>
		<table class="header-band" cellspacing="0" cellpadding="0">
			<tr>
				<td>All Subject Under Hardoi Jurisdiction</td>
				<td align="right">Mob. 7398703084, 9415777518</td>
			</tr>
			<tr>
				<td>GSTIN: <?php echo $firm->gst_no; ?></td>
				<td align="right">8800210190, 8887905070</td>
			</tr>
			<tr>
				<td>FSSAI No.: <?php echo $firm->fssai_no; ?></td>
				<td align="right">Email: <?php echo $firm->company_email; ?></td>
			</tr>
			<tr>
				<td>Mandi Samiti License: <?php echo $firm->mandi_license_mandi; ?></td>
				<td align="right">State Code: <?php echo $stateCode; ?></td>
			</tr>
			<tr>
				<td>Rice Milling License: <?php echo $firm->mandi_license_mill; ?></td>
				<td align="right"><span class="print-copy">Original for Recipient</span></td>
			</tr>
		</table>

		<table class="top-hero" cellspacing="0" cellpadding="0">
			<tr>
				<td align="center" class="hero-brand-cell">
					<table align="center" cellspacing="0" cellpadding="0" style="margin:0 auto;">
						<tr>
							<td valign="middle"><div class="logo-mark"><div class="logo-core"><?php echo $companyInitials; ?></div></div></td>
							<td valign="middle" style="padding-left:12px; text-align:center;">
								<h1 class="hero-brand-name"><?php echo $firm->name; ?></h1>
								<div class="hero-brand-address"><?php echo $firm->address; ?></div>
							</td>
							<td width="72"></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="hero-meta-band">
					<table cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse;">
						<tr>
							<td valign="middle" align="left" width="50%">
								<span class="hero-label"><?php echo $invoiceType; ?></span>
								<span class="hero-seal">&#10003; Secured</span>
							</td>
							<td valign="middle" align="right" width="50%">
								<div class="hero-meta">Invoice No. <?php echo $invoice_data['invoice_id']; ?> &#183; <?php echo $invoiceDateFormatted; ?> &#183; FY <?php echo $invoice_data['FY']; ?> &#183; <?php echo $billStatus; ?></div>
								<div class="hero-total">Total: <?php echo $invoice_data['total_invoice']; ?></div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>

		<table class="document-ribbon" cellspacing="0" cellpadding="0">
			<tr>
				<td><span>Document</span><?php echo $invoiceType; ?> / Original</td>
				<td><span>GST Status</span><?php echo $gstStatus; ?></td>
				<td><span>Supply State</span><?php echo $consigneeState; ?> (<?php echo $consigneeStateCode; ?>)</td>
				<td><span>Bill Status</span><?php echo $billStatus; ?></td>
				<td><span>Settlement</span>Bank transfer / Account payee</td>
			</tr>
		</table>

		<div class="section">
			<table class="stat-strip" cellspacing="0" cellpadding="0">
				<tr>
					<td class="stat-card sc-blue">
						<span>Billed Party</span>
						<strong><?php echo $invoice_data['contact_person_name']; ?></strong>
					</td>
					<td class="grid-gap"></td>
					<td class="stat-card sc-teal">
						<span>Product</span>
						<strong><?php echo $invoice_data['product_name']; ?></strong>
					</td>
					<td class="grid-gap"></td>
					<td class="stat-card sc-amber">
						<?php if ($doc_show_vehicle): ?>
						<span>Vehicle</span>
						<strong><?php echo $invoice_data['truck_no']; ?></strong>
						<?php else: ?>
						<span>State</span>
						<strong><?php echo $invoice_data['state']; ?> (<?php echo $invoice_data['state_code']; ?>)</strong>
						<?php endif; ?>
					</td>
					<td class="grid-gap"></td>
					<td class="stat-card sc-green">
						<span>Invoice Total</span>
						<strong><?php echo $invoice_data['total_invoice']; ?></strong>
					</td>
				</tr>
			</table>
		</div>

		<div class="section">
			<table class="info-grid" cellspacing="0" cellpadding="0">
				<tr>
					<td class="info-card">
						<div class="card-title">Detail of Receiver / Billed To</div>
						<table class="detail-table" cellspacing="0" cellpadding="0">
							<tr>
								<td class="label">Name</td>
								<td><?php echo $invoice_data['contact_person_name']; ?></td>
							</tr>
							<tr>
								<td class="label">Address</td>
								<td><?php echo $invoice_data['purchaser_address']; ?></td>
							</tr>
							<tr>
								<td class="label">GSTIN</td>
								<td><?php echo $doc_show_gst ? $invoice_data['purchaser_gst_no'] : 'Unregistered'; ?></td>
							</tr>
							<tr>
								<td class="label">State</td>
								<td><?php echo $invoice_data['state']; ?></td>
							</tr>
							<tr>
								<td class="label">State Code</td>
								<td><?php echo $invoice_data['state_code']; ?></td>
							</tr>
						</table>
					</td>
					<td class="grid-gap2"></td>
					<td class="info-card">
						<div class="card-title teal">Details of Consignee / Shipped To</div>
						<table class="detail-table" cellspacing="0" cellpadding="0">
							<tr>
								<td class="label">Name</td>
								<td><?php echo $consigneeName; ?></td>
							</tr>
							<tr>
								<td class="label">Address</td>
								<td><?php echo $consigneeAddress; ?></td>
							</tr>
							<tr>
								<td class="label">GSTIN</td>
								<td><?php echo $doc_show_gst ? $consigneeGst : 'Unregistered'; ?></td>
							</tr>
							<tr>
								<td class="label">State</td>
								<td><?php echo $consigneeState; ?></td>
							</tr>
							<tr>
								<td class="label">State Code</td>
								<td><?php echo $consigneeStateCode; ?></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>

		<div class="section">
			<table class="items-table" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th width="7%">S.No.</th>
						<th width="28%">Description of Goods</th>
						<th width="12%">HSN Code</th>
						<th width="9%">UOM</th>
						<th width="10%">Qty.</th>
						<th width="12%">Rate</th>
						<th width="14%">Amount</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>1</td>
						<td class="description"><?php echo $invoice_data['product_name']; ?></td>
						<td><?php echo $invoice_data['hsn_code']; ?></td>
						<td><?php echo $invoice_data['uom']; ?></td>
						<td><?php echo $invoice_data['quantity']; ?></td>
						<td><?php echo $invoice_data['rate']; ?></td>
						<td><?php echo $invoice_data['amount']; ?></td>
					</tr>
					<tr class="summary-row">
						<td colspan="2" class="bank-cell">Bank Detail</td>
						<td colspan="3"></td>
						<td class="total-label">Total</td>
						<td><?php echo $invoice_data['amount']; ?></td>
					</tr>
					<tr>
						<td colspan="2" class="bank-cell">Name: <?php echo $firm->bank_name; ?></td>
						<td colspan="3"></td>
						<td class="total-label"><?php echo $doc_show_gst ? 'CGST @ '.$invoice_data['cgst'].'%' : ''; ?></td>
						<td><?php echo $doc_show_gst ? $invoice_data['cgst_amount'] : ''; ?></td>
					</tr>
					<tr>
						<td colspan="2" class="bank-cell">A/C No.: <?php echo $firm->bank_number; ?></td>
						<td colspan="3"></td>
						<td class="total-label"><?php echo $doc_show_gst ? 'SGST @ '.$invoice_data['sgst'].'%' : ''; ?></td>
						<td><?php echo $doc_show_gst ? $invoice_data['sgst_amount'] : ''; ?></td>
					</tr>
					<tr>
						<td colspan="2" class="bank-cell">IFSC Code: <?php echo $firm->bank_ifsc; ?></td>
						<td colspan="3"></td>
						<td class="total-label"><?php echo $doc_show_gst ? 'IGST @ '.$invoice_data['igst'].'%' : ''; ?></td>
						<td><?php echo $doc_show_gst ? $invoice_data['igst_amount'] : ''; ?></td>
					</tr>
					<tr>
						<td colspan="2" class="bank-cell"></td>
						<td colspan="3"></td>
						<td class="total-label"><?php echo $doc_show_gst ? 'Tax Amount: GST' : ''; ?></td>
						<td><?php echo $doc_show_gst ? $invoice_data['tax_gst_amount'] : ''; ?></td>
					</tr>
					<tr>
						<td class="total-label">Remark</td>
						<td colspan="4" class="bank-cell"><?php echo $invoice_data['invoice_remark']; ?></td>
						<td class="total-label"><?php echo $doc_show_gst ? 'Amount After Tax' : 'Net Amount'; ?></td>
						<td><?php echo $invoice_data['total_invoice']; ?></td>
					</tr>
					<tr>
						<td colspan="5"></td>
						<td class="total-label">Freight</td>
						<td><?php echo $invoice_data['freight']; ?></td>
					</tr>
					<tr>
						<td colspan="5"></td>
						<td class="total-label">Advance</td>
						<td><?php echo $invoice_data['others']; ?></td>
					</tr>
					<tr class="grand-total">
						<td colspan="5"></td>
						<td class="total-label">Total Invoice Amount</td>
						<td><?php echo $invoice_data['total_invoice']; ?></td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="section">
			<div class="note-line">Total Amount in Words: <?php echo getIndianCurrency($invoice_data['total_invoice']); ?></div>
		</div>

		<div class="section">
			<table class="closing-grid" cellspacing="0" cellpadding="0">
				<tr>
					<td class="closing-card">
						<strong>Commercial Notes</strong>
						Goods: <?php echo $invoice_data['product_name']; ?> | HSN: <?php echo $invoice_data['hsn_code']; ?> | Qty: <?php echo $invoice_data['quantity']; ?> <?php echo $invoice_data['uom']; ?><br />
						Bank: <?php echo $firm->bank_name; ?> | IFSC: <?php echo $firm->bank_ifsc; ?>
					</td>
					<td class="grid-gap2"></td>
					<td class="closing-card">
						<strong>Declaration & Checks</strong>
						Particulars, rate, weight, and vehicle details are verified as per records.
						<table class="receipt-row" cellspacing="0" cellpadding="0">
							<tr>
								<td>Prepared By</td>
								<td>Checked By</td>
								<td>Received By</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>

		<div class="section footer-section">
			<table class="bottom-grid" cellspacing="0" cellpadding="0">
				<tr>
					<td class="bottom-card">
						<?php if ($doc_show_vehicle): ?>
						<div class="card-title">Detail of Vehicle</div>
						<table class="detail-table" cellspacing="0" cellpadding="0">
							<tr>
								<td class="label">Reference</td>
								<td>Bill of Supply: ..................</td>
							</tr>
							<tr>
								<td class="label">Truck No</td>
								<td><?php echo $invoice_data['truck_no']; ?></td>
							</tr>
							<tr>
								<td class="label">Date</td>
								<td><?php echo $invoiceDateFormatted; ?></td>
							</tr>
							<tr>
								<td class="label">Transport</td>
								<td>SAME</td>
							</tr>
							<tr>
								<td class="label">Driver</td>
								<td><?php echo $invoice_data['driver_name']; ?></td>
							</tr>
						</table>
						<?php else: ?>
						<div class="card-title">Detail of Supply</div>
						<table class="detail-table" cellspacing="0" cellpadding="0">
							<tr>
								<td class="label">Bill No</td>
								<td><?php echo $invoice_data['invoice_id']; ?> &middot; FY <?php echo $invoice_data['FY']; ?></td>
							</tr>
							<tr>
								<td class="label">Date</td>
								<td><?php echo $invoiceDateFormatted; ?></td>
							</tr>
							<tr>
								<td class="label">State</td>
								<td><?php echo $invoice_data['state']; ?></td>
							</tr>
							<tr>
								<td class="label">State Code</td>
								<td><?php echo $invoice_data['state_code']; ?></td>
							</tr>
							<tr>
								<td class="label">Type</td>
								<td>Unregistered / Non-GST</td>
							</tr>
						</table>
						<?php endif; ?>
					</td>
					<td class="grid-gap2"></td>
					<td class="bottom-card">
						<div class="signature-box">
							<div class="certified-line">Certified that the particulars given above are true and correct</div>
							<br />
							<img src="assets/images/sign_03.jpeg" alt="" />
							<br />
							<div class="signatory-title">For <?php echo $firm->name; ?></div>
							(Partnership / Authorised Signatory)
						</div>
					</td>
				</tr>
			</table>
		</div>

		<!-- SECURITY: verification seal (QR + tamper-proof code) -->
		<div class="section" style="padding-top:0;"><table class="security-strip" cellspacing="0" cellpadding="0">
			<tr>
				<td class="security-box">
					<table cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse;">
						<tr>
							<?php if ($sec_qr_uri !== '') { ?>
							<td class="sec-qr-cell"><img src="<?php echo $sec_qr_uri; ?>" alt="verify" /></td>
							<?php } ?>
							<td style="vertical-align:middle;">
								<div class="sec-title">&#128274; Secure Verification Seal</div>
								<span class="sec-code"><?php echo $sec_code; ?></span>
								<div class="sec-note">
									<?php if ($sec_is_verifiable): ?>
										<b>Scan the QR to verify this document.</b> It opens a secure page that instantly confirms whether it is genuine and unmodified. This code is cryptographically derived from the contents &mdash; any alteration invalidates it.
									<?php else: ?>
										This secure code is cryptographically derived from the document contents (party, product, quantity, rate &amp; total) &mdash; any alteration invalidates it. Match it with C R Industries to confirm authenticity.
									<?php endif; ?>
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>

		</div>

		<div class="computer-note">
			This is a computer generated bill. No stamp or physical signature is required.
		</div>
	</div>
</body>

</html>
<?php } ?>
