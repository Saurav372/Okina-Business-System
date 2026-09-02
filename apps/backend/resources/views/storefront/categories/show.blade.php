<x-layouts.storefront
    :title="$category['seo_title'] ?: $category['name']"
    :description="$category['seo_description'] ?: ($category['description'] ?: 'Browse custom apparel in this Okina collection.')"
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    :structured-data="$structuredData"
    page="category"
>
    <nav class="sf-breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('storefront.home') }}">Home</a><span aria-hidden="true">/</span>
        <a href="{{ route('storefront.categories.index') }}">Collections</a><span aria-hidden="true">/</span>
        <span aria-current="page">{{ $category['name'] }}</span>
    </nav>

    <header class="sf-category-hero">
        <div><p class="sf-eyebrow">Custom collection</p><h1>{{ $category['name'] }}</h1></div>
        <p>{{ $category['description'] ?: 'Choose a style and make it unmistakably yours with colour, placement, and artwork choices.' }}</p>
    </header>

    <section aria-labelledby="collection-products">
        <div class="sf-catalog-toolbar" data-catalog-toolbar>
            <p id="collection-products"><strong data-result-count>{{ count($products) }}</strong> {{ count($products) === 1 ? 'style' : 'styles' }}</p>
            @if(count($products) > 0)
                <div>
                    <label><span class="sf-sr-only">Filter products in {{ $category['name'] }}</span><input type="search" placeholder="Filter this collection" data-product-filter></label>
                    <label><span class="sf-sr-only">Sort products</span><select data-product-sort><option value="featured">Featured</option><option value="price-low">Price: low to high</option><option value="price-high">Price: high to low</option><option value="name">Name</option></select></label>
                </div>
            @endif
        </div>
        <div class="sf-catalog-shell">
            @if(count($products) > 0)
                <div class="sf-product-grid" data-product-grid>
                    @foreach($products as $product)
                        <x-storefront.product-card :product="$product" :index="$loop->index" />
                    @endforeach
                </div>
                <div hidden data-filter-empty><x-storefront.state-panel title="No styles match that search" message="Try a shorter or different product name." /></div>
                <p class="sf-sr-only" role="status" aria-live="polite" data-filter-status></p>
            @else
                <x-storefront.state-panel title="This collection is getting ready" message="There are no published products here yet. Explore another collection in the meantime." action-label="Browse collections" :action-url="route('storefront.categories.index')" />
            @endif
        </div>
    </section>
</x-layouts.storefront>
