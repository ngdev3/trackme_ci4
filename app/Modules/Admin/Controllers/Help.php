<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Help & FAQ Assistant — CI4 port of admin/help. A client-side searchable
 * knowledge base of admin features + FAQs. Universal page (open to any logged-in
 * user); static data, no model.
 */
class Help extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function index()
    {
        return _layout('\App\Modules\Admin\Views\help\index', [
            'title' => 'Help & FAQ · C R Industries ERP',
            'kb'    => $this->_knowledge_base(),
            'faqs'  => $this->_faqs(),
        ]);
    }

    /** Feature knowledge base: title, description, url, keyword aliases, group. */
    private function _knowledge_base()
    {
        $u = function ($p) { return base_url($p); };
        return array(
            // --- Cash book / accounts ---
            array('t' => 'Attachments Gallery', 'd' => 'All photos, voice notes and videos attached to cash-book entries. Now shows total storage size and a per-type (photo/voice/video) breakdown.', 'u' => $u('admin/attachments'), 'g' => 'Cash Book', 'k' => 'attachment attachments photo photos image images voice video media gallery rokad attachment parcha photo document scan pic size storage total space disk', 'new' => 1),
            array('t' => 'Daily Rokad Parcha (Cash Book)', 'd' => 'Day-wise cash sheet with Jama/Naam, audit trail, print & PDF.', 'u' => $u('admin/report/rokad_parcha'), 'g' => 'Cash Book', 'k' => 'rokad parcha cash book daily cash sheet jama naam nagad बुक रोकड़ पर्चा नकद'),
            array('t' => 'Add Deposit (Jama / जमा)', 'd' => 'Record a cash receipt / deposit entry.', 'u' => $u('admin/account/entry?type=deposit'), 'g' => 'Cash Book', 'k' => 'jama deposit receipt add entry credit cash in जमा प्राप्ति income received'),
            array('t' => 'Add Expenditure (Naam / नाम)', 'd' => 'Record a payment / expense entry.', 'u' => $u('admin/account/entry?type=expenses'), 'g' => 'Cash Book', 'k' => 'naam expense expenditure payment add entry debit cash out नाम खर्च paid'),
            array('t' => 'Deleted Rokad Entries', 'd' => 'View soft-deleted cash entries, reason & who deleted; restore them; export PDF.', 'u' => $u('admin/report/deleted_entries'), 'g' => 'Cash Book', 'k' => 'deleted delete restore trash removed recover deleted entry undo bin'),
            array('t' => 'Account Ledger', 'd' => 'Full ledger of any account with running balance. New: no-reload live search from remark (with suggestions), sortable table, Date+Time & Remark columns, and URL keeps your search on refresh.', 'u' => $u('admin/report/ledger'), 'g' => 'Reports', 'k' => 'ledger khata account statement balance running bahi खाता remark search filter sort date time', 'new' => 1),
            array('t' => 'Account Report', 'd' => 'Search and report by account.', 'u' => $u('admin/report/search'), 'g' => 'Reports', 'k' => 'account report search'),
            array('t' => 'Account Statement', 'd' => 'Statement by account name.', 'u' => $u('admin/report/byaccount_name'), 'g' => 'Reports', 'k' => 'account statement byaccount'),
            array('t' => 'Account Name / Parties', 'd' => 'Manage account names / trade parties; GST verify; quick edit.', 'u' => $u('admin/account_name/listing'), 'g' => 'Masters', 'k' => 'account name party parties supplier customer gst trade khata create account'),
            array('t' => 'Opening Balance', 'd' => 'Set account opening balances.', 'u' => $u('admin/opening_balance'), 'g' => 'Cash Book', 'k' => 'opening balance start balance carry forward'),

            // --- Invoicing ---
            array('t' => 'Tax Invoice (E-Invoice)', 'd' => 'Create and list GST tax invoices / e-invoices.', 'u' => $u('admin/taxinvoice/einvoice_listing'), 'g' => 'Invoicing', 'k' => 'tax invoice einvoice e-invoice gst bill sale gst invoice irn'),
            array('t' => 'Bill of Supply', 'd' => 'Registered bill of supply invoices.', 'u' => $u('admin/invoice/listing'), 'g' => 'Invoicing', 'k' => 'bill of supply bos invoice sale'),
            array('t' => 'Unregistered Bill of Supply', 'd' => 'Un-registered bill of supply.', 'u' => $u('admin/uninvoice/listing'), 'g' => 'Invoicing', 'k' => 'unregistered bill supply ubos uninvoice non gst'),
            array('t' => 'Credit / Debit Note', 'd' => 'CD notes.', 'u' => $u('admin/cd_note/listing'), 'g' => 'Invoicing', 'k' => 'credit note debit note cd note cdnote return'),
            array('t' => 'Delivery Challan', 'd' => 'Delivery challans.', 'u' => $u('admin/delivery_challan/listing'), 'g' => 'Invoicing', 'k' => 'delivery challan dispatch transport challan'),
            array('t' => 'Purchase Module', 'd' => 'Purchase bills and listing.', 'u' => $u('admin/purchase_module/listing'), 'g' => 'Purchase', 'k' => 'purchase bill buy vendor supplier kharid खरीद'),
            array('t' => 'Purchase From Farmers', 'd' => 'Farmer purchase / payment receipts.', 'u' => $u('admin/payment_receipt/listing'), 'g' => 'Purchase', 'k' => 'payment receipt farmer purchase kisan buy paddy'),

            // --- Cold storage / rice mill ---
            array('t' => 'Cold Lot System', 'd' => 'Farmer cold-storage lots, kisan/employee accounts, billing.', 'u' => $u('admin/cold_lot_system/listing'), 'g' => 'Cold Storage', 'k' => 'cold lot storage kisan farmer lot cls potato aloo भंडार शीत'),
            array('t' => 'Cold Storage Inventory', 'd' => 'Live packets in store, position & movement.', 'u' => $u('admin/cold_inventory/overview'), 'g' => 'Cold Storage', 'k' => 'cold inventory stock packets store position movement'),
            array('t' => 'Lot System', 'd' => 'Paddy / general lot system.', 'u' => $u('admin/lot_system/listing'), 'g' => 'Rice Mill', 'k' => 'lot system paddy'),
            array('t' => 'Paddy Center Challan', 'd' => 'Paddy center challans.', 'u' => $u('admin/PaddyLotsystem/listing'), 'g' => 'Rice Mill', 'k' => 'paddy center challan dhaan धान'),
            array('t' => 'Kisan Vahi', 'd' => 'Unified Kisan Vahi: registration, entries, khata naksha, thumb figure, parcha report. Now every feature is its own full page; MSP is per-FY and farmers are locked to one center per FY.', 'u' => $u('admin/kisan_vahi'), 'g' => 'Rice Mill', 'k' => 'kisan vahi farmer register entry khata naksha thumb figure किसान वही msp center', 'new' => 1),
            array('t' => 'Kisan Khata Naksha (Account Mapping)', 'd' => 'Map a farmer\'s pending Kisan Vahi purchases to a ledger account (scoped by FY, product and firm).', 'u' => $u('admin/accountMapping/account_mapping'), 'g' => 'Rice Mill', 'k' => 'account mapping khata naksha farmer kisan vahi ledger map purchase किसान खाता नक्शा', 'new' => 1),
            array('t' => 'Thumb Figure', 'd' => 'Daily per-center "Figure Send For Thumb" from Kisan Vahi vs expected target, with per-farmer Mid; inline edit.', 'u' => $u('admin/accountMapping/thumb_figure'), 'g' => 'Rice Mill', 'k' => 'thumb figure center target mid daily kisan vahi quantity', 'new' => 1),
            array('t' => 'Farmer Capture Inbox', 'd' => 'Farmer data scraped from the government portal (via the browser extension) lands here to pre-fill Kisan Vahi.', 'u' => $u('admin/farmer_capture'), 'g' => 'Rice Mill', 'k' => 'farmer capture extension government portal scrape aadhaar prefill kisan vahi inbox', 'new' => 1),
            array('t' => 'MSP Settings (per FY)', 'd' => 'Set the MSP rate for the current financial year and the next few years.', 'u' => $u('admin/setting/msp'), 'g' => 'Masters', 'k' => 'msp minimum support price rate fy financial year paddy', 'new' => 1),
            array('t' => 'Stock Records', 'd' => 'Opening stock, position and statement.', 'u' => $u('admin/stock/listing'), 'g' => 'Rice Mill', 'k' => 'stock position statement inventory goods'),
            array('t' => 'HSN Code Master', 'd' => 'Manage HSN codes.', 'u' => $u('admin/Hsn/listing'), 'g' => 'Masters', 'k' => 'hsn code tax master gst hsn'),
            array('t' => 'Item Master', 'd' => 'Create products / items (name, unit, HSN, status) that appear in the Stock module. Adding, editing and deleting is Super-Admin only; others can view.', 'u' => $u('admin/item_master/listing'), 'g' => 'Masters', 'k' => 'item master product goods commodity name unit hsn stock create item add product catalog', 'new' => 1),

            // --- People / payroll ---
            array('t' => 'Salary Module', 'd' => 'Salary listing, add and credit history.', 'u' => $u('admin/Salary_Module/listing'), 'g' => 'People', 'k' => 'salary pay wage employee payroll credit history वेतन'),
            array('t' => 'Attendance', 'd' => 'Mark and report employee attendance.', 'u' => $u('admin/attendance/listing'), 'g' => 'People', 'k' => 'attendance present employee mark hajri हाजिरी'),
            array('t' => 'FCI Driver', 'd' => 'Driver listing / add.', 'u' => $u('admin/driver_module/listing'), 'g' => 'People', 'k' => 'driver fci transport'),
            array('t' => 'FCI Truck', 'd' => 'Truck listing / add.', 'u' => $u('admin/truck_module/listing'), 'g' => 'People', 'k' => 'truck fci vehicle transport gaadi'),
            array('t' => 'Users', 'd' => 'Manage system users.', 'u' => $u('admin/users/listing'), 'g' => 'Admin', 'k' => 'user users staff login account create user add user'),

            // --- Platform / admin ---
            array('t' => 'User Permissions', 'd' => 'Grant/revoke module access for a single user.', 'u' => $u('admin/user_permissions'), 'g' => 'Admin', 'k' => 'permission access rights grant revoke user module allow block role'),
            array('t' => 'Role Permissions', 'd' => 'Set module access per role.', 'u' => $u('admin/role_permissions'), 'g' => 'Admin', 'k' => 'role permission access rights group'),
            array('t' => 'Activity & Audit Monitor', 'd' => 'Who did what, IP & location map (IPv4/IPv6), module access, timeline. Includes failed-login security tracking and a traffic session filter.', 'u' => $u('admin/monitor'), 'g' => 'Admin', 'k' => 'monitor audit activity ip location map traffic log who edited tracking mac address failed login security session', 'new' => 1),
            array('t' => 'Entry Audit Trace', 'd' => 'Per-entry audit log: which module and entry was created/edited/deleted, by whom, with IP and GPS lat/long. Opens the original entry.', 'u' => $u('admin/entry_trace/listing'), 'g' => 'Admin', 'k' => 'entry trace audit ip lat long location gps who edited created deleted module tracking trail', 'new' => 1),
            array('t' => 'Firm Switch Log', 'd' => 'Trail of every "Change Firm" switch — who moved to which firm/template/FY, when, IP and source. The Change Firm popup also shows your recently selected firms with entry counts.', 'u' => $u('admin/setting/listing'), 'g' => 'Admin', 'k' => 'change firm switch template financial year fy workspace recent log trail history switched', 'new' => 1),
            array('t' => 'Traffic & Login Retention', 'd' => 'Set how many days of page-traffic and login history to keep; auto-prunes older data.', 'u' => $u('admin/traffic/retention'), 'g' => 'Admin', 'k' => 'traffic login retention history keep days prune cleanup delete old data', 'new' => 1),
            array('t' => 'Letter Pad', 'd' => 'Create letterhead letters with QR verify.', 'u' => $u('admin/letter_pad/listing'), 'g' => 'Admin', 'k' => 'letter pad letterhead pad print letter'),
            array('t' => 'Documents Renewal', 'd' => 'Track document renewals.', 'u' => $u('admin/document/listing'), 'g' => 'Admin', 'k' => 'document renewal expiry license papers'),
            array('t' => 'Notifications', 'd' => 'In-app notification listing.', 'u' => $u('admin/notification/listing'), 'g' => 'Admin', 'k' => 'notification alert bell'),
            array('t' => 'Device Management', 'd' => 'Registered devices, send push.', 'u' => $u('admin/device/listing'), 'g' => 'Admin', 'k' => 'device mobile push fcm registered phone'),
            array('t' => 'Inward-Outward Register', 'd' => 'Awak-Jawak register.', 'u' => $u('admin/awak_jawak/listing'), 'g' => 'Admin', 'k' => 'inward outward awak jawak register gate entry आवक जावक'),
            array('t' => 'Password Manager', 'd' => 'Store & export bank/other passwords.', 'u' => $u('admin/bank_password/listing'), 'g' => 'Admin', 'k' => 'password manager bank credential vault secret'),
            array('t' => 'APK Manager / App Updates', 'd' => 'Publish Android builds for install.', 'u' => $u('admin/app_update/listing'), 'g' => 'Admin', 'k' => 'apk app update android build install version'),
            array('t' => 'Backup & Restore', 'd' => 'Export firm/FY data to a ZIP.', 'u' => $u('admin/backup_restore'), 'g' => 'Admin', 'k' => 'backup restore export zip data download save'),
            array('t' => 'GSTIN Analysis', 'd' => 'GSTIN analysis tools.', 'u' => $u('admin/gstin'), 'g' => 'Admin', 'k' => 'gstin gst analysis verify number'),
            array('t' => 'Accounting Reports', 'd' => 'Trial balance, balance sheet, P&L, outstanding, ageing.', 'u' => $u('admin/accounts_report/trial_balance'), 'g' => 'Reports', 'k' => 'trial balance sheet profit loss p&l outstanding debtor creditor ageing accounting report balance'),
            array('t' => 'Settings', 'd' => 'All ERP settings hub.', 'u' => $u('admin/setting/hub'), 'g' => 'Admin', 'k' => 'setting settings config firm fy template session lock retention'),
            array('t' => 'Tasks', 'd' => 'Task management with comments & push.', 'u' => $u('task/task'), 'g' => 'Admin', 'k' => 'task todo assignment work comment'),
        );
    }

    /** Common questions with an answer and (optional) link. */
    private function _faqs()
    {
        $u = function ($p) { return base_url($p); };
        return array(
            array('q' => 'Where are the photos / attachments of cash entries?', 'a' => 'Open the Attachments Gallery — it shows every entry\'s photo, voice and video. Click an image to zoom, and the #ID opens the full entry.', 'u' => $u('admin/attachments'), 'label' => 'Open Attachments Gallery'),
            array('q' => 'How do I see who created or edited an entry, and what they changed?', 'a' => 'Open Daily Rokad Parcha, click an entry — the "Full Audit Trail" shows every create/update/delete with user, IP, location, time, and the exact field changes (old → new). You can print or download the trail as PDF.', 'u' => $u('admin/report/rokad_parcha'), 'label' => 'Open Rokad Parcha'),
            array('q' => 'How do I restore a deleted cash entry?', 'a' => 'Go to Deleted Rokad Entries, find the entry (with delete reason and who deleted it) and click Restore.', 'u' => $u('admin/report/deleted_entries'), 'label' => 'Open Deleted Entries'),
            array('q' => 'How do I give a user access to a module?', 'a' => 'Open User Permissions, pick the user, tick the modules they may access, and Save. For a whole role, use Role Permissions.', 'u' => $u('admin/user_permissions'), 'label' => 'Open User Permissions'),
            array('q' => 'How do I add a cash receipt or payment?', 'a' => 'Use Add Deposit (Jama) for money received, or Add Expenditure (Naam) for money paid.', 'u' => $u('admin/account/entry?type=deposit'), 'label' => 'Add Deposit (Jama)'),
            array('q' => 'Where can I see login history, IP and location of activity?', 'a' => 'Open the Activity & Audit Monitor — it has a timeline, IP intelligence (IPv4/IPv6), and a map of where entries were made.', 'u' => $u('admin/monitor'), 'label' => 'Open Monitor'),
            array('q' => 'How do I download a report as PDF?', 'a' => 'Most report screens (Rokad Parcha, Deleted Entries, audit trail) have a Print / PDF button at the top or in the entry popup.', 'u' => $u('admin/report/rokad_parcha'), 'label' => 'Open Rokad Parcha'),
            array('q' => 'How do I create a GST tax invoice?', 'a' => 'Open Tax Invoice (E-Invoice) and click Add E-invoice.', 'u' => $u('admin/taxinvoice/e_invoice_add'), 'label' => 'Add E-Invoice'),
            array('q' => 'How do I add a product / item for stock?', 'a' => 'Open the Item Master and click Add Item. Enter the name, unit and HSN code — it then appears in the Stock module. Note: only the Super Admin can add or edit items; other users can view the list.', 'u' => $u('admin/item_master/listing'), 'label' => 'Open Item Master'),
            array('q' => 'How do I switch firm / financial year, and see recent ones?', 'a' => 'Use "Change Firm" from the header. It shows your recently selected firms (with entry counts) for one-click switching, and a full switch log lives on the Template Settings page.', 'u' => $u('admin/setting/listing'), 'label' => 'Open Firm Switch Log'),
            array('q' => 'How much storage do the attachments use?', 'a' => 'Open the Attachments Gallery — the header now shows the total on-disk size plus a per-type breakdown (photos, voice, video).', 'u' => $u('admin/attachments'), 'label' => 'Open Attachments Gallery'),
            array('q' => 'How do I trace a single entry\'s IP and GPS location?', 'a' => 'Open Entry Audit Trace — each row shows the module, entry, user, IP and GPS lat/long, and links to the original entry.', 'u' => $u('admin/entry_trace/listing'), 'label' => 'Open Entry Audit Trace'),
        );
    }
}
