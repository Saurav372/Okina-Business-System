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

        $summary = app(OrderDetailCatalog::class)->summarize($order);

        return view('admin.orders.detail', [
            'order' => $order,
            'summary' => $summary,
        ]);
    }
}
