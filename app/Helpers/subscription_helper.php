<?php

use App\Models\SettingModel;
use App\Models\SubscriptionModel;

/**
 * Subscription / plan gating.
 *
 * A "customer" is the owner of the active company. Everyone in that firm follows
 * the owner's subscription. Access is "pro" (full) during the free trial and while
 * a paid plan is active; it drops to "free" (restricted) once the trial expires
 * without payment. The Super Admin marks a customer paid manually.
 */

if (! function_exists('sub_trial_days')) {
    /** Configured free-trial length in days (Super Admin setting; default 30). */
    function sub_trial_days(): int
    {
        static $days = null;
        if ($days === null) {
            $v    = (int) (new SettingModel())->get('subscription_trial_days', 0, 30);
            $days = $v > 0 ? $v : 30;
        }
        return $days;
    }
}

if (! function_exists('sub_customer_id')) {
    /** Owning customer (account) id for the active company, or null (e.g. Super Admin). */
    function sub_customer_id(): ?int
    {
        if (function_exists('is_super_admin_account') && is_super_admin_account()) {
            return null;
        }
        $c = function_exists('current_company') ? current_company() : null;
        if ($c && ! empty($c['owner_id'])) {
            return (int) $c['owner_id'];
        }
        // Fallback: the signed-in user is themselves the customer/owner.
        return function_exists('user_id') && user_id() ? (int) user_id() : null;
    }
}

if (! function_exists('sub_state')) {
    /**
     * Resolve the current context's subscription state (cached per request).
     *
     * @return array{pro:bool, reason:string, status:?string, payment_status:?string, expires_at:?string, plan:?string}
     */
    function sub_state(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $base = ['pro' => true, 'reason' => 'default', 'status' => null, 'payment_status' => null, 'expires_at' => null, 'plan' => null];

        // Super Admin is never gated (they don't use firm features anyway).
        if (function_exists('is_super_admin_account') && is_super_admin_account()) {
            return $cache = ['reason' => 'superadmin'] + $base;
        }

        $cid = sub_customer_id();
        if (! $cid) {
            // No customer context (e.g. onboarding, guest) — don't block.
            return $cache = $base;
        }

        $subs = new SubscriptionModel();
        $sub  = $subs->forCustomer($cid);

        // Lazily start a trial so a customer without a record still gets their free
        // window rather than being locked out on day one.
        if (! $sub) {
            $subs->ensureFor($cid, null);
            $sub = $subs->forCustomer($cid);
        }
        if (! $sub) {
            return $cache = ['pro' => false, 'reason' => 'none'] + $base;
        }

        $reason = _sub_reason_of($sub);
        $pro    = in_array($reason, ['paid', 'trial'], true);

        return $cache = [
            'pro'            => $pro,
            'reason'         => $reason,
            'status'         => $sub['status'] ?? null,
            'payment_status' => $sub['payment_status'] ?? null,
            'expires_at'     => $sub['expires_at'] ?? null,
            'plan'           => $sub['plan_name'] ?? null,
        ];
    }
}

if (! function_exists('sub_is_pro')) {
    /**
     * Baseline access flag. Under the package model the former "pro" features
     * (Rokadh Parcha PDF/print, reports, attachments, statement download,
     * opening balance) belong to EVERY package — including Basic and the
     * expired/Basic floor — so this is always true. Kept for the transaction
     * views that still reference it. Per-feature gating flows through
     * hasFeature() instead.
     */
    function sub_is_pro(): bool
    {
        return true;
    }
}

if (! function_exists('sub_is_free')) {
    /** Retained for callers; always false now (see sub_is_pro()). */
    function sub_is_free(): bool
    {
        return false;
    }
}

/* =====================================================================
 * Package feature gating — everything below reads the plan flags straight
 * from the database (subscription_plans.feat_*), never hardcoded values.
 * ===================================================================== */

if (! function_exists('feature_catalog')) {
    /**
     * The seven gated features and their display labels. A feature NOT listed
     * here is a baseline feature available to every package.
     *
     * @return array<string,string>
     */
    function feature_catalog(): array
    {
        return [
            'calculator'       => 'Calculator',
            'password_manager' => 'Password Manager',
            'calendar'         => 'Calendar',
            'reminder'         => 'Reminder',
            'trash'            => 'Trash',
            'notes'            => 'Notes',
            'inventory'        => 'Inventory',
        ];
    }
}

if (! function_exists('feature_label')) {
    /** Human label for a feature key. */
    function feature_label(string $feature): string
    {
        return feature_catalog()[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
    }
}

if (! function_exists('effective_plan')) {
    /**
     * The plan row whose feature flags currently apply to this context:
     *   - Super Admin           → synthetic all-access.
     *   - Paid & not expired    → the customer's own plan.
     *   - Trial & not expired   → Premium (full access during the free trial).
     *   - Expired / unpaid /
     *     no subscription        → Basic floor (spec: expiry downgrades to Basic).
     * Cached per request. Reads the DB so nothing is hardcoded.
     *
     * @return array<string,mixed>
     */
    function effective_plan(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        if (function_exists('is_super_admin_account') && is_super_admin_account()) {
            return $cache = _sub_full_plan();
        }

        $plans  = new \App\Models\SubscriptionPlanModel();
        $reason = sub_state()['reason'] ?? 'none';

        if ($reason === 'paid') {
            $cid = sub_customer_id();
            $sub = $cid ? (new SubscriptionModel())->forCustomer($cid) : null;
            $row = ($sub && ! empty($sub['plan_id'])) ? $plans->find((int) $sub['plan_id']) : null;
            if ($row) {
                return $cache = $row;
            }
        } elseif ($reason === 'trial') {
            $row = $plans->where('code', 'yearly_599')->first(); // full access on trial
            if ($row) {
                return $cache = $row;
            }
        }

        // Floor: expired / unpaid / none / free → Basic package restrictions.
        $basic = $plans->where('code', 'yearly_299')->first();
        return $cache = $basic ?: _sub_full_plan();
    }
}

if (! function_exists('_sub_full_plan')) {
    /** Synthetic all-features-on plan (Super Admin / safety fallback). */
    function _sub_full_plan(): array
    {
        $row = ['id' => 0, 'name' => 'Full', 'code' => 'full', 'max_firms' => null];
        foreach (array_keys(feature_catalog()) as $f) {
            $row['feat_' . $f] = 1;
        }
        return $row;
    }
}

if (! function_exists('hasFeature')) {
    /**
     * Does the active package include $feature? Baseline features (not in the
     * catalog) are always available. This is the single gate every module,
     * controller, filter and view must use.
     */
    function hasFeature(string $feature): bool
    {
        if (function_exists('is_super_admin_account') && is_super_admin_account()) {
            return true;
        }
        if (! array_key_exists($feature, feature_catalog())) {
            return true; // baseline feature, every package has it
        }
        $plan = effective_plan();
        return (int) ($plan['feat_' . $feature] ?? 0) === 1;
    }
}

if (! function_exists('checkPackagePermission')) {
    /** Alias of hasFeature() (spec name). */
    function checkPackagePermission(string $feature): bool
    {
        return hasFeature($feature);
    }
}

if (! function_exists('required_plan_for')) {
    /**
     * Cheapest active package that unlocks $feature, read from the DB — used for
     * the "available in the <Plan> plan" upsell. Null if none / baseline.
     *
     * @return array<string,mixed>|null
     */
    function required_plan_for(string $feature): ?array
    {
        static $cache = [];
        if (array_key_exists($feature, $cache)) {
            return $cache[$feature];
        }
        if (! array_key_exists($feature, feature_catalog())) {
            return $cache[$feature] = null;
        }
        return $cache[$feature] = (new \App\Models\SubscriptionPlanModel())
            ->where('status', 1)->where('feat_' . $feature, 1)
            ->orderBy('price', 'ASC')->first();
    }
}

if (! function_exists('feature_lock_message')) {
    /** The upgrade sentence shown when a locked feature is accessed. */
    function feature_lock_message(string $feature): string
    {
        $plan = required_plan_for($feature);
        $name = $plan['name'] ?? 'a higher';
        return 'This feature is not included in your current plan. Please buy the ' . $name . ' plan first to unlock it.';
    }
}

if (! function_exists('plan_firm_limit')) {
    /** Active package's firm cap, or null for unlimited. */
    function plan_firm_limit(): ?int
    {
        if (function_exists('is_super_admin_account') && is_super_admin_account()) {
            return null;
        }
        $lim = effective_plan()['max_firms'] ?? null;
        return ($lim === null || $lim === '') ? null : (int) $lim;
    }
}

if (! function_exists('firm_count_for_customer')) {
    /** How many (non-trashed) firms the owning customer currently has. */
    function firm_count_for_customer(): int
    {
        $cid = sub_customer_id();
        if (! $cid) {
            return 0;
        }
        return (new \App\Models\CompanyModel())->where('owner_id', $cid)->countAllResults();
    }
}

if (! function_exists('firm_limit_reached')) {
    /** True when the customer is at/over their package firm cap. */
    function firm_limit_reached(): bool
    {
        $lim = plan_firm_limit();
        if ($lim === null) {
            return false; // unlimited
        }
        return firm_count_for_customer() >= $lim;
    }
}

if (! function_exists('_sub_reason_of')) {
    /**
     * Classify a subscription row into an access reason. Single source of truth
     * shared by the session state (sub_state) and the token/API path
     * (customer_effective_plan) so the rule never drifts.
     *
     * @return 'paid'|'expired'|'trial'|'trial_expired'|'free'
     */
    function _sub_reason_of(array $sub): string
    {
        $exp        = ! empty($sub['expires_at']) ? strtotime((string) $sub['expires_at']) : null;
        $notExpired = $exp === null || $exp >= time();
        $pay        = (string) ($sub['payment_status'] ?? '');
        $status     = (string) ($sub['status'] ?? '');

        // 'unpaid' is an explicit cut-off — restricted immediately regardless of date.
        if ($pay === 'unpaid') {
            return 'free';
        }

        // Once the window lapses, everything drops to the Basic floor. A lapsed
        // trial and a lapsed paid plan are distinguished only for messaging.
        if (! $notExpired) {
            return $status === 'trial' ? 'trial_expired' : 'expired';
        }

        // Within the active window, the STATUS decides access (not the payment
        // label): an activated plan grants its own features even if the payment
        // row is still flagged 'trial'; a trial grants full access.
        if ($status === 'active' || $pay === 'paid') {
            return 'paid';
        }
        if ($status === 'trial') {
            return 'trial';
        }
        return 'free';
    }
}

if (! function_exists('customer_effective_plan')) {
    /**
     * Session-independent effective plan for a specific customer id — used by the
     * bearer-token API where there is no web session. Same downgrade rules as
     * effective_plan(): paid→own plan, trial→Premium, otherwise Basic floor.
     *
     * @return array<string,mixed>
     */
    function customer_effective_plan(int $customerId): array
    {
        $subs = new SubscriptionModel();
        $sub  = $subs->forCustomer($customerId);
        if (! $sub) {
            $subs->ensureFor($customerId, null);
            $sub = $subs->forCustomer($customerId);
        }

        $plans  = new \App\Models\SubscriptionPlanModel();
        $reason = $sub ? _sub_reason_of($sub) : 'none';

        if ($reason === 'paid' && ! empty($sub['plan_id'])) {
            $row = $plans->find((int) $sub['plan_id']);
            if ($row) {
                return $row;
            }
        } elseif ($reason === 'trial') {
            $row = $plans->where('code', 'yearly_599')->first();
            if ($row) {
                return $row;
            }
        }
        return $plans->where('code', 'yearly_299')->first() ?: _sub_full_plan();
    }
}

if (! function_exists('sub_sync_expiry')) {
    /**
     * Detect a just-lapsed trial / paid window for the active customer and notify
     * them ONCE that premium features are now locked. Idempotent: the first call
     * after expiry stamps expiry_notified_at, so later page loads do nothing until
     * the customer renews (which clears the stamp). Never throws — a notification
     * failure must not break page rendering.
     */
    function sub_sync_expiry(): void
    {
        if (function_exists('is_super_admin_account') && is_super_admin_account()) {
            return;
        }
        $cid = sub_customer_id();
        if (! $cid) {
            return;
        }

        $subs = new SubscriptionModel();
        $sub  = $subs->forCustomer($cid);
        if (! $sub || ! empty($sub['expiry_notified_at'])) {
            return; // no subscription, or already notified for this lapse
        }

        $reason = _sub_reason_of($sub);
        if (! in_array($reason, ['expired', 'trial_expired'], true)) {
            return; // still within an active window
        }

        // Stamp first so a concurrent request can't double-notify.
        $subs->update($sub['id'], ['expiry_notified_at' => date('Y-m-d H:i:s')]);

        $wasTrial = $reason === 'trial_expired';
        $title    = $wasTrial ? 'Your free trial has ended' : 'Your subscription has expired';
        $message  = $wasTrial
            ? 'Your free trial is over, so premium features are now locked. Your data is safe — subscribe to a plan to unlock them again.'
            : 'Your subscription has expired, so premium features are now locked. Your data is safe — renew any time to restore full access.';

        try {
            service('notifier')->user((int) $cid, $title, $message, [
                'type'       => 'warning',
                'module'     => null,
                'priority'   => 'high',
                'action_url' => site_url('subscription'),
                'created_by' => (int) $cid,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Trial-expiry notification failed: ' . $e->getMessage());
        }

        if (function_exists('activity_log')) {
            activity_log('Subscription', 'Edit',
                ($wasTrial ? 'Free trial ended' : 'Subscription expired')
                . " for customer #{$cid} — access dropped to Basic restrictions");
        }
    }
}

if (! function_exists('customer_has_feature')) {
    /** hasFeature() for a specific customer id (API / background context). */
    function customer_has_feature(int $customerId, string $feature): bool
    {
        if (! array_key_exists($feature, feature_catalog())) {
            return true; // baseline feature
        }
        return (int) (customer_effective_plan($customerId)['feat_' . $feature] ?? 0) === 1;
    }
}
