<?php

namespace Modules\Reminders\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ReminderService;
use App\Models\ReminderModel;

/**
 * Reminders — company-shared CRUD with priority, due status (pending/completed/
 * overdue), snooze, repeat (daily/weekly/monthly/yearly/custom), module
 * attachments, list + calendar views, and in-app due notifications.
 *
 * Every query is scoped to the active company; reminders are shared across a
 * company's members. `user_id` records the author only.
 */
class ReminderController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'notes', 'company'];

    protected string $moduleCode = 'reminders';
    protected string $baseRoute  = 'reminders';

    protected ReminderModel $reminders;

    public function __construct()
    {
        $this->reminders = new ReminderModel();
    }

    private function uid(): int
    {
        return (int) user_id();
    }

    /** The active company id (all reminder data is scoped to it). */
    private function cid(): ?int
    {
        return company_id();
    }

    // ---------------------------------------------------------------
    // Listing (with today / upcoming / overdue / all / completed views)
    // ---------------------------------------------------------------
    public function index()
    {
        $cid = $this->cid();
        (new ReminderService())->fireDueForCompany($cid);

        $view     = (string) ($this->request->getGet('view') ?: 'all');
        $priority = (string) $this->request->getGet('priority');
        $search   = trim((string) $this->request->getGet('q'));

        $b = $this->reminders->scoped($cid)->orderBy('remind_at', 'ASC');

        switch ($view) {
            case 'today':
                $b->where('status', 'pending')
                  ->where($this->reminders->effectiveCond('>=', date('Y-m-d 00:00:00')), null, false)
                  ->where($this->reminders->effectiveCond('<=', date('Y-m-d 23:59:59')), null, false);
                break;
            case 'upcoming':
                $b->where('status', 'pending')
                  ->where($this->reminders->effectiveCond('>', date('Y-m-d 23:59:59')), null, false);
                break;
            case 'overdue':
                $b->where('status', 'pending')
                  ->where($this->reminders->effectiveCond('<', date('Y-m-d H:i:s')), null, false);
                break;
            case 'completed':
                $b->where('status', 'completed');
                break;
        }
        if (in_array($priority, ReminderModel::PRIORITIES, true)) {
            $b->where('priority', $priority);
        }
        if ($search !== '') {
            $b->groupStart()->like('title', $search)->orLike('description', $search)->groupEnd();
        }

        return $this->render('index', [
            'title'      => 'Reminders',
            'breadcrumb' => [['label' => 'Notes & Reminders'], ['label' => 'Reminders']],
            'rows'       => $b->paginate(15),
            'pager'      => $this->reminders->pager,
            'view'       => $view,
            'priority'   => $priority,
            'search'     => $search,
            'counts'     => [
                'today'    => count($this->reminders->today($cid)),
                'upcoming' => count($this->reminders->upcoming($cid)),
                'overdue'  => count($this->reminders->overdue($cid)),
            ],
            'model'      => $this->reminders,
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
        ]);
    }

    // ---------------------------------------------------------------
    // Calendar (month grid)
    // ---------------------------------------------------------------
    public function calendar()
    {
        $cid = $this->cid();
        $ym  = (string) ($this->request->getGet('ym') ?: date('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
            $ym = date('Y-m');
        }
        $start = $ym . '-01 00:00:00';
        $end   = date('Y-m-t 23:59:59', strtotime($start));

        $rows = $this->reminders->scoped($cid)
            ->where('remind_at >=', $start)->where('remind_at <=', $end)
            ->orderBy('remind_at', 'ASC')->findAll();

        $byDay = [];
        foreach ($rows as $r) {
            $byDay[(int) date('j', strtotime($r['remind_at']))][] = $r;
        }

        return $this->render('calendar', [
            'title'      => 'Reminders Calendar',
            'breadcrumb' => [['label' => 'Reminders', 'url' => site_url('reminders')], ['label' => 'Calendar']],
            'ym'         => $ym,
            'byDay'      => $byDay,
            'model'      => $this->reminders,
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
        ]);
    }

    // ---------------------------------------------------------------
    // Create / edit
    // ---------------------------------------------------------------
    public function create()
    {
        return $this->render('form', $this->formData(null, 'create'));
    }

    public function edit($id = null)
    {
        $row = $this->reminders->findForCompany((int) $id, $this->cid());
        if (! $row) {
            return redirect()->to(site_url('reminders'))->with('error', 'Reminder not found.');
        }
        return $this->render('form', $this->formData($row, 'edit'));
    }

    private function formData(?array $row, string $mode): array
    {
        return [
            'title'      => $mode === 'edit' ? 'Edit Reminder' : 'Add Reminder',
            'breadcrumb' => [
                ['label' => 'Reminders', 'url' => site_url('reminders')],
                ['label' => $mode === 'edit' ? 'Edit' : 'Add'],
            ],
            'row'        => $row,
            'mode'       => $mode,
            'errors'     => session()->getFlashdata('errors') ?? [],
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
        ];
    }

    public function save()
    {
        $id   = (int) $this->request->getPost('id');
        $date = trim((string) $this->request->getPost('remind_at'));

        if (trim((string) $this->request->getPost('title')) === '' || $date === '') {
            return redirect()->back()->withInput()->with('errors', ['A title and date/time are required.']);
        }

        $repeatType = (string) $this->request->getPost('repeat_type') ?: 'none';
        if (! in_array($repeatType, ReminderModel::REPEATS, true)) {
            $repeatType = 'none';
        }
        $priority = (string) $this->request->getPost('priority') ?: 'medium';
        if (! in_array($priority, ReminderModel::PRIORITIES, true)) {
            $priority = 'medium';
        }

        $data = [
            'title'           => trim((string) $this->request->getPost('title')),
            'description'     => (string) $this->request->getPost('description') ?: null,
            'remind_at'       => date('Y-m-d H:i:s', strtotime($date)),
            'priority'        => $priority,
            'repeat_type'     => $repeatType,
            'repeat_interval' => $repeatType === 'custom' ? max(1, (int) $this->request->getPost('repeat_interval')) : null,
            'repeat_until'    => trim((string) $this->request->getPost('repeat_until')) ?: null,
            'attach_module'   => (string) $this->request->getPost('attach_module') ?: null,
            'attach_ref'      => trim((string) $this->request->getPost('attach_ref')) ?: null,
        ];

        if ($id) {
            $existing = $this->reminders->findForCompany($id, $this->cid());
            if (! $existing) {
                return redirect()->to(site_url('reminders'))->with('error', 'Reminder not found.');
            }
            // Editing the time re-arms the due notification.
            $data['notified']      = 0;
            $data['snoozed_until'] = null;
            $this->reminders->update($id, $data);
            activity_log('Reminders', 'Edit', "Reminder #{$id} updated");
            return redirect()->to(site_url('reminders'))->with('success', 'Reminder updated.');
        }

        $data['user_id']    = $this->uid();
        $data['company_id'] = $this->cid();
        $data['status']     = 'pending';
        $newId = $this->reminders->insert($data, true);
        activity_log('Reminders', 'Add', "Reminder #{$newId} created");
        return redirect()->to(site_url('reminders'))->with('success', 'Reminder created.');
    }

    // ---------------------------------------------------------------
    // Complete (spawns next occurrence for repeats) / snooze / delete
    // ---------------------------------------------------------------
    /**
     * JSON feed of reminders that are due right now — polled by the global
     * reminder-popup so an alert can pop while the app/web is open.
     */
    public function due()
    {
        $rows = $this->reminders->overdue($this->cid());
        $items = [];
        foreach ($rows as $r) {
            $effective = $r['snoozed_until'] ?: $r['remind_at'];
            $items[] = [
                'id'       => (int) $r['id'],
                'title'    => $r['title'],
                'message'  => (string) ($r['description'] ?? ''),
                'priority' => $r['priority'],
                'due_at'   => date('d M Y, H:i', strtotime($effective)),
                'url'      => $this->reminderUrl($r),
            ];
        }
        return $this->response->setJSON(['items' => $items, 'csrf' => csrf_hash()]);
    }

    /** Where "Open" on a reminder should go — the linked record, else its editor. */
    private function reminderUrl(array $r): string
    {
        $mod = (string) ($r['attach_module'] ?? '');
        $ref = (string) ($r['attach_ref'] ?? '');
        if ($mod === 'transactions' && $ref !== '') {
            helper('hashid');
            return site_url('transactions/view/' . hid($ref));
        }
        if ($mod !== '' && $ref !== '') {
            return site_url($mod . '/view/' . $ref);
        }
        return site_url('reminders/edit/' . $r['id']);
    }

    public function complete($id = null)
    {
        $id  = (int) $id;
        $r   = $this->reminders->findForCompany($id, $this->cid());
        if (! $r) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
            }
            return redirect()->to(site_url('reminders'))->with('error', 'Reminder not found.');
        }

        $this->reminders->update($id, ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);

        // Roll a repeating reminder forward to its next occurrence.
        $next = $this->reminders->nextOccurrence($r['remind_at'], $r['repeat_type'] ?? 'none', $r['repeat_interval'] ?? null, $r['repeat_until'] ?? null);
        if ($next) {
            $this->reminders->insert([
                'user_id'         => (int) $r['user_id'],
                'company_id'      => $r['company_id'] ?? $this->cid(),
                'title'           => $r['title'],
                'description'     => $r['description'],
                'remind_at'       => $next,
                'priority'        => $r['priority'],
                'status'          => 'pending',
                'repeat_type'     => $r['repeat_type'],
                'repeat_interval' => $r['repeat_interval'],
                'repeat_until'    => $r['repeat_until'],
                'attach_module'   => $r['attach_module'],
                'attach_ref'      => $r['attach_ref'],
            ]);
        }

        activity_log('Reminders', 'Edit', "Reminder #{$id} completed");
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()]);
        }
        return redirect()->back()->with('success', 'Reminder marked complete.' . ($next ? ' Next occurrence scheduled.' : ''));
    }

    public function snooze($id = null)
    {
        $id  = (int) $id;
        $r   = $this->reminders->findForCompany($id, $this->cid());
        if (! $r) {
            return redirect()->to(site_url('reminders'))->with('error', 'Reminder not found.');
        }
        $minutes = (int) $this->request->getPost('minutes') ?: 10;
        $minutes = max(1, min($minutes, 60 * 24 * 7)); // clamp 1 min .. 1 week
        $this->reminders->update($id, [
            'snoozed_until' => date('Y-m-d H:i:s', time() + $minutes * 60),
            'notified'      => 0,
        ]);
        activity_log('Reminders', 'Edit', "Reminder #{$id} snoozed {$minutes}m");
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'minutes' => $minutes, 'csrf' => csrf_hash()]);
        }
        return redirect()->back()->with('success', "Snoozed for {$minutes} minute(s).");
    }

    public function delete($id = null)
    {
        $id  = (int) $id;
        $r   = $this->reminders->findForCompany($id, $this->cid());
        if (! $r) {
            return redirect()->to(site_url('reminders'))->with('error', 'Reminder not found.');
        }
        $this->reminders->delete($id); // soft delete
        activity_log('Reminders', 'Delete', "Reminder #{$id} deleted");
        return redirect()->to(site_url('reminders'))->with('success', 'Reminder deleted.');
    }
}
