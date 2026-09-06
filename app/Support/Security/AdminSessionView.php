<?php

namespace App\Support\Security;

use Carbon\CarbonImmutable;

final readonly class AdminSessionView
{
    public function __construct(
        public string $identifierHash,
        public string $browser,
        public string $platform,
        public string $ipAddress,
        public CarbonImmutable $lastActiveAt,
        public string $lastActiveLabel,
        public bool $isCurrent
    ) {}
}
