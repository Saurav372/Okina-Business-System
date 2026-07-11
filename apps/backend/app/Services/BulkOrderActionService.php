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

    public function execute(string $action, array $orderIds, ?User $actor = null): BulkOrderActionResult
    {
        $targetStatus = match ($action) {
            'confirm' => OrderStatus::Confirmed,
            'cancel' => OrderStatus::Cancelled,
            default => throw new InvalidArgumentException("Unsupported bulk action: {$action}"),
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
            foreach ($orders as $order) {
                if ($order->status === $targetStatus->value()) {
                    throw ValidationException::withMessages([
                        'orders' => ["Order {$order->public_id} is already ".$targetStatus->label().'.'],
                    ]);
                }

                $attributes = [];
                if ($targetStatus === OrderStatus::Cancelled) {
                    $attributes['cancellation_reason'] = 'Bulk cancelled by administrator';
                }

                // transitionStatus contains internal validation, ensuring atomic rollback on failure
                $this->salesOrderService->transitionStatus(
                    order: $order,
                    targetStatus: $targetStatus,
                    attributes: $attributes,
                    actor: $actor
                );
            }

            return new BulkOrderActionResult(
                updatedCount: $orders->count(),
                updatedPublicIds: $orders->pluck('public_id')->all(),
                action: $action
            );
        });
    }
}
