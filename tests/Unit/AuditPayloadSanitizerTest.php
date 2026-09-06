<?php

namespace Tests\Unit;

use App\Support\Audit\AuditPayloadSanitizer;
use PHPUnit\Framework\TestCase;

class AuditPayloadSanitizerTest extends TestCase
{
    public function test_sanitizes_sensitive_keys_case_insensitively(): void
    {
        $payload = [
            'Password' => 'secret123',
            'current_password' => 'oldsecret',
            'ACCESS_TOKEN' => 'bearer-123',
            'user_name' => 'John Doe',
            'nested' => [
                'api_key' => 'key-999',
                'normal_field' => 'value',
            ],
        ];

        $sanitized = AuditPayloadSanitizer::sanitize($payload);

        $this->assertEquals('[REDACTED]', $sanitized['Password']);
        $this->assertEquals('[REDACTED]', $sanitized['current_password']);
        $this->assertEquals('[REDACTED]', $sanitized['ACCESS_TOKEN']);
        $this->assertEquals('John Doe', $sanitized['user_name']);
        $this->assertEquals('[REDACTED]', $sanitized['nested']['api_key']);
        $this->assertEquals('value', $sanitized['nested']['normal_field']);
    }

    public function test_handles_max_recursion_depth(): void
    {
        $deep = ['level' => 1];
        $current = &$deep;
        for ($i = 2; $i <= 12; $i++) {
            $current['next'] = ['level' => $i];
            $current = &$current['next'];
        }

        $sanitized = AuditPayloadSanitizer::sanitize($deep);
        $this->assertIsArray($sanitized);
    }
}
