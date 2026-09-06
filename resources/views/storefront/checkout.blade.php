<x-layouts.storefront
    title="Secure checkout"
    description="Confirm delivery and payment details for your Okina Craft order."
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    robots="noindex, nofollow"
    page="checkout"
>
    <nav class="sf-breadcrumbs" aria-label="Breadcrumbs"><a href="{{ route('storefront.cart') }}">Bag</a><span aria-hidden="true">/</span><span aria-current="page">Checkout</span></nav>
    <header class="sf-flow-heading">
        <div><p class="sf-eyebrow">Secure checkout · step 02 of 03</p><h1>Confirm delivery.</h1></div>
        <ol class="sf-checkout-progress" aria-label="Checkout progress"><li>Bag complete</li><li aria-current="step">Delivery</li><li>Payment next</li></ol>
    </header>
    <div class="sf-flow-shell">
        @if($errors->any())<div class="sf-form-errors" role="alert" tabindex="-1" data-error-summary><strong>Please check the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @if(session('bulk_handoff'))<div class="sf-notice sf-notice-warning" role="status"><strong>Bulk quotation required</strong><p>{{ session('bulk_handoff') }} Contact the team to confirm pricing and production timing.</p></div>@endif

        @if(count($cart['items']) > 0)
            <details class="sf-disclosure" @if(count($addresses) === 0) open @endif><summary>Add a new delivery address</summary><p class="sf-address-help">The address is saved to your account. Return here after saving to continue checkout.</p><x-storefront.address-form :action="route('customer.addresses.store')" id-prefix="checkout-address" /></details>
        @endif

        @if(count($cart['items']) === 0)
            <x-storefront.state-panel title="Your bag is empty" message="Add a product before starting checkout." action-label="Browse products" :action-url="route('storefront.categories.index')" />
        @else
            <form class="sf-checkout-layout" method="post" action="{{ route('storefront.checkout.store') }}">
                @csrf
                <div>
                    <section class="sf-checkout-section" aria-labelledby="delivery-title">
                        <div class="sf-checkout-section-head"><span>01</span><div><h2 id="delivery-title">Delivery address</h2><p>Choose where this order should be sent.</p></div></div>
                        @if(count($addresses) > 0)
                            <fieldset class="sf-address-choices"><legend class="sf-sr-only">Shipping address</legend>@foreach($addresses as $address)<label><input type="radio" name="shipping_address_id" value="{{ $address['id'] }}" @checked((string) old('shipping_address_id', $defaultShippingId) === (string) $address['id']) required><span><strong>{{ $address['label'] }}</strong><small>{{ $address['contact_name'] }} · {{ $address['address_line_1'] }}, {{ $address['city'] }} {{ $address['postal_code'] }}</small></span></label>@endforeach</fieldset>
                        @else
                            <p class="sf-notice sf-notice-warning">Add an address before placing the order.</p>
                        @endif
                    </section>

                    @if(count($addresses) > 0)
                        <section class="sf-checkout-section" aria-labelledby="billing-title">
                            <div class="sf-checkout-section-head"><span>02</span><div><h2 id="billing-title">Billing address</h2><p>Use delivery details or choose another saved address.</p></div></div>
                            <fieldset class="sf-address-choices"><legend class="sf-sr-only">Billing address</legend><label><input type="radio" name="billing_address_id" value="" @checked(old('billing_address_id', $defaultBillingId) === null)><span><strong>Same as delivery</strong><small>Recommended for personal orders</small></span></label>@foreach($addresses as $address)<label><input type="radio" name="billing_address_id" value="{{ $address['id'] }}" @checked((string) old('billing_address_id', $defaultBillingId) === (string) $address['id'])><span><strong>{{ $address['label'] }}</strong><small>{{ $address['address_line_1'] }}, {{ $address['city'] }}</small></span></label>@endforeach</fieldset>
                        </section>
                    @endif

                    <section class="sf-checkout-section" aria-labelledby="payment-title">
                        <div class="sf-checkout-section-head"><span>03</span><div><h2 id="payment-title">Secure payment</h2><p>You’ll continue to the configured payment provider after this review.</p></div></div>
                        @if($onlinePaymentsEnabled)<div class="sf-payment-method"><x-storefront.icon name="check" /><span><strong>Online payment</strong><small>UPI, cards, and supported payment methods</small></span><b>Secure</b></div>@else<div class="sf-notice sf-notice-warning" role="status">Online checkout is temporarily unavailable. Your bag remains saved.</div>@endif
                    </section>
                </div>

                <aside class="sf-order-summary" aria-labelledby="review-title">
                    <p class="sf-eyebrow">Order review</p><h2 id="review-title">{{ $cart['item_count'] }} {{ $cart['item_count'] === 1 ? 'item' : 'items' }}</h2>
                    <ul class="sf-review-items">@foreach($cart['items'] as $item)<li><span><strong>{{ $item['product']['name'] }}</strong><small>{{ $item['quantity'] }} × {{ $money->format($item['pricing']['unit_price_minor'], $item['pricing']['currency']) }}</small></span><b>{{ $money->format($item['pricing']['line_total_minor'], $item['pricing']['currency']) }}</b></li>@endforeach</ul>
                    <dl><div><dt>Subtotal</dt><dd>{{ $money->format($cart['pricing']['subtotal_amount_minor'], $cart['pricing']['currency']) }}</dd></div><div><dt>Discount</dt><dd>{{ $money->format($cart['pricing']['discount_amount_minor'], $cart['pricing']['currency']) }}</dd></div><div><dt>Shipping</dt><dd>{{ $money->format($cart['pricing']['shipping_amount_minor'], $cart['pricing']['currency']) }}</dd></div><div><dt>Tax</dt><dd>{{ $money->format($cart['pricing']['tax_amount_minor'], $cart['pricing']['currency']) }}</dd></div><div class="sf-summary-total"><dt>Total</dt><dd>{{ $money->format($cart['pricing']['total_amount_minor'], $cart['pricing']['currency']) }}</dd></div></dl>
                    @if($onlinePaymentsEnabled && count($addresses) > 0)<button class="sf-button" type="submit">Validate and continue <x-storefront.icon name="arrow" /></button>@endif
                    <p class="sf-legal-copy">By continuing, you agree to the <a href="{{ route('storefront.policy', ['slug' => 'terms']) }}">terms</a> and acknowledge the custom-order approval process.</p>
                </aside>
            </form>
        @endif
    </div>
</x-layouts.storefront>
