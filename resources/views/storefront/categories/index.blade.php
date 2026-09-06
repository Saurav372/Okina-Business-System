<x-layouts.storefront
    title="Custom apparel collections"
    description="Browse custom apparel collections for teams, events, businesses, creators, and gifts."
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    page="categories"
>
    <header class="sf-collection-hero">
        <p class="sf-eyebrow">Shop by purpose</p>
        <h1>Find the right<br>canvas.</h1>
        <p>Start with the closest collection, then shape the garment around your artwork, colours, sizes, and quantity.</p>
        <strong>{{ str_pad((string) count($categories), 2, '0', STR_PAD_LEFT) }} collections</strong>
    </header>

    <section class="sf-section" aria-labelledby="all-collections">
        <div class="sf-section-heading">
            <div><p class="sf-eyebrow">Published now</p><h2 id="all-collections">Every collection.</h2></div>
            <p>Only public products and collections from the Okina catalogue appear here.</p>
        </div>
        @if(count($categories) > 0)
            <div class="sf-category-grid sf-category-grid-large">
                @foreach($categories as $category)
                    <x-storefront.category-card :category="$category" :index="$loop->index" />
                @endforeach
            </div>
        @else
            <x-storefront.state-panel title="Collections are being prepared" message="Nothing is published yet. Please check back once the first collection is ready." />
        @endif
    </section>
</x-layouts.storefront>
