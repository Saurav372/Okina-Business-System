<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidPurchaseOrderStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\ReceivePurchaseOrderRequest;
use App\Models\VendorOrder;
use App\Services\PurchaseReceivingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PurchaseOrderReceivingController extends Controller
{
    public function __construct(
        protected PurchaseReceivingService $receivingService
    ) {}

    /**
     * Process stock receiving batch for a Purchase Order.
     */
    public function receive(ReceivePurchaseOrderRequest $request, VendorOrder $vendorOrder): JsonResponse|RedirectResponse
    {
        Gate::authorize('receive', $vendorOrder);

        $validated = $request->validated();

        try {
            $result = $this->receivingService->receive(
                order: $vendorOrder,
                items: $validated['items'],
                idempotencyKey: $validated['idempotency_key'],
                actor: $request->user(),
                notes: $validated['notes'] ?? null
            );
        } catch (InvalidPurchaseOrderStatusTransitionException $e) {
            throw ValidationException::withMessages([
                'items' => $e->getMessage(),
            ]);
        }

        if ($request->expectsJson()) {
            $status = ($result['replayed'] ?? false) ? 200 : 201;

            return response()->json([
                'message' => ($result['replayed'] ?? false)
                    ? "Goods receipt batch [{$result['batch_code']}] replayed successfully."
                    : "Goods receipt batch [{$result['batch_code']}] successfully processed.",
                'data' => [
                    'vendor_order_id' => $vendorOrder->id,
                    'public_id' => $vendorOrder->public_id,
                    'receipt_id' => $result['receipt']->id,
                    'receipt_number' => $result['batch_code'],
                    'received_count' => $result['received_count'],
                    'status' => $result['vendor_order']->status->value,
                    'replayed' => $result['replayed'] ?? false,
                ],
            ], $status);
        }

        $flashMessage = ($result['replayed'] ?? false)
            ? "Goods receipt batch [{$result['batch_code']}] replayed successfully."
            : "Goods receipt batch [{$result['batch_code']}] successfully processed. Received {$result['received_count']} total stock units.";

        return redirect()->route('admin.purchases.show', $vendorOrder->public_id)->with('success', $flashMessage);
    }
}
