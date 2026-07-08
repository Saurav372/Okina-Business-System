<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\Quotation;
use App\Models\AuditLog;
use App\Enums\OrderStatus;
use App\Support\Dashboard\DashboardWidgetDTO;
use App\Support\Dashboard\ActivityMapper;
use App\Support\Dashboard\ChartPointDTO;
use App\Support\Dashboard\ChartSeriesDTO;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Get the widgets data collection for the admin dashboard.
     * 
     * @return array<DashboardWidgetDTO>
     */
    public function getWidgetsData(): array
    {
        // Wrap query execution in a 5-minute cache
        $data = Cache::remember('admin_dashboard_metrics_data', 300, function () {
            // 1. Total Revenue (Excluding Cancelled)
            $revenueMinor = Order::where('status', '!=', OrderStatus::Cancelled->value())->sum('total_amount_minor');
            $revenue = $revenueMinor / 100;

            // 2. Active Orders (Not Delivered, Cancelled, or Refunded)
            $activeOrders = Order::whereNotIn('status', [
                OrderStatus::Delivered->value(),
                OrderStatus::Cancelled->value(),
                OrderStatus::Refunded->value(),
            ])->count();

            // 3. Low Stock SKUs
            $lowStock = ProductSku::where('track_stock', true)
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->count();

            // 4. Quote Conversion
            $totalQuotes = Quotation::count();
            $convertedQuotes = Quotation::where('status', Quotation::STATUS_CONVERTED)->count();
            $conversionRate = $totalQuotes > 0 ? ($convertedQuotes / $totalQuotes) * 100 : 0.0;

            return [
                'revenue' => $revenue,
                'active_orders' => $activeOrders,
                'low_stock' => $lowStock,
                'conversion_rate' => $conversionRate,
            ];
        });

        // Map data to DashboardWidgetDTO arrays with customized alerts/variants
        return [
            new DashboardWidgetDTO(
                label: 'Total Revenue',
                value: '₹' . number_format($data['revenue'], 2),
                trend: '+12.5%',
                trendDirection: 'up',
                description: 'vs last month',
                icon: 'lucide-credit-card',
                href: route('admin.payments.index'),
                variant: 'neutral',
                accessibilityLabel: 'Total Revenue is ₹' . number_format($data['revenue'], 2) . ', up by 12.5% compared to last month.'
            ),
            new DashboardWidgetDTO(
                label: 'Active Orders',
                value: (string)$data['active_orders'],
                trend: '+4.8%',
                trendDirection: 'up',
                description: 'vs last week',
                icon: 'lucide-shopping-cart',
                href: route('admin.sales_orders.create'),
                variant: 'neutral',
                accessibilityLabel: 'Active Orders is ' . $data['active_orders'] . ', up by 4.8% compared to last week.'
            ),
            new DashboardWidgetDTO(
                label: 'Low Stock SKUs',
                value: (string)$data['low_stock'],
                trend: $data['low_stock'] > 0 ? 'Action required' : 'Optimal level',
                trendDirection: $data['low_stock'] > 0 ? 'down' : 'neutral',
                description: $data['low_stock'] > 0 ? 'critical items' : 'all items in stock',
                icon: 'lucide-tag',
                href: route('admin.google_sheets.sync_logs.index'),
                variant: $data['low_stock'] > 5 ? 'danger' : ($data['low_stock'] > 0 ? 'warning' : 'neutral'),
                accessibilityLabel: 'Low Stock SKUs is ' . $data['low_stock'] . '.' . ($data['low_stock'] > 0 ? ' Warning: action is required.' : '')
            ),
            new DashboardWidgetDTO(
                label: 'Quote Conversion',
                value: number_format($data['conversion_rate'], 1) . '%',
                trend: '+3.1%',
                trendDirection: 'up',
                description: 'leads converted',
                icon: 'lucide-user-plus',
                href: route('admin.leads.index'),
                variant: 'neutral',
                accessibilityLabel: 'Quote Conversion rate is ' . number_format($data['conversion_rate'], 1) . '%, up by 3.1% compared to last month.'
            ),
        ];
    }

    /**
     * Get the recent activity timeline collection for the current user.
     * 
     * @return Collection<ActivityItemDTO>
     */
    public function getRecentActivity(User $user, int $limit = 5): Collection
    {
        $logs = Cache::remember("dashboard_activity_user_{$user->id}", 30, function () use ($limit) {
            $allowedActions = [
                'orders.order_created',
                'orders.order_cancelled',
                'payments.payment_recorded',
                'refunds.refund_requested',
                'refunds.refund_approved',
                'purchase_orders.created',
                'leads.created',
                'vendors.created',
            ];

            return AuditLog::with('actorUser')
                ->whereIn('action', $allowedActions)
                ->orderBy('occurred_at', 'desc')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();
        });

        $mapper = new ActivityMapper();

        return collect($logs)->map(fn(AuditLog $log) => $mapper->map($log));
    }

    /**
     * Get the sales revenue trend data points.
     */
    public function getRevenueTrendSeries(): ChartSeriesDTO
    {
        return Cache::remember('dashboard:charts:revenue', 300, function () {
            $pointsMap = collect();
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $key = $date->format('Y-m');
                $pointsMap->put($key, [
                    'label' => $date->format('M'),
                    'value' => 0.0,
                ]);
            }

            // Query revenue orders from the last 6 calendar months
            $orders = Order::where('status', '!=', OrderStatus::Cancelled->value())
                ->where('placed_at', '>=', Carbon::now()->subMonths(5)->startOfMonth())
                ->get();

            foreach ($orders as $order) {
                $key = $order->placed_at->format('Y-m');
                if ($pointsMap->has($key)) {
                    $item = $pointsMap->get($key);
                    $pointsMap->put($key, [
                        'label' => $item['label'],
                        'value' => $item['value'] + ($order->total_amount_minor / 100.0),
                    ]);
                }
            }

            $points = $pointsMap->map(fn($item) => new ChartPointDTO(
                label: $item['label'],
                value: $item['value'],
                formattedValue: '₹' . number_format($item['value'], 0)
            ))->values();

            // Calculate MoM trend indicators
            $n = $points->count();
            $currentValue = $n >= 1 ? $points[$n - 1]->value : 0.0;
            $previousValue = $n >= 2 ? $points[$n - 2]->value : 0.0;

            $diff = $currentValue - $previousValue;
            $changePercent = $previousValue > 0.0 ? ($diff / $previousValue) * 100.0 : 0.0;
            $changeDirection = $diff > 0.0 ? 'up' : ($diff < 0.0 ? 'down' : 'neutral');

            return new ChartSeriesDTO(
                title: 'Revenue Trend',
                points: $points,
                color: 'chart-2',
                unit: '₹',
                currentValue: $currentValue,
                previousValue: $previousValue,
                changePercent: round($changePercent, 1),
                changeDirection: $changeDirection
            );
        });
    }

    /**
     * Get the quote pipeline overview distribution.
     */
    public function getQuotePipelineSeries(): ChartSeriesDTO
    {
        return Cache::remember('dashboard:charts:quotes', 300, function () {
            // Count quotes grouped by status
            $quotes = Quotation::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status');

            $pipelineStatuses = [
                Quotation::STATUS_DRAFT => 'Draft',
                Quotation::STATUS_SENT => 'Sent',
                Quotation::STATUS_APPROVED => 'Approved',
                Quotation::STATUS_CONVERTED => 'Converted',
                Quotation::STATUS_EXPIRED => 'Expired',
            ];

            $points = collect();
            foreach ($pipelineStatuses as $statusKey => $label) {
                $val = (float)($quotes[$statusKey] ?? 0.0);
                $points->push(new ChartPointDTO(
                    label: $label,
                    value: $val,
                    formattedValue: (string)$val
                ));
            }

            return new ChartSeriesDTO(
                title: 'Quote Pipeline',
                points: $points,
                color: 'chart-6',
                unit: '',
                currentValue: (float)Quotation::count()
            );
        });
    }

    /**
     * Helper to clear the dashboard caches on updates.
     */
    public function clearCache(User $user): void
    {
        Cache::forget('admin_dashboard_metrics_data');
        Cache::forget("dashboard_activity_user_{$user->id}");
        Cache::forget('dashboard:charts:revenue');
        Cache::forget('dashboard:charts:quotes');
    }
}
