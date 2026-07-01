<?php

namespace App\Support\Notifications;

readonly class DeliveryResult
{
    /**
     * Create a new delivery result instance.
     *
     * @param  array<mixed>|null  $responsePayload
     */
    public function __construct(
        public bool $success,
        public ?string $providerReference = null,
        public ?string $errorMessage = null,
        public ?array $responsePayload = null
    ) {}

    /**
     * Create a successful delivery result DTO.
     *
     * @param  array<mixed>|null  $payload
     */
    public static function success(?string $ref = null, ?array $payload = null): self
    {
        return new self(true, $ref, null, $payload);
    }

    /**
     * Create a failed delivery result DTO.
     *
     * @param  array<mixed>|null  $payload
     */
    public static function failed(string $error, ?array $payload = null): self
    {
        return new self(false, null, $error, $payload);
    }
}
