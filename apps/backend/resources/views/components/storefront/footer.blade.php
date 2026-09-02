@props(['site'])

<footer class="sf-footer">
    <div class="sf-footer-grid">
        <div class="sf-footer-brand">
            <a class="sf-brand sf-brand-inverse" href="{{ route('storefront.home') }}"><span class="sf-brand-mark" aria-hidden="true">O</span><span>OKINA</span></a>
            <p>Custom apparel made carefully in India. Your proof comes before production.</p>
        </div>
        <nav aria-label="Footer shop links">
            <strong>Shop</strong>
            <a href="{{ route('storefront.categories.index') }}">Collections</a>
            <a href="{{ route('storefront.search') }}">All products</a>
            <a href="{{ $site['frontend_url'] }}/mockup-generate">Create yours</a>
        </nav>
        <nav aria-label="Footer help links">
            <strong>Help</strong>
            <a href="{{ $site['frontend_url'] }}/how-it-works">How it works</a>
            <a href="{{ $site['frontend_url'] }}/track-order">Track an order</a>
            @if($site['support_email'])<a href="mailto:{{ $site['support_email'] }}">Contact support</a>@endif
        </nav>
        <nav aria-label="Footer account links">
            <strong>Account</strong>
            <a href="{{ auth('customer')->check() ? route('customer.account') : route('customer.login') }}">{{ auth('customer')->check() ? 'Your orders' : 'Sign in' }}</a>
            <a href="{{ $site['frontend_url'] }}/cart">Your bag</a>
        </nav>
    </div>
    <div class="sf-footer-bottom"><span>© {{ now()->year }} {{ $site['company_name'] }}</span><span>Proof approved before printing</span></div>
</footer>
