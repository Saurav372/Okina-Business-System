<?php

namespace App\Services;

use App\Models\MarketingEvent;
use App\Models\MarketingTrackingSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarketingTrackingService
{
    /**
     * Get GTM configuration.
     */
    public function getGtmConfig(): array
    {
        return MarketingTrackingSetting::getSetting('gtm', [
            'enabled' => false,
            'container_id' => '',
            'scope' => 'all', // 'all' or 'landing_pages'
            'environment' => 'production',
        ]);
    }

    /**
     * Get Meta Pixel configuration.
     */
    public function getPixelConfig(): array
    {
        return MarketingTrackingSetting::getSetting('meta_pixel', [
            'enabled' => true,
            'pixel_id' => '1983890512310174',
            'test_event_code' => '',
            'events' => [
                'PageView' => true,
                'ViewContent' => true,
                'Lead' => true,
                'Contact' => true,
                'WhatsAppClick' => true,
                'QuoteFormSubmit' => true,
            ],
        ]);
    }

    /**
     * Get Meta Conversions API (CAPI) configuration.
     */
    public function getCapiConfig(): array
    {
        $config = MarketingTrackingSetting::getSetting('meta_capi', [
            'enabled' => false,
            'dataset_id' => '',
            'access_token' => '',
            'test_event_code' => '',
            'api_version' => 'v19.0',
            'event_source_url' => '',
        ]);

        return $config;
    }

    /**
     * Get Event Mapping configuration.
     */
    public function getEventMapping(): array
    {
        return MarketingTrackingSetting::getSetting('event_mapping', [
            'landing_page_loaded' => 'PageView',
            'whatsapp_clicked' => 'Contact',
            'quote_form_submitted' => 'Lead',
            'quote_created' => 'QualifiedLead',
            'order_confirmed' => 'Purchase',
        ]);
    }

    /**
     * Render GTM & Meta Pixel head snippet HTML.
     */
    public function renderHeadTags(bool $isLandingPage = true): string
    {
        $html = '';

        $gtm = $this->getGtmConfig();
        if (! empty($gtm['enabled']) && ! empty($gtm['container_id'])) {
            if ($gtm['scope'] === 'all' || ($gtm['scope'] === 'landing_pages' && $isLandingPage)) {
                $containerId = e($gtm['container_id']);
                $html .= "<!-- Google Tag Manager -->\n";
                $html .= "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\n";
                $html .= "new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],\n";
                $html .= "j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n";
                $html .= "'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n";
                $html .= "})(window,document,'script','dataLayer','{$containerId}');</script>\n";
                $html .= "<!-- End Google Tag Manager -->\n";
            }
        }

        $pixel = $this->getPixelConfig();
        if (! empty($pixel['enabled']) && ! empty($pixel['pixel_id'])) {
            $pixelId = e($pixel['pixel_id']);
            $html .= "<!-- Meta Pixel Code -->\n";
            $html .= "<script>!function(f,b,e,v,n,t,s)\n";
            $html .= "{if(f.fbq)return;n=f.fbq=function(){n.callMethod?\n";
            $html .= "n.callMethod.apply(n,arguments):n.queue.push(arguments)};\n";
            $html .= "if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';\n";
            $html .= "n.queue=[];t=b.createElement(e);t.async=!0;\n";
            $html .= "t.src=v;s=b.getElementsByTagName(e)[0];\n";
            $html .= "s.parentNode.insertBefore(t,s)}(window, document,'script',\n";
            $html .= "'https://connect.facebook.net/en_US/fbevents.js');\n";
            $html .= "fbq('init', '{$pixelId}');\n";
            $html .= "fbq('track', 'PageView');\n";
            $html .= "</script>\n";
            $html .= "<noscript><img height=\"1\" width=\"1\" style=\"display:none\" src=\"https://www.facebook.com/tr?id={$pixelId}&ev=PageView&noscript=1\" /></noscript>\n";
            $html .= "<!-- End Meta Pixel Code -->\n";
        }

        return $html;
    }

    /**
     * Render GTM body noscript snippet HTML.
     */
    public function renderBodyTags(bool $isLandingPage = true): string
    {
        $gtm = $this->getGtmConfig();
        if (! empty($gtm['enabled']) && ! empty($gtm['container_id'])) {
            if ($gtm['scope'] === 'all' || ($gtm['scope'] === 'landing_pages' && $isLandingPage)) {
                $containerId = e($gtm['container_id']);
                return "<!-- Google Tag Manager (noscript) -->\n<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id={$containerId}\" height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>\n<!-- End Google Tag Manager (noscript) -->\n";
            }
        }

        return '';
    }

    /**
     * Dispatch server-side Meta Conversions API (CAPI) event.
     * Uses shared event_id for Meta deduplication with client-side pixel event.
     */
    public function dispatchCapiEvent(
        string $eventName,
        string $eventId,
        array $userData = [],
        array $customData = [],
        ?string $eventSourceUrl = null,
        ?string $landingPageSlug = 'custom-t-shirts'
    ): bool {
        $capi = $this->getCapiConfig();
        $token = MarketingTrackingSetting::getMetaCapiToken();

        if (empty($capi['enabled']) || empty($capi['dataset_id']) || empty($token)) {
            // CAPI disabled or missing credentials
            return false;
        }

        $apiVersion = $capi['api_version'] ?? 'v19.0';
        $datasetId = $capi['dataset_id'];
        $url = "https://graph.facebook.com/{$apiVersion}/{$datasetId}/events";

        $eventPayload = [
            'event_name' => $eventName,
            'event_time' => time(),
            'event_id' => $eventId,
            'event_source_url' => $eventSourceUrl ?: ($capi['event_source_url'] ?? url('/lp/custom-t-shirts')),
            'action_source' => 'website',
            'user_data' => array_filter([
                'client_ip_address' => request()->ip(),
                'client_user_agent' => request()->userAgent(),
                'ph' => ! empty($userData['phone']) ? hash('sha256', preg_replace('/[^0-9]/', '', $userData['phone'])) : null,
                'em' => ! empty($userData['email']) ? hash('sha256', strtolower(trim($userData['email']))) : null,
                'fn' => ! empty($userData['name']) ? hash('sha256', strtolower(trim(explode(' ', $userData['name'])[0]))) : null,
            ]),
            'custom_data' => $customData,
        ];

        $requestBody = [
            'data' => [$eventPayload],
            'access_token' => $token,
        ];

        if (! empty($capi['test_event_code'])) {
            $requestBody['test_event_code'] = $capi['test_event_code'];
        }

        try {
            $response = Http::timeout(5)->post($url, $requestBody);

            $status = $response->successful() ? 'forwarded_capi' : 'failed_capi';

            MarketingEvent::create([
                'event_id' => $eventId,
                'event_name' => $eventName,
                'source' => 'server',
                'landing_page_slug' => $landingPageSlug,
                'status' => $status,
                'payload' => [
                    'capi_request' => $eventPayload,
                    'capi_response' => $response->json(),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Meta CAPI dispatch error: ' . $e->getMessage());

            MarketingEvent::create([
                'event_id' => $eventId,
                'event_name' => $eventName,
                'source' => 'server',
                'landing_page_slug' => $landingPageSlug,
                'status' => 'failed_capi',
                'payload' => ['error' => $e->getMessage()],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return false;
        }
    }

    /**
     * Record internal browser event beacon.
     */
    public function recordBrowserEvent(
        string $eventName,
        ?string $eventId = null,
        array $payload = [],
        ?string $slug = null,
        array $utm = []
    ): MarketingEvent {
        $eventId = $eventId ?: (string) Str::uuid();

        return MarketingEvent::create([
            'event_id' => $eventId,
            'event_name' => $eventName,
            'source' => 'browser',
            'landing_page_slug' => $slug,
            'utm_source' => $utm['utm_source'] ?? null,
            'utm_medium' => $utm['utm_medium'] ?? null,
            'utm_campaign' => $utm['utm_campaign'] ?? null,
            'utm_content' => $utm['utm_content'] ?? null,
            'status' => 'received',
            'payload' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
