<x-layouts.storefront
    title="Order received"
    description="Your Okina Craft order has been received."
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    robots="noindex, nofollow"
    page="order-confirmation"
>
    <article class="sf-confirmation">
        <div class="sf-success-mark" aria-hidden="true"><x-storefront.icon name="check" /></div>
        <p class="sf-eyebrow">Order received</p>
        <h1>Thank you. We’ve got it.</h1>
        <p>Your choices, artwork, and delivery details are now connected to order <strong>{{ $order['public_id'] }}</strong>.</p>
        <div class="sf-confirmation-status" role="status">Order: <strong>{{ str($order['status'])->replace('_', ' ')->headline() }}</strong><span aria-hidden="true">·</span> Payment: <strong>{{ str($order['payment_status'])->replace('_', ' ')->headline() }}</strong></div>
        <div class="sf-next-steps"><article><span>01</span><h2>Payment check</h2><p>We confirm the payment provider response and attach it to your order.</p></article><article><span>02</span><h2>Artwork review</h2><p>Our team checks artwork and placement before anything enters production.</p></article><article><span>03</span><h2>Progress updates</h2><p>Production and shipping milestones appear in your account.</p></article></div>
        <div class="sf-button-row sf-confirmation-actions"><a class="sf-button" href="{{ route('customer.orders.show', ['order' => $order['public_id']]) }}">View order details</a><a class="sf-button sf-button-outline" href="{{ route('storefront.categories.index') }}">Keep browsing</a></div>
    </article>
</x-layouts.storefront>
