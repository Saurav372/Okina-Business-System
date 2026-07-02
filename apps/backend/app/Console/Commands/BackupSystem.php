<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('system:backup')]
#[Description('Generate a system backup containing the database SQL dump and private uploads')]
class BackupSystem extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info('Starting system backup...');

        try {
            $backupFilePath = $backupService->generateBackup();
            $this->info('System backup completed successfully!');
            $this->info("Backup file saved at: {$backupFilePath}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('System backup failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
