<?php

namespace App\Contracts;

use App\Support\Inventory\InventoryMetricsDTO;

interface InventoryMetricsProvider
{
    /**
     * Compute and return inventory metrics DTO.
     */
    public function getMetrics(?string $locationId = null): InventoryMetricsDTO;
}
