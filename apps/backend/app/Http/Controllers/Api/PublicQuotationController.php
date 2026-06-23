<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicQuotationController extends Controller
{
    public function approve(Request $request, Quotation $quotation)
    {
        $token = (string) $request->input('token');
        if (! hash_equals($quotation->approval_token, $token)) {
            abort(403, 'Invalid approval token.');
        }

        $idempotencyKey = $request->header('X-Idempotency-Key') ?? $request->input('idempotency_key');
        if ($idempotencyKey) {
            $existingEvent = $quotation->approvalEvents()->where('idempotency_key', $idempotencyKey)->first();
            if ($existingEvent) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quotation action processed (idempotent).',
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

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $now = now();
        $customer = Auth::guard('customer')->user();

        DB::transaction(function () use ($quotation, $now, $customer, $validated, $idempotencyKey) {
            // Lock quotation for update to ensure absolute atomicity
            $quotation->refresh();

            if (! $quotation->canTransitionTo(Quotation::STATUS_APPROVED)) {
                throw ValidationException::withMessages([
                    'status' => ["This quotation cannot be approved from its current status: {$quotation->status}."],
                ]);
            }

            $quotation->update([
                'status' => Quotation::STATUS_APPROVED,
                'approved_at' => $now,
            ]);

            $quotation->approvalEvents()->create([
                'event_type' => Quotation::STATUS_APPROVED,
                'revision_number' => $quotation->current_revision_number,
                'actor_type' => 'customer',
                'actor_customer_id' => $customer?->id,
                'actor_name_snapshot' => $customer?->display_name ?? ($customer?->name ?? ($quotation->customer_snapshot['contact_name'] ?? 'Guest Customer')),
                'actor_email_snapshot' => $customer?->email ?? ($quotation->customer_snapshot['email'] ?? null),
                'note' => $validated['note'] ?? 'Approved by customer',
                'idempotency_key' => $idempotencyKey,
                'occurred_at' => $now,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Quotation approved successfully.',
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

    public function reject(Request $request, Quotation $quotation)
    {
        $token = (string) $request->input('token');
        if (! hash_equals($quotation->approval_token, $token)) {
            abort(403, 'Invalid approval token.');
        }

        $idempotencyKey = $request->header('X-Idempotency-Key') ?? $request->input('idempotency_key');
        if ($idempotencyKey) {
            $existingEvent = $quotation->approvalEvents()->where('idempotency_key', $idempotencyKey)->first();
            if ($existingEvent) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quotation action processed (idempotent).',
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

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $now = now();
        $customer = Auth::guard('customer')->user();

        DB::transaction(function () use ($quotation, $now, $customer, $validated, $idempotencyKey) {
            // Lock quotation for update to ensure absolute atomicity
            $quotation->refresh();

            if (! $quotation->canTransitionTo(Quotation::STATUS_REJECTED)) {
                throw ValidationException::withMessages([
                    'status' => ["This quotation cannot be rejected from its current status: {$quotation->status}."],
                ]);
            }

            $quotation->update([
                'status' => Quotation::STATUS_REJECTED,
                'rejected_at' => $now,
            ]);

            $quotation->approvalEvents()->create([
                'event_type' => Quotation::STATUS_REJECTED,
                'revision_number' => $quotation->current_revision_number,
                'actor_type' => 'customer',
                'actor_customer_id' => $customer?->id,
                'actor_name_snapshot' => $customer?->display_name ?? ($customer?->name ?? ($quotation->customer_snapshot['contact_name'] ?? 'Guest Customer')),
                'actor_email_snapshot' => $customer?->email ?? ($quotation->customer_snapshot['email'] ?? null),
                'note' => $validated['note'] ?? 'Rejected by customer',
                'idempotency_key' => $idempotencyKey,
                'occurred_at' => $now,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Quotation rejected successfully.',
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
