<?php

namespace App\Events;

use App\Models\LeadFollowUp;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadFollowUpDue
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly LeadFollowUp $followUp
    ) {}
}
