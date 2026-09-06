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

    public bool $allTime = false;

    public bool $isDefaultDateScope = false;

    public bool $hasActiveUserFilters = false;

    public string $dateRangeBadge = '';

    /**
     * @param  array<string, mixed>  $input
     */
    public function __construct(array $input = [])
    {
        $this->search = isset($input['search']) && trim((string) $input['search']) !== '' ? trim((string) $input['search']) : null;

        $rawDateFrom = isset($input['date_from']) ? trim((string) $input['date_from']) : '';
        $rawDateTo = isset($input['date_to']) ? trim((string) $input['date_to']) : '';
        $this->allTime = isset($input['all_time']) && filter_var($input['all_time'], FILTER_VALIDATE_BOOLEAN);

        if ($this->allTime) {
            $this->dateFrom = null;
            $this->dateTo = null;
            $this->dateRangeBadge = 'Date Range: All Time';
        } elseif ($rawDateFrom !== '' || $rawDateTo !== '') {
            if ($rawDateFrom !== '' && $rawDateTo !== '') {
                $this->dateFrom = Carbon::parse($rawDateFrom)->startOfDay()->toDateTimeString();
                $this->dateTo = Carbon::parse($rawDateTo)->endOfDay()->toDateTimeString();
                $this->dateRangeBadge = 'Date Range: '.Carbon::parse($rawDateFrom)->format('M d, Y').' – '.Carbon::parse($rawDateTo)->format('M d, Y');
            } elseif ($rawDateFrom !== '') {
                $this->dateFrom = Carbon::parse($rawDateFrom)->startOfDay()->toDateTimeString();
                $this->dateTo = Carbon::now()->endOfDay()->toDateTimeString();
                $this->dateRangeBadge = 'Date Range: From '.Carbon::parse($rawDateFrom)->format('M d, Y');
            } else {
                $this->dateFrom = null;
                $this->dateTo = Carbon::parse($rawDateTo)->endOfDay()->toDateTimeString();
                $this->dateRangeBadge = 'Date Range: Through '.Carbon::parse($rawDateTo)->format('M d, Y');
            }
        } else {
            $this->isDefaultDateScope = true;
            $this->dateFrom = Carbon::now()->subDays(30)->startOfDay()->toDateTimeString();
            $this->dateTo = Carbon::now()->endOfDay()->toDateTimeString();
            $this->dateRangeBadge = 'Date Range: Last 30 Days (Default)';
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

        $this->createdByUserId = isset($input['created_by_user_id']) && is_numeric($input['created_by_user_id'])
            ? (int) $input['created_by_user_id']
            : null;

        $this->skuId = isset($input['sku_id']) && is_numeric($input['sku_id'])
            ? (int) $input['sku_id']
            : null;

        $this->sortBy = isset($input['sort_by']) && in_array($input['sort_by'], ['occurred_at', 'quantity', 'product_name', 'sku_code'], true)
            ? (string) $input['sort_by']
            : 'occurred_at';

        $this->sortOrder = isset($input['sort_order']) && strtolower((string) $input['sort_order']) === 'asc'
            ? 'asc'
            : 'desc';

        $this->hasActiveUserFilters = $this->search !== null
            || $this->movementType !== null
            || $this->direction !== null
            || $this->reasonCode !== null
            || $this->createdByUserId !== null
            || $this->skuId !== null
            || $this->allTime
            || $rawDateFrom !== ''
            || $rawDateTo !== '';
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
