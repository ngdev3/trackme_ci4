<?php

use App\Models\TransactionModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regression tests for the QA hardening applied to the Jama/Naam ledger.
 *
 *  - F-7: a single amount has a sane upper bound (fat-finger guard).
 *  - F-2: every read path on the model excludes soft-deleted rows, so totals
 *         can never silently include deleted money.
 *
 * @internal
 */
final class TransactionHardeningTest extends CIUnitTestCase
{
    private function amountAccepted(string $amount): bool
    {
        $rules = (new TransactionModel())->getValidationRules();
        $v     = service('validation');
        $v->reset();
        $v->setRules(['amount' => $rules['amount']]);

        return $v->run(['amount' => $amount]);
    }

    /** F-7: absurd amounts are rejected, real ones pass. */
    public function testAmountCeilingRejectsFatFingerValues(): void
    {
        $this->assertFalse($this->amountAccepted('10001200099.99'), 'A ~₹1000-crore entry should be rejected.');
        $this->assertFalse($this->amountAccepted('0'), 'Zero should be rejected.');
        $this->assertFalse($this->amountAccepted('-50'), 'Negative amounts should be rejected.');

        $this->assertTrue($this->amountAccepted('5000.00'), 'A normal amount should pass.');
        $this->assertTrue($this->amountAccepted('9999999999.99'), 'The documented ceiling should pass.');
    }

    /** F-7: the ceiling constant fits DECIMAL(15,2) and matches the rule. */
    public function testMaxAmountConstantIsWithinColumnPrecision(): void
    {
        $this->assertLessThanOrEqual(9999999999999.99, TransactionModel::MAX_AMOUNT);
        $this->assertSame(9999999999.99, TransactionModel::MAX_AMOUNT);
    }

    /**
     * F-2: the model's scope helper must always filter out soft-deleted rows.
     * We assert the compiled SQL of a scoped query carries the deleted_at guard,
     * so a future refactor can't quietly drop it and re-inflate the totals.
     */
    public function testScopedQueriesExcludeSoftDeletedRows(): void
    {
        $model   = new TransactionModel();
        $builder = (new \ReflectionMethod($model, 'scopedBuilder'))->invoke($model, 19);
        $sql     = $builder->select('id')->getCompiledSelect();

        $this->assertStringContainsString('deleted_at', $sql, 'Scoped ledger queries must exclude soft-deleted rows.');
        $this->assertStringContainsString('company_id', $sql, 'Scoped ledger queries must be company-scoped.');
    }
}
