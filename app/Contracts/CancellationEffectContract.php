<?php

namespace App\Contracts;

interface CancellationEffectContract
{
    public function cancelledOrderStatus(): string;

    public function changesPaymentFacts(): bool;

    public function triggersRefundExecution(): bool;

    public function changesStockOnCancellation(): bool;

    public function keepsTheCancelledOrderCustomerVisible(): bool;

    public function hidesSensitiveCancellationNotesFromCustomers(): bool;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
