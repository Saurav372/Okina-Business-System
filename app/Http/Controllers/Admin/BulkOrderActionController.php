<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkOrderActionRequest;
use App\Services\BulkOrderActionService;

class BulkOrderActionController extends Controller
{
    public function __construct(
        protected BulkOrderActionService $bulkService
    ) {}

    public function handle(BulkOrderActionRequest $request)
    {
        $result = $this->bulkService->execute(
            action: $request->input('action'),
            orderIds: $request->input('order_ids'),
            actor: $request->user()
        );

        $actionWord = $result->action === 'confirm' ? 'confirmed' : 'cancelled';
        $message = "{$result->updatedCount} orders {$actionWord} successfully.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'updated_count' => $result->updatedCount,
                    'updated_ids' => $result->updatedPublicIds,
                ],
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
