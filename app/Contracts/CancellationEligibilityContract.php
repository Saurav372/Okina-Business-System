<?php

namespace App\Contracts;

interface CancellationEligibilityContract
{
    /**
     * @return array<int, string>
     */
    public function orderTypes(): array;

    /**
     * @return array<int, string>
     */
    public function cancellableStatusesForWebsiteOrders(): array;

    /**
     * @return array<int, string>
     */
    public function cancellableStatusesForSalesOrders(): array;

    public function canCancel(string $orderType, string $status): bool;

    public function cancellationIsSeparateFromRefunds(): bool;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
