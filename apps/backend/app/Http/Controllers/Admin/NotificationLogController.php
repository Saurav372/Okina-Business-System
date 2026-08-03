<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NotificationLogFilterRequest;
use App\Http\Resources\Admin\NotificationLogResource;
use App\Models\NotificationLog;
use App\Support\Notification\NotificationLogFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificationLogController extends Controller
{
    /**
     * Display listing of notification logs (HTML Blade or JSON API Resource).
     */
    public function index(NotificationLogFilterRequest $request): mixed
    {
        Gate::authorize('viewAny', NotificationLog::class);

        $filters = NotificationLogFilters::fromValidated($request->validated());

        $query = NotificationLog::query()
            ->with(['attempts' => function ($q): void {
                $q->orderBy('attempted_at', 'asc');
            }]);

        if ($filters->channel) {
            $query->where('channel', $filters->channel);
        }

        if ($filters->status) {
            $query->where('status', $filters->status);
        }

        if ($filters->eventType) {
            $query->where('event_type', $filters->eventType);
        }

        if ($filters->recipientAddress) {
            $escaped = addcslashes($filters->recipientAddress, '%_');
            $query->where('recipient_address', 'like', '%'.$escaped.'%');
        }

        $query->whereBetween('created_at', [
            $filters->startDate->setTimezone('UTC'),
            $filters->endDate->setTimezone('UTC'),
        ]);

        $logs = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($filters->perPage)
            ->withQueryString();

        if ($request->wantsJson()) {
            return NotificationLogResource::collection($logs);
        }

        return view('admin.notification-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }

    /**
     * Display specified notification log.
     */
    public function show(Request $request, NotificationLog $notificationLog): mixed
    {
        Gate::authorize('view', $notificationLog);

        $notificationLog->load(['template', 'attempts' => function ($q): void {
            $q->orderBy('attempted_at', 'asc');
        }]);

        if ($request->wantsJson()) {
            return new NotificationLogResource($notificationLog);
        }

        return view('admin.notification-logs.show', [
            'notificationLog' => new NotificationLogResource($notificationLog),
        ]);
    }
}
