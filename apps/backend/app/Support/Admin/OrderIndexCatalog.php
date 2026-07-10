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
            'key' => 'orders_index',
            'label' => 'Orders',
            'model' => Order::class,
            'base_scope' => 'all',
            'default_sort' => [
                'placed_at' => 'desc',
                'public_id' => 'desc',
            ],
            'columns' => [
                'public_id',
                'customer',
                'order_source',
                'status',
                'payment_status',
                'total_amount_minor',
                'placed_at',
            ],
            'scopes' => $this->scopes(),
            'filters' => $this->filters(),
            'safety_note' => 'All orders; payment, refund, shipping, and finance histories remain out of scope for the index display.',
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
                'created_at',
            ]);

        // Eager-loading payments sum for N+1 prevention
        $query->withSum(['payments' => fn($q) => $q->where('status', 'succeeded')], 'amount_minor');

        $scope = (string) ($criteria['scope'] ?? 'all');
        $query = $this->applyScope($query, $scope);
        $query = $this->applyFilters($query, $criteria);

        // Sorting (One active sort column supported at a time)
        $sort = (string) ($criteria['sort'] ?? 'placed_at');
        $direction = strtolower((string) ($criteria['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $sortField = match ($sort) {
            'public_id' => 'public_id',
            'placed_at' => 'placed_at',
            'total_amount_minor' => 'total_amount_minor',
            'status' => 'status',
            default => 'placed_at',
        };

        return $query->orderBy($sortField, $direction);
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
                'phone' => data_get($order->customer_snapshot, 'phone'),
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
                'label' => 'All Orders',
                'statuses' => OrderStatus::values(),
            ],
            [
                'key' => 'pending_payment',
                'label' => 'Pending Payment',
                'statuses' => [OrderStatus::PendingPayment->value()],
            ],
            [
                'key' => 'active',
                'label' => 'Active Orders',
                'statuses' => [
                    OrderStatus::Confirmed->value(),
                    OrderStatus::InProduction->value(),
                    OrderStatus::ReadyToShip->value(),
                    OrderStatus::Shipped->value(),
                ],
            ],
            [
                'key' => 'completed',
                'label' => 'Completed',
                'statuses' => [
                    OrderStatus::Delivered->value(),
                ],
            ],
        ];
    }

    private function filters(): array
    {
        $sources = config('orders.sources', []);
        $sourceOptions = [];
        foreach ($sources as $val => $lbl) {
            $sourceOptions[] = ['value' => $val, 'label' => $lbl];
        }

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
                'key' => 'order_source',
                'label' => 'Order Source',
                'type' => 'select',
                'options' => $sourceOptions,
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
            'active' => $query->whereIn('status', [
                OrderStatus::Confirmed->value(),
                OrderStatus::InProduction->value(),
                OrderStatus::ReadyToShip->value(),
                OrderStatus::Shipped->value(),
            ]),
            'completed' => $query->where('status', OrderStatus::Delivered->value()),
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

        if (array_key_exists('order_source', $criteria) && $criteria['order_source'] !== null && $criteria['order_source'] !== '') {
            $query->where('order_source', (string) $criteria['order_source']);
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

        if (! empty($criteria['search'])) {
            $search = (string) $criteria['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('public_id', 'like', "%{$search}%")
                  ->orWhere('customer_snapshot->name', 'like', "%{$search}%")
                  ->orWhere('customer_snapshot->email', 'like', "%{$search}%")
                  ->orWhere('customer_snapshot->phone', 'like', "%{$search}%");
            });
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
