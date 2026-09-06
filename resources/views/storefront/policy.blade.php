<x-layouts.storefront
    :title="$policy['title']"
    :description="$policy['intro']"
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    page="policy"
>
    <nav class="sf-breadcrumbs" aria-label="Breadcrumbs"><a href="{{ route('storefront.home') }}">Home</a><span aria-hidden="true">/</span><span aria-current="page">{{ $policy['title'] }}</span></nav>
    <article class="sf-policy-page">
        <p class="sf-eyebrow">Good to know</p>
        <h1>{{ $policy['title'] }}</h1>
        <p class="sf-policy-lede">{{ $policy['intro'] }}</p>
        <div class="sf-policy-sections">
            @foreach($policy['sections'] as [$title, $copy])
                <section><h2>{{ $title }}</h2><p>{{ $copy }}</p></section>
            @endforeach
        </div>
        <p class="sf-notice sf-notice-warning">This storefront summary is designed for customer clarity. Business-specific legal text should be reviewed before public launch.</p>
    </article>
</x-layouts.storefront>
