<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuotationRequest;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\ProductSku;
use App\Models\Quotation;
use App\Support\Orders\OrderTotalsCalculator;
use App\Support\Products\CustomizationSnapshotBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuotationController extends Controller
{
    public function store(
        StoreQuotationRequest $request,
        CustomizationSnapshotBuilder $snapshotsBuilder,
        OrderTotalsCalculator $totalsCalculator
    ) {
        Gate::authorize('create', Quotation::class);

        $lead = null;
        $customer = null;

        if ($request->filled('lead_public_id')) {
            $lead = Lead::where('public_id', $request->input('lead_public_id'))->firstOrFail();
            if (! in_array($lead->status, ['qualified', 'quoted'], true)) {
                throw ValidationException::withMessages([
                    'lead_public_id' => ['The lead must be qualified or quoted to create a quotation.'],
                ]);
            }
        }

        if ($request->filled('customer_public_id')) {
            $customer = Customer::where('public_id', $request->input('customer_public_id'))->firstOrFail();
        }

        // Build customer snapshot
        $customerSnapshot = [];
        if ($lead) {
            $customerSnapshot = [
                'contact_name' => $lead->contact_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company_name' => $lead->company_name,
            ];
            if ($lead->customer) {
                $customerSnapshot['customer_public_id'] = $lead->customer->public_id;
            }
        } elseif ($customer) {
            $customerSnapshot = [
                'customer_public_id' => $customer->public_id,
                'contact_name' => $customer->display_name ?? $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'company_name' => $customer->company_name,
            ];
        } else {
            $customerSnapshot = [
                'contact_name' => $request->input('contact_name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'company_name' => $request->input('company_name'),
            ];
        }

        $validUntil = $request->filled('valid_until')
            ? Carbon::parse($request->input('valid_until'))
            : now()->addDays(30);

        $discountInput = (int) $request->input('discount_amount_minor', 0);
        $shippingInput = (int) $request->input('shipping_amount_minor', 0);
        $taxRatePercent = (float) $request->input('tax_rate_percent', 0);

        $quotation = DB::transaction(function () use (
            $request,
            $lead,
            $customer,
            $customerSnapshot,
            $validUntil,
            $discountInput,
            $shippingInput,
            $taxRatePercent,
            $snapshotsBuilder,
            $totalsCalculator
        ) {
            $itemsInput = $request->input('items', []);
            $itemsData = [];
            $lineTotals = [];

            foreach ($itemsInput as $index => $item) {
                $sku = null;
                $product = null;

                if (! empty($item['sku_code'])) {
                    $sku = ProductSku::where('sku_code', $item['sku_code'])->with('product')->firstOrFail();
                    $product = $sku->product;
                }

                // Resolve price: request unit price -> SKU price -> product base price -> fail
                $unitPrice = null;
                if (isset($item['unit_price_minor']) && is_numeric($item['unit_price_minor'])) {
                    $unitPrice = (int) $item['unit_price_minor'];
                } elseif ($sku && is_int($sku->price_minor)) {
                    $unitPrice = $sku->price_minor;
                } elseif ($product && is_int($product->base_price_minor)) {
                    $unitPrice = $product->base_price_minor;
                }

                if ($unitPrice === null) {
                    throw ValidationException::withMessages([
                        "items.{$index}.unit_price_minor" => ['No valid price found for item: '.$item['item_name']],
                    ]);
                }

                $quantity = (int) $item['quantity'];
                $lineSubtotal = $quantity * $unitPrice;
                $lineTotal = $lineSubtotal; // Default item discount/tax is 0

                $customization = $snapshotsBuilder->publicCartSnapshot($item['customization_snapshot'] ?? []);

                $itemsData[] = [
                    'product_sku_id' => $sku?->id,
                    'product_id_snapshot' => $product?->id,
                    'product_name_snapshot' => $product?->name ?? $item['item_name'],
                    'sku_code_snapshot' => $sku?->sku_code,
                    'item_name' => $item['item_name'] ?? ($product?->name ?? 'Quoted Item'),
                    'selected_options_snapshot' => $sku ? $sku->option_values : null,
                    'customization_snapshot' => ! empty($customization) ? $customization : null,
                    'quantity' => $quantity,
                    'unit_price_minor' => $unitPrice,
                    'discount_amount_minor' => 0,
                    'tax_amount_minor' => 0,
                    'line_subtotal_minor' => $lineSubtotal,
                    'line_total_minor' => $lineTotal,
                    'currency' => 'INR',
                    'sort_order' => $index,
                    'customer_note' => $item['customer_note'] ?? null,
                    'internal_notes' => $item['internal_notes'] ?? null,
                ];

                $lineTotals[] = $lineTotal;
            }

            $subtotal = array_sum($lineTotals);
            $discount = min($discountInput, $subtotal);
            $shipping = $shippingInput;

            // Defensive calculation of tax base
            $taxableAmount = max(0, $subtotal - $discount);
            $tax = (int) round($taxableAmount * ($taxRatePercent / 100), 0, PHP_ROUND_HALF_UP);

            $totals = $totalsCalculator->fromLineTotals($lineTotals, $discount, $shipping, $tax);

            $quotation = Quotation::create([
                'quotation_type' => $request->input('quotation_type'),
                'status' => Quotation::STATUS_DRAFT,
                'lead_id' => $lead?->id,
                'customer_id' => $customer?->id ?? $lead?->customer_id,
                'assigned_to_user_id' => $lead?->assigned_to_user_id ?? $request->user()?->id,
                'created_by_user_id' => $request->user()?->id,
                'customer_snapshot' => $customerSnapshot,
                'subtotal_amount_minor' => $totals->subtotalAmountMinor(),
                'discount_amount_minor' => $totals->discountAmountMinor(),
                'shipping_amount_minor' => $totals->shippingAmountMinor(),
                'tax_amount_minor' => $totals->taxAmountMinor(),
                'total_amount_minor' => $totals->totalAmountMinor(),
                'currency' => 'INR',
                'current_revision_number' => 1,
                'valid_until' => $validUntil,
                'customer_note' => $request->input('customer_note'),
                'internal_notes' => $request->input('internal_notes'),
            ]);

            $quotation->items()->createMany($itemsData);

            return $quotation;
        });

        return response()->json([
            'public_id' => $quotation->public_id,
            'quotation_number' => $quotation->public_id,
            'status' => $quotation->status,
            'quotation_type' => $quotation->quotation_type,
            'totals' => [
                'subtotal_amount_minor' => $quotation->subtotal_amount_minor,
                'discount_amount_minor' => $quotation->discount_amount_minor,
                'shipping_amount_minor' => $quotation->shipping_amount_minor,
                'tax_amount_minor' => $quotation->tax_amount_minor,
                'total_amount_minor' => $quotation->total_amount_minor,
                'currency' => $quotation->currency,
            ],
            'valid_until' => $quotation->valid_until?->toDateString(),
            'customer_snapshot' => $quotation->customer_snapshot,
            'items' => $quotation->items->map(fn ($item): array => [
                'product_name_snapshot' => $item->product_name_snapshot,
                'sku_code_snapshot' => $item->sku_code_snapshot,
                'item_name' => $item->item_name,
                'selected_options_snapshot' => $item->selected_options_snapshot,
                'customization_snapshot' => $item->customization_snapshot,
                'quantity' => $item->quantity,
                'unit_price_minor' => $item->unit_price_minor,
                'discount_amount_minor' => $item->discount_amount_minor,
                'tax_amount_minor' => $item->tax_amount_minor,
                'line_subtotal_minor' => $item->line_subtotal_minor,
                'line_total_minor' => $item->line_total_minor,
                'currency' => $item->currency,
                'customer_note' => $item->customer_note,
                'internal_notes' => $item->internal_notes,
            ])->toArray(),
            'created_at' => $quotation->created_at?->toIso8601String() ?? $quotation->created_at,
            'updated_at' => $quotation->updated_at?->toIso8601String() ?? $quotation->updated_at,
        ], 201);
    }

    public function updateStatus(Request $request, Quotation $quotation)
    {
        Gate::authorize('update', $quotation);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(Quotation::STATUSES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $targetStatus = $validated['status'];
        $now = now();

        DB::transaction(function () use ($quotation, $targetStatus, $validated, $now, $request) {
            $quotation->refresh();

            if (! $quotation->canTransitionTo($targetStatus)) {
                throw ValidationException::withMessages([
                    'status' => ["Invalid quotation status transition from {$quotation->status} to {$targetStatus}."],
                ]);
            }

            $updateData = [
                'status' => $targetStatus,
            ];

            // Automate timestamp logging and relations based on target status
            if ($targetStatus === Quotation::STATUS_SENT) {
                $updateData['sent_at'] = $now;
            } elseif ($targetStatus === Quotation::STATUS_APPROVED) {
                $updateData['approved_at'] = $now;
                $updateData['approved_by_user_id'] = Auth::id() ?? $request->user()?->id;
            } elseif ($targetStatus === Quotation::STATUS_REJECTED) {
                $updateData['rejected_at'] = $now;
            } elseif ($targetStatus === Quotation::STATUS_EXPIRED) {
                $updateData['expired_at'] = $now;
            } elseif ($targetStatus === Quotation::STATUS_CONVERTED) {
                $updateData['converted_at'] = $now;
            } elseif ($targetStatus === Quotation::STATUS_REVISED) {
                $updateData['revised_at'] = $now;
            }

            $quotation->update($updateData);

            $actorUser = $request->user();
            $quotation->approvalEvents()->create([
                'event_type' => $targetStatus,
                'revision_number' => $quotation->current_revision_number,
                'actor_type' => 'staff',
                'actor_user_id' => Auth::id() ?? $actorUser?->id,
                'actor_name_snapshot' => $actorUser?->name,
                'actor_email_snapshot' => $actorUser?->email,
                'note' => $validated['note'] ?? null,
                'occurred_at' => $now,
            ]);
        });

        return response()->json([
            'success' => true,
            'quotation' => [
                'public_id' => $quotation->public_id,
                'status' => $quotation->status,
                'sent_at' => $quotation->sent_at?->toIso8601String() ?? $quotation->sent_at,
                'approved_at' => $quotation->approved_at?->toIso8601String() ?? $quotation->approved_at,
                'rejected_at' => $quotation->rejected_at?->toIso8601String() ?? $quotation->rejected_at,
                'expired_at' => $quotation->expired_at?->toIso8601String() ?? $quotation->expired_at,
                'converted_at' => $quotation->converted_at?->toIso8601String() ?? $quotation->converted_at,
                'revised_at' => $quotation->revised_at?->toIso8601String() ?? $quotation->revised_at,
                'updated_at' => $quotation->updated_at?->toIso8601String() ?? $quotation->updated_at,
            ],
        ]);
    }
}
