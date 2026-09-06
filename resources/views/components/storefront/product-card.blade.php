@props(['product', 'index' => 0])

@php
    // Color swatches mapping for apparel variants
    $colors = collect($product['skus'] ?? [])
        ->map(function ($sku) {
            $variantKey = $sku['variant_key'] ?? '';
            $options = data_get($sku, 'option_values', []);
            
            // Check structured options first
            foreach ($options as $opt) {
                $code = strtolower((string) ($opt['code'] ?? ''));
                $label = (string) ($opt['label'] ?? $code);
                if (in_array($code, ['ink', 'paper', 'black', 'white', 'charcoal', 'cream', 'sage', 'olive', 'navy', 'maroon', 'red', 'beige', 'brown'], true)) {
                    return ['code' => $code, 'label' => $label];
                }
            }

            // Fallback to variant_key pattern (e.g. color:ink|size:m)
            if (preg_match('/color:([a-z0-9_-]+)/i', $variantKey, $matches)) {
                $cCode = strtolower($matches[1]);
                $cLabel = ucwords(str_replace(['_', '-'], ' ', $cCode));
                return ['code' => $cCode, 'label' => $cLabel];
            }

            return null;
        })
        ->filter()
        ->unique('code')
        ->values()
        ->take(4);

    $colorHexMap = [
        'ink' => '#111111',
        'paper' => '#F4F4F5',
        'black' => '#111111',
        'washed_black' => '#2A2A2A',
        'charcoal' => '#374151',
        'white' => '#F3F4F6',
        'cream' => '#F5F2EB',
        'off_white' => '#F5F5F0',
        'sage' => '#7C9082',
        'sage_green' => '#6B8E23',
        'olive' => '#556B2F',
        'navy' => '#1E3A8A',
        'royal_blue' => '#2563EB',
        'maroon' => '#800000',
        'red' => '#DC2626',
        'beige' => '#E5DCC5',
        'brown' => '#5C4033',
    ];
@endphp

<article class="sf-product-card" data-product-card data-name="{{ Str::lower($product['name']) }}" data-category="{{ Str::lower(data_get($product, 'category.name', '')) }}" data-price="{{ $product['price_minor'] }}" data-order="{{ $index }}">
    <a href="{{ $product['url'] }}" class="sf-card-link" aria-label="View and customize {{ $product['name'] }}">
        <div class="sf-product-media">
            @if(!empty($product['badge']))
                <span class="sf-card-badge">{{ $product['badge'] }}</span>
            @endif
            @if(data_get($product, 'cover_image.url'))
                <img src="{{ data_get($product, 'cover_image.url') }}" alt="{{ data_get($product, 'cover_image.alt_text') ?: $product['name'] }}" loading="lazy" width="{{ data_get($product, 'cover_image.width') ?: 720 }}" height="{{ data_get($product, 'cover_image.height') ?: 780 }}">
            @else
                <div class="sf-product-fallback" aria-hidden="true"><span>{{ mb_strtoupper(mb_substr($product['name'], 0, 2)) }}</span></div>
            @endif

            <!-- Quick Action Hover Overlay -->
            <div class="sf-card-hover-action">
                <span>Design Now &rarr;</span>
            </div>
        </div>

        <div class="sf-product-copy">
            <!-- Swatches Row -->
            @if($colors->isNotEmpty())
                <div class="sf-card-swatches" aria-label="Available colors">
                    @foreach($colors as $color)
                        @php
                            $hex = $colorHexMap[strtolower(str_replace([' ', '-'], '_', $color['code']))] ?? '#9CA3AF';
                        @endphp
                        <span class="sf-swatch-dot" style="background-color: {{ $hex }};" title="{{ $color['label'] }}" aria-label="{{ $color['label'] }}"></span>
                    @endforeach
                    @if(count($product['skus'] ?? []) > 4)
                        <span class="sf-swatch-more">+more</span>
                    @endif
                </div>
            @endif

            <p class="sf-product-cat">{{ data_get($product, 'category.name', 'Custom apparel') }}</p>
            <h3 class="sf-product-name">{{ $product['name'] }}</h3>

            <!-- Social proof stars -->
            <div class="sf-product-rating" aria-label="Rated 4.9 out of 5 stars">
                <span class="sf-stars">★★★★★</span>
                <span class="sf-rating-count">(4.9)</span>
            </div>

            <div class="sf-price-row">
                <div class="sf-price-group">
                    <strong class="sf-price-current">{{ $product['display_price'] }}</strong>
                    @if(!empty($product['display_compare_at_price']))
                        <del class="sf-price-strike">{{ $product['display_compare_at_price'] }}</del>
                    @endif
                </div>
                <span class="sf-cta-pill">Customize <x-storefront.icon name="arrow" /></span>
            </div>
        </div>
    </a>
</article>
