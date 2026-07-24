<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderIndexResource;
use App\Models\AuditLog;
use App\Models\Order;
use App\Support\Admin\OrderDetailCatalog;
use App\Support\Admin\OrderIndexCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request, OrderIndexCatalog $catalog)
    {
        Gate::authorize('viewAny', Order::class);

        $criteria = $request->only([
            'search',
            'scope',
            'status',
            'order_source',
            'design_approved',
            'placed_from',
            'placed_to',
            'sort',
            'direction',
        ]);

        $perPage = (int) $request->query('per_page', 20);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        $ordersQuery = $catalog->query($criteria);
        $orders = $ordersQuery->paginate($perPage)->withQueryString();

        if ($request->wantsJson()) {
            return OrderIndexResource::collection($orders);
        }

        $scopes = $catalog->definition()['scopes'];
        $filters = $catalog->definition()['filters'];

        return view('admin.orders.index', [
            'orders' => $orders,
            'scopes' => $scopes,
            'filters' => $filters,
            'activeFilters' => $criteria,
        ]);
    }

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

        $timelineLogs = AuditLog::query()
            ->where('subject_type', 'order')
            ->where(function ($query) use ($order) {
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
