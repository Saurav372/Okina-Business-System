<?php

namespace App\DTOs;

final class BulkOrderActionResult
{
    public function __construct(
        public readonly int $updatedCount,
        public readonly array $updatedPublicIds,
        public readonly string $action
    ) {}
}
