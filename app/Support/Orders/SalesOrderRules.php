<?php

namespace App\Support\Orders;

use App\Contracts\SalesOrderContract;
use App\Enums\OrderStatus;
use App\Enums\OrderType;

readonly class SalesOrderRules implements SalesOrderContract
{
    public function orderType(): string
    {
        return OrderType::SalesOrder->value();
    }

    public function orderSource(): string
    {
        return 'admin';
    }

    public function initialStatus(): string
    {
        return OrderStatus::Confirmed->value();
    }

    public function mayBeCreatedManually(): bool
    {
        return true;
    }

    public function mayBeCreatedFromApprovedQuotation(): bool
    {
        return true;
    }

    public function supportsAdvancePayments(): bool
    {
        return true;
    }

    public function supportsFinalBalancePayments(): bool
    {
        return true;
    }

    public function requiresGatewayInitiation(): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_type' => $this->orderType(),
            'order_source' => $this->orderSource(),
            'initial_status' => $this->initialStatus(),
            'may_be_created_manually' => $this->mayBeCreatedManually(),
            'may_be_created_from_approved_quotation' => $this->mayBeCreatedFromApprovedQuotation(),
            'supports_advance_payments' => $this->supportsAdvancePayments(),
            'supports_final_balance_payments' => $this->supportsFinalBalancePayments(),
            'requires_gateway_initiation' => $this->requiresGatewayInitiation(),
        ];
    }
}
