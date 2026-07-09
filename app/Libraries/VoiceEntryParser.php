<?php

namespace App\Libraries;

/**
 * Turns a spoken sentence into a structured inventory entry so the worker only
 * has to review and confirm — never type. Understands sentences such as:
 *
 *   "Received 100 bags of potatoes from Sharma Traders."
 *   "Dispatched 50 bags of rice to Gupta Traders."
 *
 * The parse is deliberately forgiving: it matches loosely against the company's
 * own product / godown / party masters (so "potatoes" finds the "Potato"
 * product, "sharma" finds "Sharma Traders"). Nothing is ever saved from here —
 * the caller shows the result for confirmation and the normal inward / outward
 * flow does the actual write. Reused by the web screen and the mobile REST API.
 */
class VoiceEntryParser
{
    /** Words that mean goods came IN. */
    private const INWARD = [
        'received', 'receive', 'recieved', 'inward', 'inwards', 'purchase', 'purchased',
        'bought', 'buy', 'incoming', 'arrived', 'arrival', 'got', 'add', 'added', 'aaya',
        'aya', 'aayi', 'jama', 'stock in', 'stocked',
    ];

    /** Words that mean goods went OUT. */
    private const OUTWARD = [
        'dispatched', 'dispatch', 'despatch', 'sent', 'send', 'sold', 'sell', 'delivered',
        'deliver', 'delivery', 'outward', 'outwards', 'issued', 'issue', 'supplied', 'supply',
        'shipped', 'ship', 'removed', 'remove', 'gaya', 'naam', 'given', 'gave',
    ];

    /** Units that flag the quantity as a bag count. */
    private const BAG_UNITS = ['bags', 'bag', 'bora', 'bori', 'boras', 'sack', 'sacks', 'packet', 'packets', 'bundle', 'bundles'];

    /**
     * @param string $transcript  the raw spoken text
     * @param array  $products    [['id','name','sku','avg_weight'],...]
     * @param array  $warehouses  [['id','name'],...]
     * @param array  $parties     [['id','name','type'],...]
     * @return array structured, forgiving best-effort parse (see keys below)
     */
    public function parse(string $transcript, array $products, array $warehouses, array $parties): array
    {
        $raw  = trim($transcript);
        $norm = $this->normalise($raw);

        $direction = $this->detectDirection($norm);
        [$bags, $weight] = $this->detectQuantity($norm);
        $product   = $this->matchProduct($norm, $products);
        $warehouse = $this->matchWarehouse($norm, $warehouses);
        $party     = $this->matchParty($norm, $direction, $parties);

        // What is still missing before the entry can be saved?
        $missing = [];
        if ($direction === null)     { $missing[] = 'direction'; }
        if ($product === null)       { $missing[] = 'product'; }
        if ($bags === null || $bags <= 0) { $missing[] = 'bags'; }
        if ($warehouse === null)     { $missing[] = 'warehouse'; }

        // Confidence from how much of the core (direction + product + qty) resolved.
        $core = (int) ($direction !== null) + (int) ($product !== null) + (int) ($bags !== null && $bags > 0);
        $confidence = $core >= 3 ? 'high' : ($core === 2 ? 'medium' : 'low');

        return [
            'transcript'     => $raw,
            'direction'      => $direction,
            'bags'           => $bags,
            'weight'         => $weight,
            'product_id'     => $product['id']   ?? null,
            'product_name'   => $product['name'] ?? null,
            'warehouse_id'   => $warehouse['id']   ?? null,
            'warehouse_name' => $warehouse['name'] ?? null,
            'party_id'       => $party['id']   ?? null,
            'party_name'     => $party['name'] ?? null,
            'party_is_new'   => $party['is_new'] ?? false,
            'confidence'     => $confidence,
            'missing'        => $missing,
        ];
    }

    // -----------------------------------------------------------------
    // Normalisation
    // -----------------------------------------------------------------

    /** Lower-case, strip punctuation, turn number-words into digits, pad. */
    private function normalise(string $text): string
    {
        $text = strtolower($text);
        $text = str_replace(['-', '/', ',', '.', ';', ':', '"', "'", '(', ')'], ' ', $text);
        $text = $this->wordsToNumbers($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return ' ' . trim($text) . ' ';
    }

    /**
     * Convert English number-words to digits within a sentence, composing simple
     * runs ("one hundred fifty" → 150, "twenty five" → 25). Best effort.
     */
    private function wordsToNumbers(string $text): string
    {
        static $units = [
            'zero' => 0, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5,
            'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10, 'eleven' => 11,
            'twelve' => 12, 'thirteen' => 13, 'fourteen' => 14, 'fifteen' => 15, 'sixteen' => 16,
            'seventeen' => 17, 'eighteen' => 18, 'nineteen' => 19,
        ];
        static $tens = [
            'twenty' => 20, 'thirty' => 30, 'forty' => 40, 'fifty' => 50, 'sixty' => 60,
            'seventy' => 70, 'eighty' => 80, 'ninety' => 90,
        ];
        static $scales = ['hundred' => 100, 'thousand' => 1000, 'lakh' => 100000, 'lac' => 100000];

        $tokens = explode(' ', $text);
        $out    = [];
        $current = 0;      // running value of the current number phrase
        $inNumber = false; // are we mid-number?

        $flush = static function () use (&$out, &$current, &$inNumber) {
            if ($inNumber) {
                $out[] = (string) $current;
                $current = 0;
                $inNumber = false;
            }
        };

        foreach ($tokens as $tok) {
            if ($tok === 'and' && $inNumber) {
                continue; // "one hundred and fifty"
            }
            if (isset($units[$tok])) {
                $current += $units[$tok];
                $inNumber = true;
            } elseif (isset($tens[$tok])) {
                $current += $tens[$tok];
                $inNumber = true;
            } elseif (isset($scales[$tok])) {
                $current = ($current === 0 ? 1 : $current) * $scales[$tok];
                $inNumber = true;
            } else {
                $flush();
                $out[] = $tok;
            }
        }
        $flush();

        return implode(' ', $out);
    }

    // -----------------------------------------------------------------
    // Field extraction
    // -----------------------------------------------------------------

    private function detectDirection(string $norm): ?string
    {
        $inPos  = $this->earliestKeyword($norm, self::INWARD);
        $outPos = $this->earliestKeyword($norm, self::OUTWARD);

        if ($inPos === null && $outPos === null) {
            return null;
        }
        if ($inPos === null)  { return 'outward'; }
        if ($outPos === null) { return 'inward'; }
        return $inPos <= $outPos ? 'inward' : 'outward';
    }

    /** Position of the earliest of a set of keywords, or null. */
    private function earliestKeyword(string $norm, array $words): ?int
    {
        $best = null;
        foreach ($words as $w) {
            $pos = strpos($norm, ' ' . $w . ' ');
            if ($pos !== false && ($best === null || $pos < $best)) {
                $best = $pos;
            }
        }
        return $best;
    }

    /**
     * @return array{0: float|null, 1: float|null} [bags, weight]
     */
    private function detectQuantity(string $norm): array
    {
        $weight = null;
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:kg|kgs|kilo|kilos|kilogram|kilograms|quintal|quintals)/', $norm, $m)) {
            $weight = (float) $m[1];
        }

        $bags = null;
        // Prefer a number explicitly attached to a bag unit.
        $unitAlt = implode('|', self::BAG_UNITS);
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:' . $unitAlt . ')/', $norm, $m)) {
            $bags = (float) $m[1];
        } else {
            // Otherwise the first standalone number that is not the weight figure.
            if (preg_match_all('/\d+(?:\.\d+)?/', $norm, $all)) {
                foreach ($all[0] as $num) {
                    if ($weight === null || (float) $num !== $weight) {
                        $bags = (float) $num;
                        break;
                    }
                }
            }
        }

        return [$bags, $weight];
    }

    /** Best product whose name words appear in the sentence. */
    private function matchProduct(string $norm, array $products): ?array
    {
        $hay      = $this->stems($norm);
        $best     = null;
        $bestScore = 0.0;

        foreach ($products as $p) {
            // Exact SKU / QR token wins outright.
            $sku = strtolower(trim((string) ($p['sku'] ?? '')));
            if ($sku !== '' && strpos($norm, ' ' . $sku . ' ') !== false) {
                return ['id' => (int) $p['id'], 'name' => $p['name']];
            }

            $nameStems = $this->stems(strtolower((string) $p['name']));
            if ($nameStems === []) {
                continue;
            }
            $hit = 0;
            foreach ($nameStems as $w) {
                if (in_array($w, $hay, true)) {
                    $hit++;
                }
            }
            if ($hit === 0) {
                continue;
            }
            // Score favours matching more of the product's own words.
            $score = $hit / count($nameStems) + ($hit * 0.01);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = ['id' => (int) $p['id'], 'name' => $p['name']];
            }
        }

        // Require at least one whole word matched.
        return $bestScore >= 0.5 ? $best : null;
    }

    private function matchWarehouse(string $norm, array $warehouses): ?array
    {
        $hay       = $this->stems($norm);
        $best      = null;
        $bestScore = 0.0;
        foreach ($warehouses as $w) {
            $nameStems = $this->stems(strtolower((string) $w['name']));
            if ($nameStems === []) {
                continue;
            }
            $hit = 0;
            foreach ($nameStems as $s) {
                // Ignore generic filler words so "Main Godown" isn't matched on "godown".
                if (in_array($s, ['godown', 'warehouse', 'store', 'go down'], true)) {
                    continue;
                }
                if (in_array($s, $hay, true)) {
                    $hit++;
                }
            }
            if ($hit > 0 && $hit > $bestScore) {
                $bestScore = $hit;
                $best      = ['id' => (int) $w['id'], 'name' => $w['name']];
            }
        }
        return $best;
    }

    /**
     * Party after "from" (inward) or "to" (outward), fuzzily matched to the
     * company's own supplier/customer list; unknown names are returned as new.
     */
    private function matchParty(string $norm, ?string $direction, array $parties): ?array
    {
        $keyword = $direction === 'outward' ? 'to' : 'from';
        $phrase  = null;

        if (preg_match('/\b' . $keyword . '\s+(.+)$/', trim($norm), $m)) {
            $phrase = trim($m[1]);
        } elseif ($keyword === 'to' && preg_match('/\bfrom\s+(.+)$/', trim($norm), $m)) {
            $phrase = trim($m[1]); // spoken "from" even on a dispatch — still the party
        }
        if ($phrase === null || $phrase === '') {
            return null;
        }

        // Cut the name off at a location/preposition boundary or trailing noise
        // so "verma farm in cold store" yields just "verma farm".
        $phrase = trim(preg_replace('/\b(\d+.*)$/', '', $phrase));
        $phrase = trim(preg_replace('/\b(in|at|into|inside|for|godown|warehouse|store|rack|' . implode('|', self::BAG_UNITS) . ')\b.*$/', '', $phrase));
        if ($phrase === '') {
            return null;
        }

        // Best existing party by token overlap / similarity.
        $best = null; $bestScore = 0.0;
        foreach ($parties as $pt) {
            $name = strtolower((string) $pt['name']);
            similar_text($phrase, $name, $pct);
            $tokenHit = 0;
            foreach (explode(' ', $name) as $w) {
                if ($w !== '' && strpos(' ' . $phrase . ' ', ' ' . $w . ' ') !== false) {
                    $tokenHit++;
                }
            }
            $score = $pct + ($tokenHit * 25);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = $pt;
            }
        }

        if ($best !== null && $bestScore >= 60) {
            return ['id' => (int) $best['id'], 'name' => $best['name'], 'is_new' => false];
        }

        // Unknown → propose a new, title-cased party name.
        return ['id' => null, 'name' => ucwords($phrase), 'is_new' => true];
    }

    /** Word list of a string, each crudely singularised (trailing "s"/"es"). */
    private function stems(string $text): array
    {
        $out = [];
        foreach (explode(' ', trim($text)) as $w) {
            $w = trim($w);
            if ($w === '' || in_array($w, ['of', 'the', 'a', 'an', 'and'], true)) {
                continue;
            }
            if (strlen($w) > 4 && str_ends_with($w, 'es')) {
                $w = substr($w, 0, -2);
            } elseif (strlen($w) > 3 && str_ends_with($w, 's')) {
                $w = substr($w, 0, -1);
            }
            $out[] = $w;
        }
        return $out;
    }
}
