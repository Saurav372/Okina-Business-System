<x-layouts.storefront
    title="Custom Premium Apparel Crafted to Perfection"
    description="Custom heavyweight oversized t-shirts, fleece hoodies, polo & team merch. Free digital 3D proof approval before production."
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    :cart="$cart ?? null"
    :money="$money ?? null"
    :structured-data="$structuredData"
    page="home"
>
    <!-- Benchmark Split Hero (Destiny Clothing & Printmine elevated) -->
    <section class="sf-home-hero-v2">
        <div class="sf-hero-split-card">
            <div class="sf-hero-content-col">
                <div class="sf-hero-badge-pill">
                    <span class="sf-badge-pulse"></span>
                    <span>NEW STUDIO DROP · SS26</span>
                </div>
                <h1 class="sf-hero-title">
                    YOUR BRAND.<br>
                    <span>CRAFTED TO</span><br>
                    PERFECTION.
                </h1>
                <p class="sf-hero-lead">
                    Custom 240+ GSM heavyweight t-shirts, fleece hoodies, and corporate merchandise. Inspect your free high-res digital proof before we print a single stitch.
                </p>
                <div class="sf-hero-cta-group">
                    <a class="sf-btn-flame" href="{{ route('storefront.categories.index') }}">
                        <span>EXPLORE STYLES</span>
                        <x-storefront.icon name="arrow" />
                    </a>
                    <a class="sf-btn-outline" href="{{ route('storefront.categories.index') }}">
                        VIEW ALL COLLECTIONS
                    </a>
                </div>

                <!-- Hero Mini Reassurance Stats -->
                <div class="sf-hero-stats">
                    <div class="sf-hero-stat">
                        <strong>10,000+</strong>
                        <span>Garments Printed</span>
                    </div>
                    <div class="sf-hero-stat-sep"></div>
                    <div class="sf-hero-stat">
                        <strong>4.9 ★</strong>
                        <span>Verified Quality</span>
                    </div>
                    <div class="sf-hero-stat-sep"></div>
                    <div class="sf-hero-stat">
                        <strong>0 MOQ</strong>
                        <span>1 to 10k+ Units</span>
                    </div>
                </div>
            </div>

            <!-- Hero Visual Canvas / Lineup -->
            <div class="sf-hero-lineup-col">
                <div class="sf-hero-lineup-wrapper">
                    <img src="/storefront/okina-campaign-hero.png" alt="Okina custom apparel campaign featuring oversized tees, hoodies, and merchandise" class="sf-hero-lineup-img" width="1600" height="1050" fetchpriority="high">
                    <div class="sf-hero-overlay-tag">
                        <span class="sf-tag-dot"></span>
                        <span>100% Super-Combed French Terry Cotton</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4 High-Converting Trust Pillars (Competitor Benchmark) -->
    <section class="sf-trust-grid" aria-label="Why order with Okina">
        <div class="sf-trust-card">
            <div class="sf-trust-icon-box">
                <span class="sf-icon-emoji">☁️</span>
            </div>
            <div>
                <strong>100% Combed Cotton</strong>
                <small>240+ GSM premium bio-washed fabric</small>
            </div>
        </div>
        <div class="sf-trust-card">
            <div class="sf-trust-icon-box">
                <span class="sf-icon-emoji">🛡️</span>
            </div>
            <div>
                <strong>Digital Proof Approval</strong>
                <small>Inspect artwork before printing</small>
            </div>
        </div>
        <div class="sf-trust-card">
            <div class="sf-trust-icon-box">
                <span class="sf-icon-emoji">📦</span>
            </div>
            <div>
                <strong>1 to 10,000+ Units</strong>
                <small>No minimum order for creators & teams</small>
            </div>
        </div>
        <div class="sf-trust-card">
            <div class="sf-trust-icon-box">
                <span class="sf-icon-emoji">⚡</span>
            </div>
            <div>
                <strong>Rapid Pan-India Dispatch</strong>
                <small>Express door-to-door courier tracking</small>
            </div>
        </div>
    </section>

    <!-- Category Collections Carousel / Grid -->
    <section class="sf-section" aria-labelledby="shop-by-category">
        <div class="sf-section-heading">
            <div>
                <p class="sf-eyebrow">CHOOSE YOUR CANVAS</p>
                <h2 id="shop-by-category">Shop by Category.</h2>
            </div>
            <a class="sf-text-link" href="{{ route('storefront.categories.index') }}">
                View all collections <x-storefront.icon name="arrow" />
            </a>
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

    <!-- Best Sellers Section with Swatches & Strike Prices -->
    <section class="sf-product-band" aria-labelledby="popular-products">
        <div class="sf-section">
            <div class="sf-section-heading">
                <div>
                    <p class="sf-eyebrow">BEST SELLERS & TRENDING</p>
                    <h2 id="popular-products">Custom-Ready Favorites.</h2>
                </div>
                <p>Engineered for high-definition screen printing, DTF, and premium embroidery.</p>
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

    <!-- Campaign Split Banner: Creators vs Corporate Bulk (Printmine Benchmark) -->
    <section class="sf-section" aria-labelledby="campaign-heading">
        <div class="sf-section-heading">
            <div>
                <p class="sf-eyebrow">TAILORED FOR EVERY NEED</p>
                <h2 id="campaign-heading">One Studio. Every Scale.</h2>
            </div>
        </div>
        <div class="sf-campaign-grid">
            <article class="sf-campaign-tile sf-campaign-creators">
                <div class="sf-tile-content">
                    <span class="sf-tile-tag">FOR INDIVIDUALS & CREATORS</span>
                    <h3>Start with 1 Single Piece.</h3>
                    <p>No MOQ. Choose your garment, select your color & size, and upload your artwork with instant online checkout.</p>
                    <a class="sf-button" href="{{ route('storefront.categories.index') }}">Explore Garments &rarr;</a>
                </div>
            </article>
            <article class="sf-campaign-tile sf-campaign-teams">
                <div class="sf-tile-content">
                    <span class="sf-tile-tag sf-tag-dark">FOR TEAMS & BUSINESSES</span>
                    <h3>Volume Orders (25 to 10,000+).</h3>
                    <p>Get tiered wholesale pricing, free physical fabric swatch kit, and dedicated account manager support.</p>
                    <a class="sf-button sf-button-light" href="{{ route('storefront.categories.index') }}">Request Wholesale Quote &rarr;</a>
                </div>
            </article>
        </div>
    </section>

    <!-- 4 Step Process Row -->
    <section class="sf-process" aria-labelledby="process-heading">
        <div class="sf-section">
            <div class="sf-section-heading">
                <div>
                    <p class="sf-eyebrow">SIMPLE 4-STEP PROCESS</p>
                    <h2 id="process-heading">Nothing Prints by Surprise.</h2>
                </div>
                <p>Total transparency from artwork upload to doorstep delivery.</p>
            </div>
            <ol class="sf-step-grid">
                <li>
                    <span class="sf-step-num">01</span>
                    <h3>Pick Garment</h3>
                    <p>Select your favorite cut, GSM weight, and color palette.</p>
                </li>
                <li>
                    <span class="sf-step-num">02</span>
                    <h3>Upload Design</h3>
                    <p>Place your artwork on chest, back, or sleeves in 3D.</p>
                </li>
                <li>
                    <span class="sf-step-num">03</span>
                    <h3>Approve Proof</h3>
                    <p>Verify color tones and dimensions before printing starts.</p>
                </li>
                <li>
                    <span class="sf-step-num">04</span>
                    <h3>Track & Wear</h3>
                    <p>Receive live dispatch alerts and courier tracking link.</p>
                </li>
            </ol>
        </div>
    </section>
</x-layouts.storefront>
