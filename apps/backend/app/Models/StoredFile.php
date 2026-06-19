<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'public_id',
    'customer_id',
    'uploaded_by_user_id',
    'uploaded_by_customer_id',
    'storage_disk',
    'storage_path',
    'original_filename',
    'stored_filename',
    'extension',
    'mime_type',
    'size_bytes',
    'checksum_sha256',
    'file_kind',
    'visibility',
    'status',
    'scan_status',
    'metadata',
    'protected_until',
    'deleted_by_user_id',
])]
class StoredFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'files';

    public const KIND_ORIGINAL_UPLOAD = 'original_upload';

    public const KIND_PREVIEW = 'preview';

    public const KIND_MOCKUP = 'mockup';

    public const KIND_PROOF = 'proof';

    public const KIND_ATTACHMENT = 'attachment';

    public const KIND_EXPORT = 'export';

    public const FILE_KINDS = [
        self::KIND_ORIGINAL_UPLOAD,
        self::KIND_PREVIEW,
        self::KIND_MOCKUP,
        self::KIND_PROOF,
        self::KIND_ATTACHMENT,
        self::KIND_EXPORT,
    ];

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_CUSTOMER_VISIBLE = 'customer_visible';

    public const VISIBILITY_STAFF_ONLY = 'staff_only';

    public const VISIBILITY_PUBLIC_SAFE_PREVIEW = 'public_safe_preview';

    public const VISIBILITIES = [
        self::VISIBILITY_PRIVATE,
        self::VISIBILITY_CUSTOMER_VISIBLE,
        self::VISIBILITY_STAFF_ONLY,
        self::VISIBILITY_PUBLIC_SAFE_PREVIEW,
    ];

    public const STATUS_UPLOADING = 'uploading';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_QUARANTINED = 'quarantined';

    public const STATUS_REPLACED = 'replaced';

    public const STATUS_DELETED = 'deleted';

    public const STATUSES = [
        self::STATUS_UPLOADING,
        self::STATUS_ACTIVE,
        self::STATUS_QUARANTINED,
        self::STATUS_REPLACED,
        self::STATUS_DELETED,
    ];

    public const SCAN_PENDING = 'pending';

    public const SCAN_PASSED = 'passed';

    public const SCAN_FAILED = 'failed';

    public const SCAN_SKIPPED = 'skipped';

    public const SCAN_STATUSES = [
        self::SCAN_PENDING,
        self::SCAN_PASSED,
        self::SCAN_FAILED,
        self::SCAN_SKIPPED,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function uploadedByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'uploaded_by_customer_id');
    }

    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function hasPreview(): bool
    {
        return is_array($this->metadata)
            && filled(data_get($this->metadata, 'preview.path'));
    }

    public function previewMetadata(): ?array
    {
        $preview = data_get($this->metadata, 'preview');

        return is_array($preview) ? $preview : null;
    }

    public function previewPath(): ?string
    {
        return data_get($this->metadata, 'preview.path');
    }

    public function previewMimeType(): ?string
    {
        return data_get($this->metadata, 'preview.mime_type');
    }

    public function previewStorageDisk(): ?string
    {
        return data_get($this->metadata, 'preview.storage_disk');
    }

    public function previewSizeBytes(): ?int
    {
        return data_get($this->metadata, 'preview.size_bytes');
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size_bytes' => 'integer',
            'checksum_sha256' => 'string',
            'protected_until' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $file): void {
            if (! filled($file->public_id)) {
                $file->public_id = self::generatePublicId();
            }

            $file->status ??= self::STATUS_ACTIVE;
            $file->scan_status ??= self::SCAN_SKIPPED;
        });
    }

    private static function generatePublicId(): string
    {
        return 'FIL-'.Str::upper(Str::random(16));
    }
}
