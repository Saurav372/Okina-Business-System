<?php

namespace App\Contracts;

interface SalesOrderContract
{
    public function orderType(): string;

    public function orderSource(): string;

    public function initialStatus(): string;

    public function mayBeCreatedManually(): bool;

    public function mayBeCreatedFromApprovedQuotation(): bool;

    public function supportsAdvancePayments(): bool;

    public function supportsFinalBalancePayments(): bool;

    public function requiresGatewayInitiation(): bool;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
