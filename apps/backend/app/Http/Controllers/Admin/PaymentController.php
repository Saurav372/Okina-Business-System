<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentLedgerIndexRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Support\Finance\FinanceDashboardSummary;
use App\Support\Finance\PaymentCatalog;
use App\Support\Finance\PaymentFilters;
use App\Support\Finance\PaymentMetrics;
use App\Support\Finance\RefundFilters;
use App\Support\Finance\RefundMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentCatalog $catalog
    ) {}

    public function index(PaymentLedgerIndexRequest $request)
    {
        Gate::authorize('viewAny', Payment::class);

        $filters = new PaymentFilters($request->all());
        $paymentMetrics = new PaymentMetrics($filters);
        $refundMetrics = new RefundMetrics(new RefundFilters($request->all()));
        $summary = new FinanceDashboardSummary($paymentMetrics, $refundMetrics);
        $payments = $this->catalog->getPaginatedPayments($filters, $request->integer('per_page', 25));

        if ($request->wantsJson()) {
            return PaymentResource::collection($payments)->additional([
                'meta' => [
                    'total_amount_minor' => $summary->grossCollectionsMinor,
                    'total_gateway_fee_minor' => $summary->totalGatewayFeesMinor,
                    'net_revenue_minor' => $summary->netRevenueMinor,
                ],
            ]);
        }

        return view('admin.payments.index', [
            'filters' => $filters,
            'summary' => $summary,
            'payments' => $payments,
        ]);
    }

    public function show(Request $request, Payment $payment)
    {
        Gate::authorize('view', $payment);

        $payment->load(['order.customer', 'refunds']);

        if ($request->wantsJson()) {
            return new PaymentResource($payment);
        }

        return view('admin.payments.show', [
            'payment' => $payment,
        ]);
    }
}
