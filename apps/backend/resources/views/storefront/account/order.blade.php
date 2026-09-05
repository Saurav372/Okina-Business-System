<x-layouts.storefront
    :title="'Order '.$order['public_id']"
    description="Review your Okina Craft order, payment, proof, production, and delivery status."
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    robots="noindex, nofollow"
    page="order"
>
    <nav class="sf-breadcrumbs" aria-label="Breadcrumbs"><a href="{{ route('customer.account') }}">Account</a><span aria-hidden="true">/</span><span aria-current="page">{{ $order['public_id'] }}</span></nav>
    <header class="sf-order-hero">
        <div><p class="sf-eyebrow">Order details</p><h1>{{ $order['public_id'] }}</h1><p>Placed {{ $order['placed_at'] ? \Illuminate\Support\Carbon::parse($order['placed_at'])->format('d M Y, g:i a') : 'recently' }}</p></div>
        <div class="sf-status-stack"><span><small>Order</small>{{ str($order['status'])->replace('_', ' ')->headline() }}</span><span><small>Payment</small>{{ str($order['payment_status'])->replace('_', ' ')->headline() }}</span></div>
    </header>
    <div class="sf-order-shell">
        @if($errors->has('reorder'))<p class="sf-notice sf-notice-warning" role="alert">{{ $errors->first('reorder') }}</p>@endif
        <div class="sf-order-columns">
            <div>
                <section class="sf-account-section" aria-labelledby="timeline-heading">
                    <div class="sf-account-section-heading"><p class="sf-eyebrow">Progress</p><h2 id="timeline-heading">What happens next</h2></div>
                    <ol class="sf-timeline">
                        @foreach($order['timeline'] as $event)
                            <li @class(['is-complete' => (bool) ($event['completed'] ?? false)])><span aria-hidden="true"></span><div><strong>{{ $event['label'] ?? str($event['status'] ?? 'Update')->headline() }}</strong>@if($event['timestamp'] ?? null)<small>{{ \Illuminate\Support\Carbon::parse($event['timestamp'])->format('d M Y, g:i a') }}</small>@endif</div></li>
                        @endforeach
                    </ol>
                </section>

                @if(count($order['proofs']) > 0)
                    <section class="sf-account-section" aria-labelledby="proofs-heading">
                        <div class="sf-account-section-heading"><p class="sf-eyebrow">Prepared by our artwork team</p><h2 id="proofs-heading">Your artwork proofs</h2></div>
                        <div class="sf-proof-grid">
                            @foreach($order['proofs'] as $proof)
                                <article>
                                    @if(str_starts_with($proof['mime_type'] ?? '', 'image/'))<img src="{{ $proof['preview_url'] }}" alt="{{ $proof['display_name'] }}">@else<div class="sf-proof-file" aria-hidden="true">PDF</div>@endif
                                    <div><h3>{{ $proof['display_name'] }}</h3>@if($proof['notes'])<p>{{ $proof['notes'] }}</p>@endif<div class="sf-button-row"><a class="sf-button sf-button-outline" href="{{ $proof['preview_url'] }}" target="_blank" rel="noopener">Open proof</a><a class="sf-button" href="{{ $proof['download_url'] }}">Download</a></div></div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="sf-account-section" aria-labelledby="items-heading">
                    <div class="sf-account-section-heading"><p class="sf-eyebrow">Garments and artwork</p><h2 id="items-heading">Order items</h2></div>
                    <div class="sf-order-items">
                        @foreach($order['items'] as $item)
                            <article><div><h3>{{ $item['product_name'] }}</h3><p>SKU {{ $item['sku_code'] }}</p>@if(data_get($item, 'customization_snapshot.print_position'))<p>{{ str(data_get($item, 'customization_snapshot.print_position'))->replace('_', ' ')->headline() }} · {{ str(data_get($item, 'customization_snapshot.print_method', ''))->upper() }}</p>@endif</div><div><strong>{{ $item['quantity'] }} × {{ $money->format($item['unit_price_minor'], $order['currency']) }}</strong><span>{{ $money->format($item['line_total_minor'], $order['currency']) }}</span></div></article>
                        @endforeach
                    </div>
                    <form method="post" action="{{ route('customer.orders.reorder', ['order' => $order['public_id']]) }}">@csrf<button class="sf-button sf-button-outline" type="submit">Add available items to bag</button></form>
                </section>
            </div>

            <aside>
                <section class="sf-order-summary" aria-labelledby="price-heading"><p class="sf-eyebrow">Price snapshot</p><h2 id="price-heading">Order total</h2><dl><div><dt>Subtotal</dt><dd>{{ $money->format($order['subtotal_amount_minor'], $order['currency']) }}</dd></div><div><dt>Discount</dt><dd>{{ $money->format($order['discount_amount_minor'], $order['currency']) }}</dd></div><div><dt>Shipping</dt><dd>{{ $money->format($order['shipping_amount_minor'], $order['currency']) }}</dd></div><div><dt>Tax</dt><dd>{{ $money->format($order['tax_amount_minor'], $order['currency']) }}</dd></div><div class="sf-summary-total"><dt>Total</dt><dd>{{ $money->format($order['total_amount_minor'], $order['currency']) }}</dd></div></dl></section>
                @foreach(['shipping_address_snapshot' => 'Shipping address', 'billing_address_snapshot' => 'Billing address'] as $key => $label)
                    @php($address = $order[$key])
                    <section class="sf-side-card"><h2>{{ $label }}</h2>@if($address)<address><strong>{{ $address['contact_name'] ?? '' }}</strong><br>{{ $address['address_line_1'] ?? '' }}@if($address['address_line_2'] ?? null)<br>{{ $address['address_line_2'] }}@endif<br>{{ $address['city'] ?? '' }}, {{ $address['state'] ?? '' }} {{ $address['postal_code'] ?? '' }}<br>{{ $address['phone'] ?? '' }}</address>@else<p>Not stored.</p>@endif</section>
                @endforeach
                @if($order['tracking_number'])<section class="sf-side-card"><h2>Shipment tracking</h2><p>{{ $order['courier_name'] ?: 'Courier' }}<br><strong>{{ $order['tracking_number'] }}</strong></p>@if($order['tracking_url'])<a class="sf-text-link" href="{{ $order['tracking_url'] }}" target="_blank" rel="noopener">Open courier tracking</a>@endif</section>@endif
            </aside>
        </div>
    </div>
</x-layouts.storefront>
