<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoredFile;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderMockupUploadController extends Controller
{
    public function upload(Request $request, FileUploadService $files): JsonResponse
    {
        $request->validate([
            'mockup_file' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ], [
            'mockup_file.required' => 'Please provide an image file or paste an image.',
            'mockup_file.mimes' => 'Mockup image must be a PNG, JPG, or WEBP file.',
            'mockup_file.max' => 'Mockup image must not exceed 10 MB.',
        ]);

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $uploadedFile = $request->file('mockup_file');

        $storedFile = $files->store($uploadedFile, $actor, [
            'file_kind' => StoredFile::KIND_MOCKUP,
            'visibility' => StoredFile::VISIBILITY_CUSTOMER_VISIBLE,
        ]);

        return response()->json([
            'success' => true,
            'stored_file_id' => $storedFile->id,
            'public_id' => $storedFile->public_id,
            'filename' => $storedFile->original_filename,
            'size_bytes' => $storedFile->size_bytes,
            'preview_url' => $files->temporaryPreviewUrl($storedFile, 60),
        ], 201);
    }
}
