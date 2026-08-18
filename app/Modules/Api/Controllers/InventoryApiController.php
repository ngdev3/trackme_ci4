<?php

namespace Modules\Api\Controllers;

use App\Libraries\InventoryService;
use App\Models\CompanyUserModel;
use App\Models\InvPartyModel;
use App\Models\InvProductModel;
use App\Models\InvStockModel;
use App\Models\InvWarehouseModel;

/**
 * Mandi Inventory REST API for the mobile app. Bearer-token authenticated (see
 * BaseApiController). The company is resolved from the caller's membership: a
 * `company_id` may be supplied but is always validated against what the user
 * actually belongs to, so one firm's stock can never be touched by another.
 */
class InventoryApiController extends BaseApiController
{
    /** Resolve + authorise the active company for the API caller. */
    private function companyId(array $user): ?int
    {
        $members = new CompanyUserModel();
        $requested = (int) ($this->input('company_id') ?? 0);
        if ($requested > 0 && $members->isMember($requested, (int) $user['id'])) {
            return $requested;
        }
        $companies = (new \App\Models\CompanyModel())->forUser((int) $user['id']);
        return $companies !== [] ? (int) $companies[0]['id'] : null;
    }

    /** GET masters: products, godowns, parties — to populate the app's dropdowns. */
    public function masters()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        return $this->respond([
            'status'     => 'ok',
            'company_id' => $cid,
            'products'   => (new InvProductModel())->forCompany($cid),
            'warehouses' => (new InvWarehouseModel())->forCompany($cid),
            'suppliers'  => (new InvPartyModel())->forCompany($cid, 'supplier'),
            'customers'  => (new InvPartyModel())->forCompany($cid, 'customer'),
        ]);
    }

    /** GET current stock (per product+warehouse) for the app. */
    public function stock()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        $rows = (new InvStockModel())->scopedList($cid)->orderBy('p.name', 'ASC')->get()->getResultArray();
        return $this->respond(['status' => 'ok', 'stock' => $rows]);
    }

    /** GET stock search (product / party / godown / SKU / lot) — card data for the app. */
    public function search()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        $q = trim((string) ($this->input('q') ?? $this->request->getGet('q') ?? ''));
        return $this->respond([
            'status'  => 'ok',
            'q'       => $q,
            'results' => (new InvStockModel())->search($cid, $q),
        ]);
    }

    /** POST parse a spoken sentence into a structured entry (mobile voice input). */
    public function voiceParse()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        $transcript = trim((string) ($this->input('transcript') ?? ''));
        if ($transcript === '') {
            return $this->failValidationErrors('No transcript provided.');
        }
        $parsed = (new \App\Libraries\VoiceEntryParser())->parse(
            $transcript,
            (new InvProductModel())->forCompany($cid),
            (new InvWarehouseModel())->forCompany($cid),
            (new InvPartyModel())->forCompany($cid)
        );
        if (! empty($parsed['product_id']) && ! empty($parsed['warehouse_id'])) {
            $parsed['available'] = (new InventoryService())->availableBags($cid, (int) $parsed['product_id'], (int) $parsed['warehouse_id']);
        }
        return $this->respond(['status' => 'ok', 'parsed' => $parsed]);
    }

    /** POST record a stock inward from the app. */
    public function inward()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }

        $productId   = (int) $this->input('product_id');
        $warehouseId = (int) $this->input('warehouse_id');
        $bags        = (float) $this->input('bags');

        if (! (new InvProductModel())->findForCompany($productId, $cid)) {
            return $this->failValidationErrors('Invalid product.');
        }
        if (! (new InvWarehouseModel())->findForCompany($warehouseId, $cid)) {
            return $this->failValidationErrors('Invalid godown.');
        }
        if ($bags <= 0) {
            return $this->failValidationErrors('Bags must be greater than zero.');
        }

        $partyId = null;
        $supplier = trim((string) ($this->input('supplier_name') ?? ''));
        if ($supplier !== '') {
            $partyId = (new InvPartyModel())->findOrCreate($cid, $supplier, 'supplier') ?: null;
        }

        $result = (new InventoryService())->recordInward([
            'company_id'   => $cid,
            'product_id'   => $productId,
            'warehouse_id' => $warehouseId,
            'party_id'     => $partyId,
            'bags'         => $bags,
            'weight'       => $this->input('weight') !== null ? (float) $this->input('weight') : null,
            'rack'         => trim((string) ($this->input('rack') ?? '')) ?: null,
            'notes'        => trim((string) ($this->input('notes') ?? '')) ?: null,
            'source'       => 'mobile',
            'created_by'   => (int) $user['id'],
        ]);

        return $this->respondCreated([
            'status'    => 'ok',
            'message'   => 'Inward saved.',
            'entry_no'  => $result['entry_no'],
            'lot_no'    => $result['lot_no'],
            'weight'    => $result['weight'],
            'available' => (new InventoryService())->availableBags($cid, $productId, $warehouseId),
        ]);
    }

    /** POST record a stock outward from the app. */
    public function outward()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }

        $productId   = (int) $this->input('product_id');
        $warehouseId = (int) $this->input('warehouse_id');
        $bags        = (float) $this->input('bags');

        if (! (new InvProductModel())->findForCompany($productId, $cid)) {
            return $this->failValidationErrors('Invalid product.');
        }
        if (! (new InvWarehouseModel())->findForCompany($warehouseId, $cid)) {
            return $this->failValidationErrors('Invalid godown.');
        }
        if ($bags <= 0) {
            return $this->failValidationErrors('Bags must be greater than zero.');
        }

        $partyId  = null;
        $customer = trim((string) ($this->input('customer_name') ?? ''));
        if ($customer !== '') {
            $partyId = (new InvPartyModel())->findOrCreate($cid, $customer, 'customer') ?: null;
        }

        try {
            $result = (new InventoryService())->recordOutward([
                'company_id'     => $cid,
                'product_id'     => $productId,
                'warehouse_id'   => $warehouseId,
                'party_id'       => $partyId,
                'bags'           => $bags,
                'weight'         => $this->input('weight') !== null ? (float) $this->input('weight') : null,
                'vehicle_no'     => trim((string) ($this->input('vehicle_no') ?? '')) ?: null,
                'notes'          => trim((string) ($this->input('notes') ?? '')) ?: null,
                'allow_negative' => (bool) $this->input('allow_negative'),
                'source'         => 'mobile',
                'created_by'     => (int) $user['id'],
            ]);
        } catch (\RuntimeException $e) {
            return $this->respond([
                'status'    => 'insufficient_stock',
                'message'   => 'Not enough stock in this godown.',
                'available' => (float) $e->getMessage(),
            ], 409);
        }

        return $this->respondCreated([
            'status'    => 'ok',
            'message'   => 'Outward saved.',
            'entry_no'  => $result['entry_no'],
            'weight'    => $result['weight'],
            'available' => $result['available'],
        ]);
    }

    /** POST move stock between two godowns (atomic OUT+IN). */
    public function transfer()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }

        $productId = (int) $this->input('product_id');
        $fromId    = (int) $this->input('from_warehouse_id');
        $toId      = (int) $this->input('to_warehouse_id');
        $bags      = (float) $this->input('bags');

        if (! (new InvProductModel())->findForCompany($productId, $cid)) {
            return $this->failValidationErrors('Invalid product.');
        }
        if (! (new InvWarehouseModel())->findForCompany($fromId, $cid) || ! (new InvWarehouseModel())->findForCompany($toId, $cid)) {
            return $this->failValidationErrors('Invalid godown.');
        }

        try {
            $result = (new InventoryService())->recordTransfer([
                'company_id'        => $cid,
                'product_id'        => $productId,
                'from_warehouse_id' => $fromId,
                'to_warehouse_id'   => $toId,
                'bags'              => $bags,
                'weight'            => $this->input('weight') !== null ? (float) $this->input('weight') : null,
                'notes'             => trim((string) ($this->input('notes') ?? '')) ?: null,
                'allow_negative'    => (bool) $this->input('allow_negative'),
                'source'            => 'mobile',
                'created_by'        => (int) $user['id'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respond([
                'status'    => 'insufficient_stock',
                'message'   => 'Not enough stock in the source godown.',
                'available' => (float) $e->getMessage(),
            ], 409);
        }

        return $this->respondCreated(['status' => 'ok', 'message' => 'Transfer saved.'] + $result);
    }

    /** POST convert one input into many outputs + wastage (atomic batch). */
    public function production()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }

        $input   = (array) ($this->input('input') ?? []);
        $outputs = (array) ($this->input('outputs') ?? []);

        // Every referenced product must belong to this company.
        $ids = array_filter(array_merge([(int) ($input['product_id'] ?? 0)], array_map(static fn ($o) => (int) ($o['product_id'] ?? 0), $outputs)));
        foreach (array_unique($ids) as $pid) {
            if (! (new InvProductModel())->findForCompany((int) $pid, $cid)) {
                return $this->failValidationErrors('Invalid product in production.');
            }
        }
        if (! (new InvWarehouseModel())->findForCompany((int) ($input['warehouse_id'] ?? 0), $cid)) {
            return $this->failValidationErrors('Invalid godown.');
        }

        try {
            $result = (new InventoryService())->recordProduction([
                'company_id'     => $cid,
                'input'          => $input,
                'outputs'        => $outputs,
                'wastage_bags'   => (float) ($this->input('wastage_bags') ?? 0),
                'notes'          => trim((string) ($this->input('notes') ?? '')) ?: null,
                'allow_negative' => (bool) $this->input('allow_negative'),
                'source'         => 'mobile',
                'created_by'     => (int) $user['id'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respond([
                'status'    => 'insufficient_stock',
                'message'   => 'Not enough input stock to produce.',
                'available' => (float) $e->getMessage(),
            ], 409);
        }

        return $this->respondCreated(['status' => 'ok', 'message' => 'Production saved.'] + $result);
    }

    /** GET the proof files linked to an entry. */
    public function entryAttachments($id = null)
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        $mv = (new \App\Models\InvMovementModel())->findForCompany((int) $id, $cid);
        if (! $mv) {
            return $this->failNotFound('Entry not found.');
        }
        $rows = (new \App\Models\InvAttachmentModel())->forMovement((int) $id);
        foreach ($rows as &$r) {
            $r['url'] = site_url('inventory/attachment/' . $r['id']);
        }
        return $this->respond(['status' => 'ok', 'attachments' => $rows]);
    }

    /** POST upload one or more proof files to an entry (multipart attachments[]). */
    public function attachToEntry($id = null)
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        $mv = (new \App\Models\InvMovementModel())->findForCompany((int) $id, $cid);
        if (! $mv) {
            return $this->failNotFound('Entry not found.');
        }

        $dir = WRITEPATH . 'uploads/inventory/' . $cid . '/';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $att    = new \App\Models\InvAttachmentModel();
        $stored = 0;
        foreach ((array) $this->request->getFileMultiple('attachments') as $file) {
            if (! $file || ! $file->isValid() || $file->hasMoved() || $file->getSizeByUnit('mb') > 50) {
                continue;
            }
            $ext  = strtolower($file->getExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
            $name = $file->getRandomName();
            try {
                $file->move($dir, $name);
            } catch (\Throwable $e) {
                continue;
            }
            $att->insert([
                'company_id' => $cid, 'movement_id' => (int) $id,
                'product_id' => $mv['product_id'] ?? null, 'lot_id' => $mv['lot_id'] ?? null, 'party_id' => $mv['party_id'] ?? null,
                'kind' => \App\Models\InvAttachmentModel::kindFor((string) $file->getClientMimeType(), (string) $ext),
                'original_name' => $file->getClientName(), 'stored_name' => $name,
                'mime' => $file->getClientMimeType(), 'size' => (int) filesize($dir . $name),
                'created_by' => (int) $user['id'], 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $stored++;
        }
        return $this->respond(['status' => $stored > 0 ? 'ok' : 'empty', 'stored' => $stored, 'message' => "{$stored} file(s) attached."]);
    }

    /** Whether the API caller may approve corrections for this company (owner/admin). */
    private function canApprove(int $companyId, array $user): bool
    {
        $m = (new CompanyUserModel())->membership($companyId, (int) $user['id']);
        return $m && in_array($m['role'] ?? '', ['owner', 'admin'], true);
    }

    /** POST submit a physical-verification correction request. */
    public function verify()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }

        $productId   = (int) $this->input('product_id');
        $warehouseId = (int) $this->input('warehouse_id');
        $physical    = (float) $this->input('physical_bags');
        $reason      = (string) ($this->input('reason') ?? '');

        if (! (new InvProductModel())->findForCompany($productId, $cid)) {
            return $this->failValidationErrors('Invalid product.');
        }
        if (! (new InvWarehouseModel())->findForCompany($warehouseId, $cid)) {
            return $this->failValidationErrors('Invalid godown.');
        }

        $system = (new InventoryService())->availableBags($cid, $productId, $warehouseId);
        $diff   = round($physical - $system, 2);
        if ($diff == 0.0) {
            return $this->respond(['status' => 'match', 'message' => 'Physical matches system. No correction needed.', 'system' => $system]);
        }
        if (! array_key_exists($reason, \App\Models\InvCorrectionModel::REASONS)) {
            return $this->failValidationErrors('Select a valid reason: ' . implode(', ', array_keys(\App\Models\InvCorrectionModel::REASONS)));
        }

        $id = (new \App\Models\InvCorrectionModel())->insert([
            'company_id' => $cid, 'product_id' => $productId, 'warehouse_id' => $warehouseId,
            'system_bags' => $system, 'physical_bags' => $physical, 'difference' => $diff,
            'reason' => $reason, 'note' => trim((string) ($this->input('note') ?? '')) ?: null,
            'status' => 'pending', 'requested_by' => (int) $user['id'],
        ]);

        return $this->respondCreated([
            'status' => 'pending_approval', 'message' => 'Correction request submitted. Stock unchanged until approved.',
            'correction_id' => (int) $id, 'system' => $system, 'physical' => $physical, 'difference' => $diff,
        ]);
    }

    /** GET the owner dashboard cards (Task 8). */
    public function dashboard()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        return $this->respond(['status' => 'ok', 'dashboard' => (new \App\Libraries\InventoryReport())->dashboard($cid)]);
    }

    /** GET a report dataset by key (Task 8). Date range via ?from=&to=. */
    public function report($key = '')
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        $rep  = new \App\Libraries\InventoryReport();
        $from = (string) ($this->request->getGet('from') ?? '');
        $to   = (string) ($this->request->getGet('to') ?? '');
        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : date('Y-m-01');
        $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? $to : date('Y-m-d');

        $data = match ($key) {
            'product'    => $rep->productWise($cid),
            'warehouse'  => $rep->warehouseWise($cid),
            'party'      => $rep->partyWise($cid),
            'lot'        => $rep->lotWise($cid),
            'inward'     => $rep->dailyFlow($cid, 'inward', $from, $to),
            'outward'    => $rep->dailyFlow($cid, 'outward', $from, $to),
            'difference' => $rep->stockDifference($cid, $from, $to),
            'pending'    => $rep->pendingApproval($cid),
            'movement'   => $rep->movement($cid, $from, $to),
            default      => null,
        };
        if ($data === null) {
            return $this->failNotFound('Unknown report.');
        }
        return $this->respond(['status' => 'ok', 'key' => $key, 'report' => $data]);
    }

    /** GET the daily closing summary for a date (defaults to today). */
    public function closingSummary()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        $date = (string) ($this->input('date') ?? $this->request->getGet('date') ?? '');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $model = new \App\Models\InvDailyClosingModel();
        return $this->respond([
            'status'   => 'ok',
            'date'     => $date,
            'summary'  => (new \App\Libraries\InventoryReport())->dailySummary($cid, $date),
            'closing'  => $model->forDate($cid, $date),
            'is_locked'=> $model->isLocked($cid, $date),
        ]);
    }

    /** POST close a day (locks worker edits). */
    public function closeDay()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        $date = (string) ($this->input('date') ?? date('Y-m-d'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > date('Y-m-d')) {
            return $this->failValidationErrors('Invalid or future date.');
        }
        $model = new \App\Models\InvDailyClosingModel();
        if ($model->isLocked($cid, $date)) {
            return $this->respond(['status' => 'already_closed', 'message' => 'This day is already closed.'], 409);
        }
        $s   = (new \App\Libraries\InventoryReport())->dailySummary($cid, $date);
        $now = date('Y-m-d H:i:s');
        $data = [
            'company_id' => $cid, 'closing_date' => $date,
            'opening_bags' => $s['opening_bags'], 'received_bags' => $s['received_bags'], 'dispatched_bags' => $s['dispatched_bags'],
            'adjustment_bags' => $s['adjustment_bags'], 'closing_bags' => $s['closing_bags'],
            'received_weight' => $s['received_weight'], 'dispatched_weight' => $s['dispatched_weight'],
            'difference_bags' => $s['difference_bags'], 'pending_corrections' => $s['pending_corrections'],
            'entry_count' => $s['entry_count'], 'status' => 'closed', 'closed_by' => (int) $user['id'], 'closed_at' => $now,
            'reopened_by' => null, 'reopened_at' => null,
        ];
        $existing = $model->forDate($cid, $date);
        if ($existing) {
            $model->update((int) $existing['id'], $data);
        } else {
            $model->insert($data);
        }
        $mvb = (new \App\Models\InvMovementModel())->builder()->where('DATE(created_at)', $date)->where('company_id', $cid);
        $mvb->update(['day_closed' => 1]);
        return $this->respond(['status' => 'ok', 'message' => 'Day closed.', 'summary' => $s]);
    }

    /** POST reopen a closed day (owner/admin only). */
    public function reopenDay()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        if (! $this->canApprove($cid, $user)) {
            return $this->failForbidden('Only the owner or an admin can reopen a day.');
        }
        $date  = (string) ($this->input('date') ?? date('Y-m-d'));
        $model = new \App\Models\InvDailyClosingModel();
        $row   = $model->forDate($cid, $date);
        if (! $row || $row['status'] !== 'closed') {
            return $this->failNotFound('That day is not closed.');
        }
        $model->update((int) $row['id'], ['status' => 'reopened', 'reopened_by' => (int) $user['id'], 'reopened_at' => date('Y-m-d H:i:s')]);
        (new \App\Models\InvMovementModel())->builder()->where('DATE(created_at)', $date)->where('company_id', $cid)->update(['day_closed' => 0]);
        return $this->respond(['status' => 'ok', 'message' => 'Day reopened.']);
    }

    /** GET list correction requests for the company. */
    public function corrections()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        $rows = (new \App\Models\InvCorrectionModel())->scopedList($cid)
            ->orderBy('inv_corrections.id', 'DESC')->findAll(100);
        return $this->respond(['status' => 'ok', 'can_approve' => $this->canApprove($cid, $user), 'corrections' => $rows]);
    }

    /** POST approve a correction (owner/admin only) → auto adjustment entry. */
    public function approveCorrection($id = null)
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        if (! $this->canApprove($cid, $user)) {
            return $this->failForbidden('Only the owner or an admin can approve corrections.');
        }
        $model = new \App\Models\InvCorrectionModel();
        $c     = $model->findForCompany((int) $id, $cid);
        if (! $c || $c['status'] !== 'pending') {
            return $this->failNotFound('Correction not found or already reviewed.');
        }
        $result = (new InventoryService())->recordAdjustment([
            'company_id' => $cid, 'product_id' => (int) $c['product_id'], 'warehouse_id' => (int) $c['warehouse_id'],
            'delta_bags' => (float) $c['difference'], 'reason' => $c['reason'],
            'notes' => 'Correction #' . $c['id'], 'source' => 'mobile', 'created_by' => (int) $user['id'],
        ]);
        $model->update((int) $c['id'], [
            'status' => 'approved', 'movement_id' => $result['movement_id'],
            'reviewed_by' => (int) $user['id'], 'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->respond([
            'status' => 'ok', 'message' => 'Approved. Adjustment created and stock reconciled.',
            'entry_no' => $result['entry_no'],
            'available' => (new InventoryService())->availableBags($cid, (int) $c['product_id'], (int) $c['warehouse_id']),
        ]);
    }
}
