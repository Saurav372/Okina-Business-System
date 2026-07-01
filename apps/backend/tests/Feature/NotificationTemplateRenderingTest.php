<?php

namespace Tests\Feature;

use App\Models\NotificationTemplate;
use App\Support\Notifications\NotificationRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTemplateRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new NotificationRenderer;
    }

    /**
     * Test nested variable interpolation using dot notation.
     */
    public function test_nested_variables_render_correctly(): void
    {
        $template = NotificationTemplate::create([
            'template_key' => 'test.nested',
            'channel' => 'email',
            'name' => 'Test Nested',
            'body_template' => 'Hello {{ customer.name }}, your order ID is {{ order.public_id }}.',
            'allowed_variables' => ['customer.name', 'order.public_id'],
        ]);

        $payload = [
            'customer' => ['name' => 'Saurav'],
            'order' => ['public_id' => 'ORD-1234'],
        ];

        $rendered = $this->renderer->renderBody($template, $payload);
        $this->assertEquals('Hello Saurav, your order ID is ORD-1234.', $rendered);
    }

    /**
     * Test that unknown variables resolve to an empty string.
     */
    public function test_unknown_variables_resolve_to_empty_string(): void
    {
        $template = NotificationTemplate::create([
            'template_key' => 'test.unknown',
            'channel' => 'email',
            'name' => 'Test Unknown',
            'body_template' => 'Hello {{ customer.name }}{{ customer.phone }}!',
            'allowed_variables' => ['customer.name', 'customer.phone'],
        ]);

        $payload = [
            'customer' => ['name' => 'Saurav'],
            // customer.phone is missing
        ];

        $rendered = $this->renderer->renderBody($template, $payload);
        $this->assertEquals('Hello Saurav!', $rendered);
    }

    /**
     * Test recursive payload sanitization for sensitive keys.
     */
    public function test_recursive_payload_sanitization(): void
    {
        $template = NotificationTemplate::create([
            'template_key' => 'test.sanitize',
            'channel' => 'email',
            'name' => 'Test Sanitize',
            'body_template' => 'Token: {{ auth.access_token }} Pass: {{ user.password }} Safe: {{ user.name }}',
            'allowed_variables' => ['auth.access_token', 'user.password', 'user.name'],
        ]);

        $payload = [
            'auth' => [
                'access_token' => 'secret-jwt-token-123',
            ],
            'user' => [
                'password' => 'super-secret-password',
                'name' => 'Saurav',
            ],
        ];

        $rendered = $this->renderer->renderBody($template, $payload);
        $this->assertEquals('Token: [MASKED] Pass: [MASKED] Safe: Saurav', $rendered);
    }

    /**
     * Test multiple and repeated placeholders in templates.
     */
    public function test_multiple_and_repeated_placeholders(): void
    {
        $template = NotificationTemplate::create([
            'template_key' => 'test.multiple',
            'channel' => 'email',
            'name' => 'Test Multiple',
            'body_template' => 'Hi {{ customer.name }}. Welcome {{ customer.name }}! Order: {{ order.id }}',
            'allowed_variables' => ['customer.name', 'order.id'],
        ]);

        $payload = [
            'customer' => ['name' => 'Saurav'],
            'order' => ['id' => 999],
        ];

        $rendered = $this->renderer->renderBody($template, $payload);
        $this->assertEquals('Hi Saurav. Welcome Saurav! Order: 999', $rendered);
    }

    /**
     * Test null values resolve to empty strings instead of printing "null".
     */
    public function test_null_values_render_as_empty_string(): void
    {
        $template = NotificationTemplate::create([
            'template_key' => 'test.null',
            'channel' => 'email',
            'name' => 'Test Null',
            'body_template' => 'Status: {{ status }}',
            'allowed_variables' => ['status'],
        ]);

        $payload = [
            'status' => null,
        ];

        $rendered = $this->renderer->renderBody($template, $payload);
        $this->assertEquals('Status: ', $rendered);
    }

    /**
     * Test trimming and double space cleanup after placeholder replacement.
     */
    public function test_double_spaces_trimmed_after_replacement(): void
    {
        $template = NotificationTemplate::create([
            'template_key' => 'test.spaces',
            'channel' => 'email',
            'name' => 'Test Spaces',
            'body_template' => 'Hello {{ title }} {{ name }} , how are you?',
            'allowed_variables' => ['title', 'name'],
        ]);

        // title is empty/missing
        $payload = [
            'name' => 'Saurav',
        ];

        $rendered = $this->renderer->renderBody($template, $payload);
        // "Hello  Saurav ," should become "Hello Saurav,"
        $this->assertEquals('Hello Saurav, how are you?', $rendered);
    }

    /**
     * Test variable whitelisting behaviors (null, empty, and defined).
     */
    public function test_allowed_variables_filtering_behavior(): void
    {
        // 1. null allowed_variables (allow all)
        $templateAll = NotificationTemplate::create([
            'template_key' => 'test.whitelist.all',
            'channel' => 'email',
            'name' => 'Test Whitelist All',
            'body_template' => 'A: {{ a }} B: {{ b }}',
            'allowed_variables' => null,
        ]);

        $payload = ['a' => 1, 'b' => 2];
        $rendered = $this->renderer->renderBody($templateAll, $payload);
        $this->assertEquals('A: 1 B: 2', $rendered);

        // 2. empty array [] allowed_variables (allow nothing)
        $templateNone = NotificationTemplate::create([
            'template_key' => 'test.whitelist.none',
            'channel' => 'email',
            'name' => 'Test Whitelist None',
            'body_template' => 'A: {{ a }} B: {{ b }}',
            'allowed_variables' => [],
        ]);

        $rendered = $this->renderer->renderBody($templateNone, $payload);
        $this->assertEquals('A: B: ', $rendered);

        // 3. defined allowed_variables (allow only subset)
        $templateSubset = NotificationTemplate::create([
            'template_key' => 'test.whitelist.subset',
            'channel' => 'email',
            'name' => 'Test Whitelist Subset',
            'body_template' => 'A: {{ a }} B: {{ b }}',
            'allowed_variables' => ['a'],
        ]);

        $rendered = $this->renderer->renderBody($templateSubset, $payload);
        $this->assertEquals('A: 1 B: ', $rendered);
    }

    /**
     * Test that non-scalar values (arrays/objects) resolve to empty strings.
     */
    public function test_non_scalar_values_resolve_to_empty_string(): void
    {
        $template = NotificationTemplate::create([
            'template_key' => 'test.non_scalar',
            'channel' => 'email',
            'name' => 'Test Non Scalar',
            'body_template' => 'Data: {{ details }}',
            'allowed_variables' => ['details'],
        ]);

        $payload = [
            'details' => ['nested' => 'array'],
        ];

        $rendered = $this->renderer->renderBody($template, $payload);
        $this->assertEquals('Data: ', $rendered);
    }
}
