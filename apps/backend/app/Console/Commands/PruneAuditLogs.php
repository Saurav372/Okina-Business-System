<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune audit logs older than the configured retention period.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Resolve and normalize the retention days configuration once at command startup
        $retentionDays = max(1, (int) config('audit.retention_days', 365));

        // Determine the cutoff timestamp: records with occurred_at strictly older than the threshold are deleted.
        $cutoff = now()->subDays($retentionDays);

        $this->info("Retention: {$retentionDays} days");

        $deletedCount = 0;

        // Query only the IDs using chunkById to keep locks short and memory low
        DB::table('audit_logs')
            ->select('id')
            ->where('occurred_at', '<', $cutoff)
            ->chunkById(1000, function ($logs) use (&$deletedCount) {
                $ids = $logs->pluck('id')->toArray();
                if (! empty($ids)) {
                    $deletedCount += DB::table('audit_logs')
                        ->whereIn('id', $ids)
                        ->delete();
                }
            });

        $this->info('');
        $this->info('Deleted:');
        $this->info("{$deletedCount} audit logs");
        $this->info('');
        $this->info('Completed.');

        return Command::SUCCESS;
    }
}
