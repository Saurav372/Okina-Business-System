<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\Refund;
use App\Services\FinanceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinanceReportTest extends TestCase
{
    use RefreshDatabase;

    private FinanceReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = new FinanceReportService;
    }

    /**
     * Test report summary on an empty database.
     */
    public function test_report_empty_dataset(): void
    {
        $report = $this->reportService->generateSummary([]);

        $this->assertEquals('INR', $report['currency']);
        $this->assertEquals(0, $report['summary']['total_sales_minor']);
        $this->assertEquals(0, $report['summary']['total_payments_minor']);
        $this->assertEquals(0, $report['summary']['total_refunds_minor']);
        $this->assertEquals(0, $report['summary']['total_expenses_minor']);
        $this->assertEquals(0, $report['summary']['total_outstanding_minor']);
        $this->assertEquals(0, $report['summary']['total_orders_count']);
        $this->assertEquals(0, $report['summary']['total_payments_count']);
        $this->assertEquals(0, $report['summary']['total_refunds_count']);
        $this->assertEquals(0, $report['summary']['total_expenses_count']);
    }

    /**
     * Test outstanding balance calculations under various payment states.
     */
    public function test_outstanding_balance_calculations(): void
    {
        $customer = Customer::factory()->create();

        // 1. Order with partial payments
        // Order: 1000 INR (100000 minor)
        $order1 = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'total_amount_minor' => 100000,
            'placed_at' => Carbon::parse('2026-06-15 10:00:00'),
            'created_at' => Carbon::parse('2026-06-15 10:00:00'),
        ]);

        // Payment 1: 200 INR succeeded
        $p1 = new Payment([
            'order_id' => $order1->id,
            'payment_type' => Payment::TYPE_PARTIAL,
            'provider' => 'manual',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 20000,
            'currency' => 'INR',
        ]);
        $p1->created_at = Carbon::parse('2026-06-15 10:05:00');
        $p1->save();

        // Payment 2: 300 INR succeeded
        $p2 = new Payment([
            'order_id' => $order1->id,
            'payment_type' => Payment::TYPE_PARTIAL,
            'provider' => 'manual',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 30000,
            'currency' => 'INR',
        ]);
        $p2->created_at = Carbon::parse('2026-06-15 10:10:00');
        $p2->save();

        // Payment 3: 400 INR failed/pending -> should be excluded
        $p3 = new Payment([
            'order_id' => $order1->id,
            'payment_type' => Payment::TYPE_PARTIAL,
            'provider' => 'manual',
            'status' => Payment::STATUS_FAILED,
            'amount_minor' => 40000,
            'currency' => 'INR',
        ]);
        $p3->created_at = Carbon::parse('2026-06-15 10:15:00');
        $p3->save();

        // Outstanding balance should be 100000 - 50000 = 50000 minor
        $report1 = $this->reportService->generateSummary([]);
        $this->assertEquals(100000, $report1['summary']['total_sales_minor']);
        $this->assertEquals(50000, $report1['summary']['total_payments_minor']);
        $this->assertEquals(50000, $report1['summary']['total_outstanding_minor']);

        // 2. Overpayment scenario (Outstanding clamped to 0)
        // Add another succeeded payment of 600 INR (60000 minor) -> total payments = 1100 INR
        $p4 = new Payment([
            'order_id' => $order1->id,
            'payment_type' => Payment::TYPE_PARTIAL,
            'provider' => 'manual',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 60000,
            'currency' => 'INR',
        ]);
        $p4->created_at = Carbon::parse('2026-06-15 10:20:00');
        $p4->save();

        $report2 = $this->reportService->generateSummary([]);
        $this->assertEquals(110000, $report2['summary']['total_payments_minor']);
        $this->assertEquals(0, $report2['summary']['total_outstanding_minor']); // Clamped to zero
    }

    /**
     * Test that draft and cancelled orders are excluded from sales and outstanding balances.
     */
    public function test_draft_and_cancelled_orders_excluded(): void
    {
        $customer = Customer::factory()->create();

        // Cancelled order (1500 INR)
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Cancelled->value(),
            'total_amount_minor' => 150000,
            'placed_at' => Carbon::parse('2026-06-15 10:00:00'),
            'created_at' => Carbon::parse('2026-06-15 10:00:00'),
        ]);

        // Pending payment (draft) order (2000 INR)
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value(),
            'total_amount_minor' => 200000,
            'placed_at' => Carbon::parse('2026-06-15 10:00:00'),
            'created_at' => Carbon::parse('2026-06-15 10:00:00'),
        ]);

        // Valid order (500 INR)
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'total_amount_minor' => 50000,
            'placed_at' => Carbon::parse('2026-06-15 10:00:00'),
            'created_at' => Carbon::parse('2026-06-15 10:00:00'),
        ]);

        $report = $this->reportService->generateSummary([]);
        $this->assertEquals(50000, $report['summary']['total_sales_minor']);
        $this->assertEquals(1, $report['summary']['total_orders_count']);
    }

    /**
     * Test date range filters are inclusive and apply to correct columns.
     */
    public function test_inclusive_date_filters(): void
    {
        $customer = Customer::factory()->create();

        // Order 1: 2026-06-01 (In range)
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'total_amount_minor' => 10000,
            'placed_at' => Carbon::parse('2026-06-01 00:00:00'),
            'created_at' => Carbon::parse('2026-06-01 00:00:00'),
        ]);

        // Order 2: 2026-06-10 (In range - boundary)
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'total_amount_minor' => 20000,
            'placed_at' => Carbon::parse('2026-06-10 23:59:59'),
            'created_at' => Carbon::parse('2026-06-10 23:59:59'),
        ]);

        // Order 3: 2026-06-11 (Out of range)
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'total_amount_minor' => 30000,
            'placed_at' => Carbon::parse('2026-06-11 00:00:01'),
            'created_at' => Carbon::parse('2026-06-11 00:00:01'),
        ]);

        $report = $this->reportService->generateSummary([
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-10',
        ]);

        $this->assertEquals(30000, $report['summary']['total_sales_minor']);
        $this->assertEquals(2, $report['summary']['total_orders_count']);
    }

    /**
     * Test monthly grouping and chronological sorting across years.
     */
    public function test_monthly_grouping_chronological_sorting(): void
    {
        $customer = Customer::factory()->create();

        // 1. Order in 2025-12
        $order1 = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'total_amount_minor' => 10000,
            'placed_at' => Carbon::parse('2025-12-15 12:00:00'),
            'created_at' => Carbon::parse('2025-12-15 12:00:00'),
        ]);

        // 2. Order in 2026-01
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'total_amount_minor' => 20000,
            'placed_at' => Carbon::parse('2026-01-05 12:00:00'),
            'created_at' => Carbon::parse('2026-01-05 12:00:00'),
        ]);

        // 3. Payment in 2025-12 (explicit created_at set)
        $payment = new Payment([
            'order_id' => $order1->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'manual',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 5000,
            'currency' => 'INR',
        ]);
        $payment->created_at = Carbon::parse('2025-12-20 12:00:00');
        $payment->save();

        // 4. Expense in 2026-01
        $category = ExpenseCategory::factory()->create();
        Expense::factory()->create([
            'expense_category_id' => $category->id,
            'status' => Expense::STATUS_APPROVED,
            'amount_minor' => 3000,
            'occurred_at' => Carbon::parse('2026-01-10'),
        ]);

        $report = $this->reportService->generateSummary(['group_by' => 'month']);

        $this->assertCount(2, $report['monthly']);

        // First month must be 2025-12 (chronological order check)
        $this->assertEquals('2025-12', $report['monthly'][0]['month']);
        $this->assertEquals(10000, $report['monthly'][0]['totals']['sales_minor']);
        $this->assertEquals(5000, $report['monthly'][0]['totals']['payments_minor']);
        $this->assertEquals(0, $report['monthly'][0]['totals']['expenses_minor']);

        // Second month must be 2026-01
        $this->assertEquals('2026-01', $report['monthly'][1]['month']);
        $this->assertEquals(20000, $report['monthly'][1]['totals']['sales_minor']);
        $this->assertEquals(0, $report['monthly'][1]['totals']['payments_minor']);
        $this->assertEquals(3000, $report['monthly'][1]['totals']['expenses_minor']);
    }

    /**
     * Test sales and expenses grouped by categories.
     */
    public function test_category_grouping(): void
    {
        $customer = Customer::factory()->create();

        // Create categories
        $cat1 = ProductCategory::factory()->create(['name' => 'Apparel', 'slug' => 'apparel']);
        $cat2 = ProductCategory::factory()->create(['name' => 'Signage', 'slug' => 'signage']);

        $prod1 = Product::factory()->create(['primary_category_id' => $cat1->id]);
        $prod2 = Product::factory()->create(['primary_category_id' => $cat2->id]);

        $sku1 = ProductSku::factory()->create(['product_id' => $prod1->id]);
        $sku2 = ProductSku::factory()->create(['product_id' => $prod2->id]);

        // Order 1 with Apparel item
        $order1 = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'placed_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $prod1->id,
            'sku_id' => $sku1->id,
            'quantity' => 1,
            'line_total_minor' => 15000,
            'product_name_snapshot' => 'Apparel',
            'product_slug_snapshot' => 'apparel',
            'sku_code_snapshot' => 'APP-1',
            'customization_fingerprint' => 'fingerprint-1',
            'customization_snapshot' => [],
        ]);

        // Order 2 with Signage item
        $order2 = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'placed_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $prod2->id,
            'sku_id' => $sku2->id,
            'quantity' => 1,
            'line_total_minor' => 25000,
            'product_name_snapshot' => 'Signage',
            'product_slug_snapshot' => 'signage',
            'sku_code_snapshot' => 'SIG-1',
            'customization_fingerprint' => 'fingerprint-2',
            'customization_snapshot' => [],
        ]);

        // Expense Category
        $expCat = ExpenseCategory::factory()->create(['name' => 'Marketing', 'public_id' => 'EXPCAT-1']);
        Expense::factory()->create([
            'expense_category_id' => $expCat->id,
            'status' => Expense::STATUS_APPROVED,
            'amount_minor' => 4500,
            'occurred_at' => now(),
        ]);

        $report = $this->reportService->generateSummary(['group_by' => 'category']);

        // Check Sales By Category
        $this->assertCount(2, $report['sales_by_category']);
        $this->assertEquals('apparel', $report['sales_by_category'][0]['category']['slug']);
        $this->assertEquals(15000, $report['sales_by_category'][0]['total_sales_minor']);

        $this->assertEquals('signage', $report['sales_by_category'][1]['category']['slug']);
        $this->assertEquals(25000, $report['sales_by_category'][1]['total_sales_minor']);

        // Check Expenses By Category
        $this->assertCount(1, $report['expenses_by_category']);
        $this->assertEquals('EXPCAT-1', $report['expenses_by_category'][0]['category']['public_id']);
        $this->assertEquals(4500, $report['expenses_by_category'][0]['total_expenses_minor']);
    }

    /**
     * Test succeeded vs other statuses for refunds.
     */
    public function test_refund_status_filtering(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value(),
            'placed_at' => now(),
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'manual',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 50000,
            'currency' => 'INR',
        ]);

        // Succeeded refund -> included
        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'manual',
            'refund_type' => Refund::TYPE_FULL,
            'status' => Refund::STATUS_SUCCEEDED,
            'amount_minor' => 15000,
            'currency' => 'INR',
        ]);

        // Pending refund -> excluded
        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'manual',
            'refund_type' => Refund::TYPE_FULL,
            'status' => Refund::STATUS_PROCESSING,
            'amount_minor' => 20000,
            'currency' => 'INR',
        ]);

        $report = $this->reportService->generateSummary([]);
        $this->assertEquals(15000, $report['summary']['total_refunds_minor']);
        $this->assertEquals(1, $report['summary']['total_refunds_count']);
    }
}
