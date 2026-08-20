<?php

namespace Modules\SuperAdmin\Controllers;

use App\Controllers\BaseController;
use App\Models\CompanyModel;
use App\Models\CouponModel;
use App\Models\CouponRedemptionModel;
use App\Models\PaymentOrderModel;
use App\Models\SubscriptionModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use Config\Database;

/**
 * Super Admin SaaS panel — oversight of all customers, firms, users,
 * subscriptions and activity across the whole application. It manages accounts
 * and billing status only; it never edits a customer's business data.
 */
class SuperAdminController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company'];

    private function db()
    {
        return Database::connect();
    }

    // ---------------------------------------------------------------
    public function dashboard()
    {
        $db = $this->db();

        $customers = $db->table('users')->where('account_type', 'customer')->where('deleted_at', null);
        $totalCustomers = (clone $customers)->countAllResults(false);
        $activeCustomers = (clone $customers)->where('status', 1)->countAllResults();

        $firms = $db->table('companies')->where('deleted_at', null);
        $totalFirms  = (clone $firms)->countAllResults(false);
        $activeFirms = (clone $firms)->where('status', 1)->countAllResults();

        $totalFirmUsers = $db->table('users')->where('account_type', 'firm_user')->where('deleted_at', null)->countAllResults();
        $locatedLogins = $db->fieldExists('latitude', 'login_logs')
            ? $db->table('login_logs')
                ->where('status', 'success')
                ->where('latitude IS NOT NULL', null, false)
                ->where('longitude IS NOT NULL', null, false)
                ->countAllResults()
            : 0;

        // Subscription / payment summary.
        $subs = $db->table('subscriptions')->select('payment_status, COUNT(*) AS c')->groupBy('payment_status')->get()->getResultArray();
        $payments = [];
        foreach ($subs as $s) {
            $payments[$s['payment_status']] = (int) $s['c'];
        }

        $recentActivity = $db->table('activity_logs')
            ->select('activity_logs.*, users.name AS user_name')
            ->join('users', 'users.id = activity_logs.user_id', 'left')
            ->orderBy('activity_logs.id', 'DESC')->limit(10)->get()->getResultArray();

        return $this->render('dashboard', [
            'title'      => 'Super Admin',
            'breadcrumb' => [['label' => 'Super Admin']],
            'stats'      => [
                'customers'        => $totalCustomers,
                'customers_active' => $activeCustomers,
                'customers_inactive' => $totalCustomers - $activeCustomers,
                'firms'            => $totalFirms,
                'firms_active'     => $activeFirms,
                'firms_inactive'   => $totalFirms - $activeFirms,
                'firm_users'       => $totalFirmUsers,
                'plans'            => $db->table('subscription_plans')->where('status', 1)->countAllResults(),
                'located_logins'   => $locatedLogins,
            ],
            'payments'   => $payments,
            'recent'     => $recentActivity,
        ]);
    }

    // ---------------------------------------------------------------
    public function locations()
    {
        $db = $this->db();

        if (! $db->fieldExists('latitude', 'login_logs') || ! $db->fieldExists('longitude', 'login_logs')) {
            return $this->render('locations', [
                'title'      => 'Mobile User Locations',
                'breadcrumb' => [['label' => 'Super Admin', 'url' => site_url('admin')], ['label' => 'Mobile User Locations']],
                'ready'      => false,
                'filters'    => ['days' => 30, 'source' => '', 'q' => ''],
            ]);
        }

        $days = (int) ($this->request->getGet('days') ?: 30);
        $days = max(1, min(365, $days));
        $source = trim((string) $this->request->getGet('source'));
        if (! in_array($source, ['gps', 'ip'], true)) {
            $source = '';
        }
        $q = trim((string) $this->request->getGet('q'));
        $from = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));

        $base = $this->locationLogBuilder($from, $source, $q);

        $total = (clone $base)->countAllResults();
        $gps = (clone $base)->where('login_logs.location_source', 'gps')->countAllResults();
        $ip = (clone $base)->where('login_logs.location_source', 'ip')->countAllResults();
        $suspicious = (clone $base)->where('login_logs.is_suspicious', 1)->countAllResults();
        $mobile = (clone $base)->where('login_logs.device_type', 'Mobile')->countAllResults();
        $usersRow = (clone $base)->select('COUNT(DISTINCT login_logs.user_id) AS c', false)->get()->getRowArray();
        $avgRow = (clone $base)
            ->where('login_logs.location_source', 'gps')
            ->where('login_logs.location_accuracy IS NOT NULL', null, false)
            ->select('AVG(login_logs.location_accuracy) AS c', false)
            ->get()->getRowArray();

        $bySource = (clone $base)
            ->select('COALESCE(login_logs.location_source, "unknown") AS source, COUNT(*) AS total', false)
            ->groupBy('login_logs.location_source')
            ->orderBy('total', 'DESC')
            ->get()->getResultArray();

        $byDay = (clone $base)
            ->select('DATE(COALESCE(login_logs.login_at, login_logs.created_at)) AS day, COUNT(*) AS total', false)
            ->groupBy('DATE(COALESCE(login_logs.login_at, login_logs.created_at))', false)
            ->orderBy('day', 'ASC')
            ->get()->getResultArray();

        $topLocations = (clone $base)
            ->select('COALESCE(NULLIF(login_logs.location_label, ""), CONCAT(ROUND(login_logs.latitude, 3), ", ", ROUND(login_logs.longitude, 3))) AS label, login_logs.location_source, COUNT(*) AS total, COUNT(DISTINCT login_logs.user_id) AS users', false)
            ->groupBy('label, login_logs.location_source', false)
            ->orderBy('total', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        $recent = (clone $base)
            ->select('login_logs.*, users.name AS user_name, users.email AS user_email, users.mobile AS user_mobile, users.account_type')
            ->orderBy('login_logs.id', 'DESC')
            ->limit(30)
            ->get()->getResultArray();

        $points = [];
        foreach ($recent as $row) {
            if ($row['latitude'] === null || $row['longitude'] === null || $row['latitude'] === '' || $row['longitude'] === '') {
                continue;
            }
            $points[] = [
                'lat'    => (float) $row['latitude'],
                'lng'    => (float) $row['longitude'],
                'label'  => trim((string) ($row['location_label'] ?? '')) ?: ((float) $row['latitude'] . ', ' . (float) $row['longitude']),
                'user'   => $row['user_name'] ?: ($row['username'] ?? 'Unknown user'),
                'source' => $row['location_source'] ?: 'unknown',
                'when'   => $row['login_at'] ?: $row['created_at'],
            ];
        }

        return $this->render('locations', [
            'title'      => 'Mobile User Locations',
            'breadcrumb' => [['label' => 'Super Admin', 'url' => site_url('admin')], ['label' => 'Mobile User Locations']],
            'ready'      => true,
            'filters'    => ['days' => $days, 'source' => $source, 'q' => $q],
            'stats'      => [
                'total'        => $total,
                'gps'          => $gps,
                'ip'           => $ip,
                'users'        => (int) ($usersRow['c'] ?? 0),
                'suspicious'   => $suspicious,
                'mobile'       => $mobile,
                'avg_accuracy' => $avgRow && $avgRow['c'] !== null ? (int) round((float) $avgRow['c']) : null,
            ],
            'bySource'     => $bySource,
            'byDay'        => $byDay,
            'topLocations' => $topLocations,
            'recent'       => $recent,
            'points'       => $points,
        ]);
    }

    private function locationLogBuilder(string $from, string $source = '', string $q = '')
    {
        $builder = $this->db()->table('login_logs')
            ->join('users', 'users.id = login_logs.user_id', 'left')
            ->where('login_logs.status', 'success')
            ->where('login_logs.latitude IS NOT NULL', null, false)
            ->where('login_logs.longitude IS NOT NULL', null, false)
            ->where('COALESCE(login_logs.login_at, login_logs.created_at) >=', $from);

        if ($source !== '') {
            $builder->where('login_logs.location_source', $source);
        }
        if ($q !== '') {
            $builder->groupStart()
                ->like('users.name', $q)
                ->orLike('users.email', $q)
                ->orLike('users.mobile', $q)
                ->orLike('login_logs.username', $q)
                ->orLike('login_logs.ip_address', $q)
                ->orLike('login_logs.location_label', $q)
                ->groupEnd();
        }

        return $builder;
    }

    // ---------------------------------------------------------------
    public function customers()
    {
        $search = trim((string) $this->request->getGet('q'));
        $b = (new UserModel())
            ->select('users.*, (SELECT COUNT(*) FROM companies c WHERE c.owner_id = users.id AND c.deleted_at IS NULL) AS firm_count')
            ->where('account_type', 'customer')
            ->orderBy('users.id', 'DESC');
        if ($search !== '') {
            $b->groupStart()->like('users.name', $search)->orLike('users.email', $search)->groupEnd();
        }

        $rows = $b->paginate(15);
        $subModel = new SubscriptionModel();
        foreach ($rows as &$r) {
            $r['subscription'] = $subModel->forCustomer((int) $r['id']);
        }

        return $this->render('customers', [
            'title'      => 'Customers',
            'breadcrumb' => [['label' => 'Super Admin', 'url' => site_url('admin')], ['label' => 'Customers']],
            'rows'       => $rows,
            'pager'      => (new UserModel())->pager,
            'search'     => $search,
        ]);
    }

    /** Simple guided screen: pick a customer + package and activate the paid plan. */
    public function activate()
    {
        $customers = (new UserModel())
            ->select('id, name, email')
            ->where('account_type', 'customer')
            ->orderBy('name', 'ASC')->findAll();

        $subModel = new SubscriptionModel();
        foreach ($customers as &$c) {
            $sub = $subModel->forCustomer((int) $c['id']);
            $c['sub_status'] = $sub['payment_status'] ?? 'none';
            $c['expires_at'] = $sub['expires_at'] ?? null;
        }
        unset($c);

        return $this->render('activate', [
            'title'      => 'Activate Plan',
            'breadcrumb' => [['label' => 'Super Admin', 'url' => site_url('admin')], ['label' => 'Activate Plan']],
            'customers'  => $customers,
            'plans'      => (new SubscriptionPlanModel())->where('status', 1)->where('price >', 0)->orderBy('price', 'ASC')->findAll(),
            'preselect'  => (int) $this->request->getGet('customer'),
        ]);
    }

    /** Apply the chosen paid plan to the chosen customer. */
    public function activateSave()
    {
        $customerId = (int) $this->request->getPost('customer_id');
        $planId     = (int) $this->request->getPost('plan_id');

        $user = (new UserModel())->where('account_type', 'customer')->find($customerId);
        if (! $user) {
            return redirect()->back()->withInput()->with('error', 'Choose a valid customer.');
        }
        $plan = (new SubscriptionPlanModel())->find($planId);
        if (! $plan || (int) $plan['status'] !== 1) {
            return redirect()->back()->withInput()->with('error', 'Choose a valid, active package.');
        }

        (new SubscriptionModel())->activatePaid($customerId, $plan);
        activity_log('SuperAdmin', 'Edit', "Activated plan '{$plan['name']}' for customer #{$customerId}");
        $this->notifyCustomer($customerId, 'success', $plan['name'] . ' plan activated',
            'Your ' . $plan['name'] . ' subscription has been activated by our team. All included features are now unlocked.');

        return redirect()->to(site_url('admin/activate'))
            ->with('success', $user['name'] . ' is now on the ' . $plan['name'] . ' plan.');
    }

    public function toggleCustomer($id = null)
    {
        $users = new UserModel();
        $u = $users->where('account_type', 'customer')->find((int) $id);
        if (! $u) {
            return redirect()->back()->with('error', 'Customer not found.');
        }
        $users->update((int) $id, ['status' => (int) $u['status'] === 1 ? 0 : 1]);
        activity_log('SuperAdmin', 'Edit', "Customer #{$id} status toggled");
        return redirect()->back()->with('success', 'Customer status updated.');
    }

    /** Force the customer to reset their password on next login. */
    public function resetAccess($id = null)
    {
        $users = new UserModel();
        if (! $users->where('account_type', 'customer')->find((int) $id)) {
            return redirect()->back()->with('error', 'Customer not found.');
        }
        $users->update((int) $id, ['must_change_password' => 1, 'remember_token' => null]);
        activity_log('SuperAdmin', 'Edit', "Reset access for customer #{$id}");
        return redirect()->back()->with('success', 'Access reset — the customer must set a new password on next login.');
    }

    /**
     * Set a new login password for a customer (support flow: the customer emailed
     * asking for help getting back in). We NEVER show an existing password — they
     * are stored as one-way bcrypt hashes and are unrecoverable by design. Instead
     * the admin sets a brand-new one (typed, or auto-generated) which is shown ONCE
     * here to relay, optionally emailed, and flagged must-change so the customer
     * picks their own on next login.
     */
    public function setPassword($id = null)
    {
        $users = new UserModel();
        $user  = $users->where('account_type', 'customer')->find((int) $id);
        if (! $user) {
            return redirect()->back()->with('error', 'Customer not found.');
        }

        $new    = trim((string) $this->request->getPost('new_password'));
        $notify = (string) $this->request->getPost('email_customer') === '1';

        // Blank field = "just generate one for me" (the email-me-a-password case).
        if ($new === '') {
            $new = $this->generatePassword();
        } elseif (strlen($new) < 8) {
            return redirect()->back()->with('error', 'Password must be at least 8 characters.');
        }

        // Store only the bcrypt hash; force a change on next login; drop any
        // remembered sessions so the old device tokens can't keep the account open.
        $users->update((int) $id, [
            'password'             => password_hash($new, PASSWORD_DEFAULT),
            'must_change_password' => 1,
            'remember_token'       => null,
        ]);
        activity_log('SuperAdmin', 'Edit', "Set a new password for customer #{$id}");

        $emailed = false;
        if ($notify && filter_var($user['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $emailed = (new \App\Libraries\Mailer())->temporaryPassword($user['email'], (string) ($user['name'] ?? ''), $new);
        }

        // Reveal the new password to the admin ONCE (flashdata — not persisted) so
        // they can relay it; include whether the email went out.
        return redirect()->back()
            ->with('new_password', $new)
            ->with('new_password_for', (string) ($user['name'] ?? $user['email'] ?? ('#' . $id)))
            ->with('new_password_emailed', $emailed ? '1' : '0')
            ->with('success', 'New password set. The customer must change it on next login.');
    }

    /**
     * Email the customer a one-click password-reset link (support flow — the
     * customer resets it themselves, so no password is ever set or seen by the
     * admin). Reuses the exact same token machinery as the self-service
     * forgot-password page, so the link + expiry behave identically.
     */
    public function sendResetLink($id = null)
    {
        $users = new UserModel();
        $user  = $users->where('account_type', 'customer')->find((int) $id);
        if (! $user) {
            return redirect()->back()->with('error', 'Customer not found.');
        }
        $email = (string) ($user['email'] ?? '');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'This customer has no valid email on file.');
        }

        $token = bin2hex(random_bytes(32));
        (new \App\Models\PasswordResetModel())->insert([
            'email'      => $email,
            'token'      => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        helper('reset_email');
        $sent = send_password_reset_email($email, $token);
        activity_log('SuperAdmin', 'Edit', "Sent a password-reset link to customer #{$id}");

        if (! $sent) {
            // SMTP/SendGrid not configured — surface the link so the admin can relay it.
            return redirect()->back()
                ->with('reset_link', site_url('reset-password/' . $token))
                ->with('reset_link_for', (string) ($user['name'] ?? $email))
                ->with('warning', 'Email not configured — copy the reset link below and share it privately.');
        }
        return redirect()->back()->with('success', 'A password-reset link was emailed to ' . esc($email) . '.');
    }

    /**
     * Generate a strong, human-relayable password: 12 chars from an unambiguous
     * alphabet (no 0/O/1/l/I), guaranteed to include lower/upper/digit/symbol.
     */
    private function generatePassword(): string
    {
        $lower  = 'abcdefghijkmnpqrstuvwxyz';
        $upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $digits = '23456789';
        $sym    = '@#%&*!?';
        $all    = $lower . $upper . $digits . $sym;

        $pick = static fn (string $set): string => $set[random_int(0, strlen($set) - 1)];
        $chars = [$pick($lower), $pick($upper), $pick($digits), $pick($sym)];
        for ($i = strlen(implode('', $chars)); $i < 12; $i++) {
            $chars[] = $pick($all);
        }
        // Shuffle so the guaranteed-class chars aren't always in the first slots.
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }
        return implode('', $chars);
    }

    public function updatePayment($id = null)
    {
        $status = (string) $this->request->getPost('payment_status');
        if (! in_array($status, ['trial', 'paid', 'unpaid'], true)) {
            return redirect()->back()->with('error', 'Invalid payment status.');
        }
        $sub = new SubscriptionModel();

        if ($status === 'paid') {
            // Grant a real paid period: assign the chosen plan (or the cheapest paid
            // one) and extend the expiry by its billing cycle.
            $planId = (int) $this->request->getPost('plan_id');
            $plans  = new SubscriptionPlanModel();
            $plan   = $planId ? $plans->find($planId) : null;
            if (! $plan) {
                $plan = $plans->where('status', 1)->where('price >', 0)->orderBy('price', 'ASC')->first();
            }
            if (! $plan) {
                return redirect()->back()->with('error', 'No paid plan is defined. Create one on the Plans page first.');
            }
            $sub->activatePaid((int) $id, $plan);
            $this->notifyCustomer((int) $id, 'success', $plan['name'] . ' plan activated',
                'Your ' . $plan['name'] . ' subscription has been activated by our team. All included features are now unlocked.');
        } else {
            $row = $sub->where('customer_id', (int) $id)->orderBy('id', 'DESC')->first();
            if ($row) {
                $sub->update($row['id'], ['payment_status' => $status]);
            }
        }
        activity_log('SuperAdmin', 'Edit', "Payment status of customer #{$id} set to {$status}");
        return redirect()->back()->with('success', 'Payment status updated.');
    }

    /** Access (impersonate) any user's account. Gated by the superadmin filter. */
    public function impersonate($id = null)
    {
        // Record on the SUPER ADMIN's own audit trail BEFORE switching sessions,
        // so the access is accountable here but leaves no trace in the user's account.
        activity_log('SuperAdmin', 'Access', 'Accessed user account #' . (int) $id);

        // Remember the admin page this was launched from so "Return to Super Admin"
        // lands back exactly there (e.g. the customer's subscription page), not on a
        // fixed dashboard. Captured before the session switch.
        $return = (string) ($this->request->getServer('HTTP_REFERER') ?? '');

        [$ok, $msg] = auth()->impersonate((int) $id);
        if (! $ok) {
            return redirect()->back()->with('error', $msg);
        }
        // Only same-site admin URLs are stored (guards against an open redirect on return).
        if ($return !== '' && str_starts_with($return, base_url()) && strpos($return, '/admin') !== false) {
            session()->set('impersonator_return', $return);
        }
        return redirect()->to(site_url('dashboard'))->with('success', $msg);
    }

    // ---------------------------------------------------------------
    // Per-customer subscription: current plan, activation / deactivation,
    // the full payment chain and the subscription activity log.
    // ---------------------------------------------------------------

    /** Full subscription oversight for one customer. */
    public function customerSubscription($id = null)
    {
        $id   = (int) $id;
        $user = (new UserModel())->where('account_type', 'customer')->find($id);
        if (! $user) {
            return redirect()->to(site_url('admin/customers'))->with('error', 'Customer not found.');
        }

        $sub    = (new SubscriptionModel())->forCustomer($id);
        $orders = (new PaymentOrderModel())->forCustomer($id);
        $plans  = (new SubscriptionPlanModel())->where('status', 1)->where('price >', 0)->orderBy('price', 'ASC')->findAll();

        // Subscription / payment activity for this customer. Every such log line
        // records the owning "customer #<id>"; also fold in entries the customer
        // performed under their own account. End-anchored + space-suffixed LIKE
        // avoids matching #5 against #50.
        $logs = $this->db()->table('activity_logs')
            ->select('activity_logs.*, u.name AS user_name')
            ->join('users u', 'u.id = activity_logs.user_id', 'left')
            ->groupStart()
                ->like('activity_logs.description', 'customer #' . $id . ' ')
                ->orLike('activity_logs.description', 'customer #' . $id, 'before')
                ->orGroupStart()
                    ->where('activity_logs.user_id', $id)
                    ->whereIn('activity_logs.module', ['Subscription'])
                ->groupEnd()
            ->groupEnd()
            ->orderBy('activity_logs.id', 'DESC')
            ->limit(60)
            ->get()->getResultArray();

        return $this->render('customer_subscription', [
            'title'      => 'Subscription — ' . $user['name'],
            'breadcrumb' => [
                ['label' => 'Super Admin', 'url' => site_url('admin')],
                ['label' => 'Customers', 'url' => site_url('admin/customers')],
                ['label' => 'Subscription'],
            ],
            'user'   => $user,
            'sub'    => $sub,
            'orders' => $orders,
            'plans'  => $plans,
            'logs'   => $logs,
        ]);
    }

    /** Activate / change the customer's paid plan (from the detail page). */
    public function customerActivate($id = null)
    {
        $id   = (int) $id;
        $user = (new UserModel())->where('account_type', 'customer')->find($id);
        if (! $user) {
            return redirect()->to(site_url('admin/customers'))->with('error', 'Customer not found.');
        }
        $plan = (new SubscriptionPlanModel())->find((int) $this->request->getPost('plan_id'));
        if (! $plan || (int) $plan['status'] !== 1) {
            return redirect()->back()->with('error', 'Choose a valid, active package.');
        }

        (new SubscriptionModel())->activatePaid($id, $plan);
        activity_log('SuperAdmin', 'Edit', "Activated plan '{$plan['name']}' for customer #{$id}");
        $this->notifyCustomer($id, 'success', $plan['name'] . ' plan activated',
            'Your ' . $plan['name'] . ' subscription has been activated by our team. All included features are now unlocked.');

        return redirect()->to(site_url('admin/customers/subscription/' . $id))
            ->with('success', $user['name'] . ' is now on the ' . $plan['name'] . ' plan.');
    }

    /** Deactivate the customer's subscription — access drops to Basic; data is kept. */
    public function customerDeactivate($id = null)
    {
        $id = (int) $id;
        $ok = (new SubscriptionModel())->cancel($id);
        if (! $ok) {
            return redirect()->back()->with('error', 'No subscription found for that customer.');
        }
        activity_log('SuperAdmin', 'Edit', "Subscription deactivated for customer #{$id}");
        $this->notifyCustomer($id, 'warning', 'Subscription deactivated',
            'Your subscription has been deactivated. Your data is safe, but premium features are now locked. Renew any time from the Subscription page.');

        return redirect()->to(site_url('admin/customers/subscription/' . $id))
            ->with('success', 'Subscription deactivated. The customer is now on Basic restrictions (their data is preserved).');
    }

    /**
     * Correct a subscription's expiry date. Fixes an accidental over-extension —
     * e.g. tapping "Activate" several times stacks a billing cycle each press
     * (activatePaid extends from the current expiry), so 4 taps on a yearly plan
     * adds 4 years. This lets the admin set the intended expiry back by hand.
     */
    public function setExpiry($id = null)
    {
        $id   = (int) $id;
        $user = (new UserModel())->where('account_type', 'customer')->find($id);
        if (! $user) {
            return redirect()->to(site_url('admin/customers'))->with('error', 'Customer not found.');
        }

        $sub = new SubscriptionModel();
        $row = $sub->where('customer_id', $id)->orderBy('id', 'DESC')->first();
        if (! $row) {
            return redirect()->back()->with('error', 'This customer has no subscription to correct.');
        }

        $date = trim((string) $this->request->getPost('expires_at')); // yyyy-mm-dd from the date input
        $ts   = $date !== '' ? strtotime($date) : false;
        if ($ts === false) {
            return redirect()->back()->with('error', 'Enter a valid expiry date.');
        }

        // Store end-of-day so the customer keeps access through the whole day.
        $newExpiry = date('Y-m-d 23:59:59', $ts);
        $old       = (string) ($row['expires_at'] ?? '—');
        $sub->update($row['id'], [
            'expires_at'         => $newExpiry,
            'expiry_notified_at' => null, // let expiry reminders fire again for the corrected date
        ]);
        activity_log('SuperAdmin', 'Edit', "Corrected subscription expiry for customer #{$id} from {$old} to {$newExpiry}");

        return redirect()->to(site_url('admin/customers/subscription/' . $id))
            ->with('success', 'Expiry date corrected to ' . date('d M Y', $ts) . '.');
    }

    /**
     * Notify a customer about a subscription change (in-app + best-effort push).
     * Never lets a notification failure break the admin action.
     */
    private function notifyCustomer(int $customerId, string $type, string $title, string $message): void
    {
        if ($customerId <= 0) {
            return;
        }
        try {
            service('notifier')->user($customerId, $title, $message, [
                'type'       => $type,
                'module'     => null, // null module = always visible to the targeted user
                'priority'   => 'high',
                'action_url' => site_url('subscription/transactions'),
                'created_by' => (int) (function_exists('user_id') ? user_id() : 0) ?: $customerId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Customer subscription notification failed: ' . $e->getMessage());
        }
    }

    // ---------------------------------------------------------------
    public function firms()
    {
        $search = trim((string) $this->request->getGet('q'));
        $b = (new CompanyModel())
            ->select('companies.*, users.name AS owner_name, users.email AS owner_email,
                (SELECT COUNT(*) FROM company_users cu WHERE cu.company_id = companies.id) AS user_count')
            ->join('users', 'users.id = companies.owner_id', 'left')
            ->orderBy('companies.id', 'DESC');
        if ($search !== '') {
            $b->groupStart()->like('companies.name', $search)->orLike('users.name', $search)->groupEnd();
        }

        return $this->render('firms', [
            'title'      => 'Firms',
            'breadcrumb' => [['label' => 'Super Admin', 'url' => site_url('admin')], ['label' => 'Firms']],
            'rows'       => $b->paginate(15),
            'pager'      => (new CompanyModel())->pager,
            'search'     => $search,
        ]);
    }

    public function toggleFirm($id = null)
    {
        $companies = new CompanyModel();
        $c = $companies->find((int) $id);
        if (! $c) {
            return redirect()->back()->with('error', 'Firm not found.');
        }
        $companies->update((int) $id, ['status' => (int) $c['status'] === 1 ? 0 : 1]);
        activity_log('SuperAdmin', 'Edit', "Firm #{$id} status toggled");
        return redirect()->back()->with('success', 'Firm status updated.');
    }

    // ---------------------------------------------------------------
    public function plans()
    {
        helper('settings');
        $set = new \App\Models\SettingModel();
        return $this->render('plans', [
            'title'      => 'Subscription Plans',
            'breadcrumb' => [['label' => 'Super Admin', 'url' => site_url('admin')], ['label' => 'Plans']],
            'rows'       => (new SubscriptionPlanModel())->orderBy('price', 'ASC')->findAll(),
            'trialDays'  => (int) $set->get('subscription_trial_days', 0, 180),
            'upiId'      => (string) $set->get('subscription_upi_id', 0, ''),
            'upiName'    => (string) $set->get('subscription_upi_name', 0, ''),
            'invoice'    => [
                'name'    => (string) $set->get('invoice_seller_name', 0, ''),
                'gstin'   => (string) $set->get('invoice_seller_gstin', 0, ''),
                'state'   => (string) $set->get('invoice_seller_state', 0, ''),
                'address' => (string) $set->get('invoice_seller_address', 0, ''),
                'email'   => (string) $set->get('invoice_seller_email', 0, ''),
                'rate'    => (string) $set->get('invoice_tax_rate', 0, '18'),
                'prefix'  => (string) $set->get('invoice_prefix', 0, 'INV'),
            ],
        ]);
    }

    /** Save the UPI id / payee name customers pay to (shown as a QR on /subscription). */
    public function savePayment()
    {
        $set = new \App\Models\SettingModel();
        $set->put('subscription_upi_id', trim((string) $this->request->getPost('upi_id')), 0);
        $set->put('subscription_upi_name', trim((string) $this->request->getPost('upi_name')), 0);
        activity_log('SuperAdmin', 'Edit', 'Subscription UPI payment details updated');
        return redirect()->to(site_url('admin/plans'))->with('success', 'Payment details saved.');
    }

    /** Create or update a plan (price / cycle / limits). */
    public function planSave($id = null)
    {
        $rules = [
            'name'          => 'required|max_length[60]',
            'price'         => 'required|numeric|greater_than_equal_to[0]',
            'billing_cycle' => 'in_list[monthly,yearly,lifetime]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $plans = new SubscriptionPlanModel();
        $name  = trim((string) $this->request->getPost('name'));
        $data  = [
            'name'          => $name,
            'price'         => round((float) $this->request->getPost('price'), 2),
            'billing_cycle' => (string) $this->request->getPost('billing_cycle'),
            'max_firms'     => $this->request->getPost('max_firms') === '' ? null : (int) $this->request->getPost('max_firms'),
            'max_users'     => $this->request->getPost('max_users') === '' ? null : (int) $this->request->getPost('max_users'),
            'features'      => trim((string) $this->request->getPost('features')) ?: null,
            'status'        => $this->request->getPost('status') ? 1 : 0,
        ];

        // Per-feature package flags (checkboxes) — the enforced source of truth.
        $feats = (array) $this->request->getPost('feat');
        foreach (SubscriptionPlanModel::FEATURE_COLUMNS as $feature) {
            $data['feat_' . $feature] = ! empty($feats[$feature]) ? 1 : 0;
        }

        if ($id) {
            $plans->update((int) $id, $data);
            activity_log('SuperAdmin', 'Edit', "Plan #{$id} ({$name}) updated");
            $msg = 'Plan updated.';
        } else {
            // A stable, unique code derived from the name.
            $base = preg_replace('/[^a-z0-9]+/', '_', strtolower($name)) ?: 'plan';
            $code = trim($base, '_');
            $n    = 1;
            while ($plans->where('code', $code)->countAllResults() > 0) {
                $code = trim($base, '_') . '_' . (++$n);
            }
            $plans->insert($data + ['code' => $code]);
            activity_log('SuperAdmin', 'Add', "Plan '{$name}' created");
            $msg = 'Plan created.';
        }
        return redirect()->to(site_url('admin/plans'))->with('success', $msg);
    }

    /** Toggle a plan on/off (an off plan is hidden from customers). */
    public function planToggle($id = null)
    {
        $plans = new SubscriptionPlanModel();
        $p = $plans->find((int) $id);
        if (! $p) {
            return redirect()->back()->with('error', 'Plan not found.');
        }
        $plans->update((int) $id, ['status' => (int) $p['status'] === 1 ? 0 : 1]);
        activity_log('SuperAdmin', 'Edit', "Plan #{$id} status toggled");
        return redirect()->back()->with('success', 'Plan status updated.');
    }

    /** Delete a plan (does not touch existing customer subscriptions). */
    public function planDelete($id = null)
    {
        (new SubscriptionPlanModel())->delete((int) $id);
        activity_log('SuperAdmin', 'Delete', "Plan #{$id} deleted");
        return redirect()->to(site_url('admin/plans'))->with('success', 'Plan deleted.');
    }

    /** Set the global free-trial length (days). */
    public function saveTrial()
    {
        $days = (int) $this->request->getPost('trial_days');
        $days = max(0, min($days, 3650));
        (new \App\Models\SettingModel())->put('subscription_trial_days', (string) $days, 0);
        activity_log('SuperAdmin', 'Edit', "Free-trial length set to {$days} days");
        return redirect()->to(site_url('admin/plans'))->with('success', 'Free-trial length saved.');
    }

    // ---------------------------------------------------------------
    // Coupons (discount + redeem codes)
    // ---------------------------------------------------------------

    /** List all coupons + the form (plans feed the plan-scope / grant dropdown). */
    public function coupons()
    {
        return $this->render('coupons', [
            'title'      => 'Coupons',
            'breadcrumb' => [['label' => 'Super Admin', 'url' => site_url('admin')], ['label' => 'Coupons']],
            'rows'       => (new CouponModel())->listAll(),
            'plans'      => (new SubscriptionPlanModel())->where('status', 1)->orderBy('price', 'ASC')->findAll(),
        ]);
    }

    /** Create or update a coupon. Validates by kind (discount vs redeem). */
    public function couponSave($id = null)
    {
        $req  = $this->request;
        $code = CouponModel::normalize((string) $req->getPost('code'));
        $kind = in_array($req->getPost('kind'), ['discount', 'redeem'], true) ? (string) $req->getPost('kind') : 'discount';

        if ($code === '' || ! preg_match('/^[A-Z0-9_-]{3,40}$/', $code)) {
            return redirect()->back()->withInput()->with('error', 'Code must be 3–40 chars: letters, numbers, - or _.');
        }

        $coupons = new CouponModel();
        // Unique code (ignoring the row being edited).
        $dupe = $coupons->where('code', $code);
        if ($id) {
            $dupe->where('id !=', (int) $id);
        }
        if ($dupe->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('error', 'That code already exists.');
        }

        $planId = $req->getPost('plan_id') !== '' ? (int) $req->getPost('plan_id') : null;

        $data = [
            'code'            => $code,
            'description'     => trim((string) $req->getPost('description')) ?: null,
            'kind'            => $kind,
            'plan_id'         => $planId,
            'min_amount'      => round((float) $req->getPost('min_amount'), 2),
            'max_redemptions' => $req->getPost('max_redemptions') !== '' ? (int) $req->getPost('max_redemptions') : null,
            'per_user_limit'  => $req->getPost('per_user_limit') !== '' ? max(0, (int) $req->getPost('per_user_limit')) : 1,
            'starts_at'       => $req->getPost('starts_at') ?: null,
            'expires_at'      => $req->getPost('expires_at') ?: null,
            'status'          => $req->getPost('status') ? 1 : 0,
        ];

        if ($kind === 'discount') {
            $type  = in_array($req->getPost('discount_type'), ['percent', 'fixed'], true) ? (string) $req->getPost('discount_type') : 'percent';
            $value = round((float) $req->getPost('discount_value'), 2);
            if ($value <= 0 || ($type === 'percent' && $value > 100)) {
                return redirect()->back()->withInput()->with('error', 'Enter a valid discount value (percent 1–100, or a fixed ₹ amount).');
            }
            $data['discount_type']  = $type;
            $data['discount_value'] = $value;
            $data['max_discount']   = $req->getPost('max_discount') !== '' ? round((float) $req->getPost('max_discount'), 2) : null;
            $data['free_days']      = null;
        } else {
            // redeem
            $days = (int) $req->getPost('free_days');
            if ($days <= 0) {
                return redirect()->back()->withInput()->with('error', 'A redeem code needs a free period (days) greater than 0.');
            }
            if (! $planId) {
                return redirect()->back()->withInput()->with('error', 'A redeem code must grant a specific plan — choose one.');
            }
            $data['free_days']      = $days;
            $data['discount_type']  = null;
            $data['discount_value'] = 0;
            $data['max_discount']   = null;
        }

        if ($id) {
            $coupons->update((int) $id, $data);
            activity_log('SuperAdmin', 'Edit', "Coupon {$code} updated");
            $msg = 'Coupon updated.';
        } else {
            $data['created_by'] = (int) (function_exists('user_id') ? user_id() : 0) ?: null;
            $coupons->insert($data);
            activity_log('SuperAdmin', 'Add', "Coupon {$code} created");
            $msg = 'Coupon created.';
        }
        return redirect()->to(site_url('admin/coupons'))->with('success', $msg);
    }

    /** Toggle a coupon active/inactive. */
    public function couponToggle($id = null)
    {
        $coupons = new CouponModel();
        $c = $coupons->find((int) $id);
        if (! $c) {
            return redirect()->back()->with('error', 'Coupon not found.');
        }
        $coupons->update((int) $id, ['status' => (int) $c['status'] === 1 ? 0 : 1]);
        activity_log('SuperAdmin', 'Edit', "Coupon #{$id} status toggled");
        return redirect()->back()->with('success', 'Coupon status updated.');
    }

    /** Soft-delete a coupon (its past redemptions stay for the audit trail). */
    public function couponDelete($id = null)
    {
        (new CouponModel())->delete((int) $id);
        activity_log('SuperAdmin', 'Delete', "Coupon #{$id} deleted");
        return redirect()->to(site_url('admin/coupons'))->with('success', 'Coupon deleted.');
    }

    /**
     * Coupon usage trail — who redeemed which coupon, when, and what they got.
     * Optional ?coupon_id= filter (drill in from the coupon list) and ?q= search.
     */
    public function couponLog()
    {
        $couponId = (int) $this->request->getGet('coupon_id') ?: null;
        $q        = trim((string) $this->request->getGet('q'));
        $model    = new CouponRedemptionModel();

        return $this->render('coupon_redemptions', [
            'title'      => 'Coupon Usage',
            'breadcrumb' => [
                ['label' => 'Super Admin', 'url' => site_url('admin')],
                ['label' => 'Coupons', 'url' => site_url('admin/coupons')],
                ['label' => 'Usage'],
            ],
            'rows'      => $model->recent($couponId, $q),
            'stats'     => $model->summary(),
            'q'         => $q,
            'couponId'  => $couponId,
            'coupon'    => $couponId ? (new CouponModel())->find($couponId) : null,
        ]);
    }

    // ---------------------------------------------------------------
    // Transactions / payments management
    // ---------------------------------------------------------------

    /** Public inquiry / contact-form submissions, newest first. */
    public function inquiries()
    {
        $status = (string) $this->request->getGet('status');
        $b = (new \App\Models\InquiryModel())->orderBy('id', 'DESC');
        if (in_array($status, ['new', 'read', 'closed'], true)) {
            $b->where('status', $status);
        }

        $db     = $this->db();
        $counts = $db->table('inquiries')->select('status, COUNT(*) AS c')->groupBy('status')->get()->getResultArray();
        $byStatus = ['new' => 0, 'read' => 0, 'closed' => 0];
        foreach ($counts as $c) {
            $byStatus[$c['status']] = (int) $c['c'];
        }

        return $this->render('inquiries', [
            'title'      => 'Inquiries',
            'breadcrumb' => [['label' => 'Super Admin', 'url' => site_url('admin')], ['label' => 'Inquiries']],
            'rows'       => $b->findAll(),
            'counts'     => $byStatus,
            'status'     => $status,
        ]);
    }

    /** Update an inquiry's status (new / read / closed). */
    public function inquiryStatus($id = null)
    {
        $status = (string) $this->request->getPost('status');
        if (! in_array($status, ['new', 'read', 'closed'], true)) {
            return redirect()->back()->with('error', 'Invalid status.');
        }
        $m = new \App\Models\InquiryModel();
        if (! $m->find((int) $id)) {
            return redirect()->back()->with('error', 'Inquiry not found.');
        }
        $m->update((int) $id, ['status' => $status]);
        return redirect()->back()->with('success', 'Inquiry marked ' . $status . '.');
    }

    /** All Cashfree payment orders with customer + plan, filterable by status. */
    public function transactions()
    {
        $db     = $this->db();
        $status = (string) $this->request->getGet('status');
        $q      = trim((string) $this->request->getGet('q'));

        $b = $db->table('payment_orders po')
            ->select('po.*, u.name AS customer_name, u.email AS customer_email, sp.name AS plan_name')
            ->join('users u', 'u.id = po.customer_id', 'left')
            ->join('subscription_plans sp', 'sp.id = po.plan_id', 'left')
            ->orderBy('po.id', 'DESC');

        if (in_array($status, ['created', 'paid', 'failed', 'refunded'], true)) {
            $b->where('po.status', $status);
        }
        if ($q !== '') {
            $b->groupStart()
                ->like('po.order_id', $q)->orLike('po.invoice_no', $q)
                ->orLike('u.name', $q)->orLike('u.email', $q)
                ->groupEnd();
        }

        $rows  = $b->get()->getResultArray();
        $stats = $db->table('payment_orders')
            ->select("COUNT(*) AS total,
                      SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) AS revenue,
                      SUM(status='paid') AS paid, SUM(status='failed') AS failed, SUM(refunded=1) AS refunded")
            ->get()->getRowArray();

        return $this->render('transactions', [
            'title'      => 'Transactions',
            'breadcrumb' => [['label' => 'Super Admin', 'url' => site_url('admin')], ['label' => 'Transactions']],
            'rows'       => $rows,
            'stats'      => $stats,
            'status'     => $status,
            'q'          => $q,
        ]);
    }

    /** Mark a paid order refunded (record-keeping; the money is refunded in the gateway). */
    public function refundTransaction($id = null)
    {
        $orders = new \App\Models\PaymentOrderModel();
        $order  = $orders->find((int) $id);
        if (! $order || $order['status'] !== 'paid') {
            return redirect()->back()->with('error', 'Only a paid transaction can be marked refunded.');
        }
        $orders->update((int) $id, ['status' => 'refunded', 'refunded' => 1]);
        activity_log('SuperAdmin', 'Edit', "Transaction {$order['order_id']} marked refunded");
        return redirect()->back()->with('success', 'Transaction marked as refunded.');
    }

    /** Cancel a customer's subscription — access drops to Basic; data is preserved. */
    public function cancelSubscription($customerId = null)
    {
        $ok = (new SubscriptionModel())->cancel((int) $customerId);
        if (! $ok) {
            return redirect()->back()->with('error', 'No subscription found for that customer.');
        }
        activity_log('SuperAdmin', 'Edit', "Subscription cancelled for customer #{$customerId}");
        $this->notifyCustomer((int) $customerId, 'warning', 'Subscription cancelled',
            'Your subscription has been cancelled. Your data is preserved, but premium features are now locked. You can renew any time from the Subscription page.');
        return redirect()->back()->with('success', 'Subscription cancelled. The customer is now on Basic restrictions (their data is kept).');
    }

    /** Save invoice / GST seller details used on tax receipts. */
    public function saveInvoice()
    {
        (new \App\Models\SettingModel())->putMany([
            'invoice_seller_name'    => trim((string) $this->request->getPost('invoice_seller_name')),
            'invoice_seller_gstin'   => strtoupper(trim((string) $this->request->getPost('invoice_seller_gstin'))),
            'invoice_seller_state'   => trim((string) $this->request->getPost('invoice_seller_state')),
            'invoice_seller_address' => trim((string) $this->request->getPost('invoice_seller_address')),
            'invoice_seller_email'   => trim((string) $this->request->getPost('invoice_seller_email')),
            'invoice_tax_rate'       => (string) max(0, min(100, (float) $this->request->getPost('invoice_tax_rate'))),
            'invoice_prefix'         => trim((string) $this->request->getPost('invoice_prefix')) ?: 'INV',
        ], 0);
        activity_log('SuperAdmin', 'Edit', 'Invoice / GST seller details updated');
        return redirect()->to(site_url('admin/plans'))->with('success', 'Invoice details saved.');
    }
}
