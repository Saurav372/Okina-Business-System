<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class BackupService
{
    /**
     * Generate a new system backup archive.
     *
     * @return string Path to the generated ZIP backup file
     */
    public function generateBackup(): string
    {
        $privatePath = rtrim(Config::get('backup.private_storage_path'), '/\\');
        $backupDirName = Config::get('backup.backup_directory', 'backups');
        $backupPath = $privatePath.DIRECTORY_SEPARATOR.$backupDirName;

        if (! file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        // 1. Serialize Database
        $sqlDump = $this->serializeDatabase();
        $tempSqlFile = tempnam(sys_get_temp_dir(), 'backup_db_');
        file_put_contents($tempSqlFile, $sqlDump);

        $dbChecksum = hash_file('sha256', $tempSqlFile);

        // 2. Generate Manifest
        $manifest = [
            'archive_version' => 1,
            'application_version' => Config::get('app.version', '1.0.0'),
            'created_at' => now()->toIso8601String(),
            'database_driver' => DB::connection()->getDriverName(),
            'checksum_algorithm' => 'sha256',
            'database_checksum' => $dbChecksum,
        ];
        $tempManifestFile = tempnam(sys_get_temp_dir(), 'backup_manifest_');
        file_put_contents($tempManifestFile, json_encode($manifest, JSON_PRETTY_PRINT));

        // 3. Create Zip Archive
        $timestamp = now()->format('Y-m-d-His');
        $zipFileName = "backup-{$timestamp}.zip";
        $zipFilePath = $backupPath.DIRECTORY_SEPARATOR.$zipFileName;

        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Could not create ZIP archive at: {$zipFilePath}");
        }

        // Add SQL dump and manifest
        $zip->addFile($tempSqlFile, 'database.sql');
        $zip->addFile($tempManifestFile, 'manifest.json');

        // Add uploads (excluding backups directory itself to prevent recursion)
        $this->addPrivateFilesToZip($zip, $privatePath, $backupPath);

        $zip->close();

        // Cleanup temporary files
        @unlink($tempSqlFile);
        @unlink($tempManifestFile);

        // 4. Prune older backups
        $this->pruneOldBackups($backupPath);

        return $zipFilePath;
    }

    /**
     * Serialize database schema and data into SQL statements.
     */
    protected function serializeDatabase(): string
    {
        $driver = DB::connection()->getDriverName();
        $tables = [];

        if ($driver === 'sqlite') {
            $results = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE 'migrations'");
            foreach ($results as $row) {
                $tables[] = $row->name;
            }
        } else {
            $results = DB::select('SHOW TABLES');
            $dbName = DB::connection()->getDatabaseName();
            $key = 'Tables_in_'.$dbName;
            foreach ($results as $row) {
                $rowArr = (array) $row;
                if (isset($rowArr[$key])) {
                    $tables[] = $rowArr[$key];
                } else {
                    $tables[] = reset($rowArr);
                }
            }
            $tables = array_filter($tables, fn ($t) => $t !== 'migrations');
        }

        $sql = "-- Okina Craft System Backup\n";
        $sql .= '-- Generated: '.now()->toDateTimeString()."\n";
        $sql .= '-- Driver: '.$driver."\n\n";

        foreach ($tables as $table) {
            $sql .= "-- Table: {$table}\n";

            // Get creation syntax
            if ($driver === 'sqlite') {
                $createResult = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
                if ($createResult && ! empty($createResult->sql)) {
                    $sql .= $createResult->sql.";\n\n";
                }
            } else {
                $createResult = DB::selectOne("SHOW CREATE TABLE `{$table}`");
                if ($createResult) {
                    $createArr = (array) $createResult;
                    if (isset($createArr['Create Table'])) {
                        $sql .= $createArr['Create Table'].";\n\n";
                    }
                }
            }

            // Dump data
            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $rowArr = (array) $row;
                $columns = array_keys($rowArr);
                $escapedColumns = array_map(fn ($col) => "`{$col}`", $columns);

                $escapedValues = [];
                foreach ($rowArr as $val) {
                    if ($val === null) {
                        $escapedValues[] = 'NULL';
                    } elseif (is_int($val) || is_float($val)) {
                        $escapedValues[] = $val;
                    } else {
                        $escapedValues[] = DB::connection()->getPdo()->quote((string) $val);
                    }
                }

                $sql .= "INSERT INTO `{$table}` (".implode(', ', $escapedColumns).') VALUES ('.implode(', ', $escapedValues).");\n";
            }
            $sql .= "\n";
        }

        return $sql;
    }

    /**
     * Add files from private storage to the ZIP archive, excluding the backup directory.
     */
    protected function addPrivateFilesToZip(ZipArchive $zip, string $privatePath, string $backupPath): void
    {
        $backupFullPath = realpath($backupPath);

        if (! file_exists($privatePath)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($privatePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();

            // Skip backups directory
            if ($backupFullPath && str_starts_with($filePath, $backupFullPath)) {
                continue;
            }

            // Calculate relative path inside ZIP
            $relativePath = substr($filePath, strlen($privatePath) + 1);
            $zip->addFile($filePath, 'uploads/'.str_replace('\\', '/', $relativePath));
        }
    }

    /**
     * Prune old backups, maintaining only the configured number of copies.
     */
    protected function pruneOldBackups(string $backupPath): void
    {
        $pattern = $backupPath.DIRECTORY_SEPARATOR.'backup-*.zip';
        $files = glob($pattern);

        if ($files === false) {
            return;
        }

        // Sort files oldest first
        usort($files, function ($a, $b) {
            return filemtime($a) <=> filemtime($b);
        });

        $keep = (int) Config::get('backup.keep_copies', 5);
        if (count($files) > $keep) {
            $toDelete = count($files) - $keep;
            for ($i = 0; $i < $toDelete; $i++) {
                @unlink($files[$i]);
            }
        }
    }
}
