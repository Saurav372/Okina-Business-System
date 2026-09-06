@props([
    'cart' => null,
    'money' => null,
])

@php
    $items = data_get($cart, 'items', []);
    $itemCount = (int) data_get($cart, 'item_count', 0);
    $subtotal = data_get($cart, 'pricing.subtotal_amount_minor', 0);
    $currency = data_get($cart, 'pricing.currency', 'INR');
    
    // Free shipping benchmark (e.g. ₹999 threshold)
    $freeShippingThreshold = 99900;
    $remainingForFreeShipping = max(0, $freeShippingThreshold - (int) $subtotal);
    $progressPercent = min(100, round(((int) $subtotal / $freeShippingThreshold) * 100));
@endphp

<div id="cart-drawer" class="sf-drawer-backdrop" aria-hidden="true" data-cart-drawer>
    <div class="sf-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="drawer-heading" tabindex="-1">
        <!-- Drawer Header -->
        <div class="sf-drawer-header">
            <div class="sf-drawer-title-group">
                <h2 id="drawer-heading">Your Bag</h2>
                <span class="sf-drawer-badge" data-drawer-count>({{ $itemCount }})</span>
            </div>
            <button type="button" class="sf-drawer-close" aria-label="Close cart drawer" data-drawer-close>
                <x-storefront.icon name="close" />
            </button>
        </div>

        <!-- Free Shipping Meter -->
        <div class="sf-drawer-meter-box">
            @if($remainingForFreeShipping > 0 && $itemCount > 0)
                <p class="sf-meter-text">
                    Add <strong>₹{{ number_format($remainingForFreeShipping / 100, 0) }}</strong> more for <span>FREE SHIPPING</span>
                </p>
            @elseif($itemCount > 0)
                <p class="sf-meter-text sf-meter-unlocked">
                    <x-storefront.icon name="check" /> You have unlocked <strong>FREE PAN-INDIA SHIPPING</strong>!
                </p>
            @else
                <p class="sf-meter-text">Free shipping on orders above ₹999</p>
            @endif
            <div class="sf-meter-track" role="progressbar" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100">
                <div class="sf-meter-fill" style="width: {{ $progressPercent }}%;"></div>
            </div>
        </div>

        <!-- Line Items Content -->
        <div class="sf-drawer-body">
            @if($itemCount === 0)
                <div class="sf-drawer-empty">
                    <div class="sf-drawer-empty-icon">
                        <x-storefront.icon name="bag" />
                    </div>
                    <h3>Your bag is empty</h3>
                    <p>Start exploring our premium blank apparel and customize with your artwork.</p>
                    <a href="{{ route('storefront.categories.index') }}" class="sf-button" data-drawer-close>
                        Explore Collections <x-storefront.icon name="arrow" />
                    </a>
                </div>
            @else
                <div class="sf-drawer-items" data-drawer-items-list>
                    @foreach($items as $item)
                        @php
                            $customization = $item['customization'] ?? [];
                            $product = $item['product'] ?? [];
                            $pricing = $item['pricing'] ?? [];
                        @endphp
                        <article class="sf-drawer-item" data-drawer-item="{{ $item['id'] }}">
                            <div class="sf-drawer-item-thumb">
                                <a href="{{ route('storefront.products.show', ['product' => $product['slug']]) }}">
                                    @if(data_get($product, 'cover_image.url'))
                                        <img src="{{ data_get($product, 'cover_image.url') }}" alt="{{ $product['name'] }}" width="80" height="80" loading="lazy">
                                    @else
                                        <span class="sf-drawer-thumb-fallback">{{ strtoupper(substr($product['name'] ?? 'OK', 0, 2)) }}</span>
                                    @endif
                                </a>
                            </div>
                            <div class="sf-drawer-item-details">
                                <div class="sf-drawer-item-top">
                                    <h4 class="sf-drawer-item-title">
                                        <a href="{{ route('storefront.products.show', ['product' => $product['slug']]) }}">{{ $product['name'] }}</a>
                                    </h4>
                                    <form method="post" action="{{ route('storefront.cart.destroy', ['cartItem' => $item['id']]) }}" class="sf-drawer-remove-form">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="sf-drawer-item-remove" aria-label="Remove {{ $product['name'] }}" title="Remove">
                                            &times;
                                        </button>
                                    </form>
                                </div>

                                <!-- Customization Snapshot -->
                                @if(data_get($customization, 'selected_options_snapshot'))
                                    <div class="sf-drawer-item-options">
                                        @foreach(data_get($customization, 'selected_options_snapshot', []) as $opt)
                                            <span class="sf-drawer-opt-tag">
                                                {{ $opt['option_name'] ?? 'Option' }}: <strong>{{ $opt['value_label'] ?? $opt['value_code'] ?? '-' }}</strong>
                                            </span>
                                        @endforeach
                                        @if(data_get($customization, 'print_position'))
                                            <span class="sf-drawer-opt-tag sf-drawer-opt-print">
                                                Print: <strong>{{ str(data_get($customization, 'print_position'))->replace('_', ' ')->headline() }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                <!-- Stepper & Price Row -->
                                <div class="sf-drawer-item-footer">
                                    <form method="post" action="{{ route('storefront.cart.update', ['cartItem' => $item['id']]) }}" class="sf-drawer-qty-stepper">
                                        @csrf
                                        @method('put')
                                        <button type="button" class="sf-qty-btn" data-step="-1" aria-label="Decrease quantity">−</button>
                                        <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="9999" class="sf-qty-input" aria-label="Item quantity">
                                        <button type="button" class="sf-qty-btn" data-step="1" aria-label="Increase quantity">+</button>
                                    </form>
                                    <span class="sf-drawer-item-price">
                                        {{ $money ? $money->format($pricing['line_total_minor'] ?? 0, $pricing['currency'] ?? $currency) : '₹'.number_format(($pricing['line_total_minor'] ?? 0) / 100, 2) }}
                                    </span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Drawer Footer with Trust & Checkout -->
        @if($itemCount > 0)
            <div class="sf-drawer-footer">
                <!-- Proof Reassurance Badge -->
                <div class="sf-drawer-trust-badge">
                    <span class="sf-drawer-trust-icon">🛡️</span>
                    <div>
                        <strong>Free Digital Proof Guarantee</strong>
                        <p>You will inspect & approve high-res 3D digital proof before production.</p>
                    </div>
                </div>

                <!-- Subtotal Row -->
                <div class="sf-drawer-summary-row">
                    <span class="sf-drawer-subtotal-label">Subtotal <small>(Taxes calculated at checkout)</small></span>
                    <span class="sf-drawer-subtotal-val">
                        {{ $money ? $money->format($subtotal, $currency) : '₹'.number_format($subtotal / 100, 2) }}
                    </span>
                </div>

                <!-- Action Buttons -->
                <div class="sf-drawer-actions">
                    <a href="{{ route('storefront.checkout') }}" class="sf-button sf-drawer-checkout-btn">
                        <span>PROCEED TO CHECKOUT</span>
                        <x-storefront.icon name="arrow" />
                    </a>
                    <a href="{{ route('storefront.cart') }}" class="sf-drawer-view-cart">
                        View Detailed Bag Page
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
