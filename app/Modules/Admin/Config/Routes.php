<?php

/**
 * Admin module routes. The admin/* group carries the guard filter chain
 * (adminAuth + fyContext + rbac) configured in app/Config/Filters.php.
 * Real admin controllers register here as they are ported.
 */

use App\Modules\Admin\Controllers\Dashboard;
use App\Modules\Admin\Controllers\Invoice;
use App\Modules\Admin\Controllers\Hsn;
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

$routes->get('admin/dashboard', [Dashboard::class, 'index']);

// Bill of Supply (invoice) — listing slice (P6 preview)
$routes->get('admin/invoice/listing', [Invoice::class, 'listing']);
$routes->post('admin/invoice/view_all', [Invoice::class, 'viewAll']);
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

// Users (P2)
$routes->get('admin/users/listing', [Users::class, 'listing']);
$routes->get('admin/users', [Users::class, 'listing']);
$routes->post('admin/users/view_all', [Users::class, 'viewAll']);
$routes->post('admin/users/updateUserStatus', [Users::class, 'updateUserStatus']);
$routes->post('admin/users/delete', [Users::class, 'delete']);
$routes->match(['get', 'post'], 'admin/users/add', [Users::class, 'add']);
$routes->match(['get', 'post'], 'admin/users/edit/(:segment)', [Users::class, 'edit']);
$routes->get('admin/users/view/(:segment)', [Users::class, 'view']);

// Profile — self-profile (P2)
$routes->match(['get', 'post'], 'admin/profile', [Profile::class, 'index']);
$routes->post('admin/profile/changeImage', [Profile::class, 'changeImage']);
$routes->match(['get', 'post'], 'admin/profile/reset_password', [Profile::class, 'reset_password']);

// Notification centre (P2)
$routes->get('admin/notification/listing', [Notification::class, 'listing']);
$routes->get('admin/notification', [Notification::class, 'listing']);
$routes->post('admin/notification/view_all', [Notification::class, 'viewAll']);
$routes->get('admin/notification/mark_all_read', [Notification::class, 'mark_all_read']);
$routes->post('admin/notification/mark_seen', [Notification::class, 'mark_seen']);
$routes->get('admin/notification/read/(:num)', [Notification::class, 'read']);
$routes->post('admin/notification/updatenotificationStatus', [Notification::class, 'updatenotificationStatus']);

// Role Permissions — RBAC matrix editor (P2, super-admin only)
$routes->match(['get', 'post'], 'admin/role_permissions', [Role_permissions::class, 'index']);
$routes->match(['get', 'post'], 'admin/user_permissions', [User_permissions::class, 'index']);

// Menu order — per-user left-menu personalisation (P2, JSON)
$routes->post('admin/menu_order/save', [Menu_order::class, 'save']);
$routes->match(['get', 'post'], 'admin/menu_order/reset', [Menu_order::class, 'reset']);

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
$routes->match(['get', 'post'], 'admin/item_master/add', [Item_master::class, 'add']);
$routes->match(['get', 'post'], 'admin/item_master/edit/(:segment)', [Item_master::class, 'edit']);
$routes->post('admin/item_master/delete', [Item_master::class, 'delete']);
$routes->post('admin/item_master/updateStatus', [Item_master::class, 'updateStatus']);

// Account Master (account_name) — listing slice (P6 master)
$routes->get('admin/account_name/listing', [Account_name::class, 'listing']);
$routes->get('admin/account_name', [Account_name::class, 'listing']);
$routes->post('admin/account_name/view_all', [Account_name::class, 'view_all']);
$routes->post('admin/account_name/updateStatus', [Account_name::class, 'updateStatus']);
$routes->post('admin/account_name/soft_delete', [Account_name::class, 'soft_delete']);
$routes->post('admin/account_name/restore', [Account_name::class, 'restore']);
$routes->post('admin/account_name/quick_update', [Account_name::class, 'quick_update']);

// GST default-rate settings (super-admin)
$routes->match(['get', 'post'], 'admin/gst_setting', [Gst_setting::class, 'index']);

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
$routes->match(['get', 'post'], 'admin/document/add', [Document::class, 'add']);
$routes->match(['get', 'post'], 'admin/document/edit/(:segment)', [Document::class, 'edit']);
$routes->get('admin/document/download/(:segment)', [Document::class, 'download']);
$routes->post('admin/document/delete', [Document::class, 'delete']);

// Letter Pad (full flow: create/edit + PDF + QR verify)
$routes->get('admin/letter_pad/listing', [Letter_pad::class, 'listing']);
$routes->get('admin/letter_pad', [Letter_pad::class, 'listing']);
$routes->post('admin/letter_pad/listing_data', [Letter_pad::class, 'listing_data']);
$routes->match(['get', 'post'], 'admin/letter_pad/add', [Letter_pad::class, 'add']);
$routes->match(['get', 'post'], 'admin/letter_pad/edit/(:segment)', [Letter_pad::class, 'edit']);
$routes->get('admin/letter_pad/pdf/(:segment)', [Letter_pad::class, 'pdf']);
$routes->get('admin/letter_pad/download/(:segment)', [Letter_pad::class, 'download']);
$routes->post('admin/letter_pad/delete', [Letter_pad::class, 'delete']);
