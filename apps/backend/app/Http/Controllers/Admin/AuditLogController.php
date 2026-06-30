<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the audit logs.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', AuditLog::class);

        $validated = $request->validate([
            'per_page' => 'integer|min:1|max:100',
            'action' => 'string|nullable',
            'module' => 'string|nullable',
            'subject_type' => 'string|nullable',
            'subject_public_id' => 'string|nullable',
        ]);

        $query = AuditLog::query()
            ->with(['actorUser', 'actorCustomer', 'relatedRecords']);

        if (! empty($validated['action'])) {
            $query->where('action', $validated['action']);
        }

        if (! empty($validated['module'])) {
            $query->where('module', $validated['module']);
        }

        if (! empty($validated['subject_type'])) {
            $query->where('subject_type', $validated['subject_type']);
        }

        if (! empty($validated['subject_public_id'])) {
            $query->where('subject_public_id', $validated['subject_public_id']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $logs = $query->orderBy('occurred_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        Gate::authorize('view', $auditLog);

        $auditLog->load(['actorUser', 'actorCustomer', 'relatedRecords']);

        return response()->json($auditLog);
    }
}
