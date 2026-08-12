<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\StoredFile;
use App\Services\CartService;
use App\Services\FileUploadService;
use App\Services\OrderTimelineService;
use App\Support\Payments\PaymentStateRecalculationRules;
use App\Support\Products\CustomizationSnapshotBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerApiController extends Controller
{
    public function __construct(
        private readonly PaymentStateRecalculationRules $stateRules,
        private readonly CustomizationSnapshotBuilder $snapshots,
        private readonly CartService $cartService,
        private readonly OrderTimelineService $timelineService,
        private readonly FileUploadService $files,
    ) {}

    private function getCustomer()
    {
        return Auth::guard('customer')->user()->customer;
    }

    public function session(): JsonResponse
    {
        $account = Auth::guard('customer')->user();

        return response()->json([
            'authenticated' => true,
            'customer' => [
                'public_id' => $account->customer->public_id,
                'name' => $account->customer->name,
                'email' => $account->customer->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function profile(): JsonResponse
    {
        $customer = $this->getCustomer();

        return response()->json([
            'data' => [
                'public_id' => $customer->public_id,
                'customer_type' => $customer->customer_type,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'display_name' => $customer->display_name,
                'company_name' => $customer->company_name,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'whatsapp_phone' => $customer->whatsapp_phone,
                'status' => $customer->status,
            ],
        ]);
    }

    public function addresses(): JsonResponse
    {
        $customer = $this->getCustomer();
        $addresses = $customer->addresses()->get();

        return response()->json([
            'data' => $addresses->map(fn (CustomerAddress $address) => $this->formatAddress($address)),
        ]);
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $customer = $this->getCustomer();

        $validated = $request->validate([
            'address_type' => ['required', 'string', Rule::in(['shipping', 'billing', 'both'])],
            'label' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'size:2'],
            'is_default_shipping' => ['nullable', 'boolean'],
            'is_default_billing' => ['nullable', 'boolean'],
            'delivery_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $address = DB::transaction(function () use ($customer, $validated) {
            $isDefaultShipping = (bool) ($validated['is_default_shipping'] ?? false);
            $isDefaultBilling = (bool) ($validated['is_default_billing'] ?? false);

            if ($isDefaultShipping) {
                $customer->addresses()->where('is_default_shipping', true)->update(['is_default_shipping' => false]);
            }
            if ($isDefaultBilling) {
                $customer->addresses()->where('is_default_billing', true)->update(['is_default_billing' => false]);
            }

            return $customer->addresses()->create($validated);
        });

        return response()->json([
            'message' => 'Address created successfully.',
            'data' => $this->formatAddress($address),
        ], 211);
    }

    public function updateAddress(Request $request, int $id): JsonResponse
    {
        $customer = $this->getCustomer();
        $address = $customer->addresses()->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'address_type' => ['required', 'string', Rule::in(['shipping', 'billing', 'both'])],
            'label' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'size:2'],
            'is_default_shipping' => ['nullable', 'boolean'],
            'is_default_billing' => ['nullable', 'boolean'],
            'delivery_notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($customer, $address, $validated) {
            $isDefaultShipping = (bool) ($validated['is_default_shipping'] ?? false);
            $isDefaultBilling = (bool) ($validated['is_default_billing'] ?? false);

            if ($isDefaultShipping) {
                $customer->addresses()->where('is_default_shipping', true)->update(['is_default_shipping' => false]);
            }
            if ($isDefaultBilling) {
                $customer->addresses()->where('is_default_billing', true)->update(['is_default_billing' => false]);
            }

            $address->update($validated);
        });

        return response()->json([
            'message' => 'Address updated successfully.',
            'data' => $this->formatAddress($address->fresh()),
        ]);
    }

    public function destroyAddress(int $id): JsonResponse
    {
        $customer = $this->getCustomer();
        $address = $customer->addresses()->where('id', $id)->firstOrFail();
        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully.',
        ]);
    }

    public function setDefaultAddress(Request $request, int $id): JsonResponse
    {
        $customer = $this->getCustomer();
        $address = $customer->addresses()->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['shipping', 'billing', 'both'])],
        ]);

        DB::transaction(function () use ($customer, $address, $validated) {
            $type = $validated['type'];

            if ($type === 'shipping' || $type === 'both') {
                $customer->addresses()->where('is_default_shipping', true)->update(['is_default_shipping' => false]);
                $address->is_default_shipping = true;
            }
            if ($type === 'billing' || $type === 'both') {
                $customer->addresses()->where('is_default_billing', true)->update(['is_default_billing' => false]);
                $address->is_default_billing = true;
            }

            $address->save();
        });

        return response()->json([
            'message' => 'Address set as default successfully.',
            'data' => $this->formatAddress($address->fresh()),
        ]);
    }

    public function orders(): JsonResponse
    {
        $customer = $this->getCustomer();

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->with(['payments', 'refunds'])
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $orders->map(fn (Order $order) => $this->formatOrderSummary($order)),
        ]);
    }

    public function orderDetail(string $publicId): JsonResponse
    {
        $customer = $this->getCustomer();

        $order = Order::query()
            ->where('customer_id', $customer->id)
            ->where('public_id', $publicId)
            ->with([
                'items',
                'paymentAttempts',
                'payments',
                'refunds',
                'mockups.file',
            ])
            ->firstOrFail();

        return response()->json([
            'data' => $this->formatOrderDetail($order),
        ]);
    }

    public function reorder(Request $request, string $publicId): JsonResponse
    {
        $customer = $this->getCustomer();

        $order = Order::query()
            ->where('customer_id', $customer->id)
            ->where('public_id', $publicId)
            ->with(['items'])
            ->firstOrFail();

        $skipped = [];
        $addedCount = 0;

        foreach ($order->items as $item) {
            try {
                $this->cartService->addItem($request, [
                    'quantity' => $item->quantity,
                    'product_slug' => $item->product_slug_snapshot,
                    'sku_code' => $item->sku_code_snapshot,
                    'customization_snapshot' => $item->customization_snapshot ?? [],
                ]);
                $addedCount++;
            } catch (ValidationException $e) {
                $skipped[] = [
                    'product_name' => $item->product_name_snapshot,
                    'reason' => $e->getMessage(),
                ];
            } catch (\Throwable $e) {
                $skipped[] = [
                    'product_name' => $item->product_name_snapshot,
                    'reason' => 'Item is no longer available.',
                ];
            }
        }

        if ($addedCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No items could be added to your cart.',
                'skipped' => $skipped,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully reordered {$addedCount} item(s).",
            'skipped' => $skipped,
        ]);
    }

    private function formatAddress(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'address_type' => $address->address_type,
            'label' => $address->label,
            'contact_name' => $address->contact_name,
            'phone' => $address->phone,
            'company_name' => $address->company_name,
            'gstin' => $address->gstin,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'landmark' => $address->landmark,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'country_code' => $address->country_code,
            'is_default_shipping' => (bool) $address->is_default_shipping,
            'is_default_billing' => (bool) $address->is_default_billing,
            'delivery_notes' => $address->delivery_notes,
        ];
    }

    private function formatOrderSummary(Order $order): array
    {
        $paidTotal = (int) $order->payments->where('status', 'succeeded')->sum('amount_minor');
        $refundTotal = (int) $order->refunds->where('status', 'succeeded')->sum('amount_minor');
        $paymentStatus = $this->stateRules->calculate($order->total_amount_minor, $paidTotal, $refundTotal, $order->getExpectedAdvanceAmount());

        return [
            'public_id' => $order->public_id,
            'order_type' => $order->order_type,
            'order_source' => $order->order_source,
            'status' => $order->status,
            'payment_status' => $paymentStatus,
            'total_amount_minor' => $order->total_amount_minor,
            'currency' => $order->currency,
            'placed_at' => $order->placed_at?->toIso8601String(),
        ];
    }

    private function formatOrderDetail(Order $order): array
    {
        $paidTotal = (int) $order->payments->where('status', 'succeeded')->sum('amount_minor');
        $refundTotal = (int) $order->refunds->where('status', 'succeeded')->sum('amount_minor');
        $paymentStatus = $this->stateRules->calculate($order->total_amount_minor, $paidTotal, $refundTotal, $order->getExpectedAdvanceAmount());

        return [
            'public_id' => $order->public_id,
            'order_type' => $order->order_type,
            'order_source' => $order->order_source,
            'status' => $order->status,
            'payment_status' => $paymentStatus,
            'currency' => $order->currency,
            'subtotal_amount_minor' => $order->subtotal_amount_minor,
            'discount_amount_minor' => $order->discount_amount_minor,
            'shipping_amount_minor' => $order->shipping_amount_minor,
            'tax_amount_minor' => $order->tax_amount_minor,
            'total_amount_minor' => $order->total_amount_minor,
            'design_approved' => (bool) $order->design_approved,
            'design_approved_at' => $order->design_approved_at?->toIso8601String(),
            'design_notes' => $order->design_notes,
            'customer_notes' => $order->customer_notes,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'confirmed_at' => $order->confirmed_at?->toIso8601String(),
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
            'refunded_at' => $order->refunded_at?->toIso8601String(),
            'ready_to_ship_at' => $order->ready_to_ship_at?->toIso8601String(),
            'shipped_at' => $order->shipped_at?->toIso8601String(),
            'delivered_at' => $order->delivered_at?->toIso8601String(),
            'estimated_delivery_at' => $order->estimated_delivery_at?->toIso8601String(),
            'design_status' => $order->design_status,
            'design_issue_message' => $order->design_issue_message,
            'production_status' => $order->production_status,
            'shipping_status' => $order->shipping_status,
            'courier_name' => $order->courier_name,
            'tracking_number' => $order->tracking_number,
            'tracking_url' => $order->tracking_url,
            'cancellation_reason' => $order->cancellation_reason,
            'timeline' => $this->timelineService->generateTimeline($order),
            'customer_snapshot' => $order->customer_snapshot,
            'shipping_address_snapshot' => $order->shipping_address_snapshot,
            'billing_address_snapshot' => $order->billing_address_snapshot,
            'items' => $order->items->map(fn (OrderItem $item) => $this->formatOrderItem($item))->all(),
            'proofs' => $order->mockups
                ->filter(function ($proof) use ($order): bool {
                    $file = $proof->file;

                    return $file instanceof StoredFile
                        && $file->status === StoredFile::STATUS_ACTIVE
                        && $file->visibility === StoredFile::VISIBILITY_CUSTOMER_VISIBLE
                        && $file->customer_id === $order->customer_id;
                })
                ->map(fn ($proof): array => [
                    'public_id' => $proof->file->public_id,
                    'display_name' => $proof->display_name,
                    'notes' => $proof->notes,
                    'is_featured' => (bool) $proof->is_featured,
                    'mime_type' => $proof->file->mime_type,
                    'size_bytes' => (int) $proof->file->size_bytes,
                    'preview_url' => $this->files->temporaryPreviewUrl($proof->file, 60),
                    'download_url' => $this->files->temporaryDownloadUrl($proof->file, 60),
                    'shared_at' => $proof->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'payments' => $order->payments->map(fn (Payment $payment) => [
                'provider' => $payment->provider,
                'method' => $payment->method,
                'status' => $payment->status,
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
                'provider_payment_id' => $payment->provider_payment_id,
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ])->all(),
            'refunds' => $order->refunds->map(fn (Refund $refund) => [
                'provider' => $refund->provider,
                'refund_type' => $refund->refund_type,
                'status' => $refund->status,
                'amount_minor' => $refund->amount_minor,
                'currency' => $refund->currency,
                'provider_refund_id' => $refund->provider_refund_id,
                'processed_at' => $refund->processed_at?->toIso8601String(),
            ])->all(),
        ];
    }

    private function formatOrderItem(OrderItem $item): array
    {
        return [
            'public_id' => $item->public_id,
            'product_name' => $item->product_name_snapshot,
            'product_slug' => $item->product_slug_snapshot,
            'sku_code' => $item->sku_code_snapshot,
            'quantity' => $item->quantity,
            'unit_price_minor' => $item->unit_price_minor,
            'line_subtotal_minor' => $item->line_subtotal_minor,
            'line_total_minor' => $item->line_total_minor,
            'customization_snapshot' => $this->formatCustomizationSnapshot($item->customization_snapshot),
        ];
    }

    private function formatCustomizationSnapshot(?array $snapshot): ?array
    {
        if ($snapshot === null) {
            return null;
        }

        $public = $this->snapshots->publicCartSnapshot($snapshot);
        unset($public['mockup_preview'], $public['mockup_preview_url']);

        return $public;
    }
}
