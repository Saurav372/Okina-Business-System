<?php

namespace App\Console\Commands;

use App\Services\RestoreService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

#[Signature('system:restore {file? : The backup ZIP filename to restore} {--force : Bypass confirmation prompts}')]
#[Description('Restore the database and private uploads from a ZIP backup archive')]
class RestoreSystem extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(RestoreService $restoreService): int
    {
        $file = $this->argument('file');
        $privatePath = rtrim(Config::get('backup.private_storage_path'), '/\\');
        $backupDirName = Config::get('backup.backup_directory', 'backups');
        $backupPath = $privatePath.DIRECTORY_SEPARATOR.$backupDirName;

        if (empty($file)) {
            // Find the latest backup file
            $pattern = $backupPath.DIRECTORY_SEPARATOR.'backup-*.zip';
            $files = glob($pattern);

            if (empty($files)) {
                $this->error("No backups found in directory: {$backupPath}");

                return Command::FAILURE;
            }

            // Sort files newest first
            usort($files, function ($a, $b) {
                return filemtime($b) <=> filemtime($a);
            });

            $filePath = $files[0];
        } else {
            // Check if absolute or relative path
            if (file_exists($file)) {
                $filePath = $file;
            } else {
                $filePath = $backupPath.DIRECTORY_SEPARATOR.$file;
            }
        }

        if (! file_exists($filePath)) {
            $this->error("Backup file not found at: {$filePath}");

            return Command::FAILURE;
        }

        // Show destructive warnings
        if (! $this->option('force')) {
            $this->warn('=====================================================================');
            $this->warn(' WARNING: This restore process is DESTRUCTIVE.');
            $this->warn(' All active database tables and private uploads will be DESTROYED.');
            $this->warn(' Target file: '.basename($filePath));
            $this->warn('=====================================================================');

            if (! $this->confirm('Do you want to proceed with the restoration?', false)) {
                $this->info('Restoration cancelled.');

                return Command::INVALID;
            }
        }

        $this->info('Starting dry-run validation...');
        try {
            // RestoreService handles validation before clearing state
            $restoreService->restoreBackup($filePath);
            $this->info('System restoration completed successfully!');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('System restoration failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
