<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\LandingLead;
use App\Services\MarketingTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LandingLeadAdminController extends Controller
{
    public function __construct(
        private readonly MarketingTrackingService $trackingService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('landing_leads.view');

        $query = LandingLead::query()->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('product_type', 'like', "%{$search}%")
                    ->orWhere('business_type', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($source = $request->input('source')) {
            $query->where('utm_source', $source)->orWhere('source', $source);
        }

        $leads = $query->paginate(20)->withQueryString();

        $counts = [
            'total' => LandingLead::count(),
            'new' => LandingLead::where('status', 'new')->count(),
            'contacted' => LandingLead::where('status', 'contacted')->count(),
            'quoted' => LandingLead::where('status', 'quoted')->count(),
            'converted' => LandingLead::where('status', 'converted')->count(),
        ];

        return view('admin.marketing.leads.index', [
            'leads' => $leads,
            'counts' => $counts,
            'statusFilter' => $request->input('status'),
            'search' => $request->input('search'),
        ]);
    }

    public function show(LandingLead $lead): View
    {
        Gate::authorize('landing_leads.view');

        return view('admin.marketing.leads.show', [
            'lead' => $lead,
        ]);
    }

    public function updateStatus(Request $request, LandingLead $lead): RedirectResponse
    {
        Gate::authorize('landing_leads.update_status');

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:new,contacted,quoted,converted'],
        ]);

        $oldStatus = $lead->status;
        $newStatus = $validated['status'];

        $lead->update([
            'status' => $newStatus,
        ]);

        // If status changed to converted or quoted, trigger server-side CAPI event if configured
        if ($newStatus === 'converted' && $oldStatus !== 'converted') {
            $this->trackingService->dispatchCapiEvent(
                eventName: 'Purchase',
                eventId: (string) Str::uuid(),
                userData: [
                    'name' => $lead->name,
                    'phone' => $lead->phone,
                ],
                customData: [
                    'lead_id' => $lead->id,
                    'product_type' => $lead->product_type,
                    'quantity_range' => $lead->quantity_range,
                    'status' => 'converted',
                ]
            );
        } elseif ($newStatus === 'quoted' && $oldStatus !== 'quoted') {
            $this->trackingService->dispatchCapiEvent(
                eventName: 'QualifiedLead',
                eventId: (string) Str::uuid(),
                userData: [
                    'name' => $lead->name,
                    'phone' => $lead->phone,
                ],
                customData: [
                    'lead_id' => $lead->id,
                    'product_type' => $lead->product_type,
                    'quantity_range' => $lead->quantity_range,
                ]
            );
        }

        return back()->with('status', "Lead #{$lead->id} marked as " . ucfirst($newStatus) . ".");
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('landing_leads.export');

        $leads = LandingLead::query()->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="landing_leads_' . date('Y-m-d_His') . '.csv"',
        ];

        $callback = function () use ($leads) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'Name',
                'Phone',
                'Email',
                'Business Type',
                'Product Type',
                'Quantity Range',
                'Design Readiness',
                'Delivery Date',
                'City',
                'Status',
                'Source',
                'UTM Source',
                'UTM Medium',
                'UTM Campaign',
                'UTM Content',
                'Notes',
                'Artwork Path',
                'Created At',
            ]);

            foreach ($leads as $lead) {
                fputcsv($handle, [
                    $lead->id,
                    $lead->name,
                    $lead->phone,
                    $lead->email,
                    $lead->business_type,
                    $lead->product_type,
                    $lead->quantity_range,
                    $lead->design_readiness,
                    $lead->delivery_date,
                    $lead->city,
                    $lead->status,
                    $lead->source,
                    $lead->utm_source,
                    $lead->utm_medium,
                    $lead->utm_campaign,
                    $lead->utm_content,
                    $lead->notes,
                    $lead->artwork_path ? url('storage/' . $lead->artwork_path) : '',
                    $lead->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
