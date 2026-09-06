<?php

namespace App\Console\Commands;

use App\Models\GoogleSheetsSyncLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

#[Signature('sheets:prune-logs')]
#[Description('Prune old Google Sheets sync logs')]
class PruneGoogleSheetsSyncLogs extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) Config::get('sheets.logging.prune_days', 30);
        $threshold = now()->subDays($days);

        $this->info("Pruning Google Sheets sync logs older than {$days} days ({$threshold->toDateTimeString()})...");

        // Delete records older than the threshold in chunks or directly
        $deleted = GoogleSheetsSyncLog::where('created_at', '<', $threshold)->delete();

        $this->info("Successfully pruned {$deleted} Google Sheets sync log record(s).");

        return Command::SUCCESS;
    }
}
