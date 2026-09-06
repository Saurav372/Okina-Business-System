<?php

namespace App\Services;

use App\DTOs\BulkOrderActionResult;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class BulkOrderActionService
{
    public function __construct(
        protected SalesOrderService $salesOrderService
    ) {}

    public function execute(string $action, array $orderIds, ?User $actor = null, ?string $targetStatusParam = null): BulkOrderActionResult
    {
        $targetStatus = match ($action) {
            'confirm' => OrderStatus::Confirmed,
            'cancel' => OrderStatus::Cancelled,
            'in_production' => OrderStatus::InProduction,
            'ready_to_ship' => OrderStatus::ReadyToShip,
            'shipped' => OrderStatus::Shipped,
            'delivered' => OrderStatus::Delivered,
            'update_status' => OrderStatus::tryFrom($targetStatusParam ?? '') ?? throw new InvalidArgumentException("Invalid target status: {$targetStatusParam}"),
            default => OrderStatus::tryFrom($action) ?? throw new InvalidArgumentException("Unsupported bulk action: {$action}"),
        };

        return DB::transaction(function () use ($targetStatus, $action, $orderIds, $actor) {
            // 1. Resolve and Lock Orders to prevent concurrent update races
            $orders = Order::query()
                ->whereIn('public_id', $orderIds)
                ->lockForUpdate()
                ->get();

            // 2. Strict ID verification: check if all requested unique IDs were resolved
            if ($orders->count() !== count($orderIds)) {
                throw new InvalidArgumentException('Mismatch between requested orders and database records. Verification failed.');
            }

            // 3. Post-Lock Sequential Delegation and Business Validation
            $updatedOrders = [];
            foreach ($orders as $order) {
                if ($order->status === $targetStatus->value()) {
                    continue;
                }

                $attributes = [];
                if ($targetStatus === OrderStatus::Cancelled) {
                    $attributes['cancellation_reason'] = 'Bulk cancelled by administrator';
                } elseif ($targetStatus === OrderStatus::InProduction) {
                    $attributes['production_status'] = 'in_progress';
                } elseif ($targetStatus === OrderStatus::ReadyToShip) {
                    $attributes['production_status'] = 'completed';
                    $attributes['shipping_status'] = 'ready';
                } elseif ($targetStatus === OrderStatus::Shipped) {
                    $attributes['shipping_status'] = 'shipped';
                } elseif ($targetStatus === OrderStatus::Delivered) {
                    $attributes['shipping_status'] = 'delivered';
                }

                // Try standardized status transition
                try {
                    $this->salesOrderService->transitionStatus(
                        order: $order,
                        targetStatus: $targetStatus,
                        attributes: $attributes,
                        actor: $actor
                    );
                } catch (\Illuminate\Validation\ValidationException $e) {
                    // Direct administrative override if state-machine step is skipped
                    $updateData = array_merge(['status' => $targetStatus->value()], $attributes);
                    if ($targetStatus === OrderStatus::Confirmed && $order->confirmed_at === null) {
                        $updateData['confirmed_at'] = now();
                    }
                    if ($targetStatus === OrderStatus::Cancelled && $order->cancelled_at === null) {
                        $updateData['cancelled_at'] = now();
                    }
                    if ($targetStatus === OrderStatus::ReadyToShip && $order->ready_to_ship_at === null) {
                        $updateData['ready_to_ship_at'] = now();
                    }
                    if ($targetStatus === OrderStatus::Shipped && $order->shipped_at === null) {
                        $updateData['shipped_at'] = now();
                    }
                    if ($targetStatus === OrderStatus::Delivered && $order->delivered_at === null) {
                        $updateData['delivered_at'] = now();
                    }
                    $order->update($updateData);
                }

                $updatedOrders[] = $order->public_id;
            }

            return new BulkOrderActionResult(
                updatedCount: count($updatedOrders),
                updatedPublicIds: $updatedOrders,
                action: $targetStatus->value()
            );
        });
    }
}
