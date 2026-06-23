<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LeadController extends Controller
{
    /**
     * Display a listing of the leads.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Lead::class);

        $leads = Lead::query()
            ->latest('created_at')
            ->paginate($request->query('per_page', 15));

        $leads->through(function ($lead) {
            return [
                'public_id' => $lead->public_id,
                'source' => $lead->source,
                'source_detail' => $lead->source_detail,
                'status' => $lead->status,
                'priority' => $lead->priority,
                'contact_name' => $lead->contact_name,
                'company_name' => $lead->company_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'interest_summary' => $lead->interest_summary,
                'assigned_to_user_id' => $lead->assigned_to_user_id,
                'created_at' => $lead->created_at?->toIso8601String() ?? $lead->created_at,
                'updated_at' => $lead->updated_at?->toIso8601String() ?? $lead->updated_at,
            ];
        });

        return response()->json($leads);
    }

    /**
     * Display the specified lead.
     */
    public function show(Lead $lead)
    {
        Gate::authorize('view', $lead);

        return response()->json([
            'public_id' => $lead->public_id,
            'source' => $lead->source,
            'source_detail' => $lead->source_detail,
            'status' => $lead->status,
            'priority' => $lead->priority,
            'contact_name' => $lead->contact_name,
            'company_name' => $lead->company_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'city' => $lead->city,
            'state' => $lead->state,
            'country_code' => $lead->country_code,
            'interest_summary' => $lead->interest_summary,
            'requirements' => $lead->requirements,
            'product_interest' => $lead->product_interest,
            'utm_source' => $lead->utm_source,
            'utm_medium' => $lead->utm_medium,
            'utm_campaign' => $lead->utm_campaign,
            'utm_content' => $lead->utm_content,
            'utm_term' => $lead->utm_term,
            'referrer_url' => $lead->referrer_url,
            'landing_page_url' => $lead->landing_page_url,
            'last_contacted_at' => $lead->last_contacted_at?->toIso8601String() ?? $lead->last_contacted_at,
            'qualified_at' => $lead->qualified_at?->toIso8601String() ?? $lead->qualified_at,
            'lost_at' => $lead->lost_at?->toIso8601String() ?? $lead->lost_at,
            'lost_reason' => $lead->lost_reason,
            'converted_at' => $lead->converted_at?->toIso8601String() ?? $lead->converted_at,
            'assigned_to_user_id' => $lead->assigned_to_user_id,
            'created_at' => $lead->created_at?->toIso8601String() ?? $lead->created_at,
            'updated_at' => $lead->updated_at?->toIso8601String() ?? $lead->updated_at,
        ], 200);
    }

    /**
     * Store a newly created lead in storage.
     */
    public function store(StoreLeadRequest $request)
    {
        Gate::authorize('create', Lead::class);

        $data = $request->validated();
        $data['created_by_user_id'] = $request->user()->id;

        $lead = Lead::create($data);

        return response()->json([
            'public_id' => $lead->public_id,
            'source' => $lead->source,
            'source_detail' => $lead->source_detail,
            'status' => $lead->status,
            'priority' => $lead->priority,
            'contact_name' => $lead->contact_name,
            'company_name' => $lead->company_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'city' => $lead->city,
            'state' => $lead->state,
            'country_code' => $lead->country_code,
            'interest_summary' => $lead->interest_summary,
            'requirements' => $lead->requirements,
            'product_interest' => $lead->product_interest,
            'created_at' => $lead->created_at,
            'updated_at' => $lead->updated_at,
        ], 201);
    }

    /**
     * Update the specified lead in storage.
     */
    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        Gate::authorize('update', $lead);

        $data = $request->validated();
        $actorId = $request->user()?->id;

        // --- status change handling ---
        $statusChanged = isset($data['status']) && $data['status'] !== $lead->status;
        $previousStatus = $lead->status;

        if ($statusChanged) {
            if (! $lead->canTransitionTo($data['status'])) {
                return response()->json([
                    'message' => "The status transition from '{$lead->status}' to '{$data['status']}' is not allowed.",
                    'errors' => [
                        'status' => ["The status transition from '{$lead->status}' to '{$data['status']}' is not allowed."],
                    ],
                ], 422);
            }

            if ($data['status'] === 'lost') {
                $lead->lost_at = now();
            } elseif ($data['status'] === 'won') {
                $lead->converted_at = now();
            } elseif ($data['status'] === 'qualified') {
                $lead->qualified_at = now();
            }
        }

        // --- assignment change handling ---
        $previousAssignee = $lead->assigned_to_user_id;
        $assignmentChanged = array_key_exists('assigned_to_user_id', $data)
            && $data['assigned_to_user_id'] !== $previousAssignee;

        $lead->fill($data);

        // Clear lost metadata when exiting the lost status
        if ($lead->status !== 'lost') {
            $lead->lost_reason = null;
            $lead->lost_at = null;
        }

        $lead->save();

        // --- record activities after save ---
        if ($statusChanged) {
            LeadActivity::recordStatusChange($lead, $previousStatus, $lead->status, $actorId);
        }

        if ($assignmentChanged) {
            LeadActivity::recordAssignment($lead, $previousAssignee, $lead->assigned_to_user_id, $actorId);
        }

        return response()->json([
            'public_id' => $lead->public_id,
            'source' => $lead->source,
            'source_detail' => $lead->source_detail,
            'status' => $lead->status,
            'priority' => $lead->priority,
            'assigned_to_user_id' => $lead->assigned_to_user_id,
            'lost_reason' => $lead->lost_reason,
            'contact_name' => $lead->contact_name,
            'company_name' => $lead->company_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'city' => $lead->city,
            'state' => $lead->state,
            'country_code' => $lead->country_code,
            'interest_summary' => $lead->interest_summary,
            'requirements' => $lead->requirements,
            'product_interest' => $lead->product_interest,
            'created_at' => $lead->created_at,
            'updated_at' => $lead->updated_at,
        ], 200);
    }
}
