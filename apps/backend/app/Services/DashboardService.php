<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductSku;
use App\Models\Refund;
use App\Models\User;
use App\Models\VendorOrder;
use App\Support\Dashboard\ActivityMapper;
use App\Support\Dashboard\ChartPointDTO;
use App\Support\Dashboard\ChartSeriesDTO;
use App\Support\Dashboard\DashboardWidgetDTO;
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
            // 1. Today's Orders
            $todaysOrders = Order::whereDate('created_at', Carbon::today())->count();

            // 2. Pending Orders
            $pendingOrders = Order::whereNotIn('status', [
                OrderStatus::Delivered->value(),
                OrderStatus::Cancelled->value(),
                OrderStatus::Refunded->value(),
            ])->count();

            // 3. Advance Payments Pending
            // We find orders where paid < expected_advance (and expected_advance > 0)
            $orders = Order::whereNotIn('status', [OrderStatus::Cancelled->value(), OrderStatus::Refunded->value()])
                ->with(['payments'])
                ->get();
            $advancePendingCount = 0;
            foreach ($orders as $o) {
                $paid = (int) $o->payments->where('status', 'succeeded')->sum('amount_minor');
                $expected = $o->getExpectedAdvanceAmount();
                if ($expected > 0 && $paid < $expected) {
                    $advancePendingCount++;
                }
            }

            // 4. Outstanding Balance
            $totalSalesMinor = Order::where('status', '!=', OrderStatus::Cancelled->value())->sum('total_amount_minor');
            $totalPaymentsMinor = Payment::where('status', 'succeeded')->sum('amount_minor');
            $totalRefundsMinor = Refund::where('status', 'succeeded')->sum('amount_minor');
            $outstandingBalance = ($totalSalesMinor - $totalPaymentsMinor + $totalRefundsMinor) / 100;

            // 5. Low Stock SKUs
            $lowStock = ProductSku::where('track_stock', true)
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->count();

            // 6. Today's Collections
            $todaysCollectionsMinor = Payment::where('status', 'succeeded')
                ->whereDate('paid_at', Carbon::today())
                ->sum('amount_minor');
            $todaysCollections = $todaysCollectionsMinor / 100;

            // 7. Active Purchase Orders
            $activePOs = VendorOrder::whereNotIn('status', ['completed', 'cancelled'])->count();

            return [
                'todays_orders' => $todaysOrders,
                'pending_orders' => $pendingOrders,
                'advance_pending' => $advancePendingCount,
                'outstanding_balance' => $outstandingBalance,
                'low_stock' => $lowStock,
                'todays_collections' => $todaysCollections,
                'active_pos' => $activePOs,
            ];
        });

        return [
            new DashboardWidgetDTO(
                label: "Today's Orders",
                value: (string) $data['todays_orders'],
                trend: 'neutral',
                trendDirection: 'neutral',
                description: 'orders placed today',
                icon: 'lucide-shopping-cart',
                href: route('admin.sales_orders.create'),
                variant: 'neutral',
                accessibilityLabel: "Today's Orders is ".$data['todays_orders']
            ),
            new DashboardWidgetDTO(
                label: 'Pending Orders',
                value: (string) $data['pending_orders'],
                trend: 'neutral',
                trendDirection: 'neutral',
                description: 'in processing pipeline',
                icon: 'lucide-clock',
                href: route('admin.sales_orders.create'),
                variant: $data['pending_orders'] > 10 ? 'warning' : 'neutral',
                accessibilityLabel: 'Pending Orders is '.$data['pending_orders']
            ),
            new DashboardWidgetDTO(
                label: 'Advance Payments Pending',
                value: (string) $data['advance_pending'],
                trend: 'action required',
                trendDirection: 'down',
                description: 'awaiting deposit',
                icon: 'lucide-alert-circle',
                href: route('admin.payments.index'),
                variant: $data['advance_pending'] > 0 ? 'warning' : 'neutral',
                accessibilityLabel: 'Advance Payments Pending is '.$data['advance_pending']
            ),
            new DashboardWidgetDTO(
                label: 'Outstanding Balance',
                value: '₹'.number_format($data['outstanding_balance'], 2),
                trend: 'neutral',
                trendDirection: 'neutral',
                description: 'receivable from clients',
                icon: 'lucide-credit-card',
                href: route('admin.accounting.customer_ledger'),
                variant: 'neutral',
                accessibilityLabel: 'Outstanding Balance is ₹'.number_format($data['outstanding_balance'], 2)
            ),
            new DashboardWidgetDTO(
                label: 'Low Stock SKUs',
                value: (string) $data['low_stock'],
                trend: $data['low_stock'] > 0 ? 'critical level' : 'optimal',
                trendDirection: $data['low_stock'] > 0 ? 'down' : 'neutral',
                description: 'items below threshold',
                icon: 'lucide-tag',
                href: route('admin.google_sheets.sync_logs.index'),
                variant: $data['low_stock'] > 0 ? 'danger' : 'neutral',
                accessibilityLabel: 'Low Stock SKUs is '.$data['low_stock']
            ),
            new DashboardWidgetDTO(
                label: "Today's Collections",
                value: '₹'.number_format($data['todays_collections'], 2),
                trend: 'up',
                trendDirection: 'up',
                description: 'collected today',
                icon: 'lucide-arrow-down-left',
                href: route('admin.payments.index'),
                variant: 'neutral',
                accessibilityLabel: "Today's Collections is ₹".number_format($data['todays_collections'], 2)
            ),
            new DashboardWidgetDTO(
                label: 'Purchase Orders',
                value: (string) $data['active_pos'],
                trend: 'neutral',
                trendDirection: 'neutral',
                description: 'active supply orders',
                icon: 'lucide-truck',
                href: route('admin.purchase_orders.index'),
                variant: 'neutral',
                accessibilityLabel: 'Purchase Orders count is '.$data['active_pos']
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
                'vendors.created',
            ];

            return AuditLog::with('actorUser')
                ->whereIn('action', $allowedActions)
                ->orderBy('occurred_at', 'desc')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();
        });

        $mapper = new ActivityMapper;

        return collect($logs)->map(fn (AuditLog $log) => $mapper->map($log));
    }

    /**
     * Get the sales revenue trend data points.
     */
    public function getRevenueTrendSeries(): ChartSeriesDTO
    {
        $data = Cache::remember('dashboard:charts:revenue', 300, function () {
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

            $points = $pointsMap->map(fn ($item) => [
                'label' => $item['label'],
                'value' => $item['value'],
                'formattedValue' => '₹'.number_format($item['value'], 0),
            ])->values()->toArray();

            // Calculate MoM trend indicators
            $n = count($points);
            $currentValue = $n >= 1 ? $points[$n - 1]['value'] : 0.0;
            $previousValue = $n >= 2 ? $points[$n - 2]['value'] : 0.0;

            $diff = $currentValue - $previousValue;
            $changePercent = $previousValue > 0.0 ? ($diff / $previousValue) * 100.0 : 0.0;
            $changeDirection = $diff > 0.0 ? 'up' : ($diff < 0.0 ? 'down' : 'neutral');

            return [
                'points' => $points,
                'currentValue' => $currentValue,
                'previousValue' => $previousValue,
                'changePercent' => round($changePercent, 1),
                'changeDirection' => $changeDirection,
            ];
        });

        $points = collect($data['points'])->map(fn ($item) => new ChartPointDTO(
            label: $item['label'],
            value: $item['value'],
            formattedValue: $item['formattedValue']
        ));

        return new ChartSeriesDTO(
            title: 'Revenue Trend',
            points: $points,
            color: 'chart-2',
            unit: '₹',
            currentValue: $data['currentValue'],
            previousValue: $data['previousValue'],
            changePercent: $data['changePercent'],
            changeDirection: $data['changeDirection']
        );
    }

    /**
     * Get the monthly orders volume series data points.
     */
    public function getMonthlyOrdersSeries(): ChartSeriesDTO
    {
        $data = Cache::remember('dashboard:charts:orders', 300, function () {
            $pointsMap = collect();
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $key = $date->format('Y-m');
                $pointsMap->put($key, [
                    'label' => $date->format('M'),
                    'value' => 0.0,
                ]);
            }

            // Query orders from the last 6 calendar months
            $orders = Order::where('status', '!=', OrderStatus::Cancelled->value())
                ->where('placed_at', '>=', Carbon::now()->subMonths(5)->startOfMonth())
                ->get();

            foreach ($orders as $order) {
                $key = $order->placed_at->format('Y-m');
                if ($pointsMap->has($key)) {
                    $item = $pointsMap->get($key);
                    $pointsMap->put($key, [
                        'label' => $item['label'],
                        'value' => $item['value'] + 1,
                    ]);
                }
            }

            $points = $pointsMap->map(fn ($item) => [
                'label' => $item['label'],
                'value' => $item['value'],
                'formattedValue' => number_format($item['value'], 0).' orders',
            ])->values()->toArray();

            // Calculate MoM trend indicators
            $n = count($points);
            $currentValue = $n >= 1 ? $points[$n - 1]['value'] : 0.0;
            $previousValue = $n >= 2 ? $points[$n - 2]['value'] : 0.0;

            $diff = $currentValue - $previousValue;
            $changePercent = $previousValue > 0.0 ? ($diff / $previousValue) * 100.0 : 0.0;
            $changeDirection = $diff > 0.0 ? 'up' : ($diff < 0.0 ? 'down' : 'neutral');

            return [
                'points' => $points,
                'currentValue' => $currentValue,
                'previousValue' => $previousValue,
                'changePercent' => round($changePercent, 1),
                'changeDirection' => $changeDirection,
            ];
        });

        $points = collect($data['points'])->map(fn ($item) => new ChartPointDTO(
            label: $item['label'],
            value: $item['value'],
            formattedValue: $item['formattedValue']
        ));

        return new ChartSeriesDTO(
            title: 'Monthly Orders',
            points: $points,
            color: 'chart-1',
            unit: '',
            currentValue: $data['currentValue'],
            previousValue: $data['previousValue'],
            changePercent: $data['changePercent'],
            changeDirection: $data['changeDirection']
        );
    }

    /**
     * Helper to clear the dashboard caches on updates.
     */
    public function clearCache(User $user): void
    {
        Cache::forget('admin_dashboard_metrics_data');
        Cache::forget("dashboard_activity_user_{$user->id}");
        Cache::forget('dashboard:charts:revenue');
        Cache::forget('dashboard:charts:orders');
    }
}
