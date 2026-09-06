<?php

namespace App\Services;

use App\Models\CustomerAccount;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class FileUploadService
{
    private const MAX_BYTES = 5242880;

    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'pdf',
    ];

    private const ALLOWED_MIME_TYPES = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
    ];

    private const DANGEROUS_EXTENSIONS = [
        'php',
        'phtml',
        'phar',
        'cgi',
        'pl',
        'asp',
        'aspx',
        'js',
        'sh',
        'bat',
        'cmd',
        'com',
        'exe',
        'dll',
    ];

    public function store(UploadedFile $uploadedFile, Authenticatable $actor, array $attributes = []): StoredFile
    {
        $this->validateUpload($uploadedFile, $attributes);

        $disk = Storage::disk('private');
        $publicId = $this->generatePublicId();
        $extension = $this->resolveExtension($uploadedFile);
        $originalFilename = $this->sanitizeOriginalFilename($uploadedFile->getClientOriginalName());
        $storedFilename = $this->generateStoredFilename($extension);
        $storagePath = trim('files/'.$publicId.'/'.$storedFilename, '/');
        $checksum = hash_file('sha256', $uploadedFile->getRealPath()) ?: null;
        $sizeBytes = (int) $uploadedFile->getSize();
        $mimeType = $this->resolveMimeType($uploadedFile, $extension);
        $clientOriginalName = $uploadedFile->getClientOriginalName();
        $previewMetadata = null;

        try {
            $disk->putFileAs(dirname($storagePath), $uploadedFile, basename($storagePath));
            $previewMetadata = $this->createPreviewMetadata($disk, $storagePath, $mimeType);

            return DB::transaction(function () use (
                $actor,
                $attributes,
                $checksum,
                $extension,
                $mimeType,
                $publicId,
                $originalFilename,
                $clientOriginalName,
                $previewMetadata,
                $sizeBytes,
                $storagePath,
                $storedFilename
            ): StoredFile {
                return StoredFile::query()->create([
                    'public_id' => $publicId,
                    'customer_id' => $this->resolveCustomerId($actor, $attributes),
                    'uploaded_by_user_id' => $actor instanceof User ? $actor->id : null,
                    'uploaded_by_customer_id' => $actor instanceof CustomerAccount ? $actor->customer_id : null,
                    'storage_disk' => 'private',
                    'storage_path' => $storagePath,
                    'original_filename' => $originalFilename,
                    'stored_filename' => $storedFilename,
                    'extension' => $extension,
                    'mime_type' => $mimeType,
                    'size_bytes' => $sizeBytes,
                    'checksum_sha256' => $checksum,
                    'file_kind' => $this->resolveFileKind($attributes),
                    'visibility' => $this->resolveVisibility($attributes),
                    'status' => StoredFile::STATUS_ACTIVE,
                    'scan_status' => StoredFile::SCAN_SKIPPED,
                    'metadata' => array_filter([
                        'preview' => $previewMetadata,
                        'source' => [
                            'client_name' => $clientOriginalName,
                        ],
                    ]),
                    'protected_until' => $this->resolveProtectedUntil($attributes),
                ]);
            });
        } catch (Throwable $throwable) {
            $disk->delete(array_values(array_filter([
                $storagePath,
                data_get($previewMetadata, 'path'),
            ])));

            throw $throwable;
        }
    }

    public function ensurePreview(StoredFile $file): StoredFile
    {
        if ($file->hasPreview() || ! $file->isImage()) {
            return $file;
        }

        $previewMetadata = $this->generatePreviewMetadata($file);

        if ($previewMetadata === null) {
            return $file;
        }

        $file->forceFill([
            'metadata' => array_merge($file->metadata ?? [], [
                'preview' => $previewMetadata,
            ]),
        ])->save();

        return $file->refresh();
    }

    public function temporaryPreviewUrl(StoredFile $file, int $minutes = 15): string
    {
        return URL::temporarySignedRoute('files.preview', now()->addMinutes($minutes), [
            'file' => $file->public_id,
        ]);
    }

    public function temporaryDownloadUrl(StoredFile $file, int $minutes = 15): string
    {
        return URL::temporarySignedRoute('files.download', now()->addMinutes($minutes), [
            'file' => $file->public_id,
        ]);
    }

    public function delete(StoredFile $file, Authenticatable $actor): void
    {
        if ($file->trashed()) {
            return;
        }

        if ($file->protected_until !== null && $file->protected_until->isFuture()) {
            throw ValidationException::withMessages([
                'file' => 'This file is protected and cannot be deleted yet.',
            ]);
        }

        $disk = Storage::disk($file->storage_disk);
        foreach (array_filter([
            $file->storage_path,
            $file->previewPath(),
        ]) as $path) {
            $disk->delete($path);
            $absolutePath = $disk->path($path);
            clearstatcache(true, $absolutePath);
            @unlink($absolutePath);
            clearstatcache(true, $absolutePath);
        }

        $directory = $disk->path(dirname($file->storage_path));
        $disk->deleteDirectory(dirname($file->storage_path));
        clearstatcache(true, $directory);
        @rmdir($directory);
        clearstatcache(true, $directory);

        $file->forceFill([
            'status' => StoredFile::STATUS_DELETED,
            'deleted_by_user_id' => $actor instanceof User ? $actor->id : null,
        ])->save();

        $file->delete();
    }

    private function validateUpload(UploadedFile $uploadedFile, array $attributes): void
    {
        Validator::make(array_merge($attributes, [
            'file' => $uploadedFile,
        ]), [
            'file' => ['required', 'file', 'max:'.(self::MAX_BYTES / 1024)],
            'file_kind' => ['nullable', Rule::in(StoredFile::FILE_KINDS)],
            'visibility' => ['nullable', Rule::in(StoredFile::VISIBILITIES)],
            'protected_until' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'integer'],
        ])->validate();

        $originalName = $uploadedFile->getClientOriginalName();
        $normalizedName = Str::lower($originalName);

        if (str_contains($originalName, "\0") || str_contains($originalName, '/') || str_contains($originalName, '\\')) {
            $this->throwUnsafeFileError();
        }

        $nameSegments = array_values(array_filter(explode('.', $normalizedName)));

        if (count($nameSegments) < 2) {
            $this->throwUnsafeFileError();
        }

        foreach (array_slice($nameSegments, 0, -1) as $segment) {
            if (in_array($segment, self::DANGEROUS_EXTENSIONS, true)) {
                $this->throwUnsafeFileError();
            }
        }

        $extension = $this->resolveExtension($uploadedFile);

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $this->throwUnsafeFileError();
        }

        $mimeType = $this->resolveMimeType($uploadedFile, $extension);

        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES[$extension], true)) {
            $this->throwUnsafeFileError();
        }

        if ((int) $uploadedFile->getSize() <= 0 || (int) $uploadedFile->getSize() > self::MAX_BYTES) {
            $this->throwUnsafeFileError();
        }
    }

    private function resolveCustomerId(Authenticatable $actor, array $attributes): ?int
    {
        if ($actor instanceof CustomerAccount) {
            return $actor->customer_id;
        }

        return isset($attributes['customer_id']) ? (int) $attributes['customer_id'] : null;
    }

    private function resolveFileKind(array $attributes): string
    {
        $fileKind = $attributes['file_kind'] ?? StoredFile::KIND_ATTACHMENT;

        return in_array($fileKind, StoredFile::FILE_KINDS, true)
            ? $fileKind
            : StoredFile::KIND_ATTACHMENT;
    }

    private function resolveVisibility(array $attributes): string
    {
        $visibility = $attributes['visibility'] ?? StoredFile::VISIBILITY_PRIVATE;

        return in_array($visibility, StoredFile::VISIBILITIES, true)
            ? $visibility
            : StoredFile::VISIBILITY_PRIVATE;
    }

    private function resolveProtectedUntil(array $attributes): ?Carbon
    {
        $value = $attributes['protected_until'] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    private function resolveExtension(UploadedFile $uploadedFile): string
    {
        return Str::lower((string) $uploadedFile->getClientOriginalExtension());
    }

    private function resolveMimeType(UploadedFile $uploadedFile, string $extension): string
    {
        $mimeType = Str::lower((string) $uploadedFile->getMimeType());

        if (in_array($mimeType, self::ALLOWED_MIME_TYPES[$extension], true)) {
            return $mimeType;
        }

        $guessed = Str::lower((string) $uploadedFile->extension());

        if (in_array($guessed, self::ALLOWED_EXTENSIONS, true) && isset(self::ALLOWED_MIME_TYPES[$guessed])) {
            $guessedMime = self::ALLOWED_MIME_TYPES[$guessed][0];

            if (in_array($guessedMime, self::ALLOWED_MIME_TYPES[$extension], true)) {
                return $guessedMime;
            }
        }

        return $mimeType;
    }

    private function sanitizeOriginalFilename(string $originalName): string
    {
        $sanitized = Str::of(pathinfo($originalName, PATHINFO_FILENAME))
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9._-]+/', '-')
            ->trim('-_.')
            ->squish()
            ->limit(180, '')
            ->toString();

        return $sanitized === '' ? 'uploaded-file' : $sanitized;
    }

    private function generateStoredFilename(string $extension): string
    {
        return Str::lower(Str::random(40)).'.'.$extension;
    }

    private function generatePublicId(): string
    {
        return 'FIL-'.Str::upper(Str::random(16));
    }

    private function throwUnsafeFileError(): never
    {
        throw ValidationException::withMessages([
            'file' => 'The selected file is invalid or unsafe.',
        ]);
    }

    private function createPreviewMetadata($disk, string $storagePath, string $mimeType): ?array
    {
        if (! str_starts_with($mimeType, 'image/')) {
            return null;
        }

        $previewMetadata = $this->generatePreviewMetadataFromBytes($disk->get($storagePath), $mimeType);

        if ($previewMetadata === null) {
            return null;
        }

        $previewPath = dirname($storagePath).'/preview.'.$previewMetadata['extension'];
        $disk->put($previewPath, $previewMetadata['contents']);

        return array_filter([
            'storage_disk' => 'private',
            'path' => $previewPath,
            'stored_filename' => $previewMetadata['stored_filename'],
            'extension' => $previewMetadata['extension'],
            'mime_type' => $previewMetadata['mime_type'],
            'size_bytes' => strlen($previewMetadata['contents']),
            'width' => $previewMetadata['width'],
            'height' => $previewMetadata['height'],
        ]);
    }

    private function generatePreviewMetadata(StoredFile $file): ?array
    {
        $disk = Storage::disk($file->storage_disk);
        $preview = $this->generatePreviewMetadataFromBytes($disk->get($file->storage_path), $file->mime_type);

        if ($preview === null) {
            return null;
        }

        $previewPath = 'files/previews/'.now()->format('Y/m').'/'.$preview['stored_filename'];
        $disk->put($previewPath, $preview['contents']);

        return array_filter([
            'storage_disk' => $file->storage_disk,
            'path' => $previewPath,
            'stored_filename' => $preview['stored_filename'],
            'extension' => $preview['extension'],
            'mime_type' => $preview['mime_type'],
            'size_bytes' => strlen($preview['contents']),
            'width' => $preview['width'],
            'height' => $preview['height'],
        ]);
    }

    private function generatePreviewMetadataFromBytes(string $bytes, string $mimeType): ?array
    {
        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $maxDimension = 1600;
        $scale = min(1, $maxDimension / max($width, 1), $maxDimension / max($height, 1));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $previewImage = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($previewImage === false) {
            imagedestroy($image);

            return null;
        }

        if (in_array($mimeType, ['image/png', 'image/webp'], true)) {
            imagealphablending($previewImage, false);
            imagesavealpha($previewImage, true);
        }

        imagecopyresampled($previewImage, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        if ($mimeType === 'image/png' || ($mimeType === 'image/webp' && function_exists('imagewebp'))) {
            $extension = $mimeType === 'image/webp' ? 'webp' : 'png';
            $previewMime = $mimeType === 'image/webp' ? 'image/webp' : 'image/png';
        } elseif ($mimeType === 'image/gif') {
            $extension = 'gif';
            $previewMime = 'image/gif';
        } else {
            $extension = 'jpg';
            $previewMime = 'image/jpeg';
        }

        ob_start();

        match ($extension) {
            'png' => imagepng($previewImage),
            'gif' => imagegif($previewImage),
            'webp' => imagewebp($previewImage),
            default => imagejpeg($previewImage, null, 82),
        };

        $previewBytes = ob_get_clean();

        imagedestroy($image);
        imagedestroy($previewImage);

        if ($previewBytes === false || $previewBytes === '') {
            return null;
        }

        return [
            'contents' => $previewBytes,
            'stored_filename' => Str::lower(Str::random(40)).'.'.$extension,
            'extension' => $extension,
            'mime_type' => $previewMime,
            'width' => $targetWidth,
            'height' => $targetHeight,
        ];
    }
}
