<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    protected string $testPrivatePath;

    protected string $testBackupPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Use a unique directory inside storage for testing backups and uploads
        $this->testPrivatePath = storage_path('app/testing_private_'.uniqid());
        $this->testBackupPath = $this->testPrivatePath.'/backups';

        if (! file_exists($this->testPrivatePath)) {
            mkdir($this->testPrivatePath, 0755, true);
        }
        if (! file_exists($this->testBackupPath)) {
            mkdir($this->testBackupPath, 0755, true);
        }

        Config::set('backup.private_storage_path', $this->testPrivatePath);
        Config::set('backup.backup_directory', 'backups');
        Config::set('backup.keep_copies', 5);
    }

    protected function tearDown(): void
    {
        // Clean up test directories recursively
        $this->deleteDirectoryRecursively($this->testPrivatePath);
        parent::tearDown();
    }

    /**
     * Test successful backup creation.
     */
    public function test_backup_creation_and_integrity(): void
    {
        // 1. Seed database state
        $user = User::factory()->create([
            'name' => 'Alice BackupTest',
            'email' => 'alice@example.com',
        ]);

        // 2. Create mock private uploads
        file_put_contents($this->testPrivatePath.'/mock_doc.txt', 'Hello, this is a mock file.');
        mkdir($this->testPrivatePath.'/subfolder');
        file_put_contents($this->testPrivatePath.'/subfolder/mock_image.png', 'fake-image-binary-data');

        // 3. Run backup command
        Artisan::call('system:backup');

        // 4. Assert backup ZIP exists and has expected files
        $backups = glob($this->testBackupPath.'/backup-*.zip');
        $this->assertCount(1, $backups);

        $zipPath = $backups[0];
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath));

        // Verify index files exist
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $this->assertNotFalse($zip->locateName('database.sql'));

        // Verify uploads are packed
        $this->assertNotFalse($zip->locateName('uploads/mock_doc.txt'));
        $this->assertNotFalse($zip->locateName('uploads/subfolder/mock_image.png'));

        // Verify recursive backup directory itself is not inside the ZIP
        $this->assertFalse($zip->locateName('uploads/backups/'));

        // Read manifest
        $manifestContent = $zip->getFromName('manifest.json');
        $manifest = json_decode($manifestContent, true);

        $this->assertEquals(1, $manifest['archive_version']);
        $this->assertEquals('sha256', $manifest['checksum_algorithm']);
        $this->assertNotEmpty($manifest['database_checksum']);
        $this->assertNotEmpty($manifest['database_driver']);

        $zip->close();
    }

    /**
     * Test old backup retention and pruning.
     */
    public function test_backup_pruning_retention(): void
    {
        // Create 7 mock zip files (some old, some new)
        for ($i = 1; $i <= 7; $i++) {
            $path = $this->testBackupPath."/backup-2026-07-02-12000{$i}.zip";
            file_put_contents($path, 'dummy zip content');
            // Shift file modification times
            touch($path, time() - (10 - $i) * 60);
        }

        // Add an unrelated file to ensure it's not deleted by pruning
        $unrelatedPath = $this->testBackupPath.'/documentation.txt';
        file_put_contents($unrelatedPath, 'do not delete');

        // Run backup service/command
        Artisan::call('system:backup');

        // We kept 5 newest backups total.
        $backups = glob($this->testBackupPath.'/backup-*.zip');
        $this->assertCount(5, $backups);

        // Assert unrelated file is still there
        $this->assertFileExists($unrelatedPath);
    }

    /**
     * Test restore validation before dropping state (validate-before-destroy).
     */
    public function test_restore_validation_safety(): void
    {
        // Seed user and files
        $user = User::factory()->create(['name' => 'Preserved User']);
        file_put_contents($this->testPrivatePath.'/important_active_doc.txt', 'This should not be deleted!');

        // 1. Create a corrupt/invalid ZIP
        $invalidZipPath = $this->testBackupPath.'/backup-invalid.zip';
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($invalidZipPath, ZipArchive::CREATE));
        // Add database.sql, but omit manifest.json
        $zip->addFromString('database.sql', 'SELECT 1;');
        $zip->close();

        // 2. Call restore and assert failure
        $exitCode = Artisan::call('system:restore', [
            'file' => 'backup-invalid.zip',
            '--force' => true,
        ]);
        $this->assertEquals(1, $exitCode);

        // 3. Assert active database and uploads are NOT destroyed/deleted
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertFileExists($this->testPrivatePath.'/important_active_doc.txt');

        // 4. Create another ZIP with manifest but incorrect checksum
        $checksumZipPath = $this->testBackupPath.'/backup-bad-checksum.zip';
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($checksumZipPath, ZipArchive::CREATE));
        $zip->addFromString('database.sql', 'SELECT 1;');
        $manifest = [
            'archive_version' => 1,
            'application_version' => '1.0.0',
            'created_at' => now()->toIso8601String(),
            'database_driver' => DB::connection()->getDriverName(),
            'checksum_algorithm' => 'sha256',
            'database_checksum' => 'wrong-checksum-hash',
        ];
        $zip->addFromString('manifest.json', json_encode($manifest));
        $zip->close();

        // 5. Call restore and assert failure
        $exitCode2 = Artisan::call('system:restore', [
            'file' => 'backup-bad-checksum.zip',
            '--force' => true,
        ]);
        $this->assertEquals(1, $exitCode2);

        // 6. Assert active database and uploads are still NOT destroyed/deleted
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertFileExists($this->testPrivatePath.'/important_active_doc.txt');
    }

    /**
     * Test successful full backup & restore cycle.
     */
    public function test_full_backup_restore_cycle(): void
    {
        // 1. Seed initial database state
        $user1 = User::factory()->create([
            'name' => 'John Original',
            'email' => 'john@example.com',
        ]);
        file_put_contents($this->testPrivatePath.'/initial_doc.txt', 'Original document content.');

        // 2. Generate backup
        Artisan::call('system:backup');
        $backups = glob($this->testBackupPath.'/backup-*.zip');
        $this->assertCount(1, $backups);
        $backupFile = basename($backups[0]);

        // 3. Mutate the active state (delete user1, add user2, add new files, change initial file)
        $user1->delete();
        $user2 = User::factory()->create([
            'name' => 'Mutated User',
            'email' => 'mutated@example.com',
        ]);

        file_put_contents($this->testPrivatePath.'/initial_doc.txt', 'Mutated document content.');
        file_put_contents($this->testPrivatePath.'/mutated_new_doc.txt', 'This is a mutated new file.');

        $this->assertDatabaseMissing('users', ['name' => 'John Original']);
        $this->assertDatabaseHas('users', ['name' => 'Mutated User']);
        $this->assertEquals('Mutated document content.', file_get_contents($this->testPrivatePath.'/initial_doc.txt'));
        $this->assertFileExists($this->testPrivatePath.'/mutated_new_doc.txt');

        // 4. Run restore
        $exitCode = Artisan::call('system:restore', [
            'file' => $backupFile,
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'Artisan Output: '.Artisan::output());

        // 5. Assert database and files are completely restored to original pre-backup state
        $this->assertDatabaseHas('users', ['name' => 'John Original', 'email' => 'john@example.com']);
        $this->assertDatabaseMissing('users', ['name' => 'Mutated User']);

        $this->assertEquals('Original document content.', file_get_contents($this->testPrivatePath.'/initial_doc.txt'));
        $this->assertFileDoesNotExist($this->testPrivatePath.'/mutated_new_doc.txt');
    }

    /**
     * Helper to delete a directory recursively.
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
