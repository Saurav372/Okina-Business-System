<?php

namespace App\Support\Dashboard;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\VendorOrder;
use Illuminate\Support\Facades\Gate;

class ActivityMapper
{
    private const EVENTS = [
        'order.created' => ['Order created', 'lucide-shopping-cart', 'info', 'orders.view'],
        'order.cancelled' => ['Order cancelled', 'lucide-shopping-cart', 'neutral', 'orders.view'],
        'payment.recorded' => ['Payment recorded', 'lucide-credit-card', 'info', 'payments.view'],
        'refund.requested' => ['Refund requested', 'lucide-credit-card', 'warning', 'refunds.view'],
        'refund.approved' => ['Refund approved', 'lucide-credit-card', 'info', 'refunds.view'],
        'stock.moved' => ['Stock adjusted', 'lucide-tag', 'info', 'inventory.view'],
        'purchase_orders.created' => ['Purchase order created', 'lucide-truck', 'info', 'purchases.view'],
        'vendors.created' => ['Vendor created', 'lucide-user', 'info', 'vendors.view'],
    ];

    private const ALIASES = [
        'orders.order_created' => 'order.created', 'orders.order_cancelled' => 'order.cancelled',
        'payments.payment_recorded' => 'payment.recorded', 'refunds.refund_requested' => 'refund.requested',
        'refunds.refund_approved' => 'refund.approved', 'inventory.stock_moved' => 'stock.moved',
    ];

    /** @return array<string> */
    public static function allowedActions(User $user): array
    {
        $allowed = array_keys(array_filter(self::EVENTS, fn ($event) => $user->hasPermissionTo($event[3])));
        foreach (self::ALIASES as $legacy => $canonical) {
            if (in_array($canonical, $allowed, true)) {
                $allowed[] = $legacy;
            }
        }

        return $allowed;
    }

    public function map(AuditLog $log): ActivityItemDTO
    {
        $action = self::ALIASES[$log->action] ?? $log->action;
        [$title, $icon, $variant] = self::EVENTS[$action] ?? ['Activity recorded', 'lucide-clipboard', 'neutral'];
        $data = array_merge($log->metadata ?? [], $log->new_values ?? []);
        $reference = $data['order_public_id'] ?? ($log->subject_type === 'order' ? $log->subject_public_id : null);
        if (str_starts_with($action, 'order.') && $reference) {
            $title = 'Order #'.$reference.($action === 'order.created' ? ' created' : ' cancelled');
        }
        if ($action === 'payment.recorded' && isset($data['amount_minor']) && ($data['record_status'] ?? $data['payment_status'] ?? null) === 'succeeded') {
            $amount = ($data['currency'] ?? 'INR') === 'INR'
                ? DashboardNumbers::money((float) $data['amount_minor'] / 100, 2)
                : $data['currency'].' '.number_format((float) $data['amount_minor'] / 100, 2);
            $title = $amount.' received';
            $variant = 'success';
        }
        if ($action === 'stock.moved' && ! empty($data['sku_public_id'])) {
            $title = 'Stock adjusted · '.$data['sku_public_id'];
        }
        if ($action === 'purchase_orders.created' && ! empty($data['public_id'])) {
            $title = 'Purchase order #'.$data['public_id'].' created';
        }
        if ($action === 'vendors.created' && ! empty($data['vendor_code'])) {
            $title = 'Vendor '.$data['vendor_code'].' created';
        }
        // A recorded human-readable summary is more useful than a generic category.
        if ($log->summary && ! $reference && str_starts_with($action, 'order.')) {
            $title = $log->summary;
        }
        $description = $log->summary && $log->summary !== $title ? $log->summary : ($reference ? 'Order #'.$reference : '');
        if ($action === 'stock.moved' && isset($data['before_on_hand'], $data['after_on_hand'])) {
            $description = $data['before_on_hand'].' → '.$data['after_on_hand'].' units on hand';
        }
        $actor = $log->actor_label_snapshot ?? $log->actorUser?->name ?? 'System';

        return new ActivityItemDTO(
            title: $title, description: $description, icon: $icon, variant: $variant,
            occurredAt: $log->occurred_at ?? $log->created_at,
            href: $this->resolveLink($log, $action, $data), actorName: $actor, actorInitials: $this->getInitials($actor),
        );
    }

    protected function getInitials(string $name): string
    {
        $words = array_values(array_filter(preg_split('/\s+/', trim($name))));
        if ($words === []) {
            return 'SY';
        }

        return count($words) >= 2
            ? mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr(end($words), 0, 1))
            : mb_strtoupper(mb_substr($words[0], 0, 2));
    }

    private function resolveLink(AuditLog $log, string $action, array $data): ?string
    {
        if (str_starts_with($action, 'order.')) {
            $ref = $log->subject_public_id ?? $data['order_public_id'] ?? null;
            $order = $ref ? Order::where('public_id', $ref)->first() : null;

            return $order && Gate::allows('view', $order) ? route('admin.orders.show', $order) : null;
        }
        if ($action === 'payment.recorded') {
            $id = $log->subject_id ?? $data['payment_public_id'] ?? null;
            $payment = $id && ctype_digit((string) $id) ? Payment::find($id) : null;

            return $payment && Gate::allows('view', $payment) ? route('admin.payments.show', $payment) : null;
        }
        if ($action === 'purchase_orders.created') {
            $ref = $log->subject_public_id ?? $data['public_id'] ?? null;
            $order = $ref ? VendorOrder::where('public_id', $ref)->first() : null;

            return $order && Gate::allows('view', $order) ? route('admin.purchases.show', $order->public_id) : null;
        }
        if ($action === 'stock.moved' && ! empty($data['sku_public_id']) && Gate::allows('inventory.view')) {
            return route('admin.inventory.index', ['search' => $data['sku_public_id']]);
        }

        return null;
    }
}
