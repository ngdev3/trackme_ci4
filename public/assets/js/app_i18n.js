/* ===========================================================================
 * app_i18n.js — lightweight per-user UI language pass for the sidebar navigation.
 *
 * Driven by window.APP_LANG ('en' | 'hi' | 'hinglish'), set from the logged-in
 * user's saved choice (admin/setting/language). It translates ONLY fixed
 * navigation labels (.sidebar-menu) — never data — so it is safe and cannot
 * corrupt account names, amounts, etc.
 *
 * Design:
 *  - DICT holds one entry per label with all three language variants.
 *  - We index EVERY variant string -> entry, so no matter what language a label
 *    is currently showing, we can re-translate it to the target. This makes the
 *    pass idempotent and reversible (switch languages any number of times).
 *  - Labels not in DICT are left untouched (graceful, incremental coverage).
 *
 * To extend: add an entry to DICT (en / hi / hinglish). To cover more screens,
 * call window.AppI18n.translate(rootEl) after rendering.
 * ======================================================================== */
(function () {
	'use strict';

	// en = English source · hi = Hindi · hinglish = Roman/mixed
	var DICT = [
		// ---- Top-level sections ----
		{ en: 'Dashboard', hi: 'डैशबोर्ड', hinglish: 'Dashboard' },
		{ en: 'Documents Renewal', hi: 'दस्तावेज़ नवीनीकरण', hinglish: 'Documents Renewal' },
		{ en: 'Letter Pad', hi: 'लेटर पैड', hinglish: 'Letter Pad' },
		{ en: 'APK Manager', hi: 'APK मैनेजर', hinglish: 'APK Manager' },
		{ en: 'Notification', hi: 'सूचना', hinglish: 'Notification' },
		{ en: 'Device Management', hi: 'डिवाइस प्रबंधन', hinglish: 'Device Management' },
		{ en: 'Tasks', hi: 'कार्य', hinglish: 'Tasks' },
		{ en: 'Traffic Module', hi: 'ट्रैफ़िक मॉड्यूल', hinglish: 'Traffic Module' },
		{ en: 'Password Manager', hi: 'पासवर्ड मैनेजर', hinglish: 'Password Manager' },
		{ en: 'Account Name', hi: 'खाता नाम', hinglish: 'Account Name' },
		{ en: 'FCI Driver', hi: 'FCI ड्राइवर', hinglish: 'FCI Driver' },
		{ en: 'FCI Truck', hi: 'FCI गाड़ी', hinglish: 'FCI Truck' },
		{ en: 'Attendance', hi: 'उपस्थिति', hinglish: 'Attendance' },
		{ en: 'Jama Naam Voucher', hi: 'जमा-नाम वाउचर', hinglish: 'Jama Naam Voucher' },
		{ en: 'Cold Lot System', hi: 'कोल्ड लॉट सिस्टम', hinglish: 'Cold Lot System' },
		{ en: 'Cold Storage Inventory', hi: 'कोल्ड स्टोरेज इन्वेंटरी', hinglish: 'Cold Storage Inventory' },
		{ en: 'Purchase From Farmers', hi: 'किसानों से खरीद', hinglish: 'Kisano Se Purchase' },
		{ en: 'Purchase Module', hi: 'खरीद मॉड्यूल', hinglish: 'Purchase Module' },
		{ en: 'Kisan Vahi', hi: 'किसान वही', hinglish: 'Kisan Vahi' },
		{ en: 'Bill Of Supply', hi: 'बिल ऑफ सप्लाई', hinglish: 'Bill Of Supply' },
		{ en: 'Unregistered BOS', hi: 'अपंजीकृत BOS', hinglish: 'Unregistered BOS' },
		{ en: 'E-Tax Invoice', hi: 'ई-टैक्स इनवॉइस', hinglish: 'E-Tax Invoice' },
		{ en: 'Credit/Debit Note', hi: 'क्रेडिट/डेबिट नोट', hinglish: 'Credit/Debit Note' },
		{ en: 'Billing Register', hi: 'बिलिंग रजिस्टर', hinglish: 'Billing Register' },
		{ en: 'Delivery Challan', hi: 'डिलीवरी चालान', hinglish: 'Delivery Challan' },
		{ en: 'Paddy Center Challan', hi: 'धान केंद्र चालान', hinglish: 'Paddy Center Challan' },
		{ en: 'Lot System', hi: 'लॉट सिस्टम', hinglish: 'Lot System' },
		{ en: 'Stock Records', hi: 'स्टॉक रिकॉर्ड', hinglish: 'Stock Records' },
		{ en: 'Accounting Reports', hi: 'लेखा रिपोर्ट', hinglish: 'Accounting Reports' },
		{ en: 'Reports', hi: 'रिपोर्ट', hinglish: 'Reports' },
		{ en: 'Salary Module', hi: 'वेतन मॉड्यूल', hinglish: 'Salary Module' },
		{ en: 'Users', hi: 'उपयोगकर्ता', hinglish: 'Users' },
		{ en: 'Setting', hi: 'सेटिंग', hinglish: 'Setting' },
		{ en: 'Settings', hi: 'सेटिंग', hinglish: 'Settings' },
		{ en: 'App Settings', hi: 'ऐप सेटिंग्स', hinglish: 'App Settings' },
		{ en: 'Rice Mill Website', hi: 'राइस मिल वेबसाइट', hinglish: 'Rice Mill Website' },
		{ en: 'SEO & Search', hi: 'SEO और सर्च', hinglish: 'SEO & Search' },
		{ en: 'Cold Storage Inventory', hi: 'कोल्ड स्टोरेज इन्वेंटरी', hinglish: 'Cold Storage Inventory' },

		// ---- Common child links ----
		{ en: 'Add', hi: 'जोड़ें', hinglish: 'Add' },
		{ en: 'Listing', hi: 'सूची', hinglish: 'Listing' },
		{ en: 'Report', hi: 'रिपोर्ट', hinglish: 'Report' },
		{ en: 'Overview', hi: 'अवलोकन', hinglish: 'Overview' },
		{ en: 'Registration', hi: 'पंजीकरण', hinglish: 'Registration' },
		{ en: 'Registrations List', hi: 'पंजीकरण सूची', hinglish: 'Registrations List' },
		{ en: 'Reg Report', hi: 'पंजीकरण रिपोर्ट', hinglish: 'Reg Report' },
		{ en: 'Add Docs', hi: 'दस्तावेज़ जोड़ें', hinglish: 'Add Docs' },
		{ en: 'Create Letter', hi: 'पत्र बनाएँ', hinglish: 'Create Letter' },
		{ en: 'Letters', hi: 'पत्र', hinglish: 'Letters' },
		{ en: 'Upload APK', hi: 'APK अपलोड करें', hinglish: 'Upload APK' },
		{ en: 'Download Portal', hi: 'डाउनलोड पोर्टल', hinglish: 'Download Portal' },
		{ en: 'Download Logs', hi: 'डाउनलोड लॉग', hinglish: 'Download Logs' },
		{ en: 'Send Notification', hi: 'सूचना भेजें', hinglish: 'Send Notification' },
		{ en: 'Registered Devices', hi: 'पंजीकृत डिवाइस', hinglish: 'Registered Devices' },
		{ en: 'All Tasks', hi: 'सभी कार्य', hinglish: 'All Tasks' },
		{ en: 'Add Task', hi: 'कार्य जोड़ें', hinglish: 'Add Task' },
		{ en: 'Retention Settings', hi: 'रिटेंशन सेटिंग्स', hinglish: 'Retention Settings' },
		{ en: 'Credit History', hi: 'क्रेडिट इतिहास', hinglish: 'Credit History' },
		{ en: 'Generate Account Name', hi: 'खाता नाम जनरेट करें', hinglish: 'Account Name Generate' },
		{ en: 'Add Driver', hi: 'ड्राइवर जोड़ें', hinglish: 'Add Driver' },
		{ en: 'Driver Listing', hi: 'ड्राइवर सूची', hinglish: 'Driver Listing' },
		{ en: 'Add Employee', hi: 'कर्मचारी जोड़ें', hinglish: 'Add Employee' },
		{ en: 'Employee Listing', hi: 'कर्मचारी सूची', hinglish: 'Employee Listing' },
		{ en: 'Attendance Listing', hi: 'उपस्थिति सूची', hinglish: 'Attendance Listing' },
		{ en: 'Mark Attendance', hi: 'उपस्थिति दर्ज करें', hinglish: 'Mark Attendance' },
		{ en: 'Add Entry', hi: 'प्रविष्टि जोड़ें', hinglish: 'Add Entry' },
		{ en: 'Daily Rokad Parcha', hi: 'दैनिक रोकड़ पर्चा', hinglish: 'Daily Rokad Parcha' },
		{ en: 'Parcha Report', hi: 'पर्चा रिपोर्ट', hinglish: 'Parcha Report' },
		{ en: 'Deleted Rokad Entries', hi: 'हटाई गई रोकड़ प्रविष्टियाँ', hinglish: 'Deleted Rokad Entries' },
		{ en: 'Cold Lot Listing', hi: 'कोल्ड लॉट सूची', hinglish: 'Cold Lot Listing' },
		{ en: 'Add Cold Lot', hi: 'कोल्ड लॉट जोड़ें', hinglish: 'Add Cold Lot' },
		{ en: 'Kisan Accounts', hi: 'किसान खाते', hinglish: 'Kisan Accounts' },
		{ en: 'Employee Accounts', hi: 'कर्मचारी खाते', hinglish: 'Employee Accounts' },
		{ en: 'Billing - Kisan Bills', hi: 'बिलिंग - किसान बिल', hinglish: 'Billing - Kisan Bills' },
		{ en: 'Bank Stock Statement', hi: 'बैंक स्टॉक स्टेटमेंट', hinglish: 'Bank Stock Statement' },
		{ en: 'Saved Bank Statements', hi: 'सहेजे गए बैंक स्टेटमेंट', hinglish: 'Saved Bank Statements' },
		{ en: 'Bank Statement Settings', hi: 'बैंक स्टेटमेंट सेटिंग्स', hinglish: 'Bank Statement Settings' },
		{ en: 'Farmer Captures', hi: 'फार्मर कैप्चर', hinglish: 'Farmer Captures' },
		{ en: 'Chrome Extension', hi: 'क्रोम एक्सटेंशन', hinglish: 'Chrome Extension' },
		{ en: 'Khata Naksha', hi: 'खाता नक्शा', hinglish: 'Khata Naksha' },
		{ en: 'Thumb Figure', hi: 'थंब फिगर', hinglish: 'Thumb Figure' },
		{ en: 'HSN Code Master', hi: 'HSN कोड मास्टर', hinglish: 'HSN Code Master' },
		{ en: 'Add Bill', hi: 'बिल जोड़ें', hinglish: 'Add Bill' },
		{ en: 'Add E-invoice', hi: 'ई-इनवॉइस जोड़ें', hinglish: 'Add E-invoice' },
		{ en: 'Add E-Invoice', hi: 'ई-इनवॉइस जोड़ें', hinglish: 'Add E-Invoice' },
		{ en: 'Listing E-Invoice', hi: 'ई-इनवॉइस सूची', hinglish: 'Listing E-Invoice' },
		{ en: 'Stock Position', hi: 'स्टॉक स्थिति', hinglish: 'Stock Position' },
		{ en: 'Stock Statement', hi: 'स्टॉक स्टेटमेंट', hinglish: 'Stock Statement' },
		{ en: 'Movement Register', hi: 'मूवमेंट रजिस्टर', hinglish: 'Movement Register' },
		{ en: 'Opening Stocks Details', hi: 'ओपनिंग स्टॉक विवरण', hinglish: 'Opening Stocks Details' },
		{ en: 'Account Ledger', hi: 'खाता बही', hinglish: 'Account Ledger' },
		{ en: 'Account Report', hi: 'खाता रिपोर्ट', hinglish: 'Account Report' },
		{ en: 'Account Statement', hi: 'खाता स्टेटमेंट', hinglish: 'Account Statement' },
		{ en: 'Reports', hi: 'रिपोर्ट', hinglish: 'Reports' },
		{ en: 'Change FY Year', hi: 'वित्तीय वर्ष बदलें', hinglish: 'Change FY Year' },
		{ en: 'MSP (Kisan Vahi Rate)', hi: 'MSP (किसान वही दर)', hinglish: 'MSP (Kisan Vahi Rate)' },
		{ en: 'TDS/TCS Compliance', hi: 'TDS/TCS अनुपालन', hinglish: 'TDS/TCS Compliance' },
		{ en: 'GSTIN Analysis', hi: 'GSTIN विश्लेषण', hinglish: 'GSTIN Analysis' },
		{ en: 'Opening Balance Carry-Forward', hi: 'ओपनिंग बैलेंस कैरी-फॉरवर्ड', hinglish: 'Opening Balance Carry-Forward' },
		{ en: 'Role Permissions', hi: 'भूमिका अनुमतियाँ', hinglish: 'Role Permissions' },
		{ en: 'User Permissions', hi: 'उपयोगकर्ता अनुमतियाँ', hinglish: 'User Permissions' },
		{ en: 'SEO Settings', hi: 'SEO सेटिंग्स', hinglish: 'SEO Settings' },
		{ en: 'Generate Sitemap', hi: 'साइटमैप जनरेट करें', hinglish: 'Generate Sitemap' },
		{ en: 'Website Inquiries', hi: 'वेबसाइट पूछताछ', hinglish: 'Website Inquiries' },
		{ en: 'Export History', hi: 'निर्यात इतिहास', hinglish: 'Export History' }
	];

	var LANGS = ['en', 'hi', 'hinglish'];

	// Build variant -> entry index (lowercased, trimmed).
	var INDEX = {};
	function norm(s) { return (s == null ? '' : String(s)).replace(/\s+/g, ' ').trim().toLowerCase(); }
	for (var i = 0; i < DICT.length; i++) {
		var e = DICT[i];
		for (var l = 0; l < LANGS.length; l++) {
			var v = e[LANGS[l]];
			if (v) {
				var k = norm(v);
				if (!(k in INDEX)) { INDEX[k] = e; }
			}
		}
	}

	function targetFor(entry, lang) {
		var val = entry[lang];
		return (val == null || val === '') ? entry.en : val;
	}

	// Translate a single pure-label element (no child elements) in place.
	function translateLabel(el, lang) {
		if (!el || el.children.length) { return; } // skip if it wraps <small>, icons, etc.
		var raw = el.textContent;
		var entry = INDEX[norm(raw)];
		if (!entry) { return; }
		var val = targetFor(entry, lang);
		if (val && norm(val) !== norm(raw)) {
			// Preserve any trailing space the source used (some labels had one).
			var trail = /\s$/.test(raw) ? ' ' : '';
			el.textContent = val + trail;
		}
	}

	function translate(root, lang) {
		lang = lang || window.APP_LANG || 'hinglish';
		root = root || document;
		if (LANGS.indexOf(lang) === -1) { lang = 'hinglish'; }
		var nodes = root.querySelectorAll('.sidebar-menu a.sidebar-link, .sidebar-menu span.title');
		for (var i = 0; i < nodes.length; i++) { translateLabel(nodes[i], lang); }
	}

	window.AppI18n = { translate: translate, dict: DICT };

	function ready(fn) {
		if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', fn); }
		else { fn(); }
	}
	ready(function () { translate(document, window.APP_LANG); });
})();
