<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class RestoreService
{
    /**
     * Restore the system from a given ZIP backup file path.
     *
     * @param  string  $zipFilePath  Absolute path to the ZIP backup file
     */
    public function restoreBackup(string $zipFilePath): void
    {
        if (! file_exists($zipFilePath)) {
            throw new \InvalidArgumentException("Backup file does not exist at: {$zipFilePath}");
        }

        $privatePath = rtrim(Config::get('backup.private_storage_path'), '/\\');
        $backupDirName = Config::get('backup.backup_directory', 'backups');
        $backupFullPath = realpath($privatePath.DIRECTORY_SEPARATOR.$backupDirName);

        // 1. Create a temporary directory for dry-run validation
        $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'restore_'.uniqid();
        if (! mkdir($tempDir, 0755, true)) {
            throw new \RuntimeException('Could not create temporary directory for restoration.');
        }

        try {
            // Extract the ZIP into temporary directory
            $zip = new ZipArchive;
            if ($zip->open($zipFilePath) !== true) {
                throw new \RuntimeException('Failed to open backup ZIP archive.');
            }
            $zip->extractTo($tempDir);
            $zip->close();

            // Validate structure
            $manifestPath = $tempDir.DIRECTORY_SEPARATOR.'manifest.json';
            $sqlPath = $tempDir.DIRECTORY_SEPARATOR.'database.sql';

            if (! file_exists($manifestPath)) {
                throw new \RuntimeException('Backup validation failed: manifest.json is missing.');
            }

            if (! file_exists($sqlPath)) {
                throw new \RuntimeException('Backup validation failed: database.sql is missing.');
            }

            // Parse and validate manifest
            $manifestData = json_decode(file_get_contents($manifestPath), true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($manifestData)) {
                throw new \RuntimeException('Backup validation failed: manifest.json contains invalid JSON.');
            }

            $requiredKeys = ['archive_version', 'application_version', 'database_driver', 'checksum_algorithm', 'database_checksum'];
            foreach ($requiredKeys as $key) {
                if (! array_key_exists($key, $manifestData)) {
                    throw new \RuntimeException("Backup validation failed: manifest.json is missing key: {$key}");
                }
            }

            // Verify checksum
            $checksumAlgorithm = $manifestData['checksum_algorithm'];
            if ($checksumAlgorithm !== 'sha256') {
                throw new \RuntimeException("Backup validation failed: unsupported checksum algorithm: {$checksumAlgorithm}");
            }

            $actualChecksum = hash_file('sha256', $sqlPath);
            if ($actualChecksum !== $manifestData['database_checksum']) {
                throw new \RuntimeException('Backup validation failed: database.sql checksum mismatch.');
            }

            // 2. Destructive Stage
            // Reset Database
            $this->clearDatabase();

            // Reset Private Uploads (leaving backups folder intact)
            $this->deleteDirectoryContents($privatePath, $backupFullPath);

            // 3. Reconstruction Stage
            // Restore Database
            $this->executeSqlDump($sqlPath);

            // Restore Uploaded Files
            $tempUploadsPath = $tempDir.DIRECTORY_SEPARATOR.'uploads';
            if (file_exists($tempUploadsPath)) {
                $this->copyDirectory($tempUploadsPath, $privatePath);
            }

        } finally {
            // Clean up temporary directory
            $this->deleteDirectoryRecursively($tempDir);
        }
    }

    /**
     * Clear all tables from the active database.
     */
    protected function clearDatabase(): void
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
                $name = isset($rowArr[$key]) ? $rowArr[$key] : reset($rowArr);
                if ($name !== 'migrations') {
                    $tables[] = $name;
                }
            }

            // Disable foreign keys for MySQL during dropping
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        // Retry dropping tables until all are gone (up to 10 iterations)
        $attempts = 0;
        while (! empty($tables) && $attempts < 10) {
            $failed = [];
            foreach ($tables as $table) {
                try {
                    DB::statement("DROP TABLE IF EXISTS `{$table}`");
                } catch (\Throwable $e) {
                    $failed[] = $table;
                }
            }
            $tables = $failed;
            $attempts++;
        }

        if ($driver !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }
    }

    /**
     * Parse and run SQL statements from the sql file.
     */
    protected function executeSqlDump(string $sqlFilePath): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        $sqlContent = file_get_contents($sqlFilePath);

        // Split statements safely by semicolon followed by newline/whitespace
        $statements = preg_split('/;\s*[\r\n]+/', $sqlContent);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (! empty($statement)) {
                DB::statement($statement);
            }
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }
    }

    /**
     * Recursively delete directory contents, excluding a specific backup folder path.
     */
    protected function deleteDirectoryContents(string $dirPath, string $excludePath): void
    {
        if (! file_exists($dirPath)) {
            return;
        }

        $items = new \DirectoryIterator($dirPath);
        foreach ($items as $item) {
            if ($item->isDot()) {
                continue;
            }

            $realPath = $item->getRealPath();
            if ($realPath === $excludePath) {
                continue;
            }

            if ($item->isDir()) {
                $this->deleteDirectoryRecursively($realPath);
            } else {
                @unlink($realPath);
            }
        }
    }

    /**
     * Recursively copy a directory to a target destination.
     */
    protected function copyDirectory(string $source, string $destination): void
    {
        if (! file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $dir = opendir($source);
        if ($dir === false) {
            return;
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath = $source.DIRECTORY_SEPARATOR.$file;
            $destPath = $destination.DIRECTORY_SEPARATOR.$file;

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $destPath);
            } else {
                copy($srcPath, $destPath);
            }
        }
        closedir($dir);
    }

    /**
     * Recursively delete a directory and all of its contents.
     */
    protected function deleteDirectoryRecursively(string $dirPath): void
    {
        if (! file_exists($dirPath)) {
            return;
        }

        if (is_file($dirPath)) {
            @unlink($dirPath);

            return;
        }

        $items = new \DirectoryIterator($dirPath);
        foreach ($items as $item) {
            if ($item->isDot()) {
                continue;
            }

            $realPath = $item->getRealPath();
            if ($item->isDir()) {
                $this->deleteDirectoryRecursively($realPath);
            } else {
                @unlink($realPath);
            }
        }
        @rmdir($dirPath);
    }
}
