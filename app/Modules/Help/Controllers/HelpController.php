<?php

namespace Modules\Help\Controllers;

use App\Controllers\BaseController;

/**
 * Help & Support — a single self-service page: how to reach support (email +
 * WhatsApp), quick links and a searchable FAQ. Available to every signed-in
 * user; contact details come from the global app settings so they can be
 * changed without touching code.
 */
class HelpController extends BaseController
{
    protected $helpers = ['url', 'auth', 'menu', 'ui', 'settings', 'company'];

    public function index()
    {
        $appName  = brand_name();
        $email    = brand_support_email();
        $waNumber = preg_replace('/\D+/', '', (string) setting('support_whatsapp', '916393505070'));
        $waShown  = (string) setting('support_whatsapp_display', '+91 63935 05070');
        $waMsg    = rawurlencode('Hello ' . $appName . ' support, I need help with my account.');

        return $this->render('index', [
            'title'      => 'Help & Support',
            'breadcrumb' => [['label' => 'Help & Support']],
            'appName'    => $appName,
            'appUrl'     => brand_domain(),
            'email'      => $email,
            'waNumber'   => $waNumber,
            'waShown'    => $waShown,
            'waLink'     => 'https://wa.me/' . $waNumber . '?text=' . $waMsg,
            'faqs'       => $this->faqs(),
        ]);
    }

    /** The customer's single ongoing support conversation — web chat view. */
    public function support()
    {
        $user = current_user();
        $svc  = new \App\Services\SupportConversation();
        $conv = $svc->getFor($user);

        if ($conv) {
            \Config\Database::connect()->table('inquiries')->where('id', (int) $conv['id'])->update(['customer_unread' => 0]);
        }

        return $this->render('support', [
            'title'      => 'Support',
            'breadcrumb' => [['label' => 'Help & Support', 'url' => site_url('help')], ['label' => 'Support Chat']],
            'messages'   => $conv ? $svc->messages($conv) : [],
            'open'       => ! $conv || $conv['status'] !== 'closed',
        ]);
    }

    /** Send a message into the single conversation (creates it on first use). */
    public function supportSend()
    {
        $user    = current_user();
        $message = trim((string) $this->request->getPost('message'));
        if ($message === '') {
            return redirect()->back()->with('error', 'Please type a message.');
        }
        (new \App\Services\SupportConversation())->appendCustomer($user, $message, 'support', [
            'ip' => $this->request->getIPAddress(),
            'ua' => (string) $this->request->getUserAgent()->getAgentString(),
        ]);
        activity_log('Help', 'Add', 'Sent a support message');

        return redirect()->to(site_url('help/support'))->with('success', 'Message sent to support.');
    }

    /** @return list<array{q:string,a:string}> */
    private function faqs(): array
    {
        return [
            ['q' => 'How do I switch between my companies?',
             'a' => 'Click the building icon in the top bar (or the company name chip) and pick a company from the list. You can also add a new company from there or from the Company Profile page. Your books, ledger, reports and dashboard all update to the selected company instantly.'],
            ['q' => 'What is the Hisaab Kitaab Vahi (Jama / Naam) ledger?',
             'a' => 'It is your cash & party ledger. “Jama” records money received (in) and “Naam” records money paid (out). Each company keeps its own separate ledger, running balance and Rokad Parcha reports. Open it from the sidebar → Hisaab Kitaab Vahi.'],
            ['q' => 'How is my data kept separate for each company?',
             'a' => 'Every entry — transactions, ledgers, notes, reminders and passwords — is tagged to the active company, so one company’s data never appears under another. Switching companies always shows only that company’s figures.'],
            ['q' => 'How secure is the Password Manager?',
             'a' => 'Passwords are stored encrypted (AES) — never in plain text — and are only decrypted when you explicitly click “reveal”. Access is limited to authorised users of your company via module permissions.'],
            ['q' => 'What does the “Shri Rokad Nagad” opening balance mean?',
             'a' => 'It is the opening cash-in-hand your books start a financial year with. Set it per company from the Rokad / reports screen; the dashboard cash-in-hand and reports then carry it forward automatically.'],
            ['q' => 'How do I set my opening balance and financial year?',
             'a' => 'Open Company Profile to set the financial year and books-beginning date. The opening cash (Shri Rokad Nagad) is set per financial year from the ledger reports screen.'],
            ['q' => 'The dashboard shows zero — why?',
             'a' => 'The dashboard reflects the active company’s real entries. If it shows zero, that company has no ledger entries in the selected period yet. Add a transaction, or change the period filter at the top of the dashboard.'],
            ['q' => 'I still need help. How do I contact support?',
             'a' => 'Message us on WhatsApp for the fastest response, or email us. Both are listed at the top of this page. Please include your registered email and a short description of the issue.'],
        ];
    }
}
