<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\LandingLead;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): View
    {
        $companyName = (string) $this->settings->get('business', 'company_name', config('branding.name', 'Okina Craft'));
        $supportPhone = $this->settings->get('business', 'support_phone') ?: config('branding.contact.phone');
        $rawWhatsapp = config('branding.contact.whatsapp') ?: env('OKINA_WHATSAPP_NUMBER') ?: $supportPhone ?: '919876543210';
        $whatsappNumber = $this->cleanPhoneNumber($rawWhatsapp);

        $defaultMessage = "Hi Okina Craft, I would like to get a quote for custom apparel for my business/team.";
        $primaryWhatsAppUrl = "https://wa.me/{$whatsappNumber}?text=" . rawurlencode($defaultMessage);

        $source = (string) $request->query('source', 'meta');
        $utm = [
            'utm_source' => $request->query('utm_source', $source),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_content' => $request->query('utm_content'),
        ];

        return view('storefront.landing-cro', [
            'companyName' => $companyName,
            'whatsappNumber' => $whatsappNumber,
            'primaryWhatsAppUrl' => $primaryWhatsAppUrl,
            'supportPhone' => $supportPhone,
            'source' => $source,
            'utm' => $utm,
        ]);
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
                'whatsapp_url' => $whatsappUrl,
            ]);
        }

        return back()
            ->with('quote_success', true)
            ->with('whatsapp_url', $whatsappUrl)
            ->with('lead_name', $lead->name);
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
