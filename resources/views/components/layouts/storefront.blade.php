@props([
    'title',
    'description',
    'site',
    'navigationCategories' => [],
    'cartCount' => 0,
    'canonical' => null,
    'robots' => null,
    'structuredData' => null,
    'page' => 'storefront',
])

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ $site['site_title'] }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="{{ $robots ?: $site['robots'] }}">
    <meta name="theme-color" content="#c8202a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/favicon.svg') }}">
    <link rel="canonical" href="{{ $canonical ?: url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title }} · {{ $site['site_title'] }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical ?: url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    @if($structuredData)
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endif
    @vite(['resources/css/storefront.css', 'resources/js/storefront.js'])
</head>
<body data-page="{{ $page }}">
    <a class="sf-skip-link" href="#main-content">Skip to main content</a>
    <x-storefront.header :site="$site" :categories="$navigationCategories" :cart-count="$cartCount" />
    <main id="main-content" tabindex="-1">{{ $slot }}</main>
    <x-storefront.footer :site="$site" />

    <x-storefront.cart-drawer :cart="$cart ?? null" :money="$money ?? null" />

    <nav class="sf-mobile-dock" aria-label="Primary mobile navigation">
        <a href="{{ route('storefront.home') }}" @if(request()->routeIs('storefront.home')) aria-current="page" @endif><x-storefront.icon name="home" /><span>Home</span></a>
        <a href="{{ route('storefront.categories.index') }}" @if(request()->routeIs('storefront.categories.*', 'storefront.search')) aria-current="page" @endif><x-storefront.icon name="search" /><span>Shop</span></a>
        <a href="{{ auth('customer')->check() ? route('customer.account') : route('customer.login') }}"><x-storefront.icon name="orders" /><span>Orders</span></a>
        <button type="button" class="sf-dock-bag-btn" data-cart-trigger aria-label="Open Bag with {{ $cartCount }} items"><x-storefront.icon name="bag" /><span>Bag</span>@if($cartCount > 0)<b aria-label="{{ $cartCount }} items">{{ min($cartCount, 99) }}</b>@endif</button>
    </nav>
</body>
</html>
