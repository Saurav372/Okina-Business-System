<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Models\Lead;
use Illuminate\Support\Facades\Gate;

class LeadController extends Controller
{
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
}
