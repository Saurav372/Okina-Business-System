<?php

namespace App\Console\Commands;

use App\Events\LeadFollowUpDue;
use App\Models\LeadFollowUp;
use Illuminate\Console\Command;

class DispatchFollowUpReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:dispatch-follow-up-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find due lead follow-ups and dispatch reminder events.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dueQuery = LeadFollowUp::pending()
            ->where('due_at', '<=', now())
            ->orderBy('due_at')
            ->orderBy('id');

        $count = 0;
        foreach ($dueQuery->lazy() as $followUp) {
            event(new LeadFollowUpDue($followUp));
            $count++;
        }

        $this->info("Successfully dispatched {$count} follow-up reminder events.");

        return Command::SUCCESS;
    }
}
