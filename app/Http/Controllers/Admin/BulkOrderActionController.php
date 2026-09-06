<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkOrderActionRequest;
use App\Models\Order;
use App\Services\BulkOrderActionService;
use Illuminate\Http\Request;

class BulkOrderActionController extends Controller
{
    public function __construct(
        protected BulkOrderActionService $bulkService
    ) {}

    public function handle(BulkOrderActionRequest $request)
    {
        $targetStatusParam = $request->input('target_status');

        $result = $this->bulkService->execute(
            action: $request->input('action'),
            orderIds: $request->input('order_ids'),
            actor: $request->user(),
            targetStatusParam: $targetStatusParam
        );

        $statusEnum = OrderStatus::tryFrom($result->action);
        $statusLabel = $statusEnum ? $statusEnum->label() : ucfirst(str_replace('_', ' ', $result->action));
        $message = "{$result->updatedCount} order(s) updated to {$statusLabel} successfully.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'updated_count' => $result->updatedCount,
                    'updated_ids' => $result->updatedPublicIds,
                    'status' => $result->action,
                ],
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function packingSlips(Request $request)
    {
        $orderIds = (array) $request->input('order_ids', []);
        if (empty($orderIds)) {
            return redirect()->back()->with('error', 'No orders selected for printing packing slips.');
        }

        $orders = Order::query()
            ->whereIn('public_id', $orderIds)
            ->with(['customer', 'shippingAddress', 'items'])
            ->orderBy('placed_at', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            return redirect()->back()->with('error', 'Matching orders could not be found.');
        }

        return view('admin.orders.packing-slips', [
            'orders' => $orders,
        ]);
    }

    public function exportManifest(Request $request)
    {
        $orderIds = (array) $request->input('order_ids', []);
        if (empty($orderIds)) {
            return redirect()->back()->with('error', 'No orders selected for courier manifest export.');
        }

        $orders = Order::query()
            ->whereIn('public_id', $orderIds)
            ->with(['customer', 'shippingAddress', 'items'])
            ->withSum(['payments' => fn ($q) => $q->where('status', 'succeeded')], 'amount_minor')
            ->orderBy('placed_at', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            return redirect()->back()->with('error', 'Matching orders could not be found.');
        }

        $filename = 'okina_courier_manifest_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header compatible with major logistics aggregators
            fputcsv($handle, [
                'Order ID',
                'Order Date',
                'Customer Name',
                'Customer Phone',
                'Customer Email',
                'Address Line 1',
                'Address Line 2',
                'City',
                'State',
                'Pincode',
                'Country',
                'Total Items',
                'Package Description',
                'Approx Weight (kg)',
                'Payment Mode',
                'Collectable COD Amount (INR)',
                'Declared Value (INR)',
                'Courier Name',
                'Tracking No',
            ]);

            foreach ($orders as $order) {
                $cust = (array) ($order->customer_snapshot ?: []);
                $name = $cust['name'] ?? $order->customer?->display_name ?? 'Valued Customer';
                $phone = $cust['phone'] ?? $order->customer?->phone ?? '';
                $email = $cust['email'] ?? $order->customer?->email ?? '';

                $addr = (array) ($order->shipping_address_snapshot ?: []);
                $line1 = $addr['address_line_1'] ?? $addr['line1'] ?? $order->shippingAddress?->address_line_1 ?? '';
                $line2 = $addr['address_line_2'] ?? $addr['line2'] ?? $order->shippingAddress?->address_line_2 ?? '';
                $city = $addr['city'] ?? $order->shippingAddress?->city ?? '';
                $state = $addr['state'] ?? $order->shippingAddress?->state ?? '';
                $pincode = $addr['postal_code'] ?? $addr['pincode'] ?? $order->shippingAddress?->postal_code ?? '';
                $country = $addr['country_code'] ?? $order->shippingAddress?->country_code ?? 'IN';

                $itemCount = $order->items->sum('quantity') ?: 1;
                $weight = round(max(0.2, $itemCount * 0.25), 2); // 250g approx per item

                $paidSum = (int) ($order->payments_sum_amount_minor ?? 0);
                $totalMinor = (int) $order->total_amount_minor;
                $isPrepaid = $paidSum >= $totalMinor && $totalMinor > 0;

                $paymentMode = $isPrepaid ? 'Prepaid' : 'COD';
                $collectable = $isPrepaid ? '0.00' : number_format($totalMinor / 100, 2, '.', '');
                $declaredVal = number_format($totalMinor / 100, 2, '.', '');

                $itemNames = $order->items->pluck('product_name_snapshot')->filter()->unique()->implode(', ');
                $description = $itemNames ? "Custom Apparel ({$itemNames})" : 'Custom Apparel / T-Shirts';

                fputcsv($handle, [
                    $order->public_id,
                    $order->placed_at?->format('Y-m-d H:i') ?? $order->created_at?->format('Y-m-d H:i') ?? '',
                    $name,
                    $phone,
                    $email,
                    $line1,
                    $line2,
                    $city,
                    $state,
                    $pincode,
                    $country,
                    $itemCount,
                    $description,
                    $weight,
                    $paymentMode,
                    $collectable,
                    $declaredVal,
                    $order->courier_name ?? '',
                    $order->tracking_number ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
