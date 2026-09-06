<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\StoredFile;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class AdminOrderProofController extends Controller
{
    public function store(Request $request, Order $order, FileUploadService $files): RedirectResponse
    {
        Gate::authorize('update', $order);

        $validated = $request->validate([
            'proof_file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:5120'],
            'display_name' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $uploadedFile = $request->file('proof_file');
        $storedFile = $files->store($uploadedFile, $actor, [
            'file_kind' => StoredFile::KIND_PROOF,
            'visibility' => StoredFile::VISIBILITY_CUSTOMER_VISIBLE,
            'customer_id' => $order->customer_id,
        ]);

        try {
            DB::transaction(function () use ($order, $actor, $storedFile, $uploadedFile, $validated): void {
                $isFeatured = (bool) ($validated['is_featured'] ?? true);

                if ($isFeatured) {
                    $order->mockups()->update(['is_featured' => false]);
                }

                $displayName = trim((string) ($validated['display_name'] ?? ''));
                if ($displayName === '') {
                    $displayName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                }

                $nextSortOrder = ((int) $order->mockups()->max('sort_order')) + 1;

                $order->mockups()->create([
                    'stored_file_id' => $storedFile->id,
                    'display_name' => $displayName,
                    'is_featured' => $isFeatured,
                    'sort_order' => $nextSortOrder,
                    'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                ]);

                $storedFile->forceFill([
                    'metadata' => array_merge($storedFile->metadata ?? [], [
                        'order_proof' => [
                            'order_public_id' => $order->public_id,
                            'uploaded_by_user_id' => $actor->id,
                            'customer_visible' => true,
                        ],
                    ]),
                ])->save();

                $order->forceFill([
                    'design_status' => 'under_review',
                    'design_approved' => false,
                    'design_approved_at' => null,
                    'design_approved_by_user_id' => null,
                    'updated_by_user_id' => $actor->id,
                ])->save();
            });
        } catch (Throwable $throwable) {
            $files->delete($storedFile, $actor);
            throw $throwable;
        }

        return redirect()
            ->route('admin.orders.show', ['order' => $order->public_id])
            ->with('proof_uploaded', true)
            ->with('success', 'Customer proof uploaded. It is now available in the customer account.');
    }
}
