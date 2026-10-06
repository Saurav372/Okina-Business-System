<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingEvent;
use App\Models\MarketingTrackingSetting;
use App\Services\MarketingTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketingTrackingController extends Controller
{
    public function __construct(
        private readonly MarketingTrackingService $trackingService,
    ) {}

    public function index(): View
    {
        Gate::authorize('marketing_tracking.view');

        $gtm = $this->trackingService->getGtmConfig();
        $pixel = $this->trackingService->getPixelConfig();
        $capi = $this->trackingService->getCapiConfig();
        $eventMapping = $this->trackingService->getEventMapping();

        // Handle CAPI token masking
        $canUpdateSecret = Gate::allows('tracking_secrets.update');
        $rawToken = MarketingTrackingSetting::getMetaCapiToken();
        $hasToken = ! empty($rawToken);

        if ($hasToken) {
            $maskedToken = MarketingTrackingSetting::maskToken($rawToken);
        } else {
            $maskedToken = '';
        }

        // Recent events for test stream
        $recentEvents = MarketingEvent::query()->latest('created_at')->take(25)->get();

        return view('admin.marketing.tracking.index', [
            'gtm' => $gtm,
            'pixel' => $pixel,
            'capi' => $capi,
            'eventMapping' => $eventMapping,
            'hasToken' => $hasToken,
            'maskedToken' => $maskedToken,
            'canUpdateSecret' => $canUpdateSecret,
            'recentEvents' => $recentEvents,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('marketing_tracking.edit');

        $tab = $request->input('tab', 'gtm');

        if ($tab === 'gtm') {
            $validated = $request->validate([
                'gtm_enabled' => ['nullable', 'boolean'],
                'container_id' => ['nullable', 'string', 'max:50'],
                'scope' => ['required', 'string', 'in:all,landing_pages'],
                'environment' => ['required', 'string', 'in:production,staging'],
            ]);

            MarketingTrackingSetting::setSetting('gtm', [
                'enabled' => $request->boolean('gtm_enabled'),
                'container_id' => trim($validated['container_id'] ?? ''),
                'scope' => $validated['scope'],
                'environment' => $validated['environment'],
            ]);

            return back()->with('status', 'Google Tag Manager settings saved.');
        }

        if ($tab === 'pixel') {
            $validated = $request->validate([
                'pixel_enabled' => ['nullable', 'boolean'],
                'pixel_id' => ['nullable', 'string', 'max:50'],
                'test_event_code' => ['nullable', 'string', 'max:50'],
                'events' => ['nullable', 'array'],
            ]);

            MarketingTrackingSetting::setSetting('meta_pixel', [
                'enabled' => $request->boolean('pixel_enabled'),
                'pixel_id' => trim($validated['pixel_id'] ?? ''),
                'test_event_code' => trim($validated['test_event_code'] ?? ''),
                'events' => [
                    'PageView' => ! empty($validated['events']['PageView']),
                    'ViewContent' => ! empty($validated['events']['ViewContent']),
                    'Lead' => ! empty($validated['events']['Lead']),
                    'Contact' => ! empty($validated['events']['Contact']),
                    'WhatsAppClick' => ! empty($validated['events']['WhatsAppClick']),
                    'QuoteFormSubmit' => ! empty($validated['events']['QuoteFormSubmit']),
                ],
            ]);

            return back()->with('status', 'Meta Pixel settings saved.');
        }

        if ($tab === 'capi') {
            $validated = $request->validate([
                'capi_enabled' => ['nullable', 'boolean'],
                'dataset_id' => ['nullable', 'string', 'max:50'],
                'access_token' => ['nullable', 'string'],
                'test_event_code' => ['nullable', 'string', 'max:50'],
                'api_version' => ['required', 'string', 'max:15'],
                'event_source_url' => ['nullable', 'url', 'max:255'],
            ]);

            $currentCapi = $this->trackingService->getCapiConfig();
            $storedEncryptedToken = $currentCapi['access_token'] ?? null;

            // Only users with tracking_secrets.update can change the access token
            $inputToken = trim($validated['access_token'] ?? '');
            $canUpdateSecret = Gate::allows('tracking_secrets.update');

            if ($canUpdateSecret && ! empty($inputToken) && ! str_contains($inputToken, '••••')) {
                $storedEncryptedToken = MarketingTrackingSetting::encryptToken($inputToken);
            }

            MarketingTrackingSetting::setSetting('meta_capi', [
                'enabled' => $request->boolean('capi_enabled'),
                'dataset_id' => trim($validated['dataset_id'] ?? ''),
                'access_token' => $storedEncryptedToken,
                'test_event_code' => trim($validated['test_event_code'] ?? ''),
                'api_version' => trim($validated['api_version'] ?? 'v19.0'),
                'event_source_url' => trim($validated['event_source_url'] ?? ''),
            ]);

            return back()->with('status', 'Meta Conversions API (CAPI) settings saved.');
        }

        if ($tab === 'mapping') {
            $validated = $request->validate([
                'landing_page_loaded' => ['required', 'string', 'max:50'],
                'whatsapp_clicked' => ['required', 'string', 'max:50'],
                'quote_form_submitted' => ['required', 'string', 'max:50'],
                'quote_created' => ['required', 'string', 'max:50'],
                'order_confirmed' => ['required', 'string', 'max:50'],
            ]);

            MarketingTrackingSetting::setSetting('event_mapping', $validated);

            return back()->with('status', 'Event mapping configuration saved.');
        }

        return back();
    }

    public function sendTestEvent(Request $request): RedirectResponse
    {
        Gate::authorize('marketing_tracking.test');

        $validated = $request->validate([
            'test_event_name' => ['required', 'string', 'max:50'],
            'test_source' => ['required', 'string', 'in:browser,server'],
        ]);

        $eventId = (string) Str::uuid();

        if ($validated['test_source'] === 'server') {
            $this->trackingService->dispatchCapiEvent(
                eventName: $validated['test_event_name'],
                eventId: $eventId,
                userData: [
                    'name' => 'Test User',
                    'phone' => '919876543210',
                ],
                customData: [
                    'test_dispatch' => true,
                    'timestamp' => now()->toIso8601String(),
                ]
            );
        } else {
            MarketingEvent::create([
                'event_id' => $eventId,
                'event_name' => $validated['test_event_name'],
                'source' => 'browser',
                'landing_page_slug' => 'custom-t-shirts',
                'status' => 'test',
                'payload' => [
                    'action' => 'Simulated test event from dashboard',
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return back()->with('status', "Test event '{$validated['test_event_name']}' recorded with ID: {$eventId}");
    }
}
