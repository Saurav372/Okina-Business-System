<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\StoredFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * B2.2.8 — Authorized admin design-file access bridge.
 *
 * Allows authorized staff to preview or download private design files that are
 * linked to an order's customization snapshot, without exposing raw storage
 * paths or bypassing the shared StoredFilePolicy.
 *
 * Rules:
 * - Staff must have dashboard access.
 * - Staff must pass the 'view' check on the associated Order (orders.view permission).
 * - Staff must pass the 'view' check on the StoredFile (files.download_private permission).
 * - Raw storage paths are never included in responses.
 * - File lookups use the file's public_id only.
 */
final class AdminOrderDesignFileController extends Controller
{
    /**
     * Serve a signed, time-limited preview of a private design file linked to an order.
     */
    public function preview(Request $request, Order $order, StoredFile $file): StreamedResponse
    {
        Gate::authorize('view', $order);
        Gate::authorize('view', $file);

        $disk = $file->storage_disk ?? 'local';
        $path = $file->storage_path;

        abort_unless(Storage::disk($disk)->exists($path), 404, 'File not found in storage.');

        $mimeType = $file->mime_type ?? 'application/octet-stream';

        return Storage::disk($disk)->response($path, $file->original_filename, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$file->original_filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Force-download a private design file linked to an order.
     */
    public function download(Request $request, Order $order, StoredFile $file): StreamedResponse
    {
        Gate::authorize('view', $order);
        Gate::authorize('download', $file);

        $disk = $file->storage_disk ?? 'local';
        $path = $file->storage_path;

        abort_unless(Storage::disk($disk)->exists($path), 404, 'File not found in storage.');

        return Storage::disk($disk)->download($path, $file->original_filename, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
