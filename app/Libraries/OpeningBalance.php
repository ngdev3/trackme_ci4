<?php

namespace App\Libraries;

use App\Models\CompanySettingModel;
use App\Models\TransactionModel;

/**
 * Shri Rokad Nagad — the opening cash-in-hand carried into a date or financial
 * year. Single source of truth for both the Rokad Parcha reports and the Rokad
 * Vahi ledger, so the ledger's running balance and the report's opening/closing
 * always agree.
 *
 * Everything here is per company: the transaction query scope and the Shri Rokad
 * Nagad opening-cash settings both key off the active company, so each company
 * keeps its own opening cash. The Super Admin's merged view (scope null, no
 * active company → companyId 0) simply has no opening cash of its own.
 */
class OpeningBalance
{
    /** company_settings scope under which the ledger's opening-cash keys live. */
    private const SCOPE = 'transactions';

    private TransactionModel $txns;
    private CompanySettingModel $settings;

    /**
     * @param int|null $scope     transaction query scope (null = all companies / Super Admin)
     * @param int      $companyId company that owns the Shri Rokad Nagad settings
     */
    public function __construct(
        private ?int $scope,
        private int $companyId,
        ?TransactionModel $txns = null,
        ?CompanySettingModel $settings = null
    ) {
        $this->txns     = $txns ?? new TransactionModel();
        $this->settings = $settings ?? new CompanySettingModel();
    }

    /** Financial-year start year (Indian FY, 1 Apr) for a date. */
    public function fyStartFor(string $date): int
    {
        return ReportPeriod::currentFyStart(strtotime($date) ?: time());
    }

    /** FY label like "2026-27" from a start year. */
    public static function fyLabel(int $fyStart): string
    {
        return $fyStart . '-' . substr((string) ($fyStart + 1), -2);
    }

    /** Whether the company has explicitly set a Shri Rokad Nagad for this FY. */
    public function isExplicit(int $fyStart): bool
    {
        $raw = $this->settings->get($this->companyId, self::SCOPE, 'shri_rokad_nagad_' . $fyStart, null);
        return $raw !== null && $raw !== '';
    }

    /**
     * "Shri Rokad Nagad" — the opening cash-in-hand for a financial year.
     *
     * If the user has fed a value for this FY, that is used. Otherwise it rolls
     * over automatically: the opening = the PREVIOUS financial year's closing
     * balance (its own Shri + that year's net Jama−Naam). So the 31-March
     * closing becomes the next year's 1-April opening with no manual step.
     * Recursion is bounded and bottoms out once there is no earlier data.
     */
    public function shriNagad(int $fyStart, int $depth = 0): float
    {
        // Defensive: never chase absurd years (guards against bad date inputs).
        if ($fyStart < 2000 || $fyStart > 2100) {
            return 0.0;
        }
        $raw = $this->settings->get($this->companyId, self::SCOPE, 'shri_rokad_nagad_' . $fyStart, null);
        if ($raw !== null && $raw !== '') {
            return round((float) $raw, 2);
        }

        $curStart = sprintf('%04d-04-01', $fyStart);
        // Nothing earlier to carry, or safety depth reached → start fresh at 0.
        if ($depth >= 25 || ! $this->txns->hasBefore($this->scope, $curStart)) {
            return 0.0;
        }

        // Auto roll-over: previous FY's closing = its opening + its net.
        $prevStart   = sprintf('%04d-04-01', $fyStart - 1);
        $prevOpening = $this->shriNagad($fyStart - 1, $depth + 1);
        $prevNet     = $this->txns->netBefore($this->scope, $curStart, $prevStart);
        return round($prevOpening + $prevNet, 2);
    }

    /** Custom, per-company display label for the opening cash. */
    public function label(): string
    {
        $l = trim((string) $this->settings->get($this->companyId, self::SCOPE, 'shri_rokad_label', ''));
        return $l !== '' ? $l : 'Opening Balance';
    }

    /**
     * Cash-in-hand carried into (strictly before) a given date: the FY's Shri
     * Rokad Nagad plus the net Jama−Naam of every entry from 1 April up to,
     * but excluding, that date.
     */
    public function carryInto(string $date): float
    {
        $fyStart     = $this->fyStartFor($date);
        $fyStartDate = sprintf('%04d-04-01', $fyStart);
        return round($this->shriNagad($fyStart) + $this->txns->netBefore($this->scope, $date, $fyStartDate), 2);
    }
}
