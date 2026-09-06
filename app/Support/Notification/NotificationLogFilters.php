<?php

namespace App\Support\Notification;

use Carbon\CarbonImmutable;

final readonly class NotificationLogFilters
{
    public function __construct(
        public int $perPage,
        public ?string $channel,
        public ?string $status,
        public ?string $eventType,
        public ?string $recipientAddress,
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate
    ) {}

    public static function fromValidated(array $validated): self
    {
        $perPage = min(max((int) ($validated['per_page'] ?? 25), 1), 100);

        $tz = config('app.timezone', 'Asia/Kolkata');

        if (! empty($validated['start_date']) && ! empty($validated['end_date'])) {
            $startDate = CarbonImmutable::parse($validated['start_date'], $tz)->startOfDay();
            $endDate = CarbonImmutable::parse($validated['end_date'], $tz)->endOfDay();
        } else {
            // Default: 30 inclusive calendar days
            $endDate = CarbonImmutable::now($tz)->endOfDay();
            $startDate = CarbonImmutable::now($tz)->subDays(29)->startOfDay();
        }

        return new self(
            perPage: $perPage,
            channel: ! empty($validated['channel']) ? trim((string) $validated['channel']) : null,
            status: ! empty($validated['status']) ? trim((string) $validated['status']) : null,
            eventType: ! empty($validated['event_type']) ? trim((string) $validated['event_type']) : null,
            recipientAddress: ! empty($validated['recipient_address']) ? trim((string) $validated['recipient_address']) : null,
            startDate: $startDate,
            endDate: $endDate
        );
    }
}
