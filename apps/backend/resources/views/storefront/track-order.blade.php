<x-layouts.storefront
    title="Track your order"
    description="Check production and delivery progress for an Okina Craft order."
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    robots="noindex, nofollow"
    page="track-order"
>
    <div class="sf-tracking-page">
        <header><p class="sf-eyebrow">Order progress</p><h1>Track what happens next.</h1><p>Enter your order number. For privacy, you’ll need to be signed into the account that placed it.</p></header>
        <form class="sf-tracking-form" method="get" action="{{ route('storefront.track-order') }}">
            <label for="order-id">Order number</label><div><input id="order-id" name="order_id" value="{{ $orderId }}" placeholder="For example: ORD-…" spellcheck="false" required><button class="sf-button" type="submit">Track order</button></div>
        </form>
        @if($notFound)
            <div class="sf-notice sf-notice-warning" role="status"><strong>Order not found</strong><p>Check the number and make sure you are signed into the account that placed it.</p></div>
        @elseif($order)
            <section class="sf-tracking-result" aria-labelledby="tracking-result-heading">
                <div><p class="sf-eyebrow">Order</p><h2 id="tracking-result-heading">{{ $order['public_id'] }}</h2><span>{{ str($order['status'])->replace('_', ' ')->headline() }}</span></div>
                <ol class="sf-timeline">@foreach($order['timeline'] as $event)<li @class(['is-complete' => (bool) ($event['completed'] ?? false)])><span aria-hidden="true"></span><div><strong>{{ $event['label'] ?? str($event['status'] ?? 'Update')->headline() }}</strong>@if($event['timestamp'] ?? null)<small>{{ \Illuminate\Support\Carbon::parse($event['timestamp'])->format('d M Y') }}</small>@endif</div></li>@endforeach</ol>
                <a class="sf-text-link" href="{{ route('customer.orders.show', ['order' => $order['public_id']]) }}">View full order details <x-storefront.icon name="arrow" /></a>
            </section>
        @endif
    </div>
</x-layouts.storefront>
