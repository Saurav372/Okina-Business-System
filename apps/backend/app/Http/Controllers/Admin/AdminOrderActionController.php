<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdminOrderActionController extends Controller
{
    public function updateStatus(Request $request, Order $order)
    {
        Gate::authorize('update', $order);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([
                'pending_payment', 'confirmed', 'in_production', 'ready_to_ship', 'shipped', 'delivered', 'cancelled', 'refunded',
            ])],
            'design_status' => ['required', 'string', Rule::in(['under_review', 'issue_found', 'approved'])],
            'design_issue_message' => ['nullable', 'string'],
            'production_status' => ['required', 'string', Rule::in(['not_started', 'in_production', 'completed'])],
            'shipping_status' => ['required', 'string', Rule::in(['not_shipped', 'shipped', 'delivered'])],
            'cancellation_reason' => ['nullable', 'string'],
        ]);

        // Auto-update timestamps based on status transitions
        if ($validated['status'] === 'confirmed' && $order->confirmed_at === null) {
            $order->confirmed_at = now();
        }
        if ($validated['status'] === 'cancelled' && $order->cancelled_at === null) {
            $order->cancelled_at = now();
        }
        if ($validated['production_status'] === 'completed' && $order->ready_to_ship_at === null) {
            $order->ready_to_ship_at = now();
        }
        if ($validated['shipping_status'] === 'shipped' && $order->shipped_at === null) {
            $order->shipped_at = now();
        }
        if ($validated['shipping_status'] === 'delivered' && $order->delivered_at === null) {
            $order->delivered_at = now();
        }

        $order->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Order status updated successfully.');
    }

    public function updateShipping(Request $request, Order $order)
    {
        Gate::authorize('update', $order);

        $validated = $request->validate([
            'courier_name' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'tracking_url' => ['nullable', 'string', 'max:1000'],
            'estimated_delivery_at' => ['nullable', 'date'],
        ]);

        $order->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Order shipping details updated successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Order shipping details updated successfully.');
    }
}
