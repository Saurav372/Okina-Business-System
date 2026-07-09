<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Admin\OrderDetailCatalog;
use Illuminate\Support\Facades\Gate;

class OrderDetailController extends Controller
{
    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load([
            'items',
            'paymentAttempts',
            'payments.paymentAttempt',
            'refunds.payment.paymentAttempt',
            'mockups.file',
        ]);

        $summary = app(OrderDetailCatalog::class)->summarize($order);

        $timelineLogs = \App\Models\AuditLog::query()
            ->where('subject_type', 'order')
            ->where(function($query) use ($order) {
                $query->where('subject_id', $order->public_id)
                      ->orWhere('subject_public_id', $order->public_id);
            })
            ->latest()
            ->get();

        return view('admin.orders.detail', [
            'order' => $order,
            'summary' => $summary,
            'timelineLogs' => $timelineLogs,
        ]);
    }
}
