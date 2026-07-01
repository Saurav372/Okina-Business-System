<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class NotificationLogController extends Controller
{
    /**
     * Display a listing of the notification logs.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', NotificationLog::class);

        $validator = Validator::make($request->all(), [
            'per_page' => 'integer|min:1|max:100',
            'channel' => 'string|nullable',
            'status' => 'string|nullable',
            'recipient_address' => 'string|nullable',
            'event_type' => 'string|nullable',
            'recipient_user_id' => 'integer|nullable',
            'recipient_customer_id' => 'integer|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $query = NotificationLog::query()
            ->with(['attempts' => function ($q): void {
                $q->select(['id', 'notification_log_id', 'status', 'attempted_at', 'provider_reference']);
            }]);

        if (! empty($validated['channel'])) {
            $query->where('channel', $validated['channel']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['recipient_address'])) {
            $query->where('recipient_address', $validated['recipient_address']);
        }

        if (! empty($validated['event_type'])) {
            $query->where('event_type', $validated['event_type']);
        }

        if (! empty($validated['recipient_user_id'])) {
            $query->where('recipient_user_id', $validated['recipient_user_id']);
        }

        if (! empty($validated['recipient_customer_id'])) {
            $query->where('recipient_customer_id', $validated['recipient_customer_id']);
        }

        $perPage = $validated['per_page'] ?? 25;
        $logs = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // Transform results to output lightweight index structure
        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'event_type' => $log->event_type,
                'template_key' => $log->template_key,
                'channel' => $log->channel,
                'status' => $log->status,
                'recipient_type' => $log->recipient_type,
                'recipient_address' => $log->recipient_address,
                'created_at' => $log->created_at->toDateTimeString(),
                'attempts' => $log->attempts->map(function ($attempt) {
                    return [
                        'status' => $attempt->status,
                        'attempted_at' => $attempt->attempted_at?->toDateTimeString(),
                        'provider_reference' => $attempt->provider_reference,
                    ];
                }),
            ];
        });

        return response()->json($logs);
    }

    /**
     * Display the specified notification log.
     */
    public function show(NotificationLog $notificationLog): JsonResponse
    {
        Gate::authorize('view', $notificationLog);

        $notificationLog->load(['template', 'attempts']);

        return response()->json($notificationLog);
    }
}
