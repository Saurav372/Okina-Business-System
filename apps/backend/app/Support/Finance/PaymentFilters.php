<?php

namespace App\Support\Finance;

use Carbon\Carbon;

class PaymentFilters
{
    public ?string $search;

    public ?string $provider;

    public ?string $method;

    public ?string $status;

    public ?string $startDate;

    public ?string $endDate;

    public string $sortBy;

    public string $sortOrder;

    /**
     * @param  array<string, mixed>  $input
     */
    public function __construct(array $input = [])
    {
        $this->search = isset($input['search']) && trim((string) $input['search']) !== '' ? trim((string) $input['search']) : null;
        $this->provider = isset($input['provider']) && $input['provider'] !== 'all' && $input['provider'] !== '' ? (string) $input['provider'] : null;
        $this->method = isset($input['method']) && $input['method'] !== 'all' && $input['method'] !== '' ? (string) $input['method'] : null;
        $this->status = isset($input['status']) && $input['status'] !== 'all' && $input['status'] !== '' ? (string) $input['status'] : null;

        $rawStart = isset($input['start_date']) ? trim((string) $input['start_date']) : '';
        $rawEnd = isset($input['end_date']) ? trim((string) $input['end_date']) : '';

        $this->startDate = $rawStart !== '' ? Carbon::parse($rawStart)->startOfDay()->toDateTimeString() : null;
        $this->endDate = $rawEnd !== '' ? Carbon::parse($rawEnd)->endOfDay()->toDateTimeString() : null;

        $this->sortBy = isset($input['sort_by']) && in_array($input['sort_by'], ['id', 'amount_minor', 'created_at'], true)
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
            'provider' => $this->provider,
            'method' => $this->method,
            'status' => $this->status,
            'start_date' => $this->startDate ? Carbon::parse($this->startDate)->toDateString() : null,
            'end_date' => $this->endDate ? Carbon::parse($this->endDate)->toDateString() : null,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
        ], fn ($val) => $val !== null && $val !== '');
    }

    public function isFiltered(): bool
    {
        return $this->search !== null
            || $this->provider !== null
            || $this->method !== null
            || $this->status !== null
            || $this->startDate !== null
            || $this->endDate !== null;
    }
}
