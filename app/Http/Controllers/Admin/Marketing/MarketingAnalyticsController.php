<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\LandingLead;
use App\Models\MarketingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MarketingAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('marketing_analytics.view');

        $range = $request->input('range', '30'); // days
        $startDate = now()->subDays((int) $range)->startOfDay();

        // High-level KPI metrics
        $pageViews = MarketingEvent::where('event_name', 'PageView')
            ->where('created_at', '>=', $startDate)
            ->count();

        $landingPageViews = MarketingEvent::where('event_name', 'LandingPageView')
            ->where('created_at', '>=', $startDate)
            ->count();

        $whatsAppClicks = MarketingEvent::where('event_name', 'WhatsAppClick')
            ->where('created_at', '>=', $startDate)
            ->count();

        $quoteFormStarts = MarketingEvent::where('event_name', 'QuoteFormStart')
            ->where('created_at', '>=', $startDate)
            ->count();

        $formSubmissions = MarketingEvent::where('event_name', 'QuoteFormSubmit')
            ->where('created_at', '>=', $startDate)
            ->count();

        $leadsCount = LandingLead::where('created_at', '>=', $startDate)->count();
        $qualifiedLeads = LandingLead::whereIn('status', ['quoted', 'converted'])
            ->where('created_at', '>=', $startDate)
            ->count();
        $convertedOrders = LandingLead::where('status', 'converted')
            ->where('created_at', '>=', $startDate)
            ->count();

        // Conversion Rate
        $totalEngagements = $whatsAppClicks + $leadsCount;
        $conversionRate = $landingPageViews > 0
            ? round(($totalEngagements / $landingPageViews) * 100, 2)
            : 0;

        // Funnel steps data
        $funnel = [
            ['name' => 'Landing Page View', 'count' => max($landingPageViews, $pageViews), 'color' => 'bg-neutral-800'],
            ['name' => 'High Intent (WhatsApp / Form Start)', 'count' => ($whatsAppClicks + $quoteFormStarts), 'color' => 'bg-indigo-600'],
            ['name' => 'Form Submitted', 'count' => max($formSubmissions, $leadsCount), 'color' => 'bg-blue-600'],
            ['name' => 'Lead Captured', 'count' => $leadsCount, 'color' => 'bg-emerald-600'],
            ['name' => 'Qualified / Quoted', 'count' => $qualifiedLeads, 'color' => 'bg-amber-600'],
            ['name' => 'Order Converted', 'count' => $convertedOrders, 'color' => 'bg-red-600'],
        ];

        // Campaign / UTM Breakdown
        $utmBreakdown = LandingLead::query()
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw("COALESCE(utm_source, source, 'direct') as source_name"),
                'utm_medium',
                'utm_campaign',
                DB::raw('COUNT(*) as total_leads'),
                DB::raw("SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_leads")
            )
            ->groupBy('source_name', 'utm_medium', 'utm_campaign')
            ->orderByDesc('total_leads')
            ->get();

        // Daily trend (last 14 days)
        $dailyTrends = DB::table('marketing_events')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN event_name IN ('PageView', 'LandingPageView') THEN 1 ELSE 0 END) as views"),
                DB::raw("SUM(CASE WHEN event_name = 'WhatsAppClick' THEN 1 ELSE 0 END) as whatsapp_clicks"),
                DB::raw("SUM(CASE WHEN event_name = 'Lead' THEN 1 ELSE 0 END) as leads")
            )
            ->where('created_at', '>=', now()->subDays(14)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->get();

        return view('admin.marketing.analytics.index', [
            'range' => $range,
            'pageViews' => $pageViews,
            'landingPageViews' => $landingPageViews,
            'whatsAppClicks' => $whatsAppClicks,
            'quoteFormStarts' => $quoteFormStarts,
            'formSubmissions' => $formSubmissions,
            'leadsCount' => $leadsCount,
            'qualifiedLeads' => $qualifiedLeads,
            'convertedOrders' => $convertedOrders,
            'conversionRate' => $conversionRate,
            'funnel' => $funnel,
            'utmBreakdown' => $utmBreakdown,
            'dailyTrends' => $dailyTrends,
        ]);
    }
}
