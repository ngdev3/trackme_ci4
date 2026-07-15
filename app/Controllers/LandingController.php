<?php

namespace App\Controllers;

use App\Models\InquiryModel;
use App\Models\SubscriptionPlanModel;

/**
 * Public marketing landing page for HissabKitaab. Shown to guests at the site
 * root; signed-in users are sent straight to their dashboard. Pricing is pulled
 * live from the active subscription plans so the page never drifts from what the
 * app actually sells.
 */
class LandingController extends BaseController
{
    protected $helpers = ['url', 'settings', 'subscription', 'auth'];

    /** Allowed inquiry subjects — kept in sync with the form <select>. */
    private const SUBJECTS = ['general', 'pricing', 'demo', 'support', 'partnership'];

    public function index()
    {
        // Already signed in → go to the app.
        if (function_exists('user_id') && user_id()) {
            return redirect()->to(site_url('dashboard'));
        }

        helper('subscription');

        $plans = (new SubscriptionPlanModel())
            ->where('status', 1)->where('price >', 0)
            ->orderBy('price', 'ASC')->findAll();

        // Feature bullets for each plan card, derived from the enforced feat_* flags
        // (baseline features first, then whatever the package unlocks).
        $catalog      = function_exists('feature_catalog') ? feature_catalog() : [];
        $planFeatures = [];
        foreach ($plans as $p) {
            $limitFirms = ($p['max_firms'] === null || $p['max_firms'] === '' || (int) $p['max_firms'] === 0)
                ? 'Unlimited firms'
                : ((int) $p['max_firms']) . ' firm' . ((int) $p['max_firms'] === 1 ? '' : 's');
            $limitUsers = ($p['max_users'] === null || $p['max_users'] === '' || (int) $p['max_users'] === 0)
                ? 'Unlimited users'
                : number_format((int) $p['max_users']) . ' users';

            $list = [$limitFirms, $limitUsers, 'Rokad cash book', 'Reports & PDF export'];
            foreach ($catalog as $key => $label) {
                if ((int) ($p['feat_' . $key] ?? 0) === 1) {
                    $list[] = $label;
                }
            }
            $planFeatures[(int) $p['id']] = $list;
        }

        return view('landing', [
            'appName'      => (string) setting('app_name', 'HissabKitaab'),
            'tagline'      => (string) setting('app_tagline', 'plan smarter, grow faster'),
            'supportWa'    => preg_replace('/\D+/', '', (string) setting('support_whatsapp', '916393505070')),
            'supportWaShown' => (string) setting('support_whatsapp_display', '+91 63935 05070'),
            'supportEmail' => (string) setting('support_email', 'admin@HissabKitaab.com'),
            'plans'        => $plans,
            'planFeatures' => $planFeatures,
            'trialDays'    => function_exists('sub_trial_days') ? sub_trial_days() : 30,
        ]);
    }

    /** Public Terms & Conditions page (linked from the footer). */
    public function terms()
    {
        $app = (string) setting('app_name', 'HissabKitaab');
        return view('legal', [
            'appName'     => $app,
            'legalTitle'  => 'Terms & Conditions',
            'legalUpdated'=> '14 Jul 2026',
            'legalIntro'  => 'Please read these terms carefully before using ' . $app . '. By creating an account or using the service, you agree to them.',
            'legalSections' => [
                ['h' => 'Acceptance of terms', 'p' => 'By registering for, accessing or using ' . $app . ' you agree to be bound by these Terms & Conditions and our Privacy Policy. If you do not agree, please do not use the service.'],
                ['h' => 'Your account', 'p' => ['You are responsible for keeping your login credentials secure and for all activity that happens under your account.', 'You must provide accurate information and are responsible for the business data you record in the app.']],
                ['h' => 'Acceptable use', 'p' => 'You agree to use ' . $app . ' only for lawful business record-keeping. You must not misuse the service, attempt to breach its security, or use it to store unlawful content.'],
                ['h' => 'Subscriptions & billing', 'p' => ['Paid plans are billed in advance for the chosen billing cycle. Access to premium features depends on an active subscription.', 'When a free trial or paid period ends without renewal, premium features are locked while your data is preserved. See our Refund & Cancellation policy for more.']],
                ['h' => 'Your data', 'p' => 'You retain ownership of the business data you enter. We process it only to provide the service, as described in the Privacy Policy. You can export your records at any time.'],
                ['h' => 'Service availability', 'p' => 'We work hard to keep the service available and reliable, but it is provided "as is" without warranties. We are not liable for indirect or consequential losses arising from use of the service.'],
                ['h' => 'Changes to these terms', 'p' => 'We may update these terms from time to time. Continued use of the service after changes take effect means you accept the updated terms.'],
                ['h' => 'Contact', 'p' => 'For any questions about these terms, contact us at support@hissabkitaab.com.'],
            ],
        ]);
    }

    /** Public Refund & Cancellation policy (linked from the footer). */
    public function refunds()
    {
        $app = (string) setting('app_name', 'HissabKitaab');
        return view('legal', [
            'appName'     => $app,
            'legalTitle'  => 'Refund & Cancellation Policy',
            'legalUpdated'=> '14 Jul 2026',
            'legalIntro'  => 'How subscription payments, cancellations and refunds work on ' . $app . '.',
            'legalSections' => [
                ['h' => 'Free trial', 'p' => 'Every new account starts with a free trial that gives full access to premium features. No payment is taken during the trial, so nothing is charged if you choose not to subscribe.'],
                ['h' => 'Subscription payments', 'p' => 'Paid plans are charged in advance through our payment partner, Cashfree. Your plan activates instantly on successful payment and a tax receipt is issued for every charge.'],
                ['h' => 'Cancellation', 'p' => ['You can stop using a paid plan at any time. On cancellation, premium features remain available until the end of the period you have already paid for, after which access drops to the free tier.', 'Your business data is always preserved and can be exported.']],
                ['h' => 'Refunds', 'p' => ['Subscription fees are generally non-refundable once a billing period has started, because the service is available to you immediately.', 'If you were charged in error or a payment was deducted without your plan activating, contact us within 7 days and we will investigate and refund eligible amounts.']],
                ['h' => 'Failed or duplicate payments', 'p' => 'If a payment fails but an amount was deducted, it is normally reversed automatically by the bank within 3-5 business days. Duplicate charges for the same order are refunded on verification.'],
                ['h' => 'How to request a refund', 'p' => 'Email support@hissabkitaab.com with your registered email, order id and a short description. We aim to respond within 2-3 business days.'],
            ],
        ]);
    }

    /** Public About Us page (linked from the footer). */
    public function about()
    {
        $app = (string) setting('app_name', 'HissabKitaab');
        return view('legal', [
            'appName'     => $app,
            'legalTitle'  => 'About Us',
            'legalUpdated'=> '14 Jul 2026',
            'legalIntro'  => 'The story behind ' . $app . ' — and why we built it.',
            'legalSections' => [
                ['h' => 'Our mission', 'p' => 'We want every small business in India to know exactly where their money is — without accountants, complicated software or messy notebooks. ' . $app . ' turns the daily habit of writing a cash book into clean, live financial clarity.'],
                ['h' => 'What we do', 'p' => $app . ' is an all-in-one business platform for recording daily Jama (in) and Naam (out) entries, managing inventory, generating reports and staying on top of payments with reminders — all from your phone or computer.'],
                ['h' => 'Who it is for', 'p' => 'Traders, retailers, wholesalers, distributors, shop owners and service firms who run their business day to day and need their books to simply stay correct and up to date.'],
                ['h' => 'Why firms choose us', 'p' => ['Dead simple: if you can write in a notebook, you can use ' . $app . '.', 'Everything in one place: cash book, inventory, reports, reminders and a secure vault.', 'Made for India: rupee-first, Hindi-friendly and built around how local businesses actually work.']],
                ['h' => 'Built in India', 'p' => $app . ' is operated by CR Industries and proudly built in India for Indian businesses.'],
            ],
        ]);
    }

    /** Public Careers page (linked from the footer). */
    public function careers()
    {
        $app = (string) setting('app_name', 'HissabKitaab');
        return view('legal', [
            'appName'     => $app,
            'legalTitle'  => 'Careers',
            'legalUpdated'=> '14 Jul 2026',
            'legalIntro'  => 'Help us build the simplest way for India to run its business books.',
            'legalSections' => [
                ['h' => 'Working at ' . $app, 'p' => 'We are a small, focused team that cares deeply about simplicity, speed and trust. We build software that real shop owners can pick up in minutes and rely on every single day.'],
                ['h' => 'What you would work on', 'p' => 'Clean product experiences, rock-solid billing and data, and features that remove friction for millions of everyday business owners.'],
                ['h' => 'Open roles', 'p' => ['We hire across product, engineering, design and customer support.', 'Even if you do not see a specific role listed, we would still love to hear from exceptional people.']],
                ['h' => 'How to apply', 'p' => 'Email your resume and a short note about what you would like to work on to support@hissabkitaab.com with the subject line "Careers".'],
            ],
        ]);
    }

    /** Public Contact & Support page (linked from the footer). */
    public function contact()
    {
        $app     = (string) setting('app_name', 'HissabKitaab');
        $waShown = (string) setting('support_whatsapp_display', '+91 63935 05070');
        return view('legal', [
            'appName'     => $app,
            'legalTitle'  => 'Contact & Support',
            'legalUpdated'=> '14 Jul 2026',
            'legalIntro'  => 'We would love to hear from you. Here is how to reach the ' . $app . ' team.',
            'legalSections' => [
                ['h' => 'Chat with us on WhatsApp', 'p' => 'The fastest way to reach us is on WhatsApp at ' . $waShown . '. Send us your question and we will get back to you quickly.'],
                ['h' => 'Email support', 'p' => 'For detailed queries, email support@hissabkitaab.com. Please include your registered email so we can help you faster.'],
                ['h' => 'Support hours', 'p' => 'Our team is available Monday to Saturday, 10:00 AM to 7:00 PM IST. We aim to respond to every message within one business day.'],
                ['h' => 'Billing & refunds', 'p' => 'For payment, subscription or refund questions, email us with your order id and registered email. See our Refund & Cancellation policy for full details.'],
                ['h' => 'Report a problem or grievance', 'p' => 'Facing an issue or have a complaint? Write to support@hissabkitaab.com with the subject "Grievance" and we will prioritise it.'],
            ],
        ]);
    }

    /**
     * Handle a public inquiry-form submission (AJAX, JSON). Fully server-validated
     * and CSRF-protected (global filter). A spam honeypot silently absorbs bots.
     * On success the message is stored and the Super Admin is notified.
     */
    public function submitInquiry()
    {
        $response = service('response');

        // Honeypot: a real user never fills the hidden "website" field. Pretend all
        // is well so bots don't learn anything, but store nothing.
        if (trim((string) $this->request->getPost('website')) !== '') {
            return $response->setJSON(['ok' => true, 'message' => 'Thank you! We will get back to you soon.']);
        }

        $rules = [
            'name'    => 'required|string|min_length[2]|max_length[120]',
            'email'   => 'required|valid_email|max_length[190]',
            'phone'   => 'permit_empty|regex_match[/^[0-9+()\-\s]{7,20}$/]',
            'company' => 'permit_empty|max_length[150]',
            'subject' => 'permit_empty|in_list[' . implode(',', self::SUBJECTS) . ']',
            'message' => 'required|string|min_length[10]|max_length[2000]',
            'consent' => 'required',
        ];
        $messages = [
            'name'    => ['required' => 'Please enter your name.', 'min_length' => 'Your name looks too short.'],
            'email'   => ['required' => 'Please enter your email.', 'valid_email' => 'Please enter a valid email address.'],
            'phone'   => ['regex_match' => 'Please enter a valid phone number.'],
            'subject' => ['in_list' => 'Please choose a valid subject.'],
            'message' => ['required' => 'Please tell us how we can help.', 'min_length' => 'Please add a little more detail (at least 10 characters).'],
            'consent' => ['required' => 'Please agree to be contacted.'],
        ];

        if (! $this->validate($rules, $messages)) {
            return $response->setStatusCode(422)->setJSON([
                'ok'     => false,
                'message' => 'Please fix the highlighted fields and try again.',
                'errors' => $this->validator->getErrors(),
                'csrf'   => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $subject = (string) $this->request->getPost('subject');
        if (! in_array($subject, self::SUBJECTS, true)) {
            $subject = 'general';
        }

        $data = [
            'name'       => trim((string) $this->request->getPost('name')),
            'email'      => trim((string) $this->request->getPost('email')),
            'phone'      => trim((string) $this->request->getPost('phone')) ?: null,
            'company'    => trim((string) $this->request->getPost('company')) ?: null,
            'subject'    => $subject,
            'message'    => trim((string) $this->request->getPost('message')),
            'status'     => 'new',
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255),
        ];

        try {
            $id = (new InquiryModel())->insert($data);
        } catch (\Throwable $e) {
            log_message('error', 'Inquiry insert failed: ' . $e->getMessage());
            return $response->setStatusCode(500)->setJSON([
                'ok' => false, 'message' => 'Something went wrong on our end. Please try again or email us directly.',
                'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        // Notify the Super Admin (in-app bell) so inquiries don't go unseen.
        try {
            service('notifier')->broadcast(
                'New inquiry from ' . $data['name'],
                ucfirst($subject) . ' — ' . $data['email'] . ($data['phone'] ? ' · ' . $data['phone'] : ''),
                [
                    'type'       => 'user_activity',
                    'priority'   => 'high',
                    'module'     => null,
                    'action_url' => site_url('admin/inquiries'),
                ]
            );
        } catch (\Throwable $e) {
            log_message('error', 'Inquiry notify failed: ' . $e->getMessage());
        }

        return $response->setJSON([
            'ok'      => true,
            'message' => 'Thank you, ' . esc($data['name']) . '! Your message has been received — our team will get back to you within one business day.',
            'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
    }
}
