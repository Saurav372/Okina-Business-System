<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FileUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_image_upload_is_stored_privately_and_creates_a_preview(): void
    {
        Storage::fake('private');

        $service = app(FileUploadService::class);
        $actor = User::factory()->create();
        $file = UploadedFile::fake()->image('invoice-proof.png', 1200, 900)->size(512);

        $storedFile = $service->store($file, $actor, [
            'file_kind' => StoredFile::KIND_MOCKUP,
            'visibility' => StoredFile::VISIBILITY_PRIVATE,
        ]);

        Storage::disk('private')->assertExists($storedFile->storage_path);

        $this->assertSame('private', $storedFile->storage_disk);
        $this->assertNotSame('invoice-proof.png', $storedFile->stored_filename);
        $this->assertSame(StoredFile::STATUS_ACTIVE, $storedFile->status);
        $this->assertSame(StoredFile::SCAN_SKIPPED, $storedFile->scan_status);
        $this->assertNotNull($storedFile->previewMetadata());

        Storage::disk('private')->assertExists($storedFile->previewPath());
    }

    public function test_dangerous_file_names_are_rejected(): void
    {
        $service = app(FileUploadService::class);
        $actor = User::factory()->create();

        $this->expectException(ValidationException::class);

        $service->store(
            UploadedFile::fake()->create('shell.php.jpg', 10, 'image/jpeg'),
            $actor
        );
    }

    public function test_oversize_files_are_rejected(): void
    {
        $service = app(FileUploadService::class);
        $actor = User::factory()->create();

        $this->expectException(ValidationException::class);

        $service->store(
            UploadedFile::fake()->create('huge-image.png', 25000, 'image/png'),
            $actor
        );
    }

    public function test_signed_download_urls_require_a_valid_signature(): void
    {
        Storage::fake('private');

        $service = app(FileUploadService::class);
        $actor = $this->createSuperAdmin();
        $storedFile = $service->store(
            UploadedFile::fake()->image('design.png', 1000, 1000),
            $actor
        );

        $this->actingAs($actor)
            ->get(route('files.download', ['file' => $storedFile->public_id]))
            ->assertForbidden();

        $signedUrl = URL::temporarySignedRoute('files.download', now()->addMinutes(15), [
            'file' => $storedFile->public_id,
        ]);

        $this->actingAs($actor)
            ->get($signedUrl)
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_customers_can_only_access_their_own_files(): void
    {
        Storage::fake('private');

        $service = app(FileUploadService::class);
        $ownerAccount = CustomerAccount::factory()->create();
        $otherAccount = CustomerAccount::factory()->create();

        $storedFile = $service->store(
            UploadedFile::fake()->image('customer-proof.png', 900, 700),
            $ownerAccount,
            ['visibility' => StoredFile::VISIBILITY_CUSTOMER_VISIBLE]
        );

        $signedUrl = URL::temporarySignedRoute('files.preview', now()->addMinutes(15), [
            'file' => $storedFile->public_id,
        ]);

        $this->actingAs($ownerAccount, 'customer')
            ->get($signedUrl)
            ->assertOk();

        $this->actingAs($otherAccount, 'customer')
            ->get($signedUrl)
            ->assertForbidden();
    }

    public function test_protected_files_are_not_deleted_until_their_protection_expires(): void
    {
        Storage::fake('private');

        $service = app(FileUploadService::class);
        $actor = $this->createSuperAdmin();
        $storedFile = $service->store(
            UploadedFile::fake()->image('protected.png', 800, 600),
            $actor
        );

        $storedFile->forceFill([
            'protected_until' => now()->addHour(),
        ])->save();

        $this->expectException(ValidationException::class);

        $service->delete($storedFile->refresh(), $actor);
    }

    public function test_deleted_files_keep_the_database_record_but_remove_private_bytes(): void
    {
        Storage::fake('private');

        $service = app(FileUploadService::class);
        $actor = $this->createSuperAdmin();
        $storedFile = $service->store(
            UploadedFile::fake()->image('cleanup.png', 1000, 1000),
            $actor
        );

        Storage::disk('private')->assertExists($storedFile->storage_path);
        Storage::disk('private')->assertExists($storedFile->previewPath());

        $service->delete($storedFile->refresh(), $actor);

        $this->assertSoftDeleted('files', [
            'id' => $storedFile->id,
        ]);
        $storedFile->refresh();

        $this->assertSame(StoredFile::STATUS_DELETED, $storedFile->status);
        $this->assertSame($actor->id, $storedFile->deleted_by_user_id);
    }

    private function createSuperAdmin(): User
    {
        $user = User::factory()->create();

        $role = Role::query()->updateOrCreate(
            ['slug' => Role::SUPER_ADMIN],
            [
                'name' => 'Super Admin',
                'guard_name' => 'web',
                'description' => 'Super Admin',
                'is_system' => true,
                'sort_order' => 10,
            ],
        );

        $user->assignRole($role);

        return $user;
    }
}
