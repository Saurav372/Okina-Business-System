@php
    $categoryThemes = [
        'custom-t-shirts' => ['cafe', 'Logo tees for promotions, staff & events'],
        'team-layers' => ['team', 'For clubs, crews and organizations'],
        'workwear' => ['uniform', 'A shared look for your working day'],
        'event-kits' => ['event', 'Bring your campaign to life'],
    ];
    $inspiration = [
        ['cafe', 'Café uniforms', 'A shared identity, from the first coffee.', 'tee'],
        ['uniform', 'Branded workwear', 'Make your business part of every day.', 'polo'],
        ['event', 'Event printing', 'Wear the message. Bring people together.', 'tee'],
        ['team', 'Team apparel', 'One team. A look that belongs to everyone.', 'hoodie'],
    ];
    $process = [
        ['shirt', 'Choose apparel', 'Pick the right style for your team.'],
        ['artwork', 'Share logo / artwork', 'Add your artwork and print options.'],
        ['settings', 'Review your order', 'Check quantities, details and pricing.'],
        ['truck', 'Follow your delivery', 'Track progress from your account.'],
    ];
    $methods = [
        ['printer', 'DTF printing', 'Colorful, versatile transfers.', 'A design is printed onto a transfer film and applied to the garment with heat. Check the selected product for available placements and artwork requirements.'],
        ['shirt', 'DTG printing', 'Detailed, full-color designs.', 'Ink is printed directly onto a compatible garment. Fabric, artwork and garment color determine which setup is suitable.'],
        ['needle', 'Embroidery', 'A textured, stitched finish.', 'Thread creates a tactile finish for logos and lettering. Small details may need to be simplified for a clear stitched result.'],
        ['layers', 'Puff printing', 'Dimension you can feel.', 'A raised print adds texture to bold shapes and lettering. Ask about garment compatibility before planning this finish.'],
        ['drop', 'Sublimation', 'Color that becomes part of the fabric.', 'Heat transfers dye into compatible polyester fabrics. Light-colored garments usually provide the clearest result.'],
    ];
@endphp
<x-layouts.storefront
    title="Custom Printed Apparel for Brands, Teams & Events"
    description="Explore custom t-shirts, team apparel and branded workwear from Okina Craft. Choose your garment, share your artwork and make it yours."
    :site="$site" :navigation-categories="$navigationCategories" :cart-count="$cartCount"
    :cart="$cart ?? null" :money="$money ?? null" :structured-data="$structuredData" page="home"
>
    <div class="sf-business-home">
        <section class="sf-commerce-hero" aria-labelledby="home-heading">
            <div class="sf-commerce-hero-photo"><img src="/storefront/business/hero.png" alt="Model wearing a black custom printed t-shirt with a white chest design" width="1586" height="992" fetchpriority="high"></div>
            <div class="sf-commerce-hero-inner">
                <div class="sf-commerce-hero-copy">
                    <p class="sf-commerce-eyebrow">CUSTOM APPAREL. STRONGER BRANDS.</p>
                    <h1 id="home-heading">Custom Printed<br>Apparel for<br><span>Brands, Teams &amp; Events.</span></h1>
                    <p class="sf-commerce-lead">Custom t-shirts, uniforms, event apparel and branded merchandise. Bring your people together with a look that’s unmistakably yours.</p>
                    <div class="sf-commerce-actions">
                        <a class="sf-commerce-button" href="{{ route('storefront.categories.index') }}">Shop Custom Apparel <x-storefront.icon name="arrow" /></a>
                        <a class="sf-commerce-button sf-commerce-button-outline" href="#bulk-orders">{{ !empty($site['support_email']) ? 'Get Bulk Quote' : 'Explore Bulk Orders' }}</a>
                    </div>
                </div>
                <div class="sf-commerce-hero-sign" aria-hidden="true"><span>APPAREL<br>THAT BUILDS<br>BUSINESSES</span><i></i><small>UNIFORMS<br>EVENTS<br>PROMOTIONS<br>TEAMS<br>BRANDED MERCH</small></div>
            </div>
            <div class="sf-commerce-benefits">
                @foreach([['team', 'Bulk orders', 'Outfit your whole team'], ['diamond', 'Your brand, your way', 'Make your mark'], ['settings', 'Print options', 'Choose on your product'], ['truck', 'Order tracking', 'Follow every stage']] as [$icon, $heading, $copy])
                    <div><x-storefront.icon :name="$icon" /><p><strong>{{ $heading }}</strong><small>{{ $copy }}</small></p></div>
                @endforeach
            </div>
        </section>

        <div class="sf-commerce-process-strip" aria-label="Your custom apparel journey">
            @foreach($process as [$icon, $heading, $copy])
                <div><x-storefront.icon :name="$icon" /><p><span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><strong>{{ $heading }}</strong><small>{{ $copy }}</small></p>@unless($loop->last)<x-storefront.icon name="arrow" class="sf-process-arrow" />@endunless</div>
            @endforeach
        </div>

        <section class="sf-commerce-section" aria-labelledby="shop-by-category">
            <div class="sf-commerce-heading"><div><h2 id="shop-by-category">Shop Custom Apparel</h2><p>Professional apparel for every business need.</p></div><a href="{{ route('storefront.categories.index') }}">View All Categories <x-storefront.icon name="arrow" /></a></div>
            @if(count($featuredCategories))
                <div class="sf-commerce-category-grid">
                    @foreach($featuredCategories as $category)
                        @php
                            $theme = $categoryThemes[$category['slug']] ?? null;
                        @endphp
                        <a class="sf-commerce-category" href="{{ $category['url'] }}">
                            @if($theme)
                                <img src="/storefront/business/{{ $theme[0] }}.png" alt="{{ $category['name'] }} design inspiration" width="1254" height="1254" loading="lazy">
                            @elseif(data_get($category, 'cover_image.url'))
                                <img src="{{ data_get($category, 'cover_image.url') }}" alt="{{ $category['name'] }}" width="720" height="720" loading="lazy">
                            @else
                                <div class="sf-commerce-category-placeholder"><x-storefront.icon name="shirt" /></div>
                            @endif
                            <div><strong>{{ $category['name'] }}</strong><x-storefront.icon name="arrow" /><small>{{ $theme[1] ?? (($category['products_count'] ?? 0).' styles to make your own') }}</small></div>
                        </a>
                    @endforeach
                </div>
            @else
                <x-storefront.state-panel title="Fresh collections are being prepared" message="Explore our apparel collections here as soon as they are published." />
            @endif
        </section>

        <section class="sf-commerce-section" aria-labelledby="popular-products">
            <div class="sf-commerce-heading"><div><h2 id="popular-products">Choose your apparel. We print your brand.</h2><p>Featured styles for businesses, teams and events.</p></div><a href="{{ route('storefront.search') }}">View All Products <x-storefront.icon name="arrow" /></a></div>
            @if(count($featuredProducts))
                <div class="sf-product-grid sf-commerce-products">
                    @foreach(array_slice($featuredProducts, 0, 4) as $product)
                        @php
                            $productName = Str::lower($product['name']);
                            $previewArt = match (true) {
                                Str::contains($productName, 'hoodie') => 'hoodie',
                                Str::contains($productName, 'polo') => 'polo',
                                Str::contains($productName, ['tee', 't-shirt']) => 'tee',
                                default => null,
                            };
                        @endphp
                        <x-storefront.product-card :product="$product" :index="$loop->index" :business="true" :preview-art="$previewArt" />
                    @endforeach
                </div>
            @else
                <x-storefront.state-panel title="New styles are on the way" message="No products are published yet. Please check back soon." />
            @endif
        </section>

        <section class="sf-commerce-section" aria-labelledby="business-inspiration">
            <div class="sf-commerce-heading"><div><h2 id="business-inspiration">Made for the way you work.</h2><p>Design inspiration for businesses and organizations.</p></div><a href="{{ route('storefront.categories.index') }}">Find Your Team’s Style <x-storefront.icon name="arrow" /></a></div>
            <div class="sf-commerce-inspiration-grid">
                @foreach($inspiration as [$image, $title, $copy, $query])
                    <a class="sf-commerce-category sf-commerce-example" href="{{ route('storefront.search', ['q' => $query]) }}"><img src="/storefront/business/{{ $image }}.png" alt="Illustrative {{ Str::lower($title) }} design" width="1254" height="1254" loading="lazy"><div><strong>{{ $title }}</strong><small>{{ $copy }}</small></div></a>
                @endforeach
            </div>
        </section>

        <section class="sf-commerce-section" id="printing-methods" aria-labelledby="methods-heading">
            <div class="sf-commerce-heading"><div><h2 id="methods-heading">The right print for every requirement.</h2><p>Explore the finishes. Available methods depend on your garment.</p></div><a href="{{ route('storefront.how-it-works') }}">How Printing Works <x-storefront.icon name="arrow" /></a></div>
            <div class="sf-commerce-methods">
                @foreach($methods as [$icon, $title, $copy, $detail])
                    <details><summary><x-storefront.icon :name="$icon" /><span><strong>{{ $title }}</strong><small>{{ $copy }}</small></span><span class="sf-method-plus" aria-hidden="true">+</span></summary><p>{{ $detail }}</p></details>
                @endforeach
            </div>
        </section>

        <section class="sf-commerce-section sf-commerce-bulk-grid" id="bulk-orders" aria-labelledby="bulk-heading">
            <div class="sf-commerce-bulk-card">
                <img src="/storefront/business/uniform.png" alt="A coordinated team wearing custom branded polo shirts" width="1254" height="1254" loading="lazy">
                <div class="sf-commerce-bulk-copy"><p class="sf-commerce-eyebrow">FOR BUSINESSES &amp; ORGANIZATIONS</p><h2 id="bulk-heading">Businesses &amp; Bulk Orders</h2><p>Uniforms. Events. Promotions. Team apparel.<br>Build a consistent look for your people. Start with a garment, then review sizes, quantities and customization options.</p><div class="sf-commerce-actions">
                    @if(!empty($site['support_email']))
                        <a class="sf-commerce-button" href="mailto:{{ $site['support_email'] }}?subject=Bulk%20apparel%20quote">Get a Quote <x-storefront.icon name="arrow" /></a>
                    @else
                        <a class="sf-commerce-button" href="{{ route('storefront.categories.index') }}">Choose Bulk Apparel <x-storefront.icon name="arrow" /></a>
                    @endif
                    <a class="sf-commerce-button sf-commerce-button-outline" href="{{ route('storefront.how-it-works') }}">Plan Your Order</a>
                </div><div class="sf-commerce-bulk-notes"><span><x-storefront.icon name="shirt" />Garment options</span><span><x-storefront.icon name="artwork" />Your artwork</span><span><x-storefront.icon name="truck" />Order tracking</span></div></div>
            </div>
            <aside class="sf-commerce-small-order"><div><h3>Need a smaller order?<br>We’ve got you covered.</h3><p>Find a style for your next idea, personal project or growing brand.</p><a class="sf-commerce-button sf-commerce-button-outline" href="{{ route('storefront.categories.index') }}">Shop Now <x-storefront.icon name="arrow" /></a></div><img src="/storefront/business/tee.png" alt="Black Okina Craft t-shirt design concept" width="1254" height="1254" loading="lazy"></aside>
        </section>

        <section class="sf-commerce-process" aria-labelledby="process-heading">
            <div class="sf-commerce-process-inner"><p class="sf-commerce-eyebrow">OUR PROCESS</p><h2 id="process-heading">From your idea to your team.</h2><p>Simple. Considered. Personal.</p><ol>@foreach($process as [$icon, $heading, $copy])<li><x-storefront.icon :name="$icon" /><div><span>{{ $loop->iteration }}</span><strong>{{ $heading }}</strong><small>{{ $copy }}</small></div></li>@endforeach</ol></div><div class="sf-commerce-process-mark" aria-hidden="true">OKINA<span>CRAFT</span><p>APPAREL FOR A<br>BRIGHTER TOMORROW.</p></div>
        </section>
    </div>
</x-layouts.storefront>
