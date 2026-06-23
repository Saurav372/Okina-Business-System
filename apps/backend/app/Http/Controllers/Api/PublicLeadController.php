<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StorePublicLeadRequest;
use App\Models\Lead;

class PublicLeadController extends Controller
{
    /**
     * Store a newly created public lead in storage.
     */
    public function store(StorePublicLeadRequest $request)
    {
        $data = $request->validated();

        $email = isset($data['email']) ? trim(strtolower($data['email'])) : null;
        $phone = isset($data['phone']) ? trim($data['phone']) : null;
        $productInterest = isset($data['product_interest']) ? $data['product_interest'] : null;

        // Spam/Duplicate prevention checking within 5-minute window
        $duplicatesQuery = Lead::query()
            ->where('created_at', '>=', now()->subMinutes(5));

        if ($email && $phone) {
            $duplicatesQuery->where(function ($q) use ($email, $phone) {
                $q->where(function ($q2) use ($email, $phone) {
                    $q2->where('email', $email)->where('phone', $phone);
                })
                    ->orWhere(function ($q2) use ($email) {
                        $q2->where('email', $email)->whereNull('phone');
                    })
                    ->orWhere(function ($q2) use ($phone) {
                        $q2->whereNull('email')->where('phone', $phone);
                    });
            });
        } elseif ($email) {
            $duplicatesQuery->where('email', $email)->whereNull('phone');
        } elseif ($phone) {
            $duplicatesQuery->whereNull('email')->where('phone', $phone);
        }

        $duplicates = $duplicatesQuery->get();

        foreach ($duplicates as $duplicate) {
            if ($this->areProductInterestsEqual($duplicate->product_interest, $productInterest)) {
                return response()->json([
                    'message' => 'A duplicate enquiry was recently submitted. Please wait 5 minutes before submitting again.',
                    'errors' => [
                        'duplicate' => ['A duplicate enquiry was recently submitted. Please wait 5 minutes before submitting again.'],
                    ],
                ], 422);
            }
        }

        // Force source and status to website_bulk_enquiry and new
        $data['source'] = 'website_bulk_enquiry';
        $data['status'] = 'new';

        // Public submission: no creator user
        $data['created_by_user_id'] = null;

        $lead = Lead::create($data);

        return response()->json([
            'public_id' => $lead->public_id,
            'source' => $lead->source,
            'status' => $lead->status,
            'priority' => $lead->priority,
            'contact_name' => $lead->contact_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'interest_summary' => $lead->interest_summary,
            'created_at' => $lead->created_at,
            'updated_at' => $lead->updated_at,
        ], 201);
    }

    /**
     * Helper to compare product interest arrays robustly.
     */
    protected function areProductInterestsEqual($interest1, $interest2): bool
    {
        $interest1 = is_array($interest1) ? $interest1 : [];
        $interest2 = is_array($interest2) ? $interest2 : [];

        $interest1 = array_map(fn ($item) => trim(strtolower($item)), $interest1);
        $interest2 = array_map(fn ($item) => trim(strtolower($item)), $interest2);

        sort($interest1);
        sort($interest2);

        return $interest1 === $interest2;
    }
}
