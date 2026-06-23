<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadActivityRequest;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Support\Facades\Gate;

class LeadActivityController extends Controller
{
    /**
     * Display a listing of the lead's activities.
     */
    public function index(Lead $lead)
    {
        Gate::authorize('viewActivities', $lead);

        $activities = $lead->activities()->with('createdBy')->get();

        $mapped = $activities->map(function (LeadActivity $activity) {
            return [
                'activity_type' => $activity->activity_type,
                'subject' => $activity->subject,
                'body' => $activity->body,
                'metadata' => $activity->metadata,
                'customer_visible' => $activity->customer_visible,
                'occurred_at' => $activity->occurred_at?->toIso8601String() ?? $activity->occurred_at,
                'created_at' => $activity->created_at?->toIso8601String() ?? $activity->created_at,
                'created_by' => $activity->createdBy ? [
                    'name' => $activity->createdBy->name,
                    'email' => $activity->createdBy->email,
                ] : null,
            ];
        });

        return response()->json($mapped);
    }

    /**
     * Store a newly created lead activity/note in storage.
     */
    public function store(StoreLeadActivityRequest $request, Lead $lead)
    {
        Gate::authorize('createActivity', $lead);

        $data = $request->validated();

        $activity = LeadActivity::create([
            'lead_id' => $lead->id,
            'activity_type' => $data['activity_type'] ?? 'note',
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'],
            'metadata' => null,
            'customer_visible' => false,
            'created_by_user_id' => $request->user()->id,
            'occurred_at' => now(),
        ]);

        $activity->load('createdBy');

        return response()->json([
            'activity_type' => $activity->activity_type,
            'subject' => $activity->subject,
            'body' => $activity->body,
            'metadata' => $activity->metadata,
            'customer_visible' => $activity->customer_visible,
            'occurred_at' => $activity->occurred_at?->toIso8601String() ?? $activity->occurred_at,
            'created_at' => $activity->created_at?->toIso8601String() ?? $activity->created_at,
            'created_by' => $activity->createdBy ? [
                'name' => $activity->createdBy->name,
                'email' => $activity->createdBy->email,
            ] : null,
        ], 201);
    }
}
