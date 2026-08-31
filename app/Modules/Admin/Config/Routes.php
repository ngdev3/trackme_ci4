<?php

/**
 * Admin module routes. The admin/* group carries the guard filter chain
 * (adminAuth + fyContext + rbac) configured in app/Config/Filters.php.
 * Real admin controllers register here as they are ported.
 */

use App\Modules\Admin\Controllers\Dashboard;
use App\Modules\Admin\Controllers\Invoice;
use App\Modules\Admin\Controllers\Hsn;
use App\Modules\Admin\Controllers\PaymentReceipt;
use App\Modules\Admin\Controllers\Uninvoice;
use App\Modules\Admin\Controllers\Taxinvoice;
use App\Modules\Admin\Controllers\CdNote;
use App\Modules\Admin\Controllers\PurchaseModule;
use App\Modules\Admin\Controllers\DeliveryChallan;
use App\Modules\Admin\Controllers\BankPassword;
use App\Modules\Admin\Controllers\Stock;
use App\Modules\Admin\Controllers\LotSystem;
use App\Modules\Admin\Controllers\PaddyLotsystem;
use App\Modules\Admin\Controllers\Kisanreg;
use App\Modules\Admin\Controllers\KisanVahi;
use App\Modules\Admin\Controllers\DriverModule;
use App\Modules\Admin\Controllers\TruckModule;
use App\Modules\Admin\Controllers\Attendance;
use App\Modules\Admin\Controllers\SalaryModule;
use App\Modules\Admin\Controllers\ColdLotSystem;
use App\Modules\Admin\Controllers\Users;
use App\Modules\Admin\Controllers\Profile;
use App\Modules\Admin\Controllers\Notification;
use App\Modules\Admin\Controllers\Role_permissions;
use App\Modules\Admin\Controllers\User_permissions;
use App\Modules\Admin\Controllers\Menu_order;
use App\Modules\Admin\Controllers\Help;
use App\Modules\Admin\Controllers\Attachments;
use App\Modules\Admin\Controllers\App_setting;
use App\Modules\Admin\Controllers\Entry_trace;
use App\Modules\Admin\Controllers\Web_push;
use App\Modules\Admin\Controllers\Item_master;
use App\Modules\Admin\Controllers\Account_name;
use App\Modules\Admin\Controllers\Gst_setting;
use App\Modules\Admin\Controllers\Ricemill_inquiry;
use App\Modules\Admin\Controllers\Billing_register;
use App\Modules\Admin\Controllers\Traffic;
use App\Modules\Admin\Controllers\Device;
use App\Modules\Admin\Controllers\Document;
use App\Modules\Admin\Controllers\Letter_pad;
use App\Modules\Admin\Controllers\App_update;

$routes->get('admin/dashboard', [Dashboard::class, 'index']);

// Bill of Supply (invoice) — listing slice (P6 preview)
$routes->get('admin/invoice/listing', [Invoice::class, 'listing']);
$routes->post('admin/invoice/view_all', [Invoice::class, 'viewAll']);
$routes->get('admin/invoice/add', [Invoice::class, 'add']);
$routes->post('admin/invoice/add', [Invoice::class, 'add']);
$routes->get('admin/invoice/GeneratePdf/(:segment)', [Invoice::class, 'GeneratePdf']);
$routes->post('admin/invoice/stock_balance', [Invoice::class, 'stock_balance']);
$routes->get('admin/invoice', [Invoice::class, 'listing']);

// HSN Code Master — full CRUD (write-pattern reference)
$routes->get('admin/hsn/listing', [Hsn::class, 'listing']);
$routes->get('admin/hsn', [Hsn::class, 'listing']);
$routes->post('admin/hsn/listing_data', [Hsn::class, 'listingData']);
$routes->post('admin/hsn/save', [Hsn::class, 'save']);
$routes->post('admin/hsn/delete', [Hsn::class, 'delete']);
$routes->get('admin/hsn/row/(:num)', [Hsn::class, 'row']);

// Payment Receipt (Purchase from Farmer) — listing slice
$routes->get('admin/payment_receipt/listing', [PaymentReceipt::class, 'listing']);
$routes->get('admin/payment_receipt', [PaymentReceipt::class, 'listing']);
$routes->post('admin/payment_receipt/view_all', [PaymentReceipt::class, 'viewAll']);

// Unregistered Bill of Supply (UBOS) — listing slice
$routes->get('admin/uninvoice/listing', [Uninvoice::class, 'listing']);
$routes->get('admin/uninvoice', [Uninvoice::class, 'listing']);
$routes->post('admin/uninvoice/view_all', [Uninvoice::class, 'viewAll']);

// Tax Invoice — listing slice
$routes->get('admin/taxinvoice/listing', [Taxinvoice::class, 'listing']);
$routes->get('admin/taxinvoice', [Taxinvoice::class, 'listing']);
$routes->post('admin/taxinvoice/view_all', [Taxinvoice::class, 'viewAll']);

// Credit / Debit Note — listing slice
$routes->get('admin/cd_note/listing', [CdNote::class, 'listing']);
$routes->get('admin/cd_note', [CdNote::class, 'listing']);
$routes->post('admin/cd_note/view_all', [CdNote::class, 'viewAll']);

// Purchase, Delivery Challan, Password Manager — listing slices
$routes->get('admin/purchase_module/listing', [PurchaseModule::class, 'listing']);
$routes->get('admin/purchase_module', [PurchaseModule::class, 'listing']);
$routes->post('admin/purchase_module/view_all', [PurchaseModule::class, 'viewAll']);
$routes->get('admin/delivery_challan/listing', [DeliveryChallan::class, 'listing']);
$routes->get('admin/delivery_challan', [DeliveryChallan::class, 'listing']);
$routes->post('admin/delivery_challan/view_all', [DeliveryChallan::class, 'viewAll']);
$routes->get('admin/bank_password/listing', [BankPassword::class, 'listing']);
$routes->get('admin/bank_password', [BankPassword::class, 'listing']);
$routes->post('admin/bank_password/view_all', [BankPassword::class, 'viewAll']);

// Stock, Lot System, Paddy, Kisan Reg, Kisan Vahi — listing slices
$routes->get('admin/stock/listing', [Stock::class, 'listing']);
$routes->get('admin/stock', [Stock::class, 'listing']);
$routes->post('admin/stock/view_all', [Stock::class, 'viewAll']);
$routes->get('admin/lot_system/listing', [LotSystem::class, 'listing']);
$routes->get('admin/lot_system', [LotSystem::class, 'listing']);
$routes->post('admin/lot_system/view_all', [LotSystem::class, 'viewAll']);
$routes->get('admin/PaddyLotsystem/listing', [PaddyLotsystem::class, 'listing']);
$routes->get('admin/PaddyLotsystem', [PaddyLotsystem::class, 'listing']);
$routes->post('admin/PaddyLotsystem/view_all', [PaddyLotsystem::class, 'viewAll']);
$routes->get('admin/Kisanreg/listing', [Kisanreg::class, 'listing']);
$routes->get('admin/Kisanreg', [Kisanreg::class, 'listing']);
$routes->post('admin/Kisanreg/view_all', [Kisanreg::class, 'viewAll']);
$routes->get('admin/kisan_vahi/listing', [KisanVahi::class, 'listing']);
$routes->post('admin/kisan_vahi/view_all', [KisanVahi::class, 'viewAll']);

// Driver, Truck, Attendance, Salary, Cold Lot — listing slices
$routes->get('admin/driver_module/listing', [DriverModule::class, 'listing']);
$routes->get('admin/driver_module', [DriverModule::class, 'listing']);
$routes->get('admin/driver_module/add', [DriverModule::class, 'listing']); // Add opens the modal on the listing
$routes->post('admin/driver_module/view_all', [DriverModule::class, 'viewAll']);
$routes->post('admin/driver_module/save', [DriverModule::class, 'save']);
$routes->post('admin/driver_module/delete', [DriverModule::class, 'delete']);
$routes->get('admin/driver_module/row/(:num)', [DriverModule::class, 'row']);
$routes->get('admin/truck_module/listing', [TruckModule::class, 'listing']);
$routes->get('admin/truck_module', [TruckModule::class, 'listing']);
$routes->get('admin/truck_module/add', [TruckModule::class, 'listing']);
$routes->post('admin/truck_module/view_all', [TruckModule::class, 'viewAll']);
$routes->post('admin/truck_module/save', [TruckModule::class, 'save']);
$routes->post('admin/truck_module/delete', [TruckModule::class, 'delete']);
$routes->get('admin/truck_module/row/(:num)', [TruckModule::class, 'row']);
$routes->get('admin/attendance/listing', [Attendance::class, 'listing']);
$routes->get('admin/attendance', [Attendance::class, 'listing']);
$routes->get('admin/attendance/add', [Attendance::class, 'listing']);
$routes->post('admin/attendance/view_all', [Attendance::class, 'viewAll']);
$routes->post('admin/attendance/save', [Attendance::class, 'save']);
$routes->post('admin/attendance/delete', [Attendance::class, 'delete']);
$routes->get('admin/attendance/row/(:num)', [Attendance::class, 'row']);
$routes->get('admin/salary_module/listing', [SalaryModule::class, 'listing']);
$routes->get('admin/salary_module', [SalaryModule::class, 'listing']);
$routes->post('admin/salary_module/view_all', [SalaryModule::class, 'viewAll']);
$routes->post('admin/salary_module/save', [SalaryModule::class, 'save']);
$routes->post('admin/salary_module/delete', [SalaryModule::class, 'delete']);
$routes->get('admin/salary_module/row/(:num)', [SalaryModule::class, 'row']);
// Menu uses the CI3 capitalised segment — alias so the links resolve.
$routes->get('admin/Salary_Module/listing', [SalaryModule::class, 'listing']);
$routes->get('admin/Salary_Module', [SalaryModule::class, 'listing']);
$routes->get('admin/Salary_Module/add', [SalaryModule::class, 'listing']);
$routes->post('admin/Salary_Module/view_all', [SalaryModule::class, 'viewAll']);
$routes->post('admin/Salary_Module/save', [SalaryModule::class, 'save']);
$routes->post('admin/Salary_Module/delete', [SalaryModule::class, 'delete']);
$routes->get('admin/Salary_Module/row/(:num)', [SalaryModule::class, 'row']);
$routes->get('admin/cold_lot_system/listing', [ColdLotSystem::class, 'listing']);
$routes->get('admin/cold_lot_system', [ColdLotSystem::class, 'listing']);
$routes->post('admin/cold_lot_system/view_all', [ColdLotSystem::class, 'viewAll']);

// Users (P2)
$routes->get('admin/users/listing', [Users::class, 'listing']);
$routes->get('admin/users', [Users::class, 'listing']);
$routes->post('admin/users/view_all', [Users::class, 'viewAll']);
$routes->post('admin/users/updateUserStatus', [Users::class, 'updateUserStatus']);
$routes->post('admin/users/delete', [Users::class, 'delete']);
$routes->match(['GET', 'POST'], 'admin/users/add', [Users::class, 'add']);
$routes->match(['GET', 'POST'], 'admin/users/edit/(:segment)', [Users::class, 'edit']);
$routes->get('admin/users/view/(:segment)', [Users::class, 'view']);

// Profile — self-profile (P2)
$routes->match(['GET', 'POST'], 'admin/profile', [Profile::class, 'index']);
$routes->post('admin/profile/changeImage', [Profile::class, 'changeImage']);
$routes->match(['GET', 'POST'], 'admin/profile/reset_password', [Profile::class, 'reset_password']);

// Notification centre (P2)
$routes->get('admin/notification/listing', [Notification::class, 'listing']);
$routes->get('admin/notification', [Notification::class, 'listing']);
$routes->post('admin/notification/view_all', [Notification::class, 'viewAll']);
$routes->get('admin/notification/mark_all_read', [Notification::class, 'mark_all_read']);
$routes->post('admin/notification/mark_seen', [Notification::class, 'mark_seen']);
$routes->get('admin/notification/read/(:num)', [Notification::class, 'read']);
$routes->post('admin/notification/updatenotificationStatus', [Notification::class, 'updatenotificationStatus']);

// Role Permissions — RBAC matrix editor (P2, super-admin only)
$routes->match(['GET', 'POST'], 'admin/role_permissions', [Role_permissions::class, 'index']);
$routes->match(['GET', 'POST'], 'admin/user_permissions', [User_permissions::class, 'index']);

// Menu order — per-user left-menu personalisation (P2, JSON)
$routes->post('admin/menu_order/save', [Menu_order::class, 'save']);
$routes->match(['GET', 'POST'], 'admin/menu_order/reset', [Menu_order::class, 'reset']);

// Help & FAQ (P3)
$routes->get('admin/help', [Help::class, 'index']);

// Attachments Gallery (P3)
$routes->get('admin/attachments', [Attachments::class, 'index']);

// App Settings — per-user dashboard layout (P3)
$routes->get('admin/app_setting', [App_setting::class, 'index']);
$routes->post('admin/app_setting/save_dashboard_layout', [App_setting::class, 'save_dashboard_layout']);
$routes->post('admin/app_setting/reset_dashboard_layout', [App_setting::class, 'reset_dashboard_layout']);

// Entry Trace / Audit (P3, super-admin)
$routes->get('admin/entry_trace', [Entry_trace::class, 'index']);
$routes->get('admin/entry_trace/listing', [Entry_trace::class, 'listing']);
$routes->post('admin/entry_trace/listing_data', [Entry_trace::class, 'listing_data']);
$routes->post('admin/entry_trace/save_retention', [Entry_trace::class, 'save_retention']);

// Web push (FCM) registration (P2)
$routes->get('admin/web_push/config', [Web_push::class, 'config']);
$routes->post('admin/web_push/save_token', [Web_push::class, 'save_token']);
$routes->post('admin/web_push/delete_token', [Web_push::class, 'delete_token']);
$routes->get('admin/web_push/service_worker', [Web_push::class, 'service_worker']);

// Item Master (P5 — master data) — CRUD over hsn_codes + unit
$routes->get('admin/item_master/listing', [Item_master::class, 'listing']);
$routes->get('admin/item_master', [Item_master::class, 'listing']);
$routes->match(['GET', 'POST'], 'admin/item_master/add', [Item_master::class, 'add']);
$routes->match(['GET', 'POST'], 'admin/item_master/edit/(:segment)', [Item_master::class, 'edit']);
$routes->post('admin/item_master/delete', [Item_master::class, 'delete']);
$routes->post('admin/item_master/updateStatus', [Item_master::class, 'updateStatus']);

// Jama/Naam voucher (admin/account) — combined cash-book entry (write path)
$routes->match(['GET', 'POST'], 'admin/account/entry', ['\App\Modules\Admin\Controllers\Account', 'entry']);
$routes->post('admin/account/save_entry', ['\App\Modules\Admin\Controllers\Account', 'save_entry']);

// Billing — shared account picker JSON feed (acc_picker.js + report autocompletes)
$routes->match(['GET', 'POST'], 'admin/billing/account_options', ['\App\Modules\Admin\Controllers\Billing', 'account_options']);

// Reports — Account Ledger (GET page / POST JSON feed)
$routes->match(['GET', 'POST'], 'admin/report/ledger', ['\App\Modules\Admin\Controllers\Report', 'ledger']);
// Reports — Rokad Parcha (daily cash book) + drag-drop group move
$routes->match(['GET', 'POST'], 'admin/report/rokad_parcha', ['\App\Modules\Admin\Controllers\Report', 'rokad_parcha']);
$routes->post('admin/report/rokad_parcha_move', ['\App\Modules\Admin\Controllers\Report', 'rokad_parcha_move']);
// Reports — Account Report (search) + Account Statement (byaccount_name)
$routes->match(['GET', 'POST'], 'admin/report/search', ['\App\Modules\Admin\Controllers\Report', 'search']);
$routes->match(['GET', 'POST'], 'admin/report/byaccount_name', ['\App\Modules\Admin\Controllers\Report', 'byaccount_name']);
// Reports — statement/ledger exports (CSV/Excel/Hindi PDF) + per-row modal
$routes->match(['GET', 'POST'], 'admin/report/byaccount_name_export/(:segment)', ['\App\Modules\Admin\Controllers\Report', 'byaccount_name_export']);
$routes->get('admin/report/account_ledger_modal/(:num)', ['\App\Modules\Admin\Controllers\Report', 'account_ledger_modal']);
$routes->get('admin/report/account_ledger_export/(:num)/(:segment)', ['\App\Modules\Admin\Controllers\Report', 'account_ledger_export']);
// Reports — Deleted Rokad Entries (trash + restore)
$routes->get('admin/report/deleted_entries', ['\App\Modules\Admin\Controllers\Report', 'deleted_entries']);
$routes->post('admin/report/deleted_entries_data', ['\App\Modules\Admin\Controllers\Report', 'deleted_entries_data']);
$routes->post('admin/report/deleted_entry_detail', ['\App\Modules\Admin\Controllers\Report', 'deleted_entry_detail']);
$routes->post('admin/report/restore_entry', ['\App\Modules\Admin\Controllers\Report', 'restore_entry']);

// Accounting Reports (accounts_report) — live chart-of-accounts statements
$routes->get('admin/accounts_report', ['\App\Modules\Admin\Controllers\Accounts_report', 'index']);
$routes->get('admin/accounts_report/trial_balance', ['\App\Modules\Admin\Controllers\Accounts_report', 'trial_balance']);
$routes->get('admin/accounts_report/outstanding', ['\App\Modules\Admin\Controllers\Accounts_report', 'outstanding']);
$routes->get('admin/accounts_report/debtors', ['\App\Modules\Admin\Controllers\Accounts_report', 'debtors']);
$routes->get('admin/accounts_report/creditors', ['\App\Modules\Admin\Controllers\Accounts_report', 'creditors']);
$routes->get('admin/accounts_report/ageing', ['\App\Modules\Admin\Controllers\Accounts_report', 'ageing']);
$routes->get('admin/accounts_report/balance_sheet', ['\App\Modules\Admin\Controllers\Accounts_report', 'balance_sheet']);
$routes->get('admin/accounts_report/profit_loss', ['\App\Modules\Admin\Controllers\Accounts_report', 'profit_loss']);
$routes->get('admin/accounts_report/inter_firm', ['\App\Modules\Admin\Controllers\Accounts_report', 'inter_firm']);

// Account Master (account_name) — listing slice (P6 master)
$routes->get('admin/account_name/listing', [Account_name::class, 'listing']);
$routes->get('admin/account_name', [Account_name::class, 'listing']);
$routes->post('admin/account_name/view_all', [Account_name::class, 'view_all']);
$routes->post('admin/account_name/updateStatus', [Account_name::class, 'updateStatus']);
$routes->post('admin/account_name/soft_delete', [Account_name::class, 'soft_delete']);
$routes->post('admin/account_name/restore', [Account_name::class, 'restore']);
$routes->post('admin/account_name/quick_update', [Account_name::class, 'quick_update']);

// Setting — firm/FY workspace switch (top-nav Change Firm modal) + settings hub
$routes->post('admin/setting/change_fy_id', ['\App\Modules\Admin\Controllers\Setting', 'change_fy_id']);
$routes->get('admin/setting/hub', ['\App\Modules\Admin\Controllers\Setting', 'hub']);
$routes->get('admin/setting', ['\App\Modules\Admin\Controllers\Setting', 'hub']);

// SEO & Search Optimization (settings form + dynamic sitemap/robots)
$routes->match(['GET', 'POST'], 'admin/seo', ['\App\Modules\Admin\Controllers\Seo', 'index']);
$routes->get('admin/seo/generate', ['\App\Modules\Admin\Controllers\Seo', 'generate']);

// Cold Storage Inventory (read-only, derived from cls_* — no own tables)
$routes->get('admin/cold_inventory', ['\App\Modules\Admin\Controllers\Cold_inventory', 'overview']);
$routes->get('admin/cold_inventory/overview', ['\App\Modules\Admin\Controllers\Cold_inventory', 'overview']);
$routes->get('admin/cold_inventory/report/(:segment)', ['\App\Modules\Admin\Controllers\Cold_inventory', 'report/$1']);
$routes->get('admin/cold_inventory/report', ['\App\Modules\Admin\Controllers\Cold_inventory', 'report']);
$routes->get('admin/cold_inventory/report_pdf/(:segment)', ['\App\Modules\Admin\Controllers\Cold_inventory', 'report_pdf/$1']);
$routes->get('admin/cold_inventory/report_csv/(:segment)', ['\App\Modules\Admin\Controllers\Cold_inventory', 'report_csv/$1']);

// GST default-rate settings (super-admin)
$routes->match(['GET', 'POST'], 'admin/gst_setting', [Gst_setting::class, 'index']);

// Change Firm / FY switch (top-nav modal → elements/setting.php)
$routes->post('admin/setting/change_fy_id', ['\App\Modules\Admin\Controllers\Setting', 'change_fy_id']);

// Rice Mill Website Inquiries
$routes->get('admin/ricemill_inquiry/listing', [Ricemill_inquiry::class, 'listing']);
$routes->get('admin/ricemill_inquiry', [Ricemill_inquiry::class, 'listing']);
$routes->post('admin/ricemill_inquiry/view_all', [Ricemill_inquiry::class, 'view_all']);
$routes->post('admin/ricemill_inquiry/update_status', [Ricemill_inquiry::class, 'update_status']);
$routes->post('admin/ricemill_inquiry/add_remark', [Ricemill_inquiry::class, 'add_remark']);
$routes->post('admin/ricemill_inquiry/delete', [Ricemill_inquiry::class, 'delete']);

// Billing Register (report)
$routes->get('admin/billing_register/listing', [Billing_register::class, 'listing']);
$routes->get('admin/billing_register', [Billing_register::class, 'listing']);
$routes->post('admin/billing_register/listing_data', [Billing_register::class, 'listing_data']);
$routes->post('admin/billing_register/delete', [Billing_register::class, 'delete']);

// Page Traffic viewer (log)
$routes->get('admin/traffic/listing', [Traffic::class, 'listing']);
$routes->get('admin/traffic', [Traffic::class, 'listing']);
$routes->post('admin/traffic/view_all', [Traffic::class, 'view_all']);

// Device Management (log)
$routes->get('admin/device/listing', [Device::class, 'listing']);
$routes->get('admin/device', [Device::class, 'listing']);
$routes->post('admin/device/view_all', [Device::class, 'view_all']);
$routes->post('admin/device/update_status', [Device::class, 'update_status']);
$routes->post('admin/device/delete', [Device::class, 'delete']);

// Documents Renewal (full flow)
$routes->get('admin/document/listing', [Document::class, 'listing']);
$routes->get('admin/document', [Document::class, 'listing']);
$routes->post('admin/document/view_all', [Document::class, 'view_all']);
$routes->match(['GET', 'POST'], 'admin/document/add', [Document::class, 'add']);
$routes->match(['GET', 'POST'], 'admin/document/edit/(:segment)', [Document::class, 'edit']);
$routes->get('admin/document/download/(:segment)', [Document::class, 'download']);
$routes->post('admin/document/delete', [Document::class, 'delete']);

// Letter Pad (full flow: create/edit + PDF + QR verify)
$routes->get('admin/letter_pad/listing', [Letter_pad::class, 'listing']);
$routes->get('admin/letter_pad', [Letter_pad::class, 'listing']);
$routes->post('admin/letter_pad/listing_data', [Letter_pad::class, 'listing_data']);
$routes->match(['GET', 'POST'], 'admin/letter_pad/add', [Letter_pad::class, 'add']);
$routes->match(['GET', 'POST'], 'admin/letter_pad/edit/(:segment)', [Letter_pad::class, 'edit']);
$routes->get('admin/letter_pad/pdf/(:segment)', [Letter_pad::class, 'pdf']);
$routes->get('admin/letter_pad/download/(:segment)', [Letter_pad::class, 'download']);
$routes->post('admin/letter_pad/delete', [Letter_pad::class, 'delete']);

// APK Manager (App Updates — full flow)
$routes->get('admin/app_update/listing', [App_update::class, 'listing']);
$routes->get('admin/app_update', [App_update::class, 'listing']);
$routes->post('admin/app_update/versions_data', [App_update::class, 'versions_data']);
$routes->match(['GET', 'POST'], 'admin/app_update/upload', [App_update::class, 'upload']);
$routes->post('admin/app_update/toggle_status', [App_update::class, 'toggle_status']);
$routes->post('admin/app_update/flag_toggle', [App_update::class, 'flag_toggle']);
$routes->post('admin/app_update/mark_latest', [App_update::class, 'mark_latest']);
$routes->post('admin/app_update/delete', [App_update::class, 'delete']);
$routes->get('admin/app_update/download/(:num)', [App_update::class, 'download']);
$routes->match(['GET', 'POST'], 'admin/app_update/settings', [App_update::class, 'settings']);
$routes->get('admin/app_update/logs', [App_update::class, 'logs']);
$routes->get('admin/app_update/portal', [App_update::class, 'portal']);
