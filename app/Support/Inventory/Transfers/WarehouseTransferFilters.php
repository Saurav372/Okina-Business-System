<?php

namespace App\Support\Inventory\Transfers;

use App\Enums\InventoryLocation;
use App\Enums\WarehouseTransferStatus;
use Carbon\Carbon;

class WarehouseTransferFilters
{
    public ?string $search;

    public ?string $dateFrom;

    public ?string $dateTo;

    public ?WarehouseTransferStatus $status;

    public ?InventoryLocation $sourceLocation;

    public ?InventoryLocation $destinationLocation;

    public string $sortBy;

    public string $sortOrder;

    /**
     * @param  array<string, mixed>  $input
     */
    public function __construct(array $input = [])
    {
        $this->search = isset($input['search']) && trim((string) $input['search']) !== '' ? trim((string) $input['search']) : null;

        $rawDateFrom = isset($input['date_from']) ? trim((string) $input['date_from']) : '';
        $rawDateTo = isset($input['date_to']) ? trim((string) $input['date_to']) : '';

        $this->dateFrom = $rawDateFrom !== '' ? Carbon::parse($rawDateFrom)->startOfDay()->toDateTimeString() : null;
        $this->dateTo = $rawDateTo !== '' ? Carbon::parse($rawDateTo)->endOfDay()->toDateTimeString() : null;

        $this->status = isset($input['status']) && $input['status'] !== 'all' && $input['status'] !== ''
            ? WarehouseTransferStatus::tryFrom((string) $input['status'])
            : null;

        $this->sourceLocation = isset($input['source_location']) && $input['source_location'] !== 'all' && $input['source_location'] !== ''
            ? InventoryLocation::tryFrom((string) $input['source_location'])
            : null;

        $this->destinationLocation = isset($input['destination_location']) && $input['destination_location'] !== 'all' && $input['destination_location'] !== ''
            ? InventoryLocation::tryFrom((string) $input['destination_location'])
            : null;

        $this->sortBy = isset($input['sort_by']) && in_array($input['sort_by'], ['id', 'quantity', 'created_at'], true)
            ? (string) $input['sort_by']
            : 'id';

        $this->sortOrder = isset($input['sort_order']) && strtolower((string) $input['sort_order']) === 'asc'
            ? 'asc'
            : 'desc';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'date_from' => $this->dateFrom ? Carbon::parse($this->dateFrom)->toDateString() : null,
            'date_to' => $this->dateTo ? Carbon::parse($this->dateTo)->toDateString() : null,
            'status' => $this->status?->value,
            'source_location' => $this->sourceLocation?->value,
            'destination_location' => $this->destinationLocation?->value,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
        ], fn ($val) => $val !== null && $val !== '');
    }

    public function isFiltered(): bool
    {
        return $this->search !== null
            || $this->status !== null
            || $this->sourceLocation !== null
            || $this->destinationLocation !== null
            || $this->dateFrom !== null
            || $this->dateTo !== null;
    }
}
