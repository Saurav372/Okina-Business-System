<?php

namespace App\Support\Notifications;

use App\Models\NotificationTemplate;
use Illuminate\Support\Arr;

class NotificationRenderer
{
    protected NotificationPayloadSanitizer $sanitizer;

    public function __construct(?NotificationPayloadSanitizer $sanitizer = null)
    {
        $this->sanitizer = $sanitizer ?? new NotificationPayloadSanitizer;
    }

    /**
     * Render the subject template of a notification using the payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function renderSubject(NotificationTemplate $template, array $payload): ?string
    {
        if ($template->subject_template === null) {
            return null;
        }

        $processedPayload = $this->processPayload($template, $payload);

        return $this->renderString($template->subject_template, $processedPayload);
    }

    /**
     * Render the body template of a notification using the payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function renderBody(NotificationTemplate $template, array $payload): string
    {
        $processedPayload = $this->processPayload($template, $payload);

        return $this->renderString($template->body_template, $processedPayload);
    }

    /**
     * Pipeline processing: Sanitize first, then filter by template whitelist.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function processPayload(NotificationTemplate $template, array $payload): array
    {
        // 1. Sanitize payload first to mask secrets recursively
        $sanitized = $this->sanitizer->sanitize($payload);

        // 2. Apply whitelist filter
        return $this->filterPayload($template, $sanitized);
    }

    /**
     * Filter payload variables based on template allowed_variables whitelist.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function filterPayload(NotificationTemplate $template, array $payload): array
    {
        $allowed = $template->allowed_variables;

        // null allowed_variables means allow everything
        if ($allowed === null) {
            return $payload;
        }

        // [] allowed_variables means allow nothing
        if ($allowed === []) {
            return [];
        }

        $filtered = [];
        foreach ($allowed as $path) {
            if (Arr::has($payload, $path)) {
                Arr::set($filtered, $path, data_get($payload, $path));
            }
        }

        return $filtered;
    }

    /**
     * Interpolate placeholders in a string using regex pattern.
     * Collapses duplicate spaces and cleans up punctuation spacing.
     *
     * @param  array<string, mixed>  $payload
     */
    public function renderString(string $content, array $payload): string
    {
        $rendered = preg_replace_callback(
            '/{{\s*([A-Za-z0-9._]+)\s*}}/',
            function (array $matches) use ($payload) {
                $key = $matches[1];
                $value = data_get($payload, $key);

                if (is_scalar($value)) {
                    return (string) $value;
                }

                if (is_object($value) && method_exists($value, '__toString')) {
                    return (string) $value;
                }

                return '';
            },
            $content
        );

        if ($rendered === null) {
            return $content;
        }

        // Clean up double spaces/tabs on individual lines
        $rendered = preg_replace('/[ \t]{2,}/', ' ', $rendered);

        // Trim spaces before punctuation, e.g. "Hello  ," -> "Hello,"
        $rendered = preg_replace('/[ \t]+([,.!?])/', '$1', $rendered);

        return $rendered;
    }
}
