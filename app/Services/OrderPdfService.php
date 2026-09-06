<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Order;
use App\Support\Payments\PaymentStateRecalculationRules;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
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

        // 2. Load relationships (including addresses)
        $order->load(['items', 'payments', 'refunds', 'mockups.file', 'shippingAddress', 'billingAddress']);

        // 3. Fallback customer billing & shipping addresses
        $shippingAddress = ! empty($order->shipping_address_snapshot['address_line_1'])
            ? $order->shipping_address_snapshot
            : ($order->shippingAddress ? $order->shippingAddress->toArray() : null);

        $billingAddress = ! empty($order->billing_address_snapshot['address_line_1'])
            ? $order->billing_address_snapshot
            : ($order->billingAddress ? $order->billingAddress->toArray() : null);

        // 4. Subtotal & Grand Total calculations with dynamic item fallback
        $subtotalAmountMinor = $order->subtotal_amount_minor ?: (int) $order->items->sum('line_total_minor');
        $totalAmountMinor = $order->total_amount_minor ?: ($subtotalAmountMinor - $order->discount_amount_minor + $order->shipping_amount_minor + $order->tax_amount_minor);

        // 5. Derived payment status
        $paidTotal = (int) $order->payments->where('status', 'succeeded')->sum('amount_minor');
        $refundTotal = (int) $order->refunds->where('status', 'succeeded')->sum('amount_minor');
        $paymentStatus = $this->stateRules->calculate(
            $totalAmountMinor,
            $paidTotal,
            $refundTotal,
            $order->getExpectedAdvanceAmount()
        );

        // 6. Format order items with size extraction & filter customization parameters
        $formattedItems = [];
        foreach ($order->items as $index => $item) {
            $size = null;
            $customizationSnapshot = $item->customization_snapshot;

            if (is_array($customizationSnapshot)) {
                // Direct size key check
                if (isset($customizationSnapshot['size'])) {
                    $size = $customizationSnapshot['size'];
                }

                // Nested selected options check
                if (empty($size) && isset($customizationSnapshot['selected_options_snapshot']) && is_array($customizationSnapshot['selected_options_snapshot'])) {
                    foreach ($customizationSnapshot['selected_options_snapshot'] as $opt) {
                        if (isset($opt['option_code']) && strtolower($opt['option_code']) === 'size') {
                            $size = $opt['value_label'] ?? $opt['value_code'] ?? null;
                            break;
                        }
                    }
                }
            }

            // Format customization options (excluding size and meta variables)
            $customizationDetails = [];
            if (is_array($customizationSnapshot)) {
                foreach ($customizationSnapshot as $key => $val) {
                    if (is_scalar($val) && ! in_array($key, ['size', 'mockup_preview_url', 'expires_in_minutes', 'route_name', 'schema_version'])) {
                        $customizationDetails[ucwords(str_replace('_', ' ', $key))] = $val;
                    }
                }

                if (isset($customizationSnapshot['selected_options_snapshot']) && is_array($customizationSnapshot['selected_options_snapshot'])) {
                    foreach ($customizationSnapshot['selected_options_snapshot'] as $opt) {
                        $code = $opt['option_code'] ?? '';
                        if ($code && strtolower($code) !== 'size') {
                            $customizationDetails[ucwords(str_replace('_', ' ', $code))] = $opt['value_label'] ?? $opt['value_code'] ?? '';
                        }
                    }
                }
            }

            $formattedItems[] = [
                'index' => $index + 1,
                'name' => $item->product_name_snapshot,
                'sku' => $item->sku_code_snapshot,
                'size' => $size ?: '—',
                'qty' => $item->quantity,
                'unit_price_minor' => $item->unit_price_minor,
                'line_total_minor' => $item->line_total_minor,
                'customization_details' => $customizationDetails,
            ];
        }

        // 7. Dynamic UPI QR Code generation
        $upiId = $payments['upi_id'] ?? null;
        $qrCodeBase64 = null;
        $balanceDueMinor = max(0, $totalAmountMinor - $paidTotal + $refundTotal);
        if ($upiId && $balanceDueMinor > 0) {
            $balanceDueAmount = number_format($balanceDueMinor / 100, 2, '.', '');
            $upiUrl = "upi://pay?pa={$upiId}&pn=".urlencode($business['company_name'] ?? 'Okina Craft')."&am={$balanceDueAmount}&cu=INR";
            $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&margin=0&data='.urlencode($upiUrl);

            try {
                $ctx = stream_context_create(['http' => ['timeout' => 3]]);
                $qrContent = file_get_contents($qrCodeUrl, false, $ctx);
                if ($qrContent) {
                    $qrCodeBase64 = 'data:image/png;base64,'.base64_encode($qrContent);
                }
            } catch (\Throwable $e) {
                // Fail silently and keep qrCodeBase64 as null
            }
        }

        // 8. Dispatch audit event for PDF generation
        event(new AuditEvent('orders.pdf_generated', $actor, [
            'order_public_id' => $order->public_id,
            'template' => 'Classic',
            'generated_by' => $actor?->name ?? 'Admin',
            'generated_at' => now()->toIso8601String(),
            'subject_type' => 'order',
            'subject_id' => $order->id,
            'subject_public_id' => $order->public_id,
            'customer_id' => $order->customer?->id,
            'customer_public_id' => $order->customer?->public_id,
        ]));

        return [
            'order' => $order,
            'items' => $formattedItems,
            'payment_status' => $paymentStatus,
            'paid_total' => $paidTotal,
            'refund_total' => $refundTotal,
            'balance_due' => $balanceDueMinor,
            'subtotal_amount_minor' => $subtotalAmountMinor,
            'total_amount_minor' => $totalAmountMinor,
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
            'qr_code_base64' => $qrCodeBase64,
            'mockup_images' => $this->buildMockupImages($order),
            'settings' => [
                'business' => $business,
                'documents' => $documents,
                'tax' => $tax,
                'payments' => $payments,
            ],
        ];
    }

    /**
     * Build mockup image data for the PDF template.
     * Encodes images as base64 data URIs so they render in both browser preview
     * and Dompdf PDF generation (which cannot fetch authenticated storage URLs).
     */
    private function buildMockupImages(Order $order): array
    {
        $images = [];

        $mockups = $order->mockups;
        if ($mockups->isEmpty()) {
            return $images;
        }

        // Use featured mockups if any exist, otherwise show all
        $filtered = $mockups->filter(fn ($m) => $m->is_featured);
        if ($filtered->isEmpty()) {
            $filtered = $mockups;
        }

        foreach ($filtered as $mockup) {
            $imageSrc = null;

            if ($mockup->file) {
                try {
                    $disk = $mockup->file->storage_disk ?? 'private';
                    $path = $mockup->file->storage_path;

                    if ($path && Storage::disk($disk)->exists($path)) {
                        $contents = Storage::disk($disk)->get($path);
                        $mimeType = $mockup->file->mime_type ?? 'image/jpeg';
                        $imageSrc = 'data:'.$mimeType.';base64,'.base64_encode($contents);
                    }
                } catch (\Throwable $e) {
                    // Skip images that cannot be read
                }
            }

            $images[] = [
                'display_name' => $mockup->display_name,
                'notes' => $mockup->notes,
                'image_src' => $imageSrc,
            ];
        }

        return $images;
    }

    /**
     * Render the order confirmation document template to HTML.
     */
    public function renderHtml(Order $order, mixed $actor = null): string
    {
        $data = $this->buildData($order, $actor);

        $html = View::make('admin.orders.pdf', $data)->render();

        return str_replace(['{current}', '{total}'], ['1', '1'], $html);
    }

    /**
     * Generate a binary PDF from the rendered HTML using Dompdf.
     */
    public function renderPdf(Order $order, mixed $actor = null): string
    {
        $html = $this->renderHtml($order, $actor);

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('dpi', 96);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
