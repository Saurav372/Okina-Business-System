<?php

namespace App\Support\Expenses;

class ExpenseFilters
{
    public string $search;

    public string $status;

    public ?int $categoryId;

    public ?string $dateFrom;

    public ?string $dateTo;

    public function __construct(array $attributes = [])
    {
        $this->search = trim((string) ($attributes['search'] ?? ''));
        $this->status = trim((string) ($attributes['status'] ?? ''));
        $this->categoryId = isset($attributes['category_id']) && is_numeric($attributes['category_id']) ? (int) $attributes['category_id'] : null;
        $this->dateFrom = ! empty($attributes['date_from']) ? (string) $attributes['date_from'] : null;
        $this->dateTo = ! empty($attributes['date_to']) ? (string) $attributes['date_to'] : null;
    }
}
