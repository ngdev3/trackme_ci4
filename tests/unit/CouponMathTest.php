<?php

use App\Libraries\Coupon;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regression tests for coupon discount maths. A discount must never exceed the
 * amount (a customer can't be charged a negative price) and must honour the
 * optional percentage cap.
 *
 * @internal
 */
final class CouponMathTest extends CIUnitTestCase
{
    private function coupon(array $overrides = []): array
    {
        return array_merge([
            'kind'           => 'discount',
            'discount_type'  => 'percent',
            'discount_value' => 10,
            'max_discount'   => null,
        ], $overrides);
    }

    public function testPercentDiscount(): void
    {
        $off = (new Coupon())->computeDiscount($this->coupon(['discount_value' => 20]), 1000.0);
        $this->assertSame(200.0, $off);
    }

    public function testPercentDiscountHonoursCap(): void
    {
        $off = (new Coupon())->computeDiscount(
            $this->coupon(['discount_value' => 50, 'max_discount' => 300]),
            1000.0
        );
        $this->assertSame(300.0, $off, 'A capped percentage discount must not exceed max_discount.');
    }

    public function testFixedDiscountNeverExceedsAmount(): void
    {
        $off = (new Coupon())->computeDiscount(
            $this->coupon(['discount_type' => 'fixed', 'discount_value' => 5000]),
            1000.0
        );
        $this->assertSame(1000.0, $off, 'A fixed discount larger than the price must cap at the price, never go negative.');
    }

    public function testNonDiscountOrZeroAmountGivesNothing(): void
    {
        $c = new Coupon();
        $this->assertSame(0.0, $c->computeDiscount($this->coupon(['kind' => 'redeem']), 1000.0));
        $this->assertSame(0.0, $c->computeDiscount($this->coupon(), 0.0));
        $this->assertSame(0.0, $c->computeDiscount($this->coupon(), -50.0));
    }
}
