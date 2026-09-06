<?php

namespace App\Contracts;

interface PaymentStatusContract
{
    public function value(): string;

    public function label(): string;

    public function isOpenBalance(): bool;

    public function isRefundState(): bool;

    public function isSettled(): bool;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
