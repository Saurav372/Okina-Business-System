<?php

namespace App\Support\Vendors;

class VendorPaymentFilters
{
    public string $search;

    public string $status;

    public string $paymentMethod;

    public ?int $vendorId;

    public ?string $dateFrom;

    public ?string $dateTo;

    public function __construct(array $attributes = [])
    {
        $this->search = trim((string) ($attributes['search'] ?? ''));
        $this->status = trim((string) ($attributes['status'] ?? ''));
        $this->paymentMethod = trim((string) ($attributes['payment_method'] ?? ''));
        $this->vendorId = isset($attributes['vendor_id']) && is_numeric($attributes['vendor_id']) ? (int) $attributes['vendor_id'] : null;
        $this->dateFrom = ! empty($attributes['date_from']) ? (string) $attributes['date_from'] : null;
        $this->dateTo = ! empty($attributes['date_to']) ? (string) $attributes['date_to'] : null;
    }
}
