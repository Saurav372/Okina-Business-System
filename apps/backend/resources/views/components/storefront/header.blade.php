@props([
    'site',
    'categories' => [],
    'cartCount' => 0,
])

<div class="sf-promo" role="region" aria-label="Current offer">
    <p><span aria-hidden="true">◆</span> First order offer · use <strong>FIRSTORDER</strong></p>
</div>

<header class="sf-header" data-storefront-header>
    <div class="sf-header-main">
        <a class="sf-brand" href="{{ route('storefront.home') }}" aria-label="{{ $site['company_name'] }} home">
            <span class="sf-brand-mark" aria-hidden="true">O</span>
            <span>OKINA</span>
        </a>

        <form class="sf-search" action="{{ route('storefront.search') }}" method="get" role="search">
            <label class="sf-sr-only" for="site-search">Search products</label>
            <input id="site-search" name="q" type="search" value="{{ request('q') }}" placeholder="Search T-shirts, teamwear, hoodies…" autocomplete="off">
            <button type="submit" aria-label="Search products"><x-storefront.icon name="search" /></button>
        </form>

        <nav class="sf-header-actions" aria-label="Account and bag">
            <a class="sf-icon-button sf-account-link" href="{{ auth('customer')->check() ? route('customer.account') : route('customer.login') }}" aria-label="{{ auth('customer')->check() ? 'Your account' : 'Sign in' }}">
                <x-storefront.icon name="user" />
            </a>
            <a class="sf-icon-button" href="{{ $site['frontend_url'] }}/cart" aria-label="Bag with {{ $cartCount }} items">
                <x-storefront.icon name="bag" />
                @if($cartCount > 0)<span class="sf-count-badge" aria-hidden="true">{{ min($cartCount, 99) }}</span>@endif
            </a>
            <button class="sf-icon-button sf-menu-trigger" type="button" aria-expanded="false" aria-controls="storefront-mobile-menu" data-menu-trigger>
                <span class="sf-sr-only">Open menu</span>
                <x-storefront.icon name="menu" />
            </button>
        </nav>
    </div>

    <nav class="sf-category-nav" aria-label="Product collections">
        <div>
            @foreach($categories as $category)
                <a href="{{ $category['url'] }}" @if(request()->route('category') === $category['slug']) aria-current="page" @endif>{{ $category['name'] }}</a>
            @endforeach
            <a class="sf-create-link" href="{{ $site['frontend_url'] }}/mockup-generate">Create yours</a>
        </div>
    </nav>

    <nav class="sf-mobile-menu" id="storefront-mobile-menu" aria-label="Mobile navigation" hidden data-mobile-menu>
        <div class="sf-mobile-menu-head">
            <strong>Browse Okina</strong>
            <button class="sf-icon-button" type="button" data-menu-close aria-label="Close menu"><x-storefront.icon name="close" /></button>
        </div>
        <a href="{{ route('storefront.categories.index') }}">Shop all collections</a>
        @foreach($categories as $category)
            <a href="{{ $category['url'] }}">{{ $category['name'] }}</a>
        @endforeach
        <a href="{{ $site['frontend_url'] }}/mockup-generate">Create your apparel</a>
        <a href="{{ auth('customer')->check() ? route('customer.account') : route('customer.login') }}">{{ auth('customer')->check() ? 'Orders and proofs' : 'Sign in' }}</a>
    </nav>
</header>
