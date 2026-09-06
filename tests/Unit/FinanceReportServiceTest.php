<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\FinanceReportService;
use App\Support\Finance\FinanceReportFilters;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinanceReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private FinanceReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FinanceReportService;
    }

    /**
     * Test order status allowlist for Booked Sales Revenue.
     * Only Confirmed, InProduction, ReadyToShip, Shipped, Delivered are included.
     * PendingPayment, Cancelled, and Refunded (fully) are excluded.
     */
    public function test_booked_sales_order_status_allowlist(): void
    {
        $customer = Customer::factory()->create();

        // Included statuses (100 INR each -> 50000 minor total)
        foreach ([OrderStatus::Confirmed, OrderStatus::InProduction, OrderStatus::ReadyToShip, OrderStatus::Shipped, OrderStatus::Delivered] as $status) {
            Order::factory()->create([
                'customer_id' => $customer->id,
                'status' => $status->value(),
                'total_amount_minor' => 10000,
                'placed_at' => CarbonImmutable::parse('2026-06-15 10:00:00'),
            ]);
        }

        // Excluded statuses (PendingPayment, Cancelled)
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value(),
            'total_amount_minor' => 10000,
            'placed_at' => CarbonImmutable::parse('2026-06-15 10:00:00'),
        ]);
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Cancelled->value(),
            'total_amount_minor' => 10000,
            'placed_at' => CarbonImmutable::parse('2026-06-15 10:00:00'),
        ]);

        $filters = FinanceReportFilters::fromArray([
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]);

        $summary = $this->service->generateSummary($filters);

        $this->assertEquals('50000', $summary->metrics['total_sales_minor']);
        $this->assertEquals(5, $summary->metrics['total_orders_count']);
    }

    /**
     * Test per-order receivables accounting:
     * Overpaid order does NOT offset unpaid order balance.
     */
    public function test_per_order_receivables_clamping_and_net_accounting(): void
    {
        $customer = Customer::factory()->create();

        // Order 1: 1000 INR total, 1200 INR paid (Overpaid by 200 INR -> Receivable clamped to 0)
        $order1 = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'total_amount_minor' => 100000,
            'placed_at' => CarbonImmutable::parse('2026-06-10 10:00:00'),
        ]);
        Payment::create([
            'order_id' => $order1->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'manual',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 120000,
            'currency' => 'INR',
            'paid_at' => CarbonImmutable::parse('2026-06-10 10:05:00'),
        ]);

        // Order 2: 500 INR total, 200 INR paid, 50 INR refunded (Net paid = 150 INR -> Receivable = 350 INR)
        $order2 = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'total_amount_minor' => 50000,
            'placed_at' => CarbonImmutable::parse('2026-06-15 10:00:00'),
        ]);
        $p2 = Payment::create([
            'order_id' => $order2->id,
            'payment_type' => Payment::TYPE_PARTIAL,
            'provider' => 'manual',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 20000,
            'currency' => 'INR',
            'paid_at' => CarbonImmutable::parse('2026-06-15 10:05:00'),
        ]);
        Refund::create([
            'order_id' => $order2->id,
            'payment_id' => $p2->id,
            'provider' => 'manual',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_SUCCEEDED,
            'amount_minor' => 5000,
            'currency' => 'INR',
            'processed_at' => CarbonImmutable::parse('2026-06-16 10:00:00'),
        ]);

        $filters = FinanceReportFilters::fromArray([
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]);

        $summary = $this->service->generateSummary($filters);

        // Total receivables should be 0 (Order 1) + 35000 (Order 2) = 35000 minor (350 INR)
        $this->assertEquals('35000', $summary->metrics['total_outstanding_minor']);
    }

    /**
     * Test settlement timestamp priority over created_at for payments & refunds.
     */
    public function test_settlement_timestamp_priority(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'placed_at' => CarbonImmutable::parse('2026-06-01'),
        ]);

        // Payment created in May, but paid_at in June -> Included in June filter
        $payment = new Payment([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'manual',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 25000,
            'currency' => 'INR',
            'paid_at' => CarbonImmutable::parse('2026-06-05 12:00:00'),
        ]);
        $payment->created_at = CarbonImmutable::parse('2026-05-28 12:00:00');
        $payment->save();

        $filters = FinanceReportFilters::fromArray([
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]);

        $summary = $this->service->generateSummary($filters);

        $this->assertEquals('25000', $summary->metrics['total_payments_minor']);
    }

    /**
     * Test Indian fiscal year resolution (April 1 to March 31).
     */
    public function test_fiscal_year_resolution(): void
    {
        config(['finance.fiscal_year_start_month' => 4]);

        $filters = FinanceReportFilters::fromArray([
            'preset' => 'current_fiscal_year',
        ]);

        $nowMonth = (int) now('Asia/Kolkata')->format('n');
        $nowYear = (int) now('Asia/Kolkata')->format('Y');

        if ($nowMonth >= 4) {
            $expectedStart = "{$nowYear}-04-01";
        } else {
            $prevYear = $nowYear - 1;
            $expectedStart = "{$prevYear}-04-01";
        }

        $this->assertEquals($expectedStart, $filters->startDate->toDateString());
    }

    /**
     * Test zero-filled monthly trend continuous sequence.
     */
    public function test_zero_filled_monthly_trend(): void
    {
        $customer = Customer::factory()->create();

        // Order in 2026-04
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'total_amount_minor' => 10000,
            'placed_at' => CarbonImmutable::parse('2026-04-10'),
        ]);

        // Order in 2026-06
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'total_amount_minor' => 20000,
            'placed_at' => CarbonImmutable::parse('2026-06-10'),
        ]);

        $filters = FinanceReportFilters::fromArray([
            'start_date' => '2026-04-01',
            'end_date' => '2026-06-30',
        ]);

        $summary = $this->service->generateSummary($filters);

        // Must contain 2026-04, 2026-05 (zero-filled), 2026-06
        $this->assertCount(3, $summary->monthlyTrend);
        $this->assertEquals('2026-04', $summary->monthlyTrend[0]['period']);
        $this->assertEquals('10000', $summary->monthlyTrend[0]['sales_minor']);

        $this->assertEquals('2026-05', $summary->monthlyTrend[1]['period']);
        $this->assertEquals('0', $summary->monthlyTrend[1]['sales_minor']);

        $this->assertEquals('2026-06', $summary->monthlyTrend[2]['period']);
        $this->assertEquals('20000', $summary->monthlyTrend[2]['sales_minor']);
    }

    /**
     * Test expense category basis points calculation and soft-deleted category inclusion.
     */
    public function test_expense_category_basis_points_and_soft_deleted_categories(): void
    {
        $cat1 = ExpenseCategory::factory()->create(['name' => 'Marketing', 'code' => 'MARKETING_ADS']);
        $cat2 = ExpenseCategory::factory()->create(['name' => 'Office', 'code' => 'OFFICE_SUPPLIES']);

        // Expense 1: 300 INR
        Expense::factory()->create([
            'expense_category_id' => $cat1->id,
            'status' => Expense::STATUS_APPROVED,
            'amount_minor' => 30000,
            'occurred_at' => CarbonImmutable::parse('2026-06-10'),
        ]);

        // Expense 2: 100 INR
        Expense::factory()->create([
            'expense_category_id' => $cat2->id,
            'status' => Expense::STATUS_APPROVED,
            'amount_minor' => 10000,
            'occurred_at' => CarbonImmutable::parse('2026-06-15'),
        ]);

        // Soft delete Category 2
        $cat2->delete();

        $filters = FinanceReportFilters::fromArray([
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]);

        $summary = $this->service->generateSummary($filters);

        // Cat 1: 30000 / 40000 = 75% -> 7500 bps
        // Cat 2: 10000 / 40000 = 25% -> 2500 bps
        $this->assertCount(2, $summary->categoryBreakdown);

        $this->assertEquals('MARKETING_ADS', $summary->categoryBreakdown[0]['category_code']);
        $this->assertEquals(7500, $summary->categoryBreakdown[0]['share_basis_points']);
        $this->assertFalse($summary->categoryBreakdown[0]['is_deleted']);

        $this->assertEquals('OFFICE_SUPPLIES', $summary->categoryBreakdown[1]['category_code']);
        $this->assertEquals(2500, $summary->categoryBreakdown[1]['share_basis_points']);
        $this->assertTrue($summary->categoryBreakdown[1]['is_deleted']);
    }

    /**
     * Verify bounded aggregate SQL query efficiency (no N+1 loops).
     */
    public function test_bounded_query_count(): void
    {
        $customer = Customer::factory()->create();

        // Create multiple orders, payments, refunds, and expenses
        for ($i = 0; $i < 5; $i++) {
            $order = Order::factory()->create([
                'customer_id' => $customer->id,
                'status' => OrderStatus::Confirmed->value(),
                'total_amount_minor' => 10000,
                'placed_at' => CarbonImmutable::parse('2026-06-10'),
            ]);
            $p = Payment::create([
                'order_id' => $order->id,
                'payment_type' => Payment::TYPE_FULL,
                'provider' => 'manual',
                'status' => Payment::STATUS_SUCCEEDED,
                'amount_minor' => 10000,
                'currency' => 'INR',
                'paid_at' => CarbonImmutable::parse('2026-06-10'),
            ]);
            Refund::create([
                'order_id' => $order->id,
                'payment_id' => $p->id,
                'provider' => 'manual',
                'refund_type' => Refund::TYPE_FULL,
                'status' => Refund::STATUS_SUCCEEDED,
                'amount_minor' => 2000,
                'currency' => 'INR',
                'processed_at' => CarbonImmutable::parse('2026-06-11'),
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $filters = FinanceReportFilters::fromArray([
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]);

        $this->service->generateSummary($filters);

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Bounded aggregate queries (should be <= 18 queries total, regardless of order count)
        $this->assertLessThanOrEqual(18, $queryCount);
    }
}
