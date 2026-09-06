<x-layouts.storefront
    title="Your bag"
    description="Review your Okina Craft custom products before checkout."
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    robots="noindex, nofollow"
    page="cart"
>
    <nav class="sf-breadcrumbs" aria-label="Breadcrumbs">
        <a href="{{ route('storefront.home') }}">Home</a><span aria-hidden="true">/</span><span aria-current="page">Bag</span>
    </nav>

    <header class="sf-flow-heading">
        <div><p class="sf-eyebrow">Bag · step 01 of 03</p><h1>Review every detail.</h1></div>
        <p>Garment, artwork, placement, and quantity stay together. Nothing moves to production until the proof is approved.</p>
    </header>

    <div class="sf-flow-shell">
        @if(session('status'))
            <p class="sf-notice sf-notice-success" role="status">{{ session('status') }}</p>
        @endif

        @if(count($cart['items']) === 0)
            <x-storefront.state-panel title="Your bag is ready for an idea" message="Choose a style, add your artwork, and it will appear here." action-label="Start customizing" :action-url="route('storefront.categories.index')" />
        @else
            <div class="sf-cart-layout">
                <section aria-labelledby="cart-items-title">
                    <div class="sf-list-heading">
                        <h2 id="cart-items-title">Items <span>({{ $cart['item_count'] }})</span></h2>
                        <a class="sf-text-link" href="{{ route('storefront.categories.index') }}">Continue shopping</a>
                    </div>

                    @if(! $validation['valid'])
                        <div class="sf-notice sf-notice-warning" role="alert">
                            <strong>One or more items need attention before checkout.</strong>
                            <p>Review the highlighted product options or remove the unavailable item.</p>
                        </div>
                    @endif

                    <div class="sf-cart-items">
                        @foreach($cart['items'] as $item)
                            @php
                                $itemValidation = collect($validation['items'])->firstWhere('id', $item['id']);
                                $customization = $item['customization'] ?? [];
                            @endphp
                            <article class="sf-cart-item" @if($itemValidation && ! $itemValidation['valid']) data-invalid="true" @endif>
                                <a class="sf-cart-visual" href="{{ route('storefront.products.show', ['product' => $item['product']['slug']]) }}" aria-label="View {{ $item['product']['name'] }}">
                                    <span>{{ str($item['product']['name'])->substr(0, 2)->upper() }}</span>
                                </a>
                                <div class="sf-cart-copy">
                                    <p class="sf-eyebrow">Customized item {{ $loop->iteration }}</p>
                                    <h3><a href="{{ route('storefront.products.show', ['product' => $item['product']['slug']]) }}">{{ $item['product']['name'] }}</a></h3>
                                    <p class="sf-cart-meta">SKU {{ $item['sku']['code'] }}</p>
                                    @if(data_get($customization, 'selected_options_snapshot'))
                                        <ul class="sf-cart-options">
                                            @foreach(data_get($customization, 'selected_options_snapshot', []) as $option)
                                                <li>{{ $option['option_name'] ?? str($option['option_code'] ?? 'Option')->headline() }}: <strong>{{ $option['value_label'] ?? $option['value_code'] ?? 'Selected' }}</strong></li>
                                            @endforeach
                                            @if(data_get($customization, 'print_position'))
                                                <li>Print: <strong>{{ str(data_get($customization, 'print_position'))->replace('_', ' ')->headline() }} · {{ str(data_get($customization, 'print_method', ''))->replace('_', ' ')->upper() }}</strong></li>
                                            @endif
                                        </ul>
                                    @endif
                                    @if($itemValidation && ! $itemValidation['valid'])
                                        <p class="sf-field-error">This item is no longer ready for checkout. Open it to choose an available combination.</p>
                                    @endif
                                </div>
                                <div class="sf-cart-actions">
                                    <strong>{{ $money->format($item['pricing']['line_total_minor'] ?? 0, $item['pricing']['currency'] ?? 'INR') }}</strong>
                                    <small>{{ $money->format($item['pricing']['unit_price_minor'] ?? 0, $item['pricing']['currency'] ?? 'INR') }} each</small>
                                    <form method="post" action="{{ route('storefront.cart.update', ['cartItem' => $item['id']]) }}">
                                        @csrf
                                        @method('put')
                                        <label for="quantity-{{ $item['id'] }}">Quantity</label>
                                        <div><input id="quantity-{{ $item['id'] }}" name="quantity" type="number" min="1" max="9999" value="{{ $item['quantity'] }}" required><button class="sf-button sf-button-outline" type="submit">Update</button></div>
                                    </form>
                                    <form method="post" action="{{ route('storefront.cart.destroy', ['cartItem' => $item['id']]) }}">
                                        @csrf
                                        @method('delete')
                                        <button class="sf-link-button" type="submit">Remove {{ $item['product']['name'] }}</button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <aside class="sf-order-summary" aria-labelledby="summary-title">
                    <p class="sf-eyebrow">Final review comes next</p>
                    <h2 id="summary-title">Order summary</h2>
                    <dl>
                        <div><dt>Subtotal</dt><dd>{{ $money->format($cart['pricing']['subtotal_amount_minor'], $cart['pricing']['currency']) }}</dd></div>
                        <div><dt>Discount</dt><dd>{{ $money->format($cart['pricing']['discount_amount_minor'], $cart['pricing']['currency']) }}</dd></div>
                        <div><dt>Shipping</dt><dd>{{ $cart['pricing']['shipping_amount_minor'] ? $money->format($cart['pricing']['shipping_amount_minor'], $cart['pricing']['currency']) : 'Calculated at checkout' }}</dd></div>
                        <div><dt>Tax</dt><dd>{{ $money->format($cart['pricing']['tax_amount_minor'], $cart['pricing']['currency']) }}</dd></div>
                        <div class="sf-summary-total"><dt>Total</dt><dd>{{ $money->format($cart['pricing']['total_amount_minor'], $cart['pricing']['currency']) }}</dd></div>
                    </dl>
                    @if($validation['valid'])
                        <a class="sf-button" href="{{ route('storefront.checkout') }}">Continue to checkout <x-storefront.icon name="arrow" /></a>
                    @else
                        <p class="sf-field-help">Checkout becomes available after every item is valid.</p>
                    @endif
                    <ul class="sf-summary-trust">
                        <li>Artwork saved securely</li><li>Address checked before payment</li><li>Approval before production</li>
                    </ul>
                </aside>
            </div>
        @endif
    </div>
</x-layouts.storefront>
