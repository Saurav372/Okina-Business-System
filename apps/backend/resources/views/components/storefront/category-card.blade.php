@props(['category', 'index' => 0])

<a class="sf-category-card" href="{{ $category['url'] }}">
    <figure class="sf-category-media sf-category-tone-{{ ($index % 5) + 1 }}">
        @if(data_get($category, 'cover_image.url'))
            <img src="{{ data_get($category, 'cover_image.url') }}" alt="{{ data_get($category, 'cover_image.alt_text') ?: $category['name'] }}" loading="lazy" width="{{ data_get($category, 'cover_image.width') ?: 720 }}" height="{{ data_get($category, 'cover_image.height') ?: 780 }}">
        @else
            <span class="sf-category-monogram" aria-hidden="true">{{ mb_strtoupper(mb_substr($category['name'], 0, 1)) }}</span>
            <span class="sf-category-thread" aria-hidden="true"></span>
        @endif
    </figure>
    <span class="sf-category-copy"><strong>{{ $category['name'] }}</strong><small>{{ $category['products_count'] ?? 0 }} {{ ($category['products_count'] ?? 0) === 1 ? 'style' : 'styles' }}</small></span>
</a>
