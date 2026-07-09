<?php

namespace App\Libraries;

/**
 * Resolves a Rokadh Parcha reporting period from request-style inputs into a
 * concrete [from, to] date range plus a human label. Supports:
 *   day      — a single date
 *   month    — a calendar month (year + month)
 *   quarter  — a financial-year quarter (Q1 Apr-Jun … Q4 Jan-Mar)
 *   fy       — an Indian financial year (1 Apr → 31 Mar)
 *   custom   — an explicit from/to range
 *
 * All dates are Y-m-d strings.
 */
class ReportPeriod
{
    public string $period;
    public string $from;
    public string $to;
    public string $label;

    /**
     * @param array<string,string> $in  keys: period, date, month, year, quarter, fy, from, to
     * @param string               $today  Y-m-d anchor for defaults
     */
    public static function resolve(array $in, string $today): self
    {
        // Normalise empty strings to null so the `?? default` fallbacks below
        // actually trigger (request inputs arrive as '' when a param is absent).
        $in = array_map(static fn ($v) => ($v === '' || $v === null) ? null : $v, $in);

        $p = new self();
        $period = $in['period'] ?? 'month';
        $p->period = in_array($period, ['day', 'month', 'quarter', 'fy', 'custom'], true) ? $period : 'month';

        $ts = strtotime($today) ?: time();

        switch ($p->period) {
            case 'day':
                $d = self::validDate($in['date'] ?? '', $today);
                $p->from = $p->to = $d;
                $p->label = 'Daily — ' . date('d M Y', strtotime($d));
                break;

            case 'quarter':
                $fy = (int) ($in['fy'] ?? self::currentFyStart($ts));
                $q  = (int) ($in['quarter'] ?? self::fyQuarter($ts));
                $q  = max(1, min(4, $q));
                [$p->from, $p->to] = self::fyQuarterRange($fy, $q);
                $p->label = 'Quarter Q' . $q . ' — FY ' . $fy . '-' . substr((string) ($fy + 1), -2);
                break;

            case 'fy':
                $fy = (int) ($in['fy'] ?? self::currentFyStart($ts));
                $p->from = sprintf('%04d-04-01', $fy);
                $p->to   = sprintf('%04d-03-31', $fy + 1);
                $p->label = 'Financial Year ' . $fy . '-' . substr((string) ($fy + 1), -2);
                break;

            case 'custom':
                $from = self::validDate($in['from'] ?? '', date('Y-m-01', $ts));
                $to   = self::validDate($in['to'] ?? '', $today);
                if (strtotime($to) < strtotime($from)) {
                    [$from, $to] = [$to, $from];
                }
                $p->from = $from;
                $p->to   = $to;
                $p->label = 'Custom — ' . date('d M Y', strtotime($from)) . ' to ' . date('d M Y', strtotime($to));
                break;

            case 'month':
            default:
                $y = (int) ($in['year'] ?? date('Y', $ts));
                $m = (int) ($in['month'] ?? date('n', $ts));
                $m = max(1, min(12, $m));
                $p->from = sprintf('%04d-%02d-01', $y, $m);
                $p->to   = date('Y-m-t', strtotime($p->from));
                $p->label = 'Monthly — ' . date('F Y', strtotime($p->from));
                break;
        }

        return $p;
    }

    private static function validDate(string $d, string $fallback): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : $fallback;
    }

    /** FY starting year for a timestamp (Indian FY starts 1 April). */
    public static function currentFyStart(int $ts): int
    {
        $y = (int) date('Y', $ts);
        $m = (int) date('n', $ts);
        return $m >= 4 ? $y : $y - 1;
    }

    /** FY quarter number (1..4) for a timestamp. */
    private static function fyQuarter(int $ts): int
    {
        $m = (int) date('n', $ts);
        if ($m >= 4 && $m <= 6) {
            return 1;
        }
        if ($m >= 7 && $m <= 9) {
            return 2;
        }
        if ($m >= 10 && $m <= 12) {
            return 3;
        }
        return 4;
    }

    /** @return array{0:string,1:string} [from, to] for an FY quarter. */
    private static function fyQuarterRange(int $fyStart, int $q): array
    {
        switch ($q) {
            case 1: return [sprintf('%04d-04-01', $fyStart), sprintf('%04d-06-30', $fyStart)];
            case 2: return [sprintf('%04d-07-01', $fyStart), sprintf('%04d-09-30', $fyStart)];
            case 3: return [sprintf('%04d-10-01', $fyStart), sprintf('%04d-12-31', $fyStart)];
            default: return [sprintf('%04d-01-01', $fyStart + 1), sprintf('%04d-03-31', $fyStart + 1)];
        }
    }
}
