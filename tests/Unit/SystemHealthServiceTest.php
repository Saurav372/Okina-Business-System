<?php

namespace Tests\Unit;

use App\Services\SystemHealthService;
use App\Support\Health\SystemHealthSummary;
use Tests\TestCase;

class SystemHealthServiceTest extends TestCase
{
    public function test_health_check_returns_summary_dto(): void
    {
        $service = new SystemHealthService;
        $summary = $service->generateSummary();

        $this->assertInstanceOf(SystemHealthSummary::class, $summary);
        $this->assertArrayHasKey('database', $summary->components);
        $this->assertArrayHasKey('cache', $summary->components);
        $this->assertArrayHasKey('storage', $summary->components);
        $this->assertArrayHasKey('queue', $summary->components);
        $this->assertArrayHasKey('environment', $summary->components);
    }
}
