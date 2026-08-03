<?php

namespace App\Support\Audit;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class AuditLogFilters
{
    public const SUBJECT_MAP = [
        'order' => Order::class,
        'user' => User::class,
        'expense' => Expense::class,
        'payment' => Payment::class,
        'refund' => Refund::class,
        'customer' => Customer::class,
    ];

    public function __construct(
        public int $perPage,
        public ?string $action,
        public ?string $module,
        public ?string $subjectTypeClass,
        public ?string $subjectId,
        public ?string $actorPublicId,
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

        $subjectTypeInput = strtolower(trim((string) ($validated['subject_type'] ?? '')));
        $subjectTypeClass = self::SUBJECT_MAP[$subjectTypeInput] ?? null;

        return new self(
            perPage: $perPage,
            action: ! empty($validated['action']) ? trim((string) $validated['action']) : null,
            module: ! empty($validated['module']) ? trim((string) $validated['module']) : null,
            subjectTypeClass: $subjectTypeClass,
            subjectId: ! empty($validated['subject_id']) ? trim((string) $validated['subject_id']) : null,
            actorPublicId: ! empty($validated['actor_public_id']) ? trim((string) $validated['actor_public_id']) : null,
            startDate: $startDate,
            endDate: $endDate
        );
    }
}
