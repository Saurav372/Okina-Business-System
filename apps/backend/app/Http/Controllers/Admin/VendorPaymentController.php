<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorOrderStatus;
use App\Enums\VendorPaymentStatus;
use App\Events\AuditEvent;
use App\Exceptions\PurchaseOrderNotPayableException;
use App\Exceptions\PurchaseOrderPaymentLimitExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\StoreVendorPaymentRequest;
use App\Models\VendorOrder;
use App\Models\VendorPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VendorPaymentController extends Controller
{
    /**
     * Store a newly created vendor payment in storage.
     */
    public function store(StoreVendorPaymentRequest $request, VendorOrder $purchaseOrder): JsonResponse
    {
        Gate::authorize('update', $purchaseOrder);

        $user = Auth::user();
        $validated = $request->validated();

        try {
            $purchaseOrder = DB::transaction(function () use ($purchaseOrder, $validated, $user, &$payment) {
                // Lock parent purchase order
                $lockedPo = VendorOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();

                // Compute current total paid via explicit collection locking
                $payments = VendorPayment::where('vendor_order_id', $lockedPo->id)
                    ->where('status', VendorPaymentStatus::PAID->value)
                    ->lockForUpdate()
                    ->get();
                $existingPaymentsSum = $payments->sum('amount_minor');

                // Validate PO state: if draft or cancelled, throw PurchaseOrderNotPayableException
                if ($lockedPo->isEditable() || $lockedPo->status === VendorOrderStatus::CANCELLED) {
                    throw new PurchaseOrderNotPayableException(
                        "Cannot record payment on a purchase order in {$lockedPo->status->value} status."
                    );
                }

                // Validate remaining balance
                $remaining = $lockedPo->total_amount_minor - $existingPaymentsSum;
                if ($validated['amount_minor'] > $remaining) {
                    throw new PurchaseOrderPaymentLimitExceededException(
                        'The payment amount exceeds the remaining payable balance.'
                    );
                }

                // Capture previous payment status
                $previousPaymentStatus = $lockedPo->payment_status;

                // Create the immutable VendorPayment record
                $payment = VendorPayment::create([
                    'vendor_order_id' => $lockedPo->id,
                    'recorded_by_user_id' => $user->id,
                    'status' => VendorPaymentStatus::PAID,
                    'payment_method' => $validated['payment_method'],
                    'amount_minor' => $validated['amount_minor'],
                    'currency' => $lockedPo->currency,
                    'reference' => $validated['reference'] ?? null,
                    'paid_at' => $validated['paid_at'] ?? now(),
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Compute new total paid
                $newTotalPaid = $existingPaymentsSum + $payment->amount_minor;

                // Call recalculatePaymentStatus
                $lockedPo->recalculatePaymentStatus($newTotalPaid);

                // Save parent PO
                $lockedPo->save();

                // Perform non-optional refresh of models
                $payment->refresh();
                $lockedPo->refresh();

                // Define local variables for the audit payload before registering afterCommit
                $totalPaidMinor = $newTotalPaid;
                $remainingBalanceMinor = $lockedPo->total_amount_minor - $newTotalPaid;
                $previousStatusValue = $previousPaymentStatus->value;
                $currentStatusValue = $lockedPo->payment_status->value;

                DB::afterCommit(function () use ($lockedPo, $payment, $totalPaidMinor, $remainingBalanceMinor, $previousStatusValue, $currentStatusValue, $user) {
                    event(new AuditEvent('purchase_orders.payments.recorded', $user, [
                        'vendor_order_id' => $lockedPo->id,
                        'vendor_payment_id' => $payment->id,
                        'payment_amount_minor' => $payment->amount_minor,
                        'total_paid_minor' => $totalPaidMinor,
                        'remaining_balance_minor' => $remainingBalanceMinor,
                        'previous_payment_status' => $previousStatusValue,
                        'payment_status' => $currentStatusValue,
                        'currency' => $payment->currency,
                        'payment_method' => $payment->payment_method->value,
                        'reference' => $payment->reference,
                        'actor_id' => $user->id,
                    ]));
                });

                return $lockedPo;
            });
        } catch (PurchaseOrderPaymentLimitExceededException $e) {
            throw ValidationException::withMessages([
                'amount_minor' => $e->getMessage(),
            ]);
        } catch (PurchaseOrderNotPayableException $e) {
            throw ValidationException::withMessages([
                'purchase_order' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'payment' => $payment,
            'purchase_order' => $purchaseOrder,
        ]);
    }
}
