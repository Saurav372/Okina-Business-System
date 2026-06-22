<?php

namespace Database\Factories;

use App\Models\StoredFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StoredFile>
 */
class StoredFileFactory extends Factory
{
    protected $model = StoredFile::class;

    public function definition(): array
    {
        $ext = $this->faker->randomElement(['png', 'jpg', 'pdf', 'svg']);
        $name = Str::slug($this->faker->words(2, true)).'.'.$ext;

        return [
            'public_id' => 'FIL-'.strtoupper(Str::random(16)),
            'customer_id' => null,
            'uploaded_by_user_id' => null,
            'uploaded_by_customer_id' => null,
            'storage_disk' => 'local',
            'storage_path' => 'private/designs/'.$name,
            'original_filename' => $name,
            'stored_filename' => Str::random(40).'.'.$ext,
            'extension' => $ext,
            'mime_type' => match ($ext) {
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'pdf' => 'application/pdf',
                'svg' => 'image/svg+xml',
                default => 'application/octet-stream',
            },
            'size_bytes' => $this->faker->numberBetween(1024, 10_000_000),
            'file_kind' => StoredFile::KIND_ORIGINAL_UPLOAD,
            'visibility' => StoredFile::VISIBILITY_PRIVATE,
            'status' => StoredFile::STATUS_ACTIVE,
            'scan_status' => StoredFile::SCAN_SKIPPED,
        ];
    }

    public function staffOnly(): static
    {
        return $this->state(['visibility' => StoredFile::VISIBILITY_STAFF_ONLY]);
    }

    public function customerVisible(): static
    {
        return $this->state(['visibility' => StoredFile::VISIBILITY_CUSTOMER_VISIBLE]);
    }
}
