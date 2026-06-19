<?php

namespace App\Contracts;

interface OrderTypeContract
{
    public function value(): string;

    public function label(): string;

    public function isWebsiteOrder(): bool;

    public function isSalesOrder(): bool;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
