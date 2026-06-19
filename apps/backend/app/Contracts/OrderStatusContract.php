<?php

namespace App\Contracts;

interface OrderStatusContract
{
    public function value(): string;

    public function label(): string;

    public function isTerminal(): bool;

    public function isCustomerVisible(): bool;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
