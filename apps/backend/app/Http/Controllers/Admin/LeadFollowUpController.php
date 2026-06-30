<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadFollowUpStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\LeadFollowUpListRequest;
use App\Http\Requests\Lead\StoreLeadFollowUpRequest;
use App\Http\Requests\Lead\UpdateLeadFollowUpRequest;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class LeadFollowUpController extends Controller
{
    /**
     * Display a listing of lead follow-ups.
     */
    public function index(LeadFollowUpListRequest $request)
    {
        Gate::authorize('viewAny', LeadFollowUp::class);

        $query = LeadFollowUp::query()
            ->with([
                'assignedTo',
                'completedBy',
                'createdBy',
            ]);

        // Filter by assigned user
        if ($request->filled('assigned_to_user_id')) {
            $query->where('assigned_to_user_id', $request->input('assigned_to_user_id'));
        }

        // Apply mutually exclusive status or custom filters
        if ($request->input('filter') === 'overdue') {
            $query->overdue();
        } elseif ($request->input('filter') === 'due_today') {
            $query->dueToday();
        } elseif ($request->filled('status')) {
            $status = LeadFollowUpStatus::from($request->input('status'));
            $query->where('status', $status->value);
        }

        // Default sorting (ascending due_at, secondary id) and pagination preserving query strings
        $followUps = $query->orderBy('due_at')
            ->orderBy('id')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        $followUps->through(fn ($item) => $this->formatResponse($item));

        return response()->json($followUps);
    }

    /**
     * Store a newly created lead follow-up.
     */
    public function store(StoreLeadFollowUpRequest $request, Lead $lead)
    {
        Gate::authorize('create', LeadFollowUp::class);

        $data = $request->validated();
        $data['lead_id'] = $lead->id;
        $data['created_by_user_id'] = $request->user()->id;
        $data['status'] = LeadFollowUpStatus::PENDING->value;

        $followUp = DB::transaction(function () use ($data) {
            return LeadFollowUp::create($data);
        });

        return response()->json($this->formatResponse($followUp), 201);
    }

    /**
     * Update the specified lead follow-up (reschedule).
     */
    public function update(UpdateLeadFollowUpRequest $request, Lead $lead, LeadFollowUp $followUp)
    {
        Gate::authorize('update', $followUp);

        $this->ensureMutable($followUp);

        $data = $request->validated();

        if (array_key_exists('due_at', $data) && $data['due_at'] !== null) {
            $data['snoozed_until'] = null;
        }

        $followUp = DB::transaction(function () use ($followUp, $data) {
            $followUp->update($data);

            return $followUp;
        });

        return response()->json($this->formatResponse($followUp), 200);
    }

    /**
     * Complete the specified lead follow-up.
     */
    public function complete(Request $request, Lead $lead, LeadFollowUp $followUp)
    {
        Gate::authorize('complete', $followUp);

        $this->ensureMutable($followUp);

        $followUp = DB::transaction(function () use ($followUp, $request) {
            $followUp->update([
                'status' => LeadFollowUpStatus::COMPLETED->value,
                'completed_at' => now(),
                'completed_by_user_id' => $request->user()->id,
                'snoozed_until' => null,
            ]);

            return $followUp;
        });

        return response()->json($this->formatResponse($followUp), 200);
    }

    /**
     * Cancel the specified lead follow-up.
     */
    public function cancel(Request $request, Lead $lead, LeadFollowUp $followUp)
    {
        Gate::authorize('cancel', $followUp);

        $this->ensureMutable($followUp);

        $followUp = DB::transaction(function () use ($followUp) {
            $followUp->update([
                'status' => LeadFollowUpStatus::CANCELLED->value,
                'snoozed_until' => null,
            ]);

            return $followUp;
        });

        return response()->json($this->formatResponse($followUp), 200);
    }

    /**
     * Ensure the follow-up is in a mutable state (not completed or cancelled).
     */
    private function ensureMutable(LeadFollowUp $followUp): void
    {
        if (in_array($followUp->status, [LeadFollowUpStatus::COMPLETED, LeadFollowUpStatus::CANCELLED], true)) {
            throw ValidationException::withMessages([
                'status' => ['Cannot perform actions on a completed or cancelled follow-up.'],
            ]);
        }
    }

    /**
     * Format the lead follow-up representation.
     */
    private function formatResponse(LeadFollowUp $followUp): array
    {
        $followUp->loadMissing([
            'assignedTo',
            'completedBy',
            'createdBy',
        ]);

        return [
            'id' => $followUp->id,
            'status' => $followUp->status->value,
            'due_at' => $followUp->due_at?->toIso8601String() ?? $followUp->due_at,
            'completed_at' => $followUp->completed_at?->toIso8601String() ?? $followUp->completed_at,
            'snoozed_until' => $followUp->snoozed_until?->toIso8601String() ?? $followUp->snoozed_until,
            'subject' => $followUp->subject,
            'notes' => $followUp->notes,
            'notification_key' => $followUp->notification_key,
            'assigned_to' => $followUp->assignedTo ? [
                'name' => $followUp->assignedTo->name,
                'email' => $followUp->assignedTo->email,
            ] : null,
            'completed_by' => $followUp->completedBy ? [
                'name' => $followUp->completedBy->name,
                'email' => $followUp->completedBy->email,
            ] : null,
            'created_by' => $followUp->createdBy ? [
                'name' => $followUp->createdBy->name,
                'email' => $followUp->createdBy->email,
            ] : null,
            'created_at' => $followUp->created_at?->toIso8601String() ?? $followUp->created_at,
            'updated_at' => $followUp->updated_at?->toIso8601String() ?? $followUp->updated_at,
        ];
    }
}
