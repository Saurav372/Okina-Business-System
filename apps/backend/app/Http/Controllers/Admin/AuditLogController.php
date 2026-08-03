<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuditLogFilterRequest;
use App\Http\Resources\Admin\AuditLogResource;
use App\Models\AuditLog;
use App\Support\Audit\AuditLogFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    /**
     * Display listing of audit logs (HTML Blade or JSON API Resource).
     */
    public function index(AuditLogFilterRequest $request): mixed
    {
        Gate::authorize('viewAny', AuditLog::class);

        $filters = AuditLogFilters::fromValidated($request->validated());

        $query = AuditLog::query()
            ->with(['actorUser', 'actorCustomer']);

        if ($filters->action) {
            if (str_contains($filters->action, '*')) {
                $query->where('action', 'like', str_replace('*', '%', $filters->action));
            } else {
                $query->where('action', $filters->action);
            }
        }

        if ($filters->module) {
            $query->where('module', $filters->module);
        }

        if ($filters->subjectTypeClass) {
            $query->where('subject_type', $filters->subjectTypeClass);
        }

        if ($filters->subjectId) {
            $query->where(function ($q) use ($filters): void {
                $q->where('subject_id', $filters->subjectId)
                    ->orWhere('subject_public_id', $filters->subjectId);
            });
        }

        if ($filters->actorPublicId) {
            if (strtolower($filters->actorPublicId) === 'system') {
                $query->whereNull('actor_user_id');
            } else {
                $query->whereHas('actorUser', function ($q) use ($filters): void {
                    $q->where('email', $filters->actorPublicId)
                        ->orWhere('name', 'like', '%'.addcslashes($filters->actorPublicId, '%_').'%');
                });
            }
        }

        // Inclusive calendar date boundaries in UTC
        $query->whereBetween('occurred_at', [
            $filters->startDate->setTimezone('UTC'),
            $filters->endDate->setTimezone('UTC'),
        ]);

        $logs = $query->orderBy('occurred_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($filters->perPage)
            ->withQueryString();

        if ($request->wantsJson()) {
            return AuditLogResource::collection($logs);
        }

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'subjectOptions' => AuditLogFilters::SUBJECT_MAP,
        ]);
    }

    /**
     * Display specified audit log.
     */
    public function show(Request $request, AuditLog $auditLog): mixed
    {
        Gate::authorize('view', $auditLog);

        $auditLog->load(['actorUser', 'actorCustomer']);

        if ($request->wantsJson()) {
            return new AuditLogResource($auditLog);
        }

        return view('admin.audit-logs.show', [
            'auditLog' => new AuditLogResource($auditLog),
        ]);
    }
}
