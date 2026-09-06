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
        protected SalesOrderService $salesOrderService,
        protected OrderCancellationService $orderCancellationService,
    ) {}

    /**
     * Dedicated bulk cancellation method with eligible succeed + ineligible skipped semantics.
     *
     * @param  array<string>  $orderIds
     * @return array{cancelled_count: int, cancelled_ids: array<string>, skipped: array<string, array<string>>}
     */
    public function bulkCancel(array $orderIds, string $reasonCode, ?string $reasonNote, User $actor): array
    {
        return DB::transaction(function () use ($orderIds, $reasonCode, $reasonNote, $actor) {
            $orders = Order::query()
                ->whereIn('public_id', $orderIds)
                ->lockForUpdate()
                ->get();

            $cancelled = [];
            $skipped = [
                'in_production' => [],
                'ready_to_ship' => [],
                'shipped_or_terminal' => [],
            ];

            foreach ($orders as $order) {
                if ($order->status === OrderStatus::InProduction->value) {
                    $skipped['in_production'][] = $order->public_id;
                    continue;
                }

                if ($order->status === OrderStatus::ReadyToShip->value) {
                    $skipped['ready_to_ship'][] = $order->public_id;
                    continue;
                }

                if (in_array($order->status, [
                    OrderStatus::Shipped->value,
                    OrderStatus::Delivered->value,
                    OrderStatus::Cancelled->value,
                    OrderStatus::Refunded->value,
                ], true)) {
                    $skipped['shipped_or_terminal'][] = $order->public_id;
                    continue;
                }

                // Eligible orders: pending_payment or confirmed
                if (in_array($order->status, [
                    OrderStatus::PendingPayment->value,
                    OrderStatus::Confirmed->value,
                ], true)) {
                    $this->orderCancellationService->cancel(
                        order: $order,
                        actor: $actor,
                        reasonCode: $reasonCode,
                        reasonNote: $reasonNote ? "[Bulk] {$reasonNote}" : '[Bulk Cancellation]',
                    );
                    $cancelled[] = $order->public_id;
                }
            }

            return [
                'cancelled_count' => count($cancelled),
                'cancelled_ids' => $cancelled,
                'skipped' => $skipped,
            ];
        });
    }

    public function execute(string $action, array $orderIds, ?User $actor = null, ?string $targetStatusParam = null): BulkOrderActionResult
    {
        if ($action === 'cancel') {
            $actorUser = $actor ?? auth()->user();
            $result = $this->bulkCancel($orderIds, 'customer_request', 'Bulk cancelled by administrator', $actorUser);

            return new BulkOrderActionResult(
                updatedCount: $result['cancelled_count'],
                updatedPublicIds: $result['cancelled_ids'],
                action: OrderStatus::Cancelled->value
            );
        }

        $targetStatus = match ($action) {
            'confirm' => OrderStatus::Confirmed,
            'in_production' => OrderStatus::InProduction,
            'ready_to_ship' => OrderStatus::ReadyToShip,
            'shipped' => OrderStatus::Shipped,
            'delivered' => OrderStatus::Delivered,
            'update_status' => OrderStatus::tryFrom($targetStatusParam ?? '') ?? throw new InvalidArgumentException("Invalid target status: {$targetStatusParam}"),
            default => OrderStatus::tryFrom($action) ?? throw new InvalidArgumentException("Unsupported bulk action: {$action}"),
        };

        if ($targetStatus === OrderStatus::Cancelled) {
            throw new InvalidArgumentException('Cancelled cannot be updated through generic status change. Use bulk cancel instead.');
        }

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
                if ($action === 'confirm' && $order->status === $targetStatus->value) {
                    throw ValidationException::withMessages([
                        'orders' => ["Order {$order->public_id} is already " . $targetStatus->label() . '.'],
                    ]);
                }

                if ($order->status === $targetStatus->value) {
                    continue;
                }

                $attributes = [];
                if ($targetStatus === OrderStatus::InProduction) {
                    $attributes['production_status'] = 'in_progress';
                } elseif ($targetStatus === OrderStatus::ReadyToShip) {
                    $attributes['production_status'] = 'completed';
                    $attributes['shipping_status'] = 'ready';
                } elseif ($targetStatus === OrderStatus::Shipped) {
                    $attributes['shipping_status'] = 'shipped';
                } elseif ($targetStatus === OrderStatus::Delivered) {
                    $attributes['shipping_status'] = 'delivered';
                }

                // Standardized status transition enforced through state machine
                $this->salesOrderService->transitionStatus(
                    order: $order,
                    targetStatus: $targetStatus,
                    attributes: $attributes,
                    actor: $actor
                );

                $updatedOrders[] = $order->public_id;
            }

            return new BulkOrderActionResult(
                updatedCount: count($updatedOrders),
                updatedPublicIds: $updatedOrders,
                action: $targetStatus->value
            );
        });
    }
}
