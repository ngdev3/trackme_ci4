<?php

namespace Modules\SuperAdmin\Controllers;

use App\Controllers\BaseController;
use App\Models\CompanyModel;
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
            ],
            'payments'   => $payments,
            'recent'     => $recentActivity,
        ]);
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

    public function updatePayment($id = null)
    {
        $status = (string) $this->request->getPost('payment_status');
        if (! in_array($status, ['trial', 'paid', 'unpaid'], true)) {
            return redirect()->back()->with('error', 'Invalid payment status.');
        }
        $sub = new SubscriptionModel();
        $row = $sub->where('customer_id', (int) $id)->orderBy('id', 'DESC')->first();
        if ($row) {
            $sub->update($row['id'], ['payment_status' => $status, 'status' => $status === 'paid' ? 'active' : $row['status']]);
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

        [$ok, $msg] = auth()->impersonate((int) $id);
        if (! $ok) {
            return redirect()->back()->with('error', $msg);
        }
        return redirect()->to(site_url('dashboard'))->with('success', $msg);
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
        return $this->render('plans', [
            'title'      => 'Subscription Plans',
            'breadcrumb' => [['label' => 'Super Admin', 'url' => site_url('admin')], ['label' => 'Plans']],
            'rows'       => (new SubscriptionPlanModel())->orderBy('price', 'ASC')->findAll(),
        ]);
    }
}
