<x-layouts.storefront
    :title="$query !== '' ? 'Search results for '.$query : 'Search custom apparel'"
    description="Search Okina Craft custom apparel and choose a product for your artwork."
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    robots="noindex, follow"
    page="search"
>
    <header class="sf-search-hero">
        <p class="sf-eyebrow">Product search</p>
        <h1>{{ $query !== '' ? 'Results for “'.$query.'”' : 'Find your next canvas.' }}</h1>
        <form class="sf-search-large" action="{{ route('storefront.search') }}" method="get" role="search">
            <label for="catalog-search">Search published products</label>
            <div><input id="catalog-search" name="q" type="search" value="{{ $query }}" placeholder="Try T-shirt, hoodie, teamwear…" autofocus><button class="sf-button" type="submit">Search <x-storefront.icon name="arrow" /></button></div>
        </form>
    </header>
    <section class="sf-section" aria-labelledby="search-results">
        <div class="sf-section-heading"><div><p class="sf-eyebrow">Published catalogue</p><h2 id="search-results">{{ count($products) }} {{ count($products) === 1 ? 'match' : 'matches' }}.</h2></div></div>
        @if(count($products) > 0)
            <div class="sf-product-grid">
                @foreach($products as $product)<x-storefront.product-card :product="$product" :index="$loop->index" />@endforeach
            </div>
        @else
            <x-storefront.state-panel title="No products match that search" message="Try a shorter product name or browse all collections." action-label="Browse collections" :action-url="route('storefront.categories.index')" />
        @endif
    </section>
</x-layouts.storefront>
