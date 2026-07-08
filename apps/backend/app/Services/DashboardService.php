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
     * Helper to clear the dashboard caches on updates.
     */
    public function clearCache(User $user): void
    {
        Cache::forget('admin_dashboard_metrics_data');
        Cache::forget("dashboard_activity_user_{$user->id}");
    }
}
