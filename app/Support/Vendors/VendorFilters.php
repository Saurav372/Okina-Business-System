<?php

namespace App\Support\Vendors;

use App\Enums\VendorStatus;

class VendorFilters
{
    public ?string $search;

    public ?VendorStatus $status;

    public string $sortBy;

    public string $sortOrder;

    public int $perPage;

    /**
     * @param  array<string, mixed>  $input
     */
    public function __construct(array $input = [])
    {
        $rawSearch = isset($input['search']) ? trim((string) $input['search']) : '';
        $this->search = $rawSearch !== '' ? $rawSearch : null;

        $rawStatus = isset($input['status']) ? trim((string) $input['status']) : '';
        $this->status = ($rawStatus !== '' && $rawStatus !== 'all') ? VendorStatus::tryFrom($rawStatus) : null;

        $rawSortBy = isset($input['sort_by']) ? strtolower(trim((string) $input['sort_by'])) : '';
        $this->sortBy = in_array($rawSortBy, ['name', 'vendor_code', 'created_at'], true) ? $rawSortBy : 'name';

        $rawSortOrder = isset($input['sort_order']) ? strtolower(trim((string) $input['sort_order'])) : '';
        $this->sortOrder = $rawSortOrder === 'desc' ? 'desc' : 'asc';

        $rawPerPage = isset($input['per_page']) && is_numeric($input['per_page']) ? (int) $input['per_page'] : 15;
        $this->perPage = in_array($rawPerPage, [15, 25, 50, 100], true) ? $rawPerPage : 15;
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
            'status' => $this->status?->value,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
            'per_page' => $this->perPage,
        ], fn ($val) => $val !== null && $val !== '');
    }
}
