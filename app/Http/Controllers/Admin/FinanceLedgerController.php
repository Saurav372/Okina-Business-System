<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FinanceLedgerController extends Controller
{
    /**
     * Display Customer Ledger
     */
    public function customerLedger(Request $request)
    {
        Gate::authorize('finance.view_ledgers');

        $search = $request->query('search', '');

        $customers = Customer::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('display_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->get()
            ->map(function (Customer $customer) {
                $orders = Order::where('customer_id', $customer->id)->get();
                $payments = Payment::where('customer_id', $customer->id)->where('status', 'succeeded')->get();
                $refunds = Refund::whereHas('order', function ($q) use ($customer) {
                    $q->where('customer_id', $customer->id);
                })->where('status', 'succeeded')->get();

                $totalInvoicedMinor = $orders->sum('total_amount_minor');
                $totalPaidMinor = $payments->sum('amount_minor');
                $totalRefundedMinor = $refunds->sum('amount_minor');
                $outstandingBalanceMinor = $totalInvoicedMinor - $totalPaidMinor + $totalRefundedMinor;

                return [
                    'id' => $customer->id,
                    'public_id' => $customer->public_id,
                    'name' => $customer->display_name ?? $customer->name,
                    'email' => $customer->email,
                    'total_invoiced' => $totalInvoicedMinor / 100,
                    'total_paid' => $totalPaidMinor / 100,
                    'total_refunded' => $totalRefundedMinor / 100,
                    'outstanding_balance' => $outstandingBalanceMinor / 100,
                ];
            });

        if ($request->wantsJson()) {
            return response()->json($customers);
        }

        return view('admin.accounting.customer_ledger', compact('customers', 'search'));
    }

    /**
     * Display Vendor Ledger
     */
    public function vendorLedger(Request $request)
    {
        Gate::authorize('finance.view_ledgers');

        $search = $request->query('search', '');

        $vendors = Vendor::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('vendor_code', 'like', "%{$search}%");
            })
            ->get()
            ->map(function (Vendor $vendor) {
                $pos = VendorOrder::where('vendor_id', $vendor->id)->get();
                $payments = VendorPayment::whereHas('purchaseOrder', function ($q) use ($vendor) {
                    $q->where('vendor_id', $vendor->id);
                })->get();

                $totalPOValueMinor = $pos->sum('total_amount_minor');
                $totalPaidMinor = $payments->sum('amount_minor');
                $outstandingMinor = $totalPOValueMinor - $totalPaidMinor;

                return [
                    'id' => $vendor->id,
                    'vendor_code' => $vendor->vendor_code,
                    'name' => $vendor->name,
                    'status' => $vendor->status,
                    'total_po_value' => $totalPOValueMinor / 100,
                    'total_paid' => $totalPaidMinor / 100,
                    'outstanding_balance' => $outstandingMinor / 100,
                ];
            });

        if ($request->wantsJson()) {
            return response()->json($vendors);
        }

        return view('admin.accounting.vendor_ledger', compact('vendors', 'search'));
    }

    /**
     * Display Business Ledger (Cashflow summaries & general ledger items)
     */
    public function businessLedger(Request $request)
    {
        Gate::authorize('finance.view_ledgers');

        // Summary metrics
        $salesMinor = Order::where('status', '!=', 'cancelled')->sum('total_amount_minor');
        $collectionsMinor = Payment::where('status', 'succeeded')->sum('amount_minor');
        $refundsMinor = Refund::where('status', 'succeeded')->sum('amount_minor');
        $expensesMinor = Expense::where('approval_status', 'approved')->sum('amount_minor');
        $vendorPayoutsMinor = VendorPayment::sum('amount_minor');

        $netCashflowMinor = $collectionsMinor - $refundsMinor - $expensesMinor - $vendorPayoutsMinor;

        // Fetch recent ledger transactions (orders, payments, refunds, expenses, vendor payments)
        $transactions = collect();

        // 1. Sales Orders
        Order::where('status', '!=', 'cancelled')
            ->latest()
            ->limit(20)
            ->get()
            ->each(function (Order $order) use ($transactions) {
                $transactions->push([
                    'date' => $order->placed_at ?? $order->created_at,
                    'type' => 'Sales Order',
                    'reference' => $order->public_id,
                    'description' => 'Sales Order to '.($order->customer_snapshot['name'] ?? 'Customer'),
                    'debit' => $order->total_amount_minor / 100,
                    'credit' => 0,
                ]);
            });

        // 2. Collections
        Payment::where('status', 'succeeded')
            ->latest()
            ->limit(20)
            ->get()
            ->each(function (Payment $payment) use ($transactions) {
                $transactions->push([
                    'date' => $payment->paid_at ?? $payment->created_at,
                    'type' => 'Customer Payment',
                    'reference' => $payment->provider_payment_id ?? $payment->id,
                    'description' => 'Payment received via '.$payment->method,
                    'debit' => 0,
                    'credit' => $payment->amount_minor / 100,
                ]);
            });

        // 3. Refunds
        Refund::where('status', 'succeeded')
            ->latest()
            ->limit(20)
            ->get()
            ->each(function (Refund $refund) use ($transactions) {
                $transactions->push([
                    'date' => $refund->processed_at ?? $refund->created_at,
                    'type' => 'Refund',
                    'reference' => $refund->provider_refund_id ?? $refund->id,
                    'description' => 'Refund processed',
                    'debit' => $refund->amount_minor / 100,
                    'credit' => 0,
                ]);
            });

        // 4. Expenses
        Expense::where('approval_status', 'approved')
            ->latest()
            ->limit(20)
            ->get()
            ->each(function (Expense $expense) use ($transactions) {
                $transactions->push([
                    'date' => $expense->expense_date ?? $expense->created_at,
                    'type' => 'Expense',
                    'reference' => $expense->public_id,
                    'description' => $expense->description ?? 'Business expense',
                    'debit' => $expense->amount_minor / 100,
                    'credit' => 0,
                ]);
            });

        // 5. Vendor Payments
        VendorPayment::latest()
            ->limit(20)
            ->get()
            ->each(function (VendorPayment $vp) use ($transactions) {
                $transactions->push([
                    'date' => $vp->paid_at ?? $vp->created_at,
                    'type' => 'Vendor Payment',
                    'reference' => $vp->id,
                    'description' => 'Payment to Vendor for Purchase Order '.($vp->purchaseOrder?->public_id ?? ''),
                    'debit' => $vp->amount_minor / 100,
                    'credit' => 0,
                ]);
            });

        // Sort transactions by date descending
        $recentTransactions = $transactions->sortByDesc('date')->values()->take(30);

        $summary = [
            'total_sales' => $salesMinor / 100,
            'total_collections' => $collectionsMinor / 100,
            'total_refunds' => $refundsMinor / 100,
            'total_expenses' => $expensesMinor / 100,
            'total_vendor_payouts' => $vendorPayoutsMinor / 100,
            'net_cashflow' => $netCashflowMinor / 100,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'summary' => $summary,
                'transactions' => $recentTransactions,
            ]);
        }

        return view('admin.accounting.business_ledger', compact('summary', 'recentTransactions'));
    }
}
