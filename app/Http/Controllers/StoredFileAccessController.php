<?php

namespace App\Http\Controllers;

use App\Models\StoredFile;
use App\Services\FileUploadService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StoredFileAccessController extends Controller
{
    public function preview(Request $request, StoredFile $file, FileUploadService $service): Response
    {
        $actor = $this->resolveActor($request);
        abort_unless($actor instanceof Authenticatable, 403);

        Gate::forUser($actor)->authorize('preview', $file);

        $file = $service->ensurePreview($file);
        $path = $file->previewPath() ?? $file->storage_path;
        $mimeType = $file->previewMimeType() ?? $file->mime_type;

        return Storage::disk($file->previewStorageDisk() ?? $file->storage_disk)
            ->response($path, basename($path), ['Content-Type' => $mimeType], 'inline');
    }

    public function download(Request $request, StoredFile $file): Response
    {
        $actor = $this->resolveActor($request);
        abort_unless($actor instanceof Authenticatable, 403);

        Gate::forUser($actor)->authorize('download', $file);

        return Storage::disk($file->storage_disk)
            ->response($file->storage_path, $file->original_filename ?? $file->stored_filename, [
                'Content-Type' => $file->mime_type,
            ], 'attachment');
    }

    public function destroy(Request $request, StoredFile $file, FileUploadService $service): Response
    {
        $actor = $this->resolveActor($request);
        abort_unless($actor instanceof Authenticatable, 403);

        Gate::forUser($actor)->authorize('delete', $file);
        $service->delete($file, $actor);

        return response()->noContent();
    }

    private function resolveActor(Request $request): ?Authenticatable
    {
        return Auth::guard('web')->user() ?? Auth::guard('customer')->user();
    }
}
