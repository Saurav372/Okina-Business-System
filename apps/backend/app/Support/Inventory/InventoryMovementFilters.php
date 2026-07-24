<?php

namespace App\Support\Inventory;

use App\Enums\InventoryDirection;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryMovementType;
use Carbon\Carbon;

class InventoryMovementFilters
{
    public ?string $search;

    public ?string $dateFrom;

    public ?string $dateTo;

    public ?InventoryMovementType $movementType;

    public ?InventoryDirection $direction;

    public ?InventoryMovementReason $reasonCode;

    public ?int $createdByUserId;

    public ?int $skuId;

    public string $sortBy;

    public string $sortOrder;

    /**
     * @param  array<string, mixed>  $input
     */
    public function __construct(array $input = [])
    {
        $this->search = isset($input['search']) && trim((string) $input['search']) !== '' ? trim((string) $input['search']) : null;

        // Default date range: Last 30 Days if empty
        $rawDateFrom = isset($input['date_from']) ? trim((string) $input['date_from']) : '';
        $rawDateTo = isset($input['date_to']) ? trim((string) $input['date_to']) : '';
        $isAllTime = isset($input['all_time']) && filter_var($input['all_time'], FILTER_VALIDATE_BOOLEAN);

        if ($rawDateFrom === '' && $rawDateTo === '' && ! $isAllTime) {
            $this->dateFrom = Carbon::now()->subDays(30)->startOfDay()->toDateTimeString();
            $this->dateTo = Carbon::now()->endOfDay()->toDateTimeString();
        } else {
            $this->dateFrom = $rawDateFrom !== '' ? Carbon::parse($rawDateFrom)->startOfDay()->toDateTimeString() : null;
            $this->dateTo = $rawDateTo !== '' ? Carbon::parse($rawDateTo)->endOfDay()->toDateTimeString() : null;
        }

        $this->movementType = isset($input['movement_type']) && $input['movement_type'] !== 'all' && $input['movement_type'] !== ''
            ? InventoryMovementType::tryFrom((string) $input['movement_type'])
            : null;

        $this->direction = isset($input['direction']) && $input['direction'] !== 'all' && $input['direction'] !== ''
            ? InventoryDirection::tryFrom((string) $input['direction'])
            : null;

        $this->reasonCode = isset($input['reason_code']) && $input['reason_code'] !== 'all' && $input['reason_code'] !== ''
            ? InventoryMovementReason::tryFrom((string) $input['reason_code'])
            : null;

        $this->createdByUserId = isset($input['created_by_user_id']) && ctype_digit((string) $input['created_by_user_id'])
            ? (int) $input['created_by_user_id']
            : null;

        $this->skuId = isset($input['sku_id']) && ctype_digit((string) $input['sku_id'])
            ? (int) $input['sku_id']
            : null;

        $this->sortBy = isset($input['sort_by']) && in_array($input['sort_by'], ['occurred_at', 'quantity', 'product_name', 'sku_code'], true)
            ? (string) $input['sort_by']
            : 'occurred_at';

        $this->sortOrder = isset($input['sort_order']) && strtolower((string) $input['sort_order']) === 'asc'
            ? 'asc'
            : 'desc';
    }

    /**
     * Export raw filter state as array for views and URLs.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'date_from' => $this->dateFrom ? Carbon::parse($this->dateFrom)->toDateString() : null,
            'date_to' => $this->dateTo ? Carbon::parse($this->dateTo)->toDateString() : null,
            'movement_type' => $this->movementType?->value,
            'direction' => $this->direction?->value,
            'reason_code' => $this->reasonCode?->value,
            'created_by_user_id' => $this->createdByUserId,
            'sku_id' => $this->skuId,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
        ], fn ($val) => $val !== null && $val !== '');
    }

    public function isFiltered(): bool
    {
        return $this->search !== null
            || $this->movementType !== null
            || $this->direction !== null
            || $this->reasonCode !== null
            || $this->createdByUserId !== null
            || $this->skuId !== null
            || $this->dateFrom !== null
            || $this->dateTo !== null;
    }
}
