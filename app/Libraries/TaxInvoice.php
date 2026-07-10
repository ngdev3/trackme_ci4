<?php

namespace App\Libraries;

use App\Models\CompanyModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;

/**
 * Builds the data for a GST tax receipt from a paid payment order. Plan prices
 * are treated as GST-inclusive, so the taxable value and tax are derived from
 * the amount actually charged. Seller details are read from settings; the buyer
 * is the customer's firm (falling back to their account). Intra-state supply
 * splits into CGST+SGST, inter-state uses IGST.
 */
class TaxInvoice
{
    /**
     * @param array<string,mixed> $order A payment_orders row (paid).
     * @return array<string,mixed>
     */
    public static function build(array $order): array
    {
        helper('settings');

        $rate = (float) setting('invoice_tax_rate', 18);
        $rate = $rate >= 0 && $rate < 100 ? $rate : 18.0;

        $amount  = round((float) $order['amount'], 2);
        $taxable = $rate > 0 ? round($amount * 100 / (100 + $rate), 2) : $amount;
        $taxAmt  = round($amount - $taxable, 2);

        $seller = [
            'name'    => (string) setting('invoice_seller_name', setting('brand_name', setting('app_name', 'HisaabKitaab'))),
            'gstin'   => trim((string) setting('invoice_seller_gstin', '')),
            'address' => (string) setting('invoice_seller_address', ''),
            'state'   => (string) setting('invoice_seller_state', ''),
            'email'   => (string) setting('invoice_seller_email', setting('support_email', '')),
        ];

        $buyer = self::buyer((int) $order['customer_id']);

        // Place-of-supply logic: same state → CGST+SGST, else IGST.
        $intra = $seller['state'] !== '' && $buyer['state'] !== ''
            && strcasecmp(trim($seller['state']), trim($buyer['state'])) === 0;

        $plan = (new SubscriptionPlanModel())->find((int) $order['plan_id']);

        return [
            'is_tax_invoice' => $seller['gstin'] !== '',
            'invoice_no'     => (string) ($order['invoice_no'] ?? ''),
            'invoice_date'   => (string) ($order['invoice_date'] ?? $order['updated_at'] ?? ''),
            'order_id'       => (string) $order['order_id'],
            'cf_payment_id'  => (string) ($order['cf_payment_id'] ?? ''),
            'seller'         => $seller,
            'buyer'          => $buyer,
            'item'           => [
                'name'   => ($plan['name'] ?? 'Subscription') . ' plan subscription',
                'cycle'  => $plan['billing_cycle'] ?? 'yearly',
                'hsn'    => (string) setting('invoice_hsn_sac', '998314'), // SAC: IT/software services
            ],
            'currency'   => (string) ($order['currency'] ?? 'INR'),
            'amount'     => $amount,
            'taxable'    => $taxable,
            'rate'       => $rate,
            'intra'      => $intra,
            'cgst'       => $intra ? round($taxAmt / 2, 2) : 0.0,
            'sgst'       => $intra ? round($taxAmt / 2, 2) : 0.0,
            'igst'       => $intra ? 0.0 : $taxAmt,
            'tax_total'  => $taxAmt,
            'status'     => (string) $order['status'],
            'refunded'   => (int) ($order['refunded'] ?? 0) === 1,
        ];
    }

    /** Resolve the buyer (customer's firm, else their account). */
    private static function buyer(int $customerId): array
    {
        $company = (new CompanyModel())->where('owner_id', $customerId)->orderBy('id', 'ASC')->first();
        $user    = (new UserModel())->find($customerId);

        return [
            'name'    => $company['name'] ?? ($user['name'] ?? 'Customer'),
            'gstin'   => trim((string) ($company['gst_number'] ?? '')),
            'address' => (string) ($company['address'] ?? ''),
            'state'   => (string) ($company['state'] ?? ''),
            'email'   => (string) ($user['email'] ?? ''),
            'mobile'  => (string) ($user['mobile'] ?? ($company['mobile'] ?? '')),
        ];
    }

    /** Format money for display. */
    public static function money(float $n): string
    {
        return number_format($n, 2);
    }
}
