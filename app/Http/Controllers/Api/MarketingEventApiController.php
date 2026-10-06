<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MarketingTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MarketingEventApiController extends Controller
{
    public function __construct(
        private readonly MarketingTrackingService $trackingService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_name' => ['required', 'string', 'max:60'],
            'event_id' => ['nullable', 'string', 'max:64'],
            'landing_page_slug' => ['nullable', 'string', 'max:100'],
            'utm_source' => ['nullable', 'string', 'max:100'],
            'utm_medium' => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],
            'utm_content' => ['nullable', 'string', 'max:100'],
            'payload' => ['nullable', 'array'],
        ]);

        $eventId = ! empty($validated['event_id']) ? $validated['event_id'] : (string) Str::uuid();

        $event = $this->trackingService->recordBrowserEvent(
            eventName: $validated['event_name'],
            eventId: $eventId,
            payload: $validated['payload'] ?? [],
            slug: $validated['landing_page_slug'] ?? 'custom-t-shirts',
            utm: [
                'utm_source' => $validated['utm_source'] ?? null,
                'utm_medium' => $validated['utm_medium'] ?? null,
                'utm_campaign' => $validated['utm_campaign'] ?? null,
                'utm_content' => $validated['utm_content'] ?? null,
            ]
        );

        // Map internal action to CAPI if appropriate
        $mapping = $this->trackingService->getEventMapping();
        if ($validated['event_name'] === 'WhatsAppClick') {
            $capiEvent = $mapping['whatsapp_clicked'] ?? 'Contact';
            $this->trackingService->dispatchCapiEvent(
                eventName: $capiEvent,
                eventId: $eventId,
                userData: [],
                customData: ['cta' => 'whatsapp_button'],
                landingPageSlug: $validated['landing_page_slug'] ?? 'custom-t-shirts'
            );
        }

        return response()->json([
            'success' => true,
            'event_id' => $eventId,
            'recorded_at' => $event->created_at->toIso8601String(),
        ]);
    }
}
