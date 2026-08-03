<?php

namespace App\Support\Expenses;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ExpenseFilters
{
    public string $search;

    public string $status;

    public ?int $categoryId;

    public ?string $categoryPublicId;

    public ?string $dateFrom;

    public ?string $dateTo;

    public string $sortBy;

    public string $sortOrder;

    public int $perPage;

    public ?string $groupBy;

    public function __construct(array $attributes = [])
    {
        $this->search = trim((string) ($attributes['search'] ?? ''));
        $this->status = trim((string) ($attributes['status'] ?? ''));
        $this->categoryId = isset($attributes['category_id']) && is_numeric($attributes['category_id']) ? (int) $attributes['category_id'] : null;
        $this->categoryPublicId = ! empty($attributes['category_public_id']) ? (string) $attributes['category_public_id'] : (! empty($attributes['category']) ? (string) $attributes['category'] : null);

        // Accept both date_from/date_to (internal) and start_date/end_date (report API)
        $this->dateFrom = ! empty($attributes['date_from']) ? (string) $attributes['date_from'] : (! empty($attributes['start_date']) ? (string) $attributes['start_date'] : null);
        $this->dateTo = ! empty($attributes['date_to']) ? (string) $attributes['date_to'] : (! empty($attributes['end_date']) ? (string) $attributes['end_date'] : null);

        $allowedSortBy = ['occurred_at', 'amount_minor', 'public_id', 'created_at'];
        $this->sortBy = in_array($attributes['sort_by'] ?? '', $allowedSortBy, true) ? (string) $attributes['sort_by'] : 'occurred_at';

        $this->sortOrder = strtolower((string) ($attributes['sort_order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $this->perPage = isset($attributes['per_page']) && is_numeric($attributes['per_page']) ? (int) $attributes['per_page'] : 15;
        $this->groupBy = ! empty($attributes['group_by']) ? (string) $attributes['group_by'] : null;

        // Validate date range order if both provided
        if ($this->dateFrom && $this->dateTo) {
            try {
                $from = Carbon::parse($this->dateFrom, config('app.timezone'));
                $to = Carbon::parse($this->dateTo, config('app.timezone'));

                if ($from->gt($to)) {
                    throw ValidationException::withMessages([
                        'date_from' => 'The date_from must be a date before or equal to date_to.',
                    ]);
                }
            } catch (\Exception $e) {
                if ($e instanceof ValidationException) {
                    throw $e;
                }
                // Invalid date format caught by request validation
            }
        }
    }
}
