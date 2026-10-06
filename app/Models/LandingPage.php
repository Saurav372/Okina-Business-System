<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'status',
        'hero',
        'showcase',
        'use_cases',
        'techniques',
        'tagline',
        'benefits',
        'steps',
        'gallery',
        'faq',
        'cta_settings',
        'section_order',
        'published_at',
    ];

    protected $casts = [
        'hero' => 'array',
        'showcase' => 'array',
        'use_cases' => 'array',
        'techniques' => 'array',
        'tagline' => 'array',
        'benefits' => 'array',
        'steps' => 'array',
        'gallery' => 'array',
        'faq' => 'array',
        'cta_settings' => 'array',
        'section_order' => 'array',
        'published_at' => 'datetime',
    ];

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'status' => 'draft',
        ]);
    }

    /**
     * Default grounded seed structure for custom-t-shirts landing page.
     */
    public static function defaultContentFor(string $slug): array
    {
        if ($slug === 'custom-t-shirts') {
            return [
                'slug' => 'custom-t-shirts',
                'title' => 'Custom T-Shirts & Branded Apparel for Businesses',
                'status' => 'published',
                'hero' => [
                    'eyebrow' => 'B2B Custom Apparel & Merchandise',
                    'title' => 'Custom T-Shirts & Branded Apparel for Businesses',
                    'lead' => 'Company uniforms, event T-shirts, team apparel and custom merchandise with printing or embroidery. Bulk-order support with Pan-India delivery.',
                    'trust_chips' => [
                        'Pan-India Delivery',
                        'Low MOQ Options',
                        'Multiple Techniques',
                        'Artwork Confirmation',
                    ],
                    'note' => 'Direct requirement consultation with our apparel team. No online checkout required.',
                    'image' => '/storefront/custom/hero-apparel.jpg',
                    'floating_tag' => 'Custom Production: T-Shirts • Polos • Uniforms',
                ],
                'cta_settings' => [
                    'primary_text' => 'Get Quote on WhatsApp',
                    'secondary_text' => 'Request a Callback / Quote',
                    'whatsapp_message' => "Hi Okina Craft, I would like to get a quote for custom apparel / T-shirts for our business.",
                    'sticky_whatsapp_text' => 'Get Quote on WhatsApp',
                    'sticky_callback_text' => 'Get Callback',
                ],
                'showcase' => [
                    [
                        'tag' => 'Embroidery',
                        'image' => '/storefront/custom/polo-embroidery.jpg',
                        'title' => 'Corporate Polo Branding',
                        'desc' => 'Embroidery & logo placement',
                    ],
                    [
                        'tag' => 'Staff Uniforms',
                        'image' => '/storefront/custom/staff-uniforms.jpg',
                        'title' => 'Staff Uniform Sets',
                        'desc' => 'Coordinated team wear',
                    ],
                    [
                        'tag' => 'Event Print',
                        'image' => '/storefront/custom/event-bulk.jpg',
                        'title' => 'Event & Campaign Tees',
                        'desc' => 'Vibrant promotional prints',
                    ],
                    [
                        'tag' => 'Back Branding',
                        'image' => '/storefront/custom/tee-backprint.jpg',
                        'title' => 'Custom Branded Tees',
                        'desc' => 'Front & back logo printing',
                    ],
                ],
                'use_cases' => [
                    [
                        'image' => '/storefront/business/uniform.png',
                        'title' => 'Company & Staff Uniforms',
                        'desc' => 'Coordinated branded apparel for offices, retail staff, cafes, restaurants, and field teams.',
                    ],
                    [
                        'image' => '/storefront/business/event.png',
                        'title' => 'Corporate & Promotional Events',
                        'desc' => 'Bulk event T-shirts for conferences, brand launches, trade fairs, and promotional campaigns.',
                    ],
                    [
                        'image' => '/storefront/business/team.png',
                        'title' => 'Team & College Apparel',
                        'desc' => 'Branded apparel for sports teams, college fests, coaching institutes, and clubs.',
                    ],
                    [
                        'image' => '/storefront/business/cafe.png',
                        'title' => 'Branded Merchandise',
                        'desc' => 'Custom apparel and merchandise (tees, caps, diaries) for businesses and brand promotions.',
                    ],
                ],
                'techniques' => [
                    [
                        'name' => 'DTF Printing',
                        'badge' => 'Full Color',
                        'image' => '/storefront/custom/tee-backprint.jpg',
                        'desc' => 'Custom printing option based on artwork and garment requirements.',
                    ],
                    [
                        'name' => 'DTG Printing',
                        'badge' => 'Soft Hand',
                        'image' => '/storefront/business/tee.png',
                        'desc' => 'Available depending on product and artwork requirements.',
                    ],
                    [
                        'name' => 'Embroidery',
                        'badge' => 'Stitched',
                        'image' => '/storefront/custom/polo-embroidery.jpg',
                        'desc' => 'Stitched branding option for suitable apparel and merchandise.',
                    ],
                    [
                        'name' => 'Puff Printing',
                        'badge' => '3D Raised',
                        'image' => '/storefront/custom/puff-detail.jpg',
                        'desc' => 'Raised-print customization option for heavyweight cotton.',
                    ],
                    [
                        'name' => 'Sublimation',
                        'badge' => 'All-Over',
                        'image' => '/storefront/custom/event-bulk.jpg',
                        'desc' => 'Available for suitable polyester fabrics and order requirements.',
                    ],
                    [
                        'name' => 'Custom Decoration',
                        'badge' => 'Tailored',
                        'image' => '/storefront/custom/hero-apparel.jpg',
                        'desc' => 'Evaluated based on your specific artwork, fabric, and placement needs.',
                    ],
                ],
                'tagline' => [
                    'text' => 'Every stitch and print crafted to represent your team with uncompromising precision.',
                    'sub' => 'From single team batches to nationwide enterprise rollouts.',
                ],
                'benefits' => [
                    ['title' => 'B2B Customization', 'desc' => 'Made for teams, organizations and events.'],
                    ['title' => 'Low MOQ Options', 'desc' => 'Where supported by product and method.'],
                    ['title' => 'Multiple Techniques', 'desc' => 'DTF, DTG, embroidery, puff and sublimation.'],
                    ['title' => 'Pan-India Delivery', 'desc' => 'Orders delivered safely across India.'],
                    ['title' => 'Pre-production Confirmation', 'desc' => 'Artwork and placement approved before printing.'],
                    ['title' => 'Direct WhatsApp Support', 'desc' => 'Fast consultation for quotes and updates.'],
                ],
                'steps' => [
                    ['num' => '01', 'title' => 'Share Requirement', 'desc' => 'Tell us your apparel, quantity and artwork.'],
                    ['num' => '02', 'title' => 'Confirm Details', 'desc' => 'Artwork, placement and printing method are confirmed.'],
                    ['num' => '03', 'title' => 'Approve Quote', 'desc' => 'Review pricing and confirm order terms.'],
                    ['num' => '04', 'title' => 'Production & QC', 'desc' => 'Printing or embroidery with quality check.'],
                    ['num' => '05', 'title' => 'Pan-India Delivery', 'desc' => 'Packed securely and delivered to your doorstep.'],
                ],
                'gallery' => [
                    [
                        'image' => '/storefront/custom/polo-embroidery.jpg',
                        'tag' => 'Polos / Embroidery',
                        'title' => '100 Embroidered Corporate Polos',
                        'desc' => 'Clean chest branding for business staff.',
                    ],
                    [
                        'image' => '/storefront/custom/event-bulk.jpg',
                        'tag' => 'Event / DTF Print',
                        'title' => '250 Event T-Shirts',
                        'desc' => 'Color-rich promotional tees for annual conference.',
                    ],
                    [
                        'image' => '/storefront/custom/staff-uniforms.jpg',
                        'tag' => 'Uniforms / Front & Back',
                        'title' => 'Coordinated Staff Uniforms',
                        'desc' => 'Durable daily wear for retail & hospitality.',
                    ],
                    [
                        'image' => '/storefront/custom/puff-detail.jpg',
                        'tag' => 'Streetwear / Puff Print',
                        'title' => '3D Puff Print Batch',
                        'desc' => 'Raised tactile graphics on heavyweight cotton tees.',
                    ],
                    [
                        'image' => '/storefront/custom/tee-backprint.jpg',
                        'tag' => 'T-Shirts / Screen / DTF',
                        'title' => 'Branded Merchandise Run',
                        'desc' => 'Custom graphic tee run for growing brand.',
                    ],
                    [
                        'image' => '/storefront/custom/hero-apparel.jpg',
                        'tag' => 'Production / Apparel Run',
                        'title' => 'Corporate Apparel Batch',
                        'desc' => 'Coordinated staff tees and custom merchandise.',
                    ],
                ],
                'faq' => [
                    [
                        'q' => 'What is the minimum order quantity (MOQ)?',
                        'a' => 'Smaller quantities are available where the product and printing method support them. Specialized methods may require higher MOQs. Exact MOQ depends on your requirement.',
                    ],
                    [
                        'q' => 'Can we confirm artwork and placement before printing starts?',
                        'a' => 'Yes. Apparel type, artwork, placement, and printing method are confirmed before production begins.',
                    ],
                    [
                        'q' => 'What artwork should I send?',
                        'a' => 'Share your available logo or artwork. We’ll review it and confirm whether it is suitable for the selected printing or embroidery method.',
                    ],
                    [
                        'q' => 'Do you deliver across India?',
                        'a' => 'Yes, orders can be delivered across India.',
                    ],
                    [
                        'q' => 'What are the payment terms?',
                        'a' => 'Payment terms are confirmed with the quotation based on the order requirement. Bulk orders may require advance payment, and COD is not available for bulk orders.',
                    ],
                ],
                'section_order' => ['hero', 'showcase', 'use_cases', 'techniques', 'tagline', 'quote_form', 'pricing', 'steps', 'why_okina', 'gallery', 'faq', 'final_cta'],
            ];
        }

        return [];
    }
}
