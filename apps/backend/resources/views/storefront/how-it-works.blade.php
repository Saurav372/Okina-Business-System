@php
    $steps = [
        ['01', 'Choose the product', 'Start with the garment, colour, size mix, quantity, and print-ready options that suit your order.'],
        ['02', 'Place your artwork', 'Upload a clear PNG and position it on the available print area to create a protected preview.'],
        ['03', 'Review the order', 'Confirm the item details, delivery address, and payment route before submitting the order.'],
        ['04', 'Approve the proof', 'Our artwork team prepares a final proof. Production begins only after the details are approved.'],
        ['05', 'Track delivery', 'Follow the order as it moves from production to shipping and delivery across India.'],
    ];
    $details = [
        ['Artwork requirements', 'Clear PNG, PDF, SVG, AI, or other high-resolution files usually produce the best result. If a file needs attention, the artwork check catches it before production.'],
        ['Print placement', 'Choose the available position for each design—front, back, or sleeve—and use Create your design to preview the visual balance before ordering.'],
        ['Proof approval', 'The proof confirms the garment, artwork, size, position, and production setup. Review every detail because production follows the approved proof.'],
        ['Production', 'Production time depends on the product, quantity, print method, artwork readiness, and current workshop schedule. Larger or mixed-size orders may need extra preparation.'],
        ['Shipping', 'Delivery timing is confirmed from the final order and destination. Once shipped, the tracking reference stays available from Track Order.'],
    ];
@endphp

<x-layouts.storefront
    title="How custom apparel printing works"
    description="Learn how Okina Craft handles artwork, print placement, proof approval, production, pricing, and delivery for custom apparel orders in India."
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    page="how-it-works"
>
    <nav class="sf-breadcrumbs" aria-label="Breadcrumbs"><a href="{{ route('storefront.home') }}">Home</a><span aria-hidden="true">/</span><span aria-current="page">How it works</span></nav>
    <header class="sf-flow-heading">
        <div><p class="sf-eyebrow">A clear custom process</p><h1>From your idea to finished apparel.</h1></div>
        <div><p>Choose the product, place your artwork, approve the proof, and follow production through delivery.</p><div class="sf-button-row"><a class="sf-button" href="{{ route('storefront.categories.index') }}">Choose a product</a><a class="sf-button sf-button-outline" href="{{ route('storefront.mockup') }}">Create your design</a></div></div>
    </header>
    <section class="sf-section sf-process-page" aria-labelledby="process-heading">
        <div class="sf-section-heading"><div><p class="sf-eyebrow">Five calm steps</p><h2 id="process-heading">You always know what happens next.</h2></div></div>
        <ol class="sf-process-list">
            @foreach($steps as [$number, $title, $copy])
                <li><span>{{ $number }}</span><div><h3>{{ $title }}</h3><p>{{ $copy }}</p></div></li>
            @endforeach
        </ol>
    </section>
    <section class="sf-process-details" aria-labelledby="details-heading">
        <div><p class="sf-eyebrow">Before production</p><h2 id="details-heading">The details that protect your order.</h2><p>Custom printing works best when the artwork, placement, and expectations are agreed before anything reaches the press.</p></div>
        <div>
            @foreach($details as [$title, $copy])
                <article><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h3>{{ $title }}</h3><p>{{ $copy }}</p></div></article>
            @endforeach
        </div>
    </section>
    <section class="sf-section" aria-labelledby="pricing-heading">
        <div class="sf-section-heading"><div><p class="sf-eyebrow">Custom apparel pricing</p><h2 id="pricing-heading">What affects your price?</h2></div></div>
        <div class="sf-factor-grid">
            <article><strong>Garment</strong><p>T-shirt, polo, hoodie, workwear, fabric weight, and finish set the starting cost.</p></article>
            <article><strong>Quantity</strong><p>Larger orders can unlock better per-piece pricing because setup is shared across more garments.</p></article>
            <article><strong>Print setup</strong><p>Print method, artwork size, number of colours, and placement affect production.</p></article>
            <article><strong>Delivery</strong><p>Destination, package size, and required delivery speed affect the final shipping amount.</p></article>
        </div>
    </section>
</x-layouts.storefront>
