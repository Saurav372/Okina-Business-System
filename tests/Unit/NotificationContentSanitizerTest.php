<?php

namespace Tests\Unit;

use App\Support\Notification\NotificationContentSanitizer;
use PHPUnit\Framework\TestCase;

class NotificationContentSanitizerTest extends TestCase
{
    public function test_email_masking(): void
    {
        $this->assertEquals('a*@example.com', NotificationContentSanitizer::maskAddress('ab@example.com'));
        $this->assertEquals('j**n@example.com', NotificationContentSanitizer::maskAddress('john@example.com'));
        $this->assertEquals('*@example.com', NotificationContentSanitizer::maskAddress('a@example.com'));
    }

    public function test_phone_masking(): void
    {
        $masked = NotificationContentSanitizer::maskAddress('+919876543210');
        $this->assertStringStartsWith('+91', $masked);
        $this->assertStringEndsWith('3210', $masked);
        $this->assertStringContainsString('*', $masked);
    }

    public function test_url_query_string_redaction(): void
    {
        $body = 'Please click here to reset: https://okina.craft/reset-password?token=secret123&signature=abc';
        $sanitized = NotificationContentSanitizer::sanitizeBody($body);

        $this->assertStringContainsString('https://okina.craft/reset-password?[REDACTED]', $sanitized);
        $this->assertStringNotContainsString('secret123', $sanitized);
    }
}
