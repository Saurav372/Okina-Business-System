<?php

namespace App\Support\Dashboard;

use Carbon\Carbon;

class ActivityItemDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $icon,
        public readonly string $variant,
        public readonly Carbon $occurredAt,
        public readonly ?string $href = null,
        public readonly ?string $actorName = null,
        public readonly ?string $actorInitials = null
    ) {}

    /**
     * Helper to format time offsets dynamically.
     * - If occurred less than 24 hours ago: relative time (e.g. "3 hours ago")
     * - If occurred between 24 and 48 hours ago: "Yesterday, H:i A"
     * - If occurred more than 48 hours ago: "d M Y" (e.g. "08 Jul 2026")
     */
    public function formatTimeForDashboard(): string
    {
        $now = Carbon::now();

        if ($this->occurredAt->diffInHours($now) < 24) {
            return $this->occurredAt->diffForHumans();
        }

        if ($this->occurredAt->isYesterday()) {
            return 'Yesterday, '.$this->occurredAt->format('g:i A');
        }

        return $this->occurredAt->format('d M Y');
    }
}
