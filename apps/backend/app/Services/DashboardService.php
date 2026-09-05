<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\VendorOrder;
use App\Support\Dashboard\ActivityMapper;
use App\Support\Dashboard\ChartPointDTO;
use App\Support\Dashboard\ChartSeriesDTO;
use App\Support\Dashboard\DashboardNumbers;
use App\Support\Dashboard\DashboardOrders;
use App\Support\Dashboard\DashboardWidgetDTO;
use App\Support\Inventory\InventoryQueryBuilder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class DashboardService
{
    /** @return array<DashboardWidgetDTO> */
    public function getWidgetsData(): array
    {
        // Operational queues are read fresh so returning from an action updates the cards.
        $statuses = DashboardOrders::open()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $open = $statuses->sum();
        $awaiting = $statuses->get(OrderStatus::PendingPayment->value, 0);
        $ready = $statuses->get(OrderStatus::ReadyToShip->value, 0);
        $advances = count(DashboardOrders::awaitingAdvanceIds());
        $today = Carbon::today()->toDateString();
        $todayOrders = Order::query()->whereDate('placed_at', $today)->count();
        $collections = Payment::where('status', 'succeeded')->whereDate('paid_at', $today)->sum('amount_minor') / 100;
        $stockQuery = InventoryQueryBuilder::baseQuery();
        $stockCount = (clone $stockQuery)->count();
        $low = InventoryQueryBuilder::applyStatus($stockQuery, 'needs_attention')->count();
        $pos = VendorOrder::whereIn('status', ['draft', 'ordered', 'partially_received'])->count();
        $balance = app(FinanceReportService::class)->calculatePerOrderOutstandingReceivables(null) / 100;
        $parts = [];
        foreach (OrderStatus::cases() as $status) {
            if ($count = $statuses->get($status->value, 0)) {
                $parts[] = $count.' '.strtolower($status->label());
            }
        }
        $link = fn (string $ability, string $route, array $params = []) => Gate::allows($ability) ? route($route, $params) : null;
        $ordersLink = fn (array $params = []) => Gate::allows('viewAny', Order::class) ? route('admin.orders.index', $params) : null;

        return [
            new DashboardWidgetDTO(key: 'open_orders', label: 'Open Orders', value: (string) $open,
                description: $awaiting ? $awaiting.' awaiting payment' : ($open ? 'Orders moving through fulfilment' : 'No open orders'),
                detail: implode(' · ', $parts), icon: 'lucide-shopping-cart', href: $ordersLink(['scope' => 'open']),
                variant: $awaiting ? 'warning' : 'neutral', action: 'View open orders', primary: true),
            new DashboardWidgetDTO(key: 'receivables', label: 'Outstanding Receivables', value: DashboardNumbers::money($balance),
                description: $balance > 0 ? 'Unpaid balances on confirmed orders' : 'No outstanding receivables',
                detail: 'Due-date aging is not available', icon: 'lucide-credit-card',
                href: $link('reports.finance.view', 'admin.reports.finance.index', ['preset' => 'custom', 'start_date' => $today, 'end_date' => $today]), action: 'View receivables report', primary: true),
            new DashboardWidgetDTO(key: 'advances', label: 'Payments Awaiting Advance', value: (string) $advances,
                description: $advances ? $advances.' orders awaiting deposit' : 'No payments awaiting deposit',
                icon: 'lucide-credit-card', href: $ordersLink(['scope' => 'advance_pending']),
                variant: $advances ? 'warning' : 'neutral', action: 'View awaiting deposits'),
            new DashboardWidgetDTO(key: 'today_orders', label: 'Orders Today', value: (string) $todayOrders,
                description: $todayOrders ? 'Placed today' : 'No orders placed today', icon: 'lucide-shopping-cart',
                href: $ordersLink(['placed_from' => $today, 'placed_to' => $today]), action: 'View today’s orders'),
            new DashboardWidgetDTO(key: 'dispatch', label: 'Ready to Dispatch', value: (string) $ready,
                description: $ready ? 'Ready for shipment' : 'No orders ready for dispatch', icon: 'lucide-truck',
                href: $ordersLink(['status' => OrderStatus::ReadyToShip->value]), variant: $ready ? 'warning' : 'neutral', action: 'View dispatch queue'),
            new DashboardWidgetDTO(key: 'low_stock', label: 'Low Stock Items', value: (string) $low,
                description: $low ? 'Low or depleted stock needs attention' : ($stockCount ? 'All items sufficiently stocked' : 'No inventory items tracked yet'),
                icon: 'lucide-tag', href: $link('inventory.view', 'admin.inventory.index', ['status' => 'needs_attention']),
                variant: $low ? 'warning' : 'neutral', action: 'View stock balances'),
            new DashboardWidgetDTO(key: 'collections', label: 'Collections Today', value: DashboardNumbers::money($collections),
                description: $collections > 0 ? 'Successful payments received today' : 'No collections today', icon: 'lucide-credit-card',
                href: Gate::allows('viewAny', Payment::class) ? route('admin.payments.index', ['status' => 'succeeded', 'paid_on' => $today]) : null,
                action: 'View today’s payments'),
            new DashboardWidgetDTO(key: 'purchase_orders', label: 'Active Purchase Orders', value: (string) $pos,
                description: $pos ? 'Draft, ordered or partially received' : 'No active purchase orders', icon: 'lucide-truck',
                href: Gate::allows('viewAny', VendorOrder::class) ? route('admin.purchase_orders.index', ['scope' => 'active']) : null,
                action: 'View active purchases'),
        ];
    }

    public function getRecentActivity(User $user, int $limit = 5): Collection
    {
        $actions = ActivityMapper::allowedActions($user);

        return AuditLog::with('actorUser')->whereIn('action', $actions)
            ->where(function ($query) {
                $query->where('action', '!=', 'vendors.created')
                    ->orWhereNotNull('metadata->vendor_code')->orWhereNotNull('summary');
            })
            ->orderByDesc('occurred_at')->orderByDesc('id')->limit($limit)->get()
            ->map(fn (AuditLog $log) => (new ActivityMapper)->map($log));
    }

    public function getRevenueTrendSeries(int $months = 6): ChartSeriesDTO
    {
        return $this->chartSeries($months, true);
    }

    public function getMonthlyOrdersSeries(int $months = 6): ChartSeriesDTO
    {
        return $this->chartSeries($months, false);
    }

    private function chartSeries(int $months, bool $money): ChartSeriesDTO
    {
        $months = in_array($months, [3, 6, 12], true) ? $months : 6;
        $now = Carbon::now();
        $start = $now->copy()->startOfMonth()->subMonthsNoOverflow($months - 1);
        // Compare equal numbers of elapsed days ending immediately before this period.
        $days = (int) $start->diffInDays($now->copy()->startOfDay());
        $previousStart = $start->copy()->subDays($days + 1);
        $previousEnd = $start->copy()->subDay()->setTime($now->hour, $now->minute, $now->second);
        $orders = Order::query()->where('status', '!=', OrderStatus::Cancelled->value)
            ->whereBetween('placed_at', [$previousStart, $now])->get(['placed_at', 'total_amount_minor']);
        $current = $orders->filter(fn (Order $order) => $order->placed_at->gte($start));
        $previous = $orders->filter(fn (Order $order) => $order->placed_at->betweenIncluded($previousStart, $previousEnd));
        $points = collect();
        for ($i = 0; $i < $months; $i++) {
            $date = $start->copy()->addMonthsNoOverflow($i);
            $monthOrders = $current->filter(fn (Order $order) => $order->placed_at->format('Y-m') === $date->format('Y-m'));
            $value = $money ? $monthOrders->sum('total_amount_minor') / 100 : $monthOrders->count();
            $points->push(new ChartPointDTO(
                label: $date->format('M'), value: (float) $value,
                formattedValue: $money ? DashboardNumbers::money($value, 2) : number_format($value).' orders',
                fullLabel: $date->format('F Y'), orderCount: $monthOrders->count(), partial: $date->isSameMonth($now),
            ));
        }
        $total = $money ? $current->sum('total_amount_minor') / 100 : $current->count();
        $prior = $money ? $previous->sum('total_amount_minor') / 100 : $previous->count();

        return new ChartSeriesDTO(
            title: $money ? 'Order Value' : 'Monthly Orders', points: $points,
            color: $money ? 'chart-2' : 'chart-1', unit: $money ? '₹' : '',
            currentValue: (float) $total, previousValue: (float) $prior,
            changePercent: $prior > 0 ? round(($total - $prior) / $prior * 100, 1) : null,
            changeDirection: $total > $prior ? 'up' : ($total < $prior ? 'down' : 'neutral'),
            periodLabel: $start->format('j M Y').' – '.$now->format('j M Y'),
            comparisonLabel: $previousStart->format('j M Y').' – '.$previousEnd->format('j M Y').' (same elapsed time)',
            partialLabel: $now->format('M').' data through '.$now->format('j M'),
        );
    }

    public function clearCache(User $user): void
    {
        Cache::forget('admin_dashboard_metrics_data');
        Cache::forget("dashboard_activity_user_{$user->id}");
        Cache::forget('dashboard:charts:revenue');
        Cache::forget('dashboard:charts:orders');
    }
}
