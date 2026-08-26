<?php

namespace App\Services;

/**
 * TransactionMap — the SINGLE source of truth for what every kind of ledger
 * movement affects. Both the posting engine and every read/report path should
 * classify through here so web and mobile can never disagree about whether a
 * given row is a Sale, a Loan, or a plain receipt, or about which subsystems it
 * touches (party ledger, cash book, inventory, sales, purchase, loan, GST,
 * profit/COGS).
 *
 * NOTHING here changes the database: a row is still just a `transactions` record
 * (type jama/naam, ledger_only, source, notes) linked — for bills — to an
 * `invoices` row. This class only interprets those existing fields consistently.
 *
 *   KIND      = the precise economic event (sale, sale_receipt, loan_given, …)
 *   CATEGORY  = the coarse filter bucket (sales | purchases | loans | payments)
 *   EFFECTS   = which books the kind moves, and in which direction
 *
 * Effect vocabulary:
 *   party_ledger : bool   — does it move a party's running balance?
 *   direction    : 'naam'|'jama'|'none'  — the sign in the receivable ledger
 *   inventory    : 'in'|'out'|'none'     — stock movement
 *   cash_bank    : 'in'|'out'|'none'     — cash-in-hand movement (real money now)
 *   sales        : bool   — counts toward Sales turnover
 *   purchase     : bool   — counts toward Purchase turnover
 *   loan         : bool   — loan principal (must NEVER hit profit or inventory)
 *   gst          : 'output'|'input'|'none'  — GST side (captured on the bill)
 *   cogs_profit  : bool   — participates in gross-profit (revenue − COGS)
 */
final class TransactionMap
{
    /**
     * The effect table. Keyed by KIND. This is the accounting model, in one place.
     *
     * @var array<string, array<string, mixed>>
     */
    public const EFFECTS = [
        // ---- Sale bill: revenue + receivable + stock out; profit = rate − COGS.
        'sale' => [
            'category' => 'sales', 'party_ledger' => true, 'direction' => 'naam',
            'inventory' => 'out', 'cash_bank' => 'none', 'sales' => true, 'purchase' => false,
            'loan' => false, 'gst' => 'output', 'cogs_profit' => true,
        ],
        // The cash actually received against a sale (settles the receivable).
        'sale_receipt' => [
            'category' => 'sales', 'party_ledger' => true, 'direction' => 'jama',
            'inventory' => 'none', 'cash_bank' => 'in', 'sales' => false, 'purchase' => false,
            'loan' => false, 'gst' => 'none', 'cogs_profit' => false,
        ],
        // ---- Purchase bill: payable + stock in. Buying stock is an ASSET swap,
        //      so it is NEVER profit/loss and NEVER counts as COGS here.
        'purchase' => [
            'category' => 'purchases', 'party_ledger' => true, 'direction' => 'jama',
            'inventory' => 'in', 'cash_bank' => 'none', 'sales' => false, 'purchase' => true,
            'loan' => false, 'gst' => 'input', 'cogs_profit' => false,
        ],
        // The cash actually paid against a purchase (settles the payable).
        'purchase_payment' => [
            'category' => 'purchases', 'party_ledger' => true, 'direction' => 'naam',
            'inventory' => 'none', 'cash_bank' => 'out', 'sales' => false, 'purchase' => false,
            'loan' => false, 'gst' => 'none', 'cogs_profit' => false,
        ],
        // ---- Returns reverse their bill.
        'sale_return' => [
            'category' => 'sales', 'party_ledger' => true, 'direction' => 'jama',
            'inventory' => 'in', 'cash_bank' => 'none', 'sales' => true, 'purchase' => false,
            'loan' => false, 'gst' => 'output', 'cogs_profit' => true,
        ],
        'sale_return_refund' => [
            'category' => 'sales', 'party_ledger' => true, 'direction' => 'naam',
            'inventory' => 'none', 'cash_bank' => 'out', 'sales' => false, 'purchase' => false,
            'loan' => false, 'gst' => 'none', 'cogs_profit' => false,
        ],
        'purchase_return' => [
            'category' => 'purchases', 'party_ledger' => true, 'direction' => 'naam',
            'inventory' => 'out', 'cash_bank' => 'none', 'sales' => false, 'purchase' => true,
            'loan' => false, 'gst' => 'input', 'cogs_profit' => false,
        ],
        'purchase_return_refund' => [
            'category' => 'purchases', 'party_ledger' => true, 'direction' => 'jama',
            'inventory' => 'none', 'cash_bank' => 'in', 'sales' => false, 'purchase' => false,
            'loan' => false, 'gst' => 'none', 'cogs_profit' => false,
        ],
        // ---- Loans: principal only. Moves cash + party balance, NEVER stock,
        //      sales, purchase, GST or profit.
        'loan_given' => [
            'category' => 'loans', 'party_ledger' => true, 'direction' => 'naam',
            'inventory' => 'none', 'cash_bank' => 'out', 'sales' => false, 'purchase' => false,
            'loan' => true, 'gst' => 'none', 'cogs_profit' => false,
        ],
        'loan_returned' => [
            'category' => 'loans', 'party_ledger' => true, 'direction' => 'jama',
            'inventory' => 'none', 'cash_bank' => 'in', 'sales' => false, 'purchase' => false,
            'loan' => true, 'gst' => 'none', 'cogs_profit' => false,
        ],
        // ---- Plain cash-book entries against a party (or cash) with no bill.
        'payment' => [
            'category' => 'payments', 'party_ledger' => true, 'direction' => 'naam',
            'inventory' => 'none', 'cash_bank' => 'out', 'sales' => false, 'purchase' => false,
            'loan' => false, 'gst' => 'none', 'cogs_profit' => false,
        ],
        'receipt' => [
            'category' => 'payments', 'party_ledger' => true, 'direction' => 'jama',
            'inventory' => 'none', 'cash_bank' => 'in', 'sales' => false, 'purchase' => false,
            'loan' => false, 'gst' => 'none', 'cogs_profit' => false,
        ],
        // ---- The party's opening balance seed (not a transaction row).
        'opening' => [
            'category' => 'payments', 'party_ledger' => true, 'direction' => 'none',
            'inventory' => 'none', 'cash_bank' => 'none', 'sales' => false, 'purchase' => false,
            'loan' => false, 'gst' => 'none', 'cogs_profit' => false,
        ],
    ];

    /** Loan / advance detection on a free-text note (English + Hindi/Hinglish). */
    public const LOAN_NOTE_RE = '/loan|udh?aar|udhar|karz|कर्ज|कर्ज़|उधार|ऋण/iu';

    /**
     * Classify one transaction row into [kind, category].
     *
     * A bill row is resolved authoritatively from the invoice map (built by the
     * caller: txn_id → ['type'=>sale|purchase|sale_return|purchase_return,
     * 'role'=>'ledger'|'cash']). A plain cash-book row is a Loan when its note
     * says so, otherwise a generic Payment (Naam) / Receipt (Jama).
     *
     * Behaviour is IDENTICAL to the previous inline classifier — this just makes
     * it the one shared definition.
     *
     * @param array<string,mixed> $row     a transactions row (needs id, type, notes)
     * @param array<int,array{type:string,role:string}> $invMap  txnId → invoice link
     * @return array{0:string,1:string}  [kind, category]
     */
    public static function classify(array $row, array $invMap): array
    {
        $id = (int) ($row['id'] ?? 0);
        if (isset($invMap[$id])) {
            $role = $invMap[$id]['role'];
            switch ($invMap[$id]['type']) {
                case 'sale':            return [$role === 'ledger' ? 'sale' : 'sale_receipt', 'sales'];
                case 'purchase':        return [$role === 'ledger' ? 'purchase' : 'purchase_payment', 'purchases'];
                case 'sale_return':     return [$role === 'ledger' ? 'sale_return' : 'sale_return_refund', 'sales'];
                case 'purchase_return': return [$role === 'ledger' ? 'purchase_return' : 'purchase_return_refund', 'purchases'];
            }
        }

        $type  = ($row['type'] ?? 'jama') === 'naam' ? 'naam' : 'jama';
        $notes = (string) ($row['notes'] ?? '');
        if ($notes !== '' && preg_match(self::LOAN_NOTE_RE, $notes)) {
            return [$type === 'naam' ? 'loan_given' : 'loan_returned', 'loans'];
        }
        return [$type === 'naam' ? 'payment' : 'receipt', 'payments'];
    }

    /**
     * The effect descriptor for a kind (empty array for an unknown kind).
     *
     * @return array<string,mixed>
     */
    public static function effects(string $kind): array
    {
        return self::EFFECTS[$kind] ?? [];
    }

    /** True when the kind's principal must be kept out of profit/COGS (loans). */
    public static function isLoan(string $kind): bool
    {
        return (bool) (self::EFFECTS[$kind]['loan'] ?? false);
    }

    /** True when the kind participates in gross profit (Sale − COGS). */
    public static function affectsProfit(string $kind): bool
    {
        return (bool) (self::EFFECTS[$kind]['cogs_profit'] ?? false);
    }

    /** 'in' | 'out' | 'none' — how the kind moves stock. */
    public static function inventory(string $kind): string
    {
        return (string) (self::EFFECTS[$kind]['inventory'] ?? 'none');
    }
}
