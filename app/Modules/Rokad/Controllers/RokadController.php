<?php

namespace Modules\Rokad\Controllers;

use App\Controllers\BaseController;
use App\Models\CompanySettingModel;
use App\Models\RokadEntryModel;

/**
 * Rokad Parcha (Cash Book) — a simple daily cash register of Jama (money in)
 * and Naam (money out). Opening / running / closing balances are all computed
 * from the entries, so edits and deletes recalculate automatically. Firm-scoped
 * via company_id(); the opening balance and carry-forward markers live in
 * company_settings (scopes 'rokad' and 'rokad_carry').
 */
class RokadController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company'];

    protected RokadEntryModel $entries;
    protected CompanySettingModel $settings;

    public function __construct()
    {
        $this->entries  = new RokadEntryModel();
        $this->settings = new CompanySettingModel();
    }

    private function cid(): int
    {
        return (int) company_id();
    }

    private function canEdit(): bool
    {
        return firm_can('rokad', 'edit');
    }

    // ---- opening-balance base (set once when the book starts) -------------
    private function baseOpening(): array
    {
        $map = $this->settings->scopeMap($this->cid(), 'rokad');
        return [(float) ($map['opening_balance'] ?? 0), $map['opening_date'] ?? null];
    }

    /** Opening balance carried into a date = base opening + net of prior days. */
    private function openingFor(string $date): float
    {
        [$baseAmt, $baseDate] = $this->baseOpening();
        return round($baseAmt + $this->entries->netBefore($this->cid(), $date, $baseDate), 2);
    }

    // ---------------------------------------------------------------
    // Day view (default) or search results
    // ---------------------------------------------------------------
    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));
        if ($q !== '') {
            return $this->render('search', [
                'title'      => 'Search Rokad',
                'breadcrumb' => [['label' => 'Rokad Parcha', 'url' => site_url('rokad')], ['label' => 'Search']],
                'q'          => $q,
                'rows'       => $this->entries->search($this->cid(), $q),
            ]);
        }

        $date = (string) ($this->request->getGet('date') ?: date('Y-m-d'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $opening  = $this->openingFor($date);
        $entries  = $this->entries->dayEntries($this->cid(), $date);

        // Running balance per row.
        $running = $opening;
        foreach ($entries as &$e) {
            $running += (float) $e['jama'] - (float) $e['naam'];
            $e['balance'] = round($running, 2);
        }
        unset($e);

        [$totJama, $totNaam] = $this->entries->dayTotals($this->cid(), $date);
        $closing = round($opening + $totJama - $totNaam, 2);

        $carry = $this->settings->scopeMap($this->cid(), 'rokad_carry');

        return $this->render('index', [
            'title'      => 'Rokad Parcha',
            'breadcrumb' => [['label' => 'Rokad Parcha']],
            'date'       => $date,
            'prevDate'   => date('Y-m-d', strtotime($date . ' -1 day')),
            'nextDate'   => date('Y-m-d', strtotime($date . ' +1 day')),
            'opening'    => $opening,
            'entries'    => $entries,
            'totalJama'  => $totJama,
            'totalNaam'  => $totNaam,
            'closing'    => $closing,
            'carried'    => isset($carry[$date]),
            'baseOpening'=> $this->baseOpening(),
            'canEdit'    => $this->canEdit(),
            'editRow'    => $this->request->getGet('edit') ? $this->entries->findForCompany((int) $this->request->getGet('edit'), $this->cid()) : null,
        ]);
    }

    // ---------------------------------------------------------------
    // Add / edit / delete entries
    // ---------------------------------------------------------------
    public function store()
    {
        return $this->persist(null);
    }

    public function update($id = null)
    {
        return $this->persist((int) $id);
    }

    private function persist(?int $id)
    {
        $date = (string) $this->request->getPost('entry_date') ?: date('Y-m-d');
        $back = site_url('rokad?date=' . $date);

        $jama = round((float) $this->request->getPost('jama'), 2);
        $naam = round((float) $this->request->getPost('naam'), 2);

        if (trim((string) $this->request->getPost('particular')) === '') {
            return redirect()->to($back)->with('error', 'Please enter a particular / description.');
        }
        // Exactly one of Jama or Naam, and it must be positive.
        if (($jama > 0) === ($naam > 0)) {
            return redirect()->to($back)->with('error', 'Enter an amount in either Jama (in) or Naam (out) — not both, not neither.');
        }

        $data = [
            'entry_date' => date('Y-m-d', strtotime($date)),
            'particular' => trim((string) $this->request->getPost('particular')),
            'jama'       => max(0, $jama),
            'naam'       => max(0, $naam),
            'remarks'    => trim((string) $this->request->getPost('remarks')) ?: null,
        ];

        if ($id) {
            if (! $this->entries->findForCompany($id, $this->cid())) {
                return redirect()->to($back)->with('error', 'Entry not found.');
            }
            $this->entries->update($id, $data);
            activity_log('Rokad', 'Edit', "Cash entry #{$id} updated");
            $msg = 'Entry updated.';
        } else {
            $data['company_id'] = $this->cid();
            $data['created_by'] = (int) user_id();
            $this->entries->insert($data);
            activity_log('Rokad', 'Add', 'Cash entry added');
            $msg = ($jama > 0 ? 'Jama' : 'Naam') . ' entry added.';
        }

        return redirect()->to(site_url('rokad?date=' . $data['entry_date']))->with('success', $msg);
    }

    public function delete($id = null)
    {
        $id  = (int) $id;
        $row = $this->entries->findForCompany($id, $this->cid());
        if (! $row) {
            return redirect()->to(site_url('rokad'))->with('error', 'Entry not found.');
        }
        $this->entries->delete($id);
        activity_log('Rokad', 'Delete', "Cash entry #{$id} deleted");
        return redirect()->to(site_url('rokad?date=' . $row['entry_date']))->with('success', 'Entry deleted.');
    }

    // ---------------------------------------------------------------
    // Opening balance + carry forward
    // ---------------------------------------------------------------
    public function setOpening()
    {
        $amount = round((float) $this->request->getPost('opening_balance'), 2);
        $date   = (string) $this->request->getPost('opening_date') ?: date('Y-m-d');
        $this->settings->put($this->cid(), 'rokad', 'opening_balance', $amount);
        $this->settings->put($this->cid(), 'rokad', 'opening_date', date('Y-m-d', strtotime($date)));
        activity_log('Rokad', 'Edit', 'Opening balance set');
        return redirect()->to(site_url('rokad?date=' . date('Y-m-d', strtotime($date))))->with('success', 'Opening balance saved.');
    }

    /**
     * Carry a day's closing balance forward to the next day. Idempotent per
     * date: the closing already flows into the next day automatically, so this
     * just records/acknowledges the hand-off and blocks doing it twice.
     */
    public function carryForward()
    {
        $date = (string) $this->request->getPost('date') ?: date('Y-m-d');
        $date = date('Y-m-d', strtotime($date));
        $next = date('Y-m-d', strtotime($date . ' +1 day'));

        $carry = $this->settings->scopeMap($this->cid(), 'rokad_carry');
        if (isset($carry[$date])) {
            return redirect()->to(site_url('rokad?date=' . $next))->with('info', 'This day was already carried forward.');
        }

        [$j, $n] = $this->entries->dayTotals($this->cid(), $date);
        $closing = round($this->openingFor($date) + $j - $n, 2); // opening + jama − naam

        $this->settings->put($this->cid(), 'rokad_carry', $date, (string) $closing);
        activity_log('Rokad', 'Edit', "Carried forward {$date} closing {$closing}");
        return redirect()->to(site_url('rokad?date=' . $next))->with('success', 'Closing balance ' . number_format($closing, 2) . ' carried to ' . date('d-m-Y', strtotime($next)) . '.');
    }

    // ---------------------------------------------------------------
    // Print
    // ---------------------------------------------------------------
    public function printDay()
    {
        $date = (string) ($this->request->getGet('date') ?: date('Y-m-d'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $opening = $this->openingFor($date);
        $entries = $this->entries->dayEntries($this->cid(), $date);
        $running = $opening;
        foreach ($entries as &$e) {
            $running += (float) $e['jama'] - (float) $e['naam'];
            $e['balance'] = round($running, 2);
        }
        unset($e);
        [$totJama, $totNaam] = $this->entries->dayTotals($this->cid(), $date);

        // Standalone print view (no app layout).
        return view('Modules\Rokad\Views\print', [
            'firm'      => current_company(),
            'date'      => $date,
            'opening'   => $opening,
            'entries'   => $entries,
            'totalJama' => $totJama,
            'totalNaam' => $totNaam,
            'closing'   => round($opening + $totJama - $totNaam, 2),
        ]);
    }
}
