<?php

namespace App\Support\Dashboard;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;

class ActivityMapper
{
    /**
     * Map a single AuditLog record into an ActivityItemDTO.
     */
    public function map(AuditLog $log): ActivityItemDTO
    {
        $actorName = $log->actor_label_snapshot
            ?? $log->actorUser?->name
            ?? 'System';

        $actorInitials = $this->getInitials($actorName);

        // Map categories based on action keys
        $eventConfig = $this->getEventConfig($log->action);

        // Resolve drill-down links safely
        $href = $this->resolveLink($log);

        return new ActivityItemDTO(
            title: $eventConfig['title'] ?? $log->summary ?? 'System Action',
            description: $log->summary ?? 'Action completed successfully.',
            icon: $eventConfig['icon'] ?? 'lucide-clipboard',
            variant: $eventConfig['variant'] ?? 'neutral',
            occurredAt: $log->occurred_at ?? $log->created_at,
            href: $href,
            actorName: $actorName,
            actorInitials: $actorInitials
        );
    }

    /**
     * Extract initials.
     */
    protected function getInitials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name));
        $words = array_filter($words);
        if (empty($words)) {
            return 'SY';
        }

        return count($words) >= 2
            ? mb_strtoupper(mb_substr($words[0], 0, 1, 'UTF-8').mb_substr(end($words), 0, 1, 'UTF-8'), 'UTF-8')
            : mb_strtoupper(mb_substr($words[0], 0, 2, 'UTF-8'), 'UTF-8');
    }

    /**
     * Resolve icon and priority color variants.
     */
    protected function getEventConfig(string $action): array
    {
        if (str_starts_with($action, 'orders.')) {
            return [
                'title' => 'Sales Order',
                'icon' => 'lucide-shopping-cart',
                'variant' => 'info',
            ];
        }

        if (str_starts_with($action, 'payments.')) {
            return [
                'title' => 'Payment Collected',
                'icon' => 'lucide-credit-card',
                'variant' => 'success',
            ];
        }

        if (str_starts_with($action, 'refunds.')) {
            return [
                'title' => 'Refund Processing',
                'icon' => 'lucide-corner-down-left',
                'variant' => 'danger',
            ];
        }

        if (str_starts_with($action, 'purchase_orders.')) {
            return [
                'title' => 'Purchase Order',
                'icon' => 'lucide-truck',
                'variant' => 'warning',
            ];
        }

        if (str_starts_with($action, 'products.') || str_starts_with($action, 'inventory.')) {
            return [
                'title' => 'Inventory Update',
                'icon' => 'lucide-tag',
                'variant' => 'warning',
            ];
        }

        return [
            'title' => 'System Event',
            'icon' => 'lucide-clipboard',
            'variant' => 'neutral',
        ];
    }

    /**
     * Resolve dynamic URLs safely.
     */
    protected function resolveLink(AuditLog $log): ?string
    {
        try {
            if (str_starts_with($log->action, 'orders.') && ! empty($log->subject_public_id)) {
                if (Order::where('public_id', $log->subject_public_id)->exists()) {
                    return route('admin.orders.show', ['order' => $log->subject_public_id]);
                }
            }

            if (str_starts_with($log->action, 'payments.') && ! empty($log->subject_id)) {
                if (Payment::where('id', $log->subject_id)->exists()) {
                    return route('admin.payments.show', ['payment' => $log->subject_id]);
                }
            }
        } catch (\Exception $e) {
            // Gracefully ignore link resolution issues
        }

        return null;
    }
}
