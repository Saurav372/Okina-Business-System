<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncRecordToGoogleSheetsJob;
use App\Models\GoogleSheetsSyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

class GoogleSheetsSyncLogController extends Controller
{
    /**
     * Display a listing of the Google Sheets sync logs.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', GoogleSheetsSyncLog::class);

        $validated = $request->validate([
            'per_page' => 'integer|min:1|max:100',
            'status' => 'string|in:queued,processing,success,failed|nullable',
            'model_class' => 'string|nullable',
            'model_id' => 'integer|nullable',
        ]);

        $query = GoogleSheetsSyncLog::query()->with('user');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['model_class'])) {
            $query->where('model_class', $validated['model_class']);
        }

        if (! empty($validated['model_id'])) {
            $query->where('model_id', $validated['model_id']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $logs = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Display the specified sync log.
     */
    public function show(GoogleSheetsSyncLog $googleSheetsSyncLog): JsonResponse
    {
        Gate::authorize('view', $googleSheetsSyncLog);

        $googleSheetsSyncLog->load('user');

        return response()->json($googleSheetsSyncLog);
    }

    /**
     * Retry a specific failed sync log.
     */
    public function retry(GoogleSheetsSyncLog $googleSheetsSyncLog): JsonResponse
    {
        Gate::authorize('retry', GoogleSheetsSyncLog::class);

        if ($googleSheetsSyncLog->status !== GoogleSheetsSyncLog::STATUS_FAILED) {
            return response()->json([
                'message' => 'Only failed sync events can be retried.',
            ], 400);
        }

        // Transition status back to queued
        $googleSheetsSyncLog->update([
            'status' => GoogleSheetsSyncLog::STATUS_QUEUED,
            'error_message' => null,
            'completed_at' => null,
        ]);

        SyncRecordToGoogleSheetsJob::dispatch(
            $googleSheetsSyncLog->model_class,
            $googleSheetsSyncLog->model_id,
            $googleSheetsSyncLog->id,
            'retry',
            auth()->id()
        );

        return response()->json([
            'message' => 'Synchronization job has been queued for retry.',
            'log' => $googleSheetsSyncLog,
        ]);
    }

    /**
     * Bulk retry failed sync logs.
     */
    public function bulkRetry(Request $request): JsonResponse
    {
        Gate::authorize('retry', GoogleSheetsSyncLog::class);

        $validated = $request->validate([
            'log_ids' => 'required|array',
            'log_ids.*' => 'integer|exists:google_sheets_sync_logs,id',
        ]);

        $logs = GoogleSheetsSyncLog::whereIn('id', $validated['log_ids'])
            ->where('status', GoogleSheetsSyncLog::STATUS_FAILED)
            ->get();

        $count = 0;
        foreach ($logs as $log) {
            $log->update([
                'status' => GoogleSheetsSyncLog::STATUS_QUEUED,
                'error_message' => null,
                'completed_at' => null,
            ]);

            SyncRecordToGoogleSheetsJob::dispatch(
                $log->model_class,
                $log->model_id,
                $log->id,
                'retry',
                auth()->id()
            );
            $count++;
        }

        return response()->json([
            'message' => "Successfully queued {$count} job(s) for retry.",
        ]);
    }

    /**
     * Manually trigger sync for a specific model record.
     */
    public function syncRecord(Request $request): JsonResponse
    {
        Gate::authorize('sync', GoogleSheetsSyncLog::class);

        $entities = array_keys(Config::get('sheets.entities', []));
        $entitiesList = implode(',', $entities);

        $validated = $request->validate([
            'model_class' => 'required|string|in:'.$entitiesList,
            'model_id' => 'required|integer',
        ]);

        $modelClass = $validated['model_class'];
        $modelId = $validated['model_id'];

        // Confirm record exists
        $model = $modelClass::find($modelId);
        if (! $model) {
            return response()->json([
                'message' => "Record not found for model [{$modelClass}] ID [{$modelId}].",
            ], 404);
        }

        // Create a new independent sync log/event in queued status
        $log = GoogleSheetsSyncLog::create([
            'model_class' => $modelClass,
            'model_id' => $modelId,
            'unique_key' => 'pending',
            'unique_value' => 'pending',
            'status' => GoogleSheetsSyncLog::STATUS_QUEUED,
            'triggered_by' => 'manual',
            'user_id' => auth()->id(),
            'payload_hash' => '',
        ]);

        SyncRecordToGoogleSheetsJob::dispatch(
            $modelClass,
            $modelId,
            $log->id,
            'manual',
            auth()->id()
        );

        return response()->json([
            'message' => 'Manual synchronization job has been queued.',
            'log' => $log,
        ]);
    }

    /**
     * Manually trigger log pruning.
     */
    public function prune(): JsonResponse
    {
        Gate::authorize('prune', GoogleSheetsSyncLog::class);

        Artisan::call('sheets:prune-logs');
        $output = Artisan::output();

        return response()->json([
            'message' => 'Pruning complete.',
            'output' => trim($output),
        ]);
    }
}
