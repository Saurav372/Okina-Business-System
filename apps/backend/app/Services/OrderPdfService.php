<?php
 
namespace App\Services;
 
use App\Events\AuditEvent;
use App\Models\Order;
use App\Support\Payments\PaymentStateRecalculationRules;
use Illuminate\Support\Facades\View;
 
class OrderPdfService
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly PaymentStateRecalculationRules $stateRules,
    ) {}
 
    /**
     * Build the full print/PDF preview dataset for an order.
     */
    public function buildData(Order $order, mixed $actor = null): array
    {
        // 1. Fetch settings
        $business = $this->settingsService->all('business');
        $documents = $this->settingsService->all('documents');
        $tax = $this->settingsService->all('tax');
        $payments = $this->settingsService->all('payments');
 
        // 2. Load relationships
        $order->load(['items', 'payments', 'refunds', 'mockups.file']);
 
        // 3. Derived payment status
        $paidTotal = (int) $order->payments->where('status', 'succeeded')->sum('amount_minor');
        $refundTotal = (int) $order->refunds->where('status', 'succeeded')->sum('amount_minor');
        $paymentStatus = $this->stateRules->calculate(
            $order->total_amount_minor,
            $paidTotal,
            $refundTotal,
            $order->getExpectedAdvanceAmount()
        );
 
        // 4. Dispatch audit event for PDF generation
        event(new AuditEvent('orders.pdf_generated', $actor, [
            'order_public_id' => $order->public_id,
            'template' => 'Classic',
            'generated_by' => $actor?->name ?? 'Admin',
            'generated_at' => now()->toIso8601String(),
            'subject_type' => 'order',
            'subject_id' => $order->public_id,
            'subject_public_id' => $order->public_id,
            'customer_public_id' => $order->customer?->public_id,
        ]));
 
        return [
            'order' => $order,
            'payment_status' => $paymentStatus,
            'paid_total' => $paidTotal,
            'refund_total' => $refundTotal,
            'balance_due' => max(0, $order->total_amount_minor - $paidTotal + $refundTotal),
            'settings' => [
                'business' => $business,
                'documents' => $documents,
                'tax' => $tax,
                'payments' => $payments,
            ],
        ];
    }
 
    /**
     * Render the order confirmation document template to HTML.
     */
    public function renderHtml(Order $order, mixed $actor = null): string
    {
        $data = $this->buildData($order, $actor);
 
        return View::make('admin.orders.pdf', $data)->render();
    }
}
