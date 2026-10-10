<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\LandingLead;
use App\Models\LandingPage;
use App\Services\MarketingTrackingService;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly MarketingTrackingService $trackingService,
    ) {}

    public function index(Request $request): Response
    {
        $page = LandingPage::firstOrCreate(
            ['slug' => 'custom-t-shirts'],
            LandingPage::defaultContentFor('custom-t-shirts')
        );

        $companyName = (string) $this->settings->get('business', 'company_name', config('branding.name', 'Okina Craft'));
        $supportPhone = $this->settings->get('business', 'support_phone') ?: config('branding.contact.phone');

        // Check custom CTA settings from LandingPage model
        $ctaSettings = $page->cta_settings ?? [];
        $rawWhatsapp = $ctaSettings['whatsapp_phone'] ?? config('branding.contact.whatsapp') ?: env('OKINA_WHATSAPP_NUMBER') ?: $supportPhone ?: '919876543210';
        $whatsappNumber = $this->cleanPhoneNumber($rawWhatsapp);

        $defaultMessage = $ctaSettings['whatsapp_message'] ?? "Hi Okina Craft, I would like to get a quote for custom apparel for my business/team.";
        $primaryWhatsAppUrl = "https://wa.me/{$whatsappNumber}?text=" . rawurlencode($defaultMessage);

        $source = (string) $request->query('source', 'meta');
        $utm = [
            'utm_source' => $request->query('utm_source', $source),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_content' => $request->query('utm_content'),
        ];

        // Record LandingPageView internally for source of truth
        $this->trackingService->recordBrowserEvent(
            eventName: 'LandingPageView',
            eventId: (string) Str::uuid(),
            payload: ['url' => $request->fullUrl()],
            slug: $page->slug,
            utm: $utm
        );

        $trackingHead = $this->trackingService->renderHeadTags(isLandingPage: true);
        $trackingBody = $this->trackingService->renderBodyTags(isLandingPage: true);

        return response()
            ->view('storefront.landing-cro', [
                'companyName' => $companyName,
                'whatsappNumber' => $whatsappNumber,
                'primaryWhatsAppUrl' => $primaryWhatsAppUrl,
                'supportPhone' => $supportPhone,
                'source' => $source,
                'utm' => $utm,
                'page' => $page,
                'trackingHead' => $trackingHead,
                'trackingBody' => $trackingBody,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
    }

    public function storeQuote(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:25'],
            'product_type' => ['nullable', 'string', 'max:100'],
            'quantity_range' => ['nullable', 'string', 'max:50'],
            'delivery_date' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'artwork' => ['nullable', 'file', 'max:10240'],
            'source' => ['nullable', 'string', 'max:50'],
            'utm_source' => ['nullable', 'string', 'max:100'],
            'utm_medium' => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],
            'utm_content' => ['nullable', 'string', 'max:100'],
            'event_id' => ['nullable', 'string', 'max:64'],
        ]);

        $artworkPath = null;
        if ($request->hasFile('artwork')) {
            $artworkPath = $request->file('artwork')->store('leads/artwork', 'public');
        }

        $lead = LandingLead::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'product_type' => $validated['product_type'] ?? null,
            'quantity_range' => $validated['quantity_range'] ?? null,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'artwork_path' => $artworkPath,
            'city' => $validated['city'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'source' => $validated['source'] ?? 'meta',
            'utm_source' => $validated['utm_source'] ?? null,
            'utm_medium' => $validated['utm_medium'] ?? null,
            'utm_campaign' => $validated['utm_campaign'] ?? null,
            'utm_content' => $validated['utm_content'] ?? null,
            'status' => 'new',
        ]);

        // Shared event_id for Meta deduplication between browser pixel and server CAPI
        $eventId = ! empty($validated['event_id']) ? $validated['event_id'] : (string) Str::uuid();

        // 1. Record QuoteFormSubmit event in internal DB
        $this->trackingService->recordBrowserEvent(
            eventName: 'QuoteFormSubmit',
            eventId: $eventId,
            payload: ['lead_id' => $lead->id, 'product_type' => $lead->product_type],
            slug: 'custom-t-shirts',
            utm: [
                'utm_source' => $validated['utm_source'] ?? null,
                'utm_medium' => $validated['utm_medium'] ?? null,
                'utm_campaign' => $validated['utm_campaign'] ?? null,
                'utm_content' => $validated['utm_content'] ?? null,
            ]
        );

        // 2. Dispatch Server-Side Meta CAPI Lead Event
        $this->trackingService->dispatchCapiEvent(
            eventName: 'Lead',
            eventId: $eventId,
            userData: [
                'name' => $lead->name,
                'phone' => $lead->phone,
            ],
            customData: [
                'lead_id' => $lead->id,
                'product_type' => $lead->product_type,
                'quantity_range' => $lead->quantity_range,
                'delivery_date' => $lead->delivery_date,
            ],
            landingPageSlug: 'custom-t-shirts'
        );

        $supportPhone = $this->settings->get('business', 'support_phone') ?: config('branding.contact.phone');
        $rawWhatsapp = config('branding.contact.whatsapp') ?: env('OKINA_WHATSAPP_NUMBER') ?: $supportPhone ?: '919876543210';
        $whatsappNumber = $this->cleanPhoneNumber($rawWhatsapp);

        $waText = "Hi Okina Craft! My name is {$lead->name}. I submitted a quote request on your website:\n";
        if ($lead->product_type) {
            $waText .= "• Requirement: {$lead->product_type}\n";
        }
        if ($lead->quantity_range) {
            $waText .= "• Quantity: {$lead->quantity_range}\n";
        }
        if ($lead->delivery_date) {
            $waText .= "• Target Date: {$lead->delivery_date}\n";
        }
        $waText .= "Please share options and pricing.";

        $whatsappUrl = "https://wa.me/{$whatsappNumber}?text=" . rawurlencode($waText);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your quote request has been received.',
                'lead_id' => $lead->id,
                'event_id' => $eventId,
                'whatsapp_url' => $whatsappUrl,
            ]);
        }

        return back()
            ->with('quote_success', true)
            ->with('whatsapp_url', $whatsappUrl)
            ->with('lead_name', $lead->name)
            ->with('event_id', $eventId);
    }

    public function bulkPrinting(Request $request): Response
    {
        $companyName = (string) $this->settings->get('business', 'company_name', config('branding.name', 'Okina Craft'));
        $pixelConfig = $this->trackingService->getPixelConfig();
        $pixelEnabled = $pixelConfig['enabled'] ?? true;
        $metaPixelId = $pixelEnabled ? (! empty($pixelConfig['pixel_id']) ? $pixelConfig['pixel_id'] : '1983890512310174') : '';

        $source = (string) $request->query('source', 'meta');
        $utm = [
            'utm_source' => $request->query('utm_source', $source),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_content' => $request->query('utm_content'),
        ];

        $this->trackingService->recordBrowserEvent(
            eventName: 'LandingPageView',
            eventId: (string) Str::uuid(),
            payload: ['url' => $request->fullUrl()],
            slug: 'bulk-printing',
            utm: $utm
        );

        return response()
            ->view('storefront.landing-bulk-printing', [
                'companyName' => $companyName,
                'metaPixelId' => $metaPixelId,
                'source' => $source,
                'utm' => $utm,
                'logoUrl' => asset('brand/okina-mark-light.png'),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
    }

    public function bulkPrintingThankYou(Request $request): Response
    {
        $companyName = (string) $this->settings->get('business', 'company_name', config('branding.name', 'Okina Craft'));
        $pixelConfig = $this->trackingService->getPixelConfig();
        $pixelEnabled = $pixelConfig['enabled'] ?? true;
        $metaPixelId = $pixelEnabled ? (! empty($pixelConfig['pixel_id']) ? $pixelConfig['pixel_id'] : '1983890512310174') : '';

        $supportPhone = $this->settings->get('business', 'support_phone') ?: config('branding.contact.phone');
        $rawWhatsapp = config('branding.contact.whatsapp') ?: env('OKINA_WHATSAPP_NUMBER') ?: $supportPhone ?: '919876543210';
        $whatsappNumber = $this->cleanPhoneNumber($rawWhatsapp);
        $whatsappUrl = "https://wa.me/{$whatsappNumber}?text=" . rawurlencode("Hi Okina Craft, I just submitted an inquiry on your Bulk Printing page and would like to speak with a specialist.");

        return response()
            ->view('storefront.landing-bulk-printing-thank-you', [
                'companyName' => $companyName,
                'metaPixelId' => $metaPixelId,
                'whatsappUrl' => $whatsappUrl,
                'logoUrl' => asset('brand/okina-mark-light.png'),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
    }

    public function storeBulkPrintingLead(Request $request): JsonResponse
    {
        // Anti-bot Honeypot check
        if (! empty($request->input('website'))) {
            return response()->json(['success' => true]);
        }

        $validated = $request->validate([
            'full_name' => ['nullable', 'string', 'min:2', 'max:120'],
            'name' => ['nullable', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['required', 'email', 'max:120'],
            'printing_requirement' => ['nullable', 'string', 'max:100'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'string', 'max:100'],
            'design_readiness' => ['nullable', 'string', 'max:100'],
            'required_timeline' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:100'],
            'page_url' => ['nullable', 'string', 'max:500'],
            'utm_source' => ['nullable', 'string', 'max:100'],
            'utm_medium' => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],
            'utm_content' => ['nullable', 'string', 'max:100'],
            'event_id' => ['nullable', 'string', 'max:64'],
        ]);

        $name = trim($validated['full_name'] ?? $validated['name'] ?? 'Enquiry Customer');
        $phone = $validated['phone'];
        $email = trim($validated['email']);

        $lead = LandingLead::create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'product_type' => $validated['printing_requirement'] ?? null,
            'business_type' => $validated['business_type'] ?? null,
            'quantity_range' => $validated['quantity'] ?? null,
            'design_readiness' => $validated['design_readiness'] ?? null,
            'delivery_date' => $validated['required_timeline'] ?? null,
            'source' => $validated['source'] ?? 'okina-craft-landing-page',
            'page_url' => $validated['page_url'] ?? $request->fullUrl(),
            'utm_source' => $validated['utm_source'] ?? $request->query('utm_source'),
            'utm_medium' => $validated['utm_medium'] ?? $request->query('utm_medium'),
            'utm_campaign' => $validated['utm_campaign'] ?? $request->query('utm_campaign'),
            'utm_content' => $validated['utm_content'] ?? $request->query('utm_content'),
            'notes' => 'Business: ' . ($validated['business_type'] ?? 'N/A') . ' | Readiness: ' . ($validated['design_readiness'] ?? 'N/A'),
            'status' => 'new',
        ]);

        $eventId = ! empty($validated['event_id']) ? $validated['event_id'] : (string) Str::uuid();

        $this->trackingService->recordBrowserEvent(
            eventName: 'BulkPrintingLeadSubmit',
            eventId: $eventId,
            payload: [
                'lead_id' => $lead->id,
                'requirement' => $lead->product_type,
                'business_type' => $lead->business_type,
                'quantity' => $lead->quantity_range,
            ],
            slug: 'bulk-printing',
            utm: [
                'utm_source' => $lead->utm_source,
                'utm_medium' => $lead->utm_medium,
                'utm_campaign' => $lead->utm_campaign,
                'utm_content' => $lead->utm_content,
            ]
        );

        $this->trackingService->dispatchCapiEvent(
            eventName: 'Lead',
            eventId: $eventId,
            userData: [
                'name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
            ],
            customData: [
                'lead_id' => $lead->id,
                'product_type' => $lead->product_type,
                'business_type' => $lead->business_type,
                'quantity_range' => $lead->quantity_range,
            ],
            landingPageSlug: 'bulk-printing'
        );

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Your bulk printing enquiry has been submitted successfully.',
            'lead_id' => $lead->id,
            'event_id' => $eventId,
        ]);
    }

    private function cleanPhoneNumber(string $phone): string
    {
        $clean = (string) preg_replace('/[^0-9]/', '', $phone);

        if (strlen($clean) === 10) {
            return '91' . $clean;
        }

        return $clean ?: '919876543210';
    }
}
