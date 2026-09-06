<x-layouts.storefront
    title="Custom apparel, ready for your idea"
    description="Choose premium apparel, add your artwork, and approve the production proof before anything is printed."
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    :structured-data="$structuredData"
    page="home"
>
    <section class="sf-home-hero">
        <div class="sf-hero-card">
            <div class="sf-hero-copy">
                <p class="sf-eyebrow">New · Okina custom studio</p>
                <h1>Your idea.<br>Ready to wear.</h1>
                <span class="sf-red-rule" aria-hidden="true"></span>
                <p>Choose a premium blank, set every size, and send your artwork. Okina checks the production details and waits for your approval before printing.</p>
                <div class="sf-button-row">
                    <a class="sf-button" href="{{ $heroProduct ? $heroProduct['url'] : route('storefront.categories.index') }}">Create your T-shirt <x-storefront.icon name="arrow" /></a>
                    <a class="sf-button sf-button-outline" href="{{ route('storefront.categories.index') }}">Browse styles</a>
                </div>
            </div>
            <div class="sf-hero-media">
                <img src="/storefront/okina-campaign-hero.png" alt="Okina custom apparel campaign featuring a black printed T-shirt" width="1600" height="1050" fetchpriority="high">
            </div>
        </div>
    </section>

    <section class="sf-trust-row" aria-label="Why order with Okina">
        <div><span aria-hidden="true">✓</span><strong>Proof before print</strong><small>Approve the production file</small></div>
        <div><span aria-hidden="true">1+</span><strong>Flexible quantity</strong><small>Personal, team, and bulk</small></div>
        <div><span aria-hidden="true">₹</span><strong>Clear pricing</strong><small>Real totals from your choices</small></div>
        <div><span aria-hidden="true">↗</span><strong>Order tracking</strong><small>Artwork through delivery</small></div>
    </section>

    <section class="sf-section" aria-labelledby="shop-by-category">
        <div class="sf-section-heading">
            <div><p class="sf-eyebrow">Find your canvas</p><h2 id="shop-by-category">Shop by collection.</h2></div>
            <a class="sf-text-link" href="{{ route('storefront.categories.index') }}">View all collections <x-storefront.icon name="arrow" /></a>
        </div>
        @if(count($featuredCategories) > 0)
            <div class="sf-category-grid">
                @foreach($featuredCategories as $category)
                    <x-storefront.category-card :category="$category" :index="$loop->index" />
                @endforeach
            </div>
        @else
            <x-storefront.state-panel title="Fresh collections are being prepared" message="The first Okina collection will appear here as soon as it is published." />
        @endif
    </section>

    <section class="sf-product-band" aria-labelledby="popular-products">
        <div class="sf-section">
            <div class="sf-section-heading">
                <div><p class="sf-eyebrow">Custom-ready favourites</p><h2 id="popular-products">Made for your mark.</h2></div>
                <p>Every displayed price comes from the live catalogue. Final choices and availability are confirmed before checkout.</p>
            </div>
            @if(count($featuredProducts) > 0)
                <div class="sf-product-grid">
                    @foreach($featuredProducts as $product)
                        <x-storefront.product-card :product="$product" :index="$loop->index" />
                    @endforeach
                </div>
            @else
                <x-storefront.state-panel title="New styles are on the way" message="No products are published yet. Please check back soon." />
            @endif
        </div>
    </section>

    <section class="sf-section" aria-labelledby="campaign-heading">
        <div class="sf-section-heading">
            <div><p class="sf-eyebrow">Built around the occasion</p><h2 id="campaign-heading">One studio. Every scale.</h2></div>
        </div>
        <div class="sf-campaign-grid">
            <article class="sf-campaign-tile sf-campaign-image">
                <img src="/storefront/okina-campaign-hero.png" alt="Custom apparel styled for an Okina campaign" loading="lazy" width="1600" height="1050">
                <div><p class="sf-eyebrow">Creators and events</p><h3>Start with one clear idea.</h3><p>Choose the garment, placement, colours, and exact sizes before your proof is prepared.</p><a class="sf-button" href="{{ route('storefront.categories.index') }}">Explore styles</a></div>
            </article>
            <article class="sf-campaign-tile sf-campaign-red">
                <span class="sf-campaign-number" aria-hidden="true">25+</span>
                <div><p class="sf-eyebrow">Teams and businesses</p><h3>Large order? Review the price first.</h3><p>Quantities of 25 or more move to the quotation route. No payment is taken before the details and total are agreed.</p><a class="sf-button sf-button-light" href="{{ route('storefront.categories.index') }}">Choose a product</a></div>
            </article>
        </div>
    </section>

    <section class="sf-process" aria-labelledby="process-heading">
        <div class="sf-section">
            <div class="sf-section-heading">
                <div><p class="sf-eyebrow">From idea to delivery</p><h2 id="process-heading">Nothing prints by surprise.</h2></div>
                <p>Your product, quantities, artwork, and approval stay connected from checkout through production.</p>
            </div>
            <ol class="sf-step-grid">
                <li><span>01</span><h3>Choose the apparel</h3><p>Compare styles and start with the right garment.</p></li>
                <li><span>02</span><h3>Set every detail</h3><p>Add sizes, quantities, colour, placement, and artwork.</p></li>
                <li><span>03</span><h3>Review the proof</h3><p>Check spelling, scale, colour, and placement before production.</p></li>
                <li><span>04</span><h3>Track the order</h3><p>Follow production and delivery from your account.</p></li>
            </ol>
        </div>
    </section>
</x-layouts.storefront>
