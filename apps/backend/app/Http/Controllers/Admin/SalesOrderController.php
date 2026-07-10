<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesOrderCreateRequest;
use App\Http\Requests\Admin\SalesOrderUpdateRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductSku;
use App\Services\OrderPdfService;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SalesOrderController extends Controller
{
    public function store(SalesOrderCreateRequest $request, SalesOrderService $service)
    {
        Gate::authorize('create', Order::class);

        $order = $service->create($request->validated(), $request->user());

        return response()->json([
            'public_id' => $order->public_id,
            'order' => $order->toArray(),
        ], 201);
    }

    public function create()
    {
        Gate::authorize('create', Order::class);

        $customers = Customer::query()
            ->orderBy('display_name')
            ->limit(200)
            ->get(['id', 'display_name', 'name']);

        $skus = ProductSku::query()
            ->with('product')
            ->limit(500)
            ->get(['id', 'sku_code']);

        return view('admin.orders.create', [
            'customers' => $customers,
            'skus' => $skus,
        ]);
    }

    public function skuSearch(Request $request)
    {
        $q = (string) $request->query('q', '');

        $results = ProductSku::query()
            ->with('product')
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where('sku_code', 'like', "%{$q}%")
                    ->orWhereHas('product', function ($b) use ($q) {
                        $b->where('name', 'like', "%{$q}%");
                    });
            })
            ->limit(50)
            ->get(['id', 'sku_code', 'product_id'])
            ->map(function (ProductSku $sku) {
                return [
                    'id' => $sku->id,
                    'sku_code' => $sku->sku_code,
                    'label' => $sku->sku_code.($sku->product ? ' - '.$sku->product->name : ''),
                ];
            });

        return response()->json($results->values());
    }

    public function update(SalesOrderUpdateRequest $request, Order $order, SalesOrderService $service)
    {
        Gate::authorize('update', $order);

        $updatedOrder = $service->update($order, $request->validated(), $request->user());

        return response()->json([
            'public_id' => $updatedOrder->public_id,
            'order' => $updatedOrder->toArray(),
        ], 200);
    }

    public function previewPdf(Order $order, OrderPdfService $pdfService, Request $request)
    {
        Gate::authorize('view', $order);

        if ($order->items()->count() === 0) {
            throw ValidationException::withMessages([
                'order' => 'Order contains no items.',
            ]);
        }

        $html = $pdfService->renderHtml($order, $request->user());

        return response($html)->header('Content-Type', 'text/html');
    }

    public function downloadPdf(Order $order, OrderPdfService $pdfService, Request $request)
    {
        Gate::authorize('view', $order);

        if ($order->items()->count() === 0) {
            throw ValidationException::withMessages([
                'order' => 'Order contains no items.',
            ]);
        }

        $pdf = $pdfService->renderPdf($order, $request->user());

        $filename = 'Order_Confirmation_' . $order->public_id . '.pdf';

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Length', strlen($pdf));
    }
}
