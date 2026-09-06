@props([
    'site',
    'categories' => [],
    'cartCount' => 0,
])

<!-- Top Announcement Ticker (Competitor Benchmark: Destiny Clothing & Printmine) -->
<div class="sf-announcement-bar" role="region" aria-label="Announcement">
    <div class="sf-announcement-inner">
        <span class="sf-announcement-pill">FREE DIGITAL PROOF</span>
        <p class="sf-announcement-text">
            <span>📦 PAN-INDIA SHIPPING</span>
            <span class="sf-sep">|</span>
            <span>⚡ 48H RAPID DISPATCH</span>
            <span class="sf-sep">|</span>
            <span>✨ NO MINIMUM ORDER REQUIRED</span>
        </p>
        <span class="sf-announcement-code">CODE: <strong>FIRSTORDER</strong> (10% OFF)</span>
    </div>
</div>

<!-- Floating Elevated Header with Okina Logo & Direct Slide-Out Cart Bag Trigger -->
<header class="sf-header-sticky" data-storefront-header>
    <div class="sf-navbar-wrapper">
        <div class="sf-navbar">
            <!-- Brand Logo with authentic Ensō flame mark -->
            <a class="sf-brand-logo" href="{{ route('storefront.home') }}" aria-label="{{ $site['company_name'] }} Home">
                <img src="/brand/okina-logo.png" alt="{{ $site['company_name'] }}" class="sf-logo-img" width="168" height="42">
            </a>

            <!-- Center Navigation Links -->
            <nav class="sf-nav-links" aria-label="Main Navigation">
                <a href="{{ route('storefront.categories.index') }}" class="sf-nav-link @if(request()->routeIs('storefront.categories.index')) sf-active @endif">
                    All Styles
                </a>
                @foreach($categories as $category)
                    <a href="{{ $category['url'] }}" class="sf-nav-link @if(request()->route('category') === $category['slug']) sf-active @endif">
                        {{ $category['name'] }}
                    </a>
                @endforeach
            </nav>

            <!-- Quick Search Bar (Inline Elevated) -->
            <form class="sf-quick-search" action="{{ route('storefront.search') }}" method="get" role="search">
                <label class="sf-sr-only" for="header-search-input">Search products</label>
                <div class="sf-search-input-wrap">
                    <x-storefront.icon name="search" class="sf-search-ico" />
                    <input id="header-search-input" name="q" type="search" value="{{ request('q') }}" placeholder="Search oversized tees, hoodies…" autocomplete="off">
                </div>
            </form>

            <!-- Actions: Account & Slide-Out Bag Trigger -->
            <div class="sf-nav-actions" aria-label="User actions">
                <a class="sf-action-btn sf-account-btn" href="{{ auth('customer')->check() ? route('customer.account') : route('customer.login') }}" aria-label="{{ auth('customer')->check() ? 'Your Account' : 'Sign In' }}">
                    <x-storefront.icon name="user" />
                    <span class="sf-action-label">{{ auth('customer')->check() ? 'Account' : 'Sign In' }}</span>
                </a>

                <!-- Bag Trigger Button (Opens Slide-out Cart Drawer) -->
                <button type="button" class="sf-action-btn sf-bag-trigger" aria-label="Open shopping bag with {{ $cartCount }} items" aria-controls="cart-drawer" data-cart-trigger>
                    <div class="sf-bag-icon-wrapper">
                        <x-storefront.icon name="bag" />
                        <span class="sf-bag-count" data-cart-count-badge aria-hidden="true">{{ min($cartCount, 99) }}</span>
                    </div>
                    <span class="sf-action-label">Bag</span>
                </button>

                <!-- Mobile Hamburger Toggle -->
                <button class="sf-action-btn sf-mobile-toggle" type="button" aria-expanded="false" aria-controls="storefront-mobile-menu" data-menu-trigger aria-label="Open mobile menu">
                    <x-storefront.icon name="menu" />
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Slide Down Menu -->
    <nav class="sf-mobile-menu" id="storefront-mobile-menu" aria-label="Mobile navigation" hidden data-mobile-menu>
        <div class="sf-mobile-menu-head">
            <span class="sf-mobile-title">Explore Collections</span>
            <button class="sf-icon-button" type="button" data-menu-close aria-label="Close menu">
                <x-storefront.icon name="close" />
            </button>
        </div>
        <div class="sf-mobile-search-row">
            <form action="{{ route('storefront.search') }}" method="get" role="search">
                <input name="q" type="search" value="{{ request('q') }}" placeholder="Search products, tees, hoodies…" autocomplete="off">
                <button type="submit" aria-label="Search"><x-storefront.icon name="search" /></button>
            </form>
        </div>
        <div class="sf-mobile-links">
            <a href="{{ route('storefront.categories.index') }}" class="sf-mobile-link">All Collections</a>
            @foreach($categories as $category)
                <a href="{{ $category['url'] }}" class="sf-mobile-link">{{ $category['name'] }}</a>
            @endforeach
            <a href="{{ auth('customer')->check() ? route('customer.account') : route('customer.login') }}" class="sf-mobile-link">
                {{ auth('customer')->check() ? 'My Account & Orders' : 'Sign In / Register' }}
            </a>
            <a href="{{ route('storefront.track-order') }}" class="sf-mobile-link">Track Order Status</a>
        </div>
    </nav>
</header>
