@props(['product', 'index' => 0])

<article class="sf-product-card" data-product-card data-name="{{ Str::lower($product['name']) }}" data-category="{{ Str::lower(data_get($product, 'category.name', '')) }}" data-price="{{ $product['price_minor'] }}" data-order="{{ $index }}">
    <a href="{{ $product['url'] }}" aria-label="View {{ $product['name'] }}">
        <div class="sf-product-media">
            <span class="sf-card-badge">{{ $product['badge'] }}</span>
            @if(data_get($product, 'cover_image.url'))
                <img src="{{ data_get($product, 'cover_image.url') }}" alt="{{ data_get($product, 'cover_image.alt_text') ?: $product['name'] }}" loading="lazy" width="{{ data_get($product, 'cover_image.width') ?: 720 }}" height="{{ data_get($product, 'cover_image.height') ?: 780 }}">
            @else
                <div class="sf-product-fallback" aria-hidden="true"><span>{{ mb_strtoupper(mb_substr($product['name'], 0, 2)) }}</span></div>
            @endif
        </div>
        <div class="sf-product-copy">
            <p>{{ data_get($product, 'category.name', 'Custom apparel') }}</p>
            <h3>{{ $product['name'] }}</h3>
            @if($product['short_description'])<p class="sf-product-description">{{ $product['short_description'] }}</p>@endif
            <div class="sf-price-row">
                <strong>{{ $product['display_price'] }}</strong>
                @if($product['display_compare_at_price'])<del>{{ $product['display_compare_at_price'] }}</del>@endif
                <span>Customize <x-storefront.icon name="arrow" /></span>
            </div>
        </div>
    </a>
</article>
