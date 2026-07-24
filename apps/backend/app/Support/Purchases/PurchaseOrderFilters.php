<?php

namespace App\Support\Purchases;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use Carbon\Carbon;

class PurchaseOrderFilters
{
    public ?string $search;

    public ?string $dateFrom;

    public ?string $dateTo;

    public ?VendorOrderStatus $status;

    public ?VendorOrderPaymentStatus $paymentStatus;

    public ?int $vendorId;

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
            ? VendorOrderStatus::tryFrom((string) $input['status'])
            : null;

        $this->paymentStatus = isset($input['payment_status']) && $input['payment_status'] !== 'all' && $input['payment_status'] !== ''
            ? VendorOrderPaymentStatus::tryFrom((string) $input['payment_status'])
            : null;

        $this->vendorId = isset($input['vendor_id']) && ctype_digit((string) $input['vendor_id'])
            ? (int) $input['vendor_id']
            : null;

        $this->sortBy = isset($input['sort_by']) && in_array($input['sort_by'], ['id', 'total_amount_minor', 'ordered_at', 'created_at'], true)
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
            'payment_status' => $this->paymentStatus?->value,
            'vendor_id' => $this->vendorId,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
        ], fn ($val) => $val !== null && $val !== '');
    }

    public function isFiltered(): bool
    {
        return $this->search !== null
            || $this->status !== null
            || $this->paymentStatus !== null
            || $this->vendorId !== null
            || $this->dateFrom !== null
            || $this->dateTo !== null;
    }
}
