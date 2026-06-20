<?php

namespace App\Support\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final class OrderIndexCatalog
{
    public function definition(): array
    {
        return [
            'key' => 'website_orders_index',
            'label' => 'Website Orders',
            'model' => Order::class,
            'base_scope' => 'websiteOrders',
            'default_sort' => [
                'placed_at' => 'desc',
                'public_id' => 'desc',
            ],
            'columns' => [
                'public_id',
                'customer',
                'status',
                'total_amount_minor',
                'currency',
                'design_approved',
                'placed_at',
            ],
            'scopes' => $this->scopes(),
            'filters' => $this->filters(),
            'safety_note' => 'Website orders only; payment, refund, shipping, and finance histories remain out of scope.',
        ];
    }

    public function query(array $criteria = []): Builder
    {
        $query = Order::query()
            ->select([
                'id',
                'public_id',
                'order_type',
                'order_source',
                'status',
                'customer_snapshot',
                'subtotal_amount_minor',
                'total_amount_minor',
                'currency',
                'design_approved',
                'placed_at',
            ])
            ->websiteOrders();

        $scope = (string) ($criteria['scope'] ?? 'all');
        $query = $this->applyScope($query, $scope);
        $query = $this->applyFilters($query, $criteria);

        return $query->orderByDesc('placed_at')->orderByDesc('public_id');
    }

    public function summarize(Order $order): array
    {
        return [
            'public_id' => $order->public_id,
            'order_type' => $order->order_type,
            'order_source' => $order->order_source,
            'status' => $order->status,
            'customer' => [
                'public_id' => data_get($order->customer_snapshot, 'public_id'),
                'name' => data_get($order->customer_snapshot, 'name'),
                'email' => data_get($order->customer_snapshot, 'email'),
            ],
            'total_amount_minor' => $order->total_amount_minor,
            'currency' => $order->currency,
            'design_approved' => $order->design_approved,
            'placed_at' => $order->placed_at?->toIso8601String(),
        ];
    }

    private function scopes(): array
    {
        return [
            [
                'key' => 'all',
                'label' => 'All Website Orders',
                'statuses' => OrderStatus::values(),
            ],
            [
                'key' => 'pending_payment',
                'label' => 'Pending Payment',
                'statuses' => [OrderStatus::PendingPayment->value()],
            ],
            [
                'key' => 'active_fulfillment',
                'label' => 'Active Fulfillment',
                'statuses' => [
                    OrderStatus::Confirmed->value(),
                    OrderStatus::InProduction->value(),
                    OrderStatus::ReadyToShip->value(),
                    OrderStatus::Shipped->value(),
                ],
            ],
            [
                'key' => 'closed',
                'label' => 'Closed',
                'statuses' => [
                    OrderStatus::Delivered->value(),
                    OrderStatus::Cancelled->value(),
                    OrderStatus::Refunded->value(),
                ],
            ],
        ];
    }

    private function filters(): array
    {
        return [
            [
                'key' => 'status',
                'label' => 'Order Status',
                'type' => 'select',
                'options' => array_map(
                    static fn (OrderStatus $status): array => [
                        'value' => $status->value(),
                        'label' => $status->label(),
                    ],
                    OrderStatus::options(),
                ),
            ],
            [
                'key' => 'design_approved',
                'label' => 'Design Approved',
                'type' => 'boolean',
                'options' => [
                    ['value' => true, 'label' => 'Approved'],
                    ['value' => false, 'label' => 'Not approved'],
                ],
            ],
            [
                'key' => 'placed_from',
                'label' => 'Placed From',
                'type' => 'date',
            ],
            [
                'key' => 'placed_to',
                'label' => 'Placed To',
                'type' => 'date',
            ],
        ];
    }

    private function applyScope(Builder $query, string $scope): Builder
    {
        return match ($scope) {
            'pending_payment' => $query->where('status', OrderStatus::PendingPayment->value()),
            'active_fulfillment' => $query->whereIn('status', [
                OrderStatus::Confirmed->value(),
                OrderStatus::InProduction->value(),
                OrderStatus::ReadyToShip->value(),
                OrderStatus::Shipped->value(),
            ]),
            'closed' => $query->whereIn('status', [
                OrderStatus::Delivered->value(),
                OrderStatus::Cancelled->value(),
                OrderStatus::Refunded->value(),
            ]),
            'all' => $query,
            default => throw new InvalidArgumentException('Unknown order index scope: '.$scope),
        };
    }

    private function applyFilters(Builder $query, array $criteria): Builder
    {
        if (array_key_exists('status', $criteria) && $criteria['status'] !== null && $criteria['status'] !== '') {
            $status = $criteria['status'];

            if (is_array($status)) {
                $query->whereIn('status', array_values(array_filter($status, static fn ($value): bool => $value !== null && $value !== '')));
            } else {
                $query->where('status', (string) $status);
            }
        }

        if (array_key_exists('design_approved', $criteria) && $criteria['design_approved'] !== null && $criteria['design_approved'] !== '') {
            $query->designApproved($this->toBoolean($criteria['design_approved']));
        }

        if (! empty($criteria['placed_from'])) {
            $query->placedFrom($this->toDateString($criteria['placed_from']));
        }

        if (! empty($criteria['placed_to'])) {
            $query->placedUntil($this->toDateString($criteria['placed_to']));
        }

        return $query;
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
    }

    private function toDateString(mixed $value): string
    {
        return CarbonImmutable::parse($value)->toDateString();
    }
}
