<x-layouts.storefront
    title="Your account"
    description="Manage your profile, saved addresses, and Okina Craft orders."
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    robots="noindex, nofollow"
    page="account"
>
    <nav class="sf-breadcrumbs" aria-label="Breadcrumbs"><a href="{{ route('storefront.home') }}">Home</a><span aria-hidden="true">/</span><span aria-current="page">Account</span></nav>
    <header class="sf-account-hero">
        <div><p class="sf-eyebrow">Customer account</p><h1>Welcome back, {{ $profile['display_name'] ?: $profile['name'] }}.</h1><p>Keep delivery details current and follow every custom order from payment to proof and dispatch.</p></div>
        <form method="post" action="{{ route('customer.logout') }}">@csrf<button class="sf-button sf-button-outline" type="submit">Sign out</button></form>
    </header>
    <div class="sf-account-shell">
        @if(session('status'))<p class="sf-notice sf-notice-success" role="status">{{ session('status') }}</p>@endif
        @if($errors->any())
            <div class="sf-form-errors" role="alert" tabindex="-1" data-error-summary><strong>Please check the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <section class="sf-account-section" aria-labelledby="profile-heading">
            <div class="sf-account-section-heading"><p class="sf-eyebrow">Profile</p><h2 id="profile-heading">Account details</h2></div>
            <dl class="sf-fact-grid">
                <div><dt>Name</dt><dd>{{ $profile['display_name'] ?: $profile['name'] }}</dd></div>
                <div><dt>Email</dt><dd>{{ $profile['email'] }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $profile['phone'] ?: 'Not added' }}</dd></div>
                <div><dt>Company</dt><dd>{{ $profile['company_name'] ?: 'Personal account' }}</dd></div>
            </dl>
        </section>

        <section class="sf-account-section" aria-labelledby="addresses-heading">
            <div class="sf-account-section-heading"><p class="sf-eyebrow">Delivery</p><h2 id="addresses-heading">Saved addresses</h2></div>
            <details class="sf-disclosure" @if($errors->any()) open @endif><summary>Add a new address</summary><x-storefront.address-form :action="route('customer.addresses.store')" id-prefix="new-address" /></details>
            @if(count($addresses) > 0)
                <div class="sf-address-grid">
                    @foreach($addresses as $address)
                        <article class="sf-address-card">
                            <div class="sf-address-card-head"><div><p class="sf-eyebrow">{{ $address['address_type'] }}</p><h3>{{ $address['label'] }}</h3></div><div>@if($address['is_default_shipping'])<span>Default shipping</span>@endif @if($address['is_default_billing'])<span>Default billing</span>@endif</div></div>
                            <address><strong>{{ $address['contact_name'] }}</strong><br>{{ $address['address_line_1'] }}@if($address['address_line_2'])<br>{{ $address['address_line_2'] }}@endif<br>{{ $address['city'] }}, {{ $address['state'] }} {{ $address['postal_code'] }}<br>{{ $address['phone'] }}</address>
                            <div class="sf-address-actions">
                                @if(! $address['is_default_shipping'] || ! $address['is_default_billing'])
                                    <form method="post" action="{{ route('customer.addresses.default', ['address' => $address['id']]) }}">@csrf<input type="hidden" name="type" value="both"><button class="sf-link-button" type="submit">Use as default</button></form>
                                @endif
                                <form method="post" action="{{ route('customer.addresses.destroy', ['address' => $address['id']]) }}">@csrf @method('delete')<button class="sf-link-button" type="submit">Remove</button></form>
                            </div>
                            <details class="sf-disclosure sf-disclosure-compact"><summary>Edit {{ $address['label'] }}</summary><x-storefront.address-form :action="route('customer.addresses.update', ['address' => $address['id']])" method="put" :address="$address" :id-prefix="'address-'.$address['id']" /></details>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="sf-empty-copy">No saved addresses yet. Add one before checkout.</p>
            @endif
        </section>

        <section class="sf-account-section" aria-labelledby="orders-heading">
            <div class="sf-account-section-heading"><p class="sf-eyebrow">History</p><h2 id="orders-heading">Your orders</h2></div>
            @if(count($orders) > 0)
                <div class="sf-order-list">
                    @foreach($orders as $order)
                        <article><div><p class="sf-eyebrow">{{ $order['placed_at'] ? \Illuminate\Support\Carbon::parse($order['placed_at'])->format('d M Y') : 'Order' }}</p><h3>{{ $order['public_id'] }}</h3></div><dl><div><dt>Total</dt><dd>{{ $money->format($order['total_amount_minor'], $order['currency']) }}</dd></div><div><dt>Payment</dt><dd>{{ str($order['payment_status'])->replace('_', ' ')->headline() }}</dd></div><div><dt>Status</dt><dd>{{ str($order['status'])->replace('_', ' ')->headline() }}</dd></div></dl><a class="sf-button sf-button-outline" href="{{ route('customer.orders.show', ['order' => $order['public_id']]) }}">View details</a></article>
                    @endforeach
                </div>
            @else
                <x-storefront.state-panel title="No orders yet" message="Your placed orders, proofs, and delivery updates will appear here." action-label="Browse collections" :action-url="route('storefront.categories.index')" />
            @endif
        </section>
    </div>
</x-layouts.storefront>
