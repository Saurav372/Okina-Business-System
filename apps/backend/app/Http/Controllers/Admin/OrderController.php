<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderIndexResource;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\StoredFile;
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

        $artworkReferences = $order->items
            ->flatMap(function ($item): array {
                $snapshot = $item->customization_snapshot ?? [];
                $files = is_array($snapshot['files'] ?? null) ? $snapshot['files'] : [];

                return collect($files)
                    ->filter(fn ($file): bool => is_array($file) && filled($file['public_id'] ?? null))
                    ->map(fn (array $file): array => [
                        'item' => $item,
                        'reference' => $file,
                        'print_position' => $snapshot['print_position'] ?? null,
                        'print_method' => $snapshot['print_method'] ?? null,
                        'customer_note' => $snapshot['customer_note'] ?? null,
                    ])
                    ->all();
            })
            ->values();

        $storedFiles = StoredFile::query()
            ->whereIn('public_id', $artworkReferences->pluck('reference.public_id')->filter()->unique()->all())
            ->get()
            ->keyBy('public_id');

        $artworkUploads = $artworkReferences
            ->map(function (array $entry) use ($storedFiles): array {
                $entry['file'] = $storedFiles->get($entry['reference']['public_id']);

                return $entry;
            })
            ->filter(fn (array $entry): bool => $entry['file'] instanceof StoredFile)
            ->values();

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
            'artworkUploads' => $artworkUploads,
        ]);
    }
}
