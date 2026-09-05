@php
    $availableSkus = collect($customization['skus'] ?? [])->filter(fn (array $sku) => data_get($sku, 'availability.available_for_checkout', false));
    $initialSku = $availableSkus->first() ?? collect($customization['skus'] ?? [])->first();
    $initialOptions = [];
    foreach (explode('|', (string) data_get($initialSku, 'variant_key', '')) as $pair) {
        if (!str_contains($pair, ':')) continue;
        [$optionCode, $valueCode] = explode(':', $pair, 2);
        $initialOptions[$optionCode] = $valueCode;
    }
    $selectedOptions = old('selected_options', $initialOptions);
    $requiresPrint = (bool) data_get($customization, 'validation.requires_print_position', false);
    $initialPosition = old('print_position', data_get($customization, 'print_positions.0.code'));
    $compatibleMethods = data_get($customization, 'validation.print_method_compatibility.'.$initialPosition, []);
    $initialMethod = old('print_method', collect($customization['print_methods'] ?? [])->first(fn (array $method) => in_array($method['code'], $compatibleMethods, true))['code'] ?? null);
    $canPurchase = $availableSkus->isNotEmpty() && $product['direct_checkout_enabled'];
@endphp

<x-layouts.storefront
    :title="data_get($product, 'seo.title') ?: $product['name']"
    :description="data_get($product, 'seo.description') ?: ($product['short_description'] ?: 'Customize this Okina product.')"
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    :canonical="data_get($product, 'seo.canonical_url')"
    :robots="(data_get($product, 'seo.robots.index', true) ? 'index' : 'noindex').', '.(data_get($product, 'seo.robots.follow', true) ? 'follow' : 'nofollow')"
    :structured-data="$structuredData"
    page="product"
>
    <nav class="sf-breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('storefront.home') }}">Home</a><span aria-hidden="true">/</span>
        <a href="{{ route('storefront.categories.show', ['category' => data_get($product, 'category.slug')]) }}">{{ data_get($product, 'category.name', 'Shop') }}</a><span aria-hidden="true">/</span>
        <span aria-current="page">{{ $product['name'] }}</span>
    </nav>

    <section class="sf-product-detail" data-product-customizer>
        <div class="sf-product-gallery">
            <div class="sf-product-stage" data-product-stage>
                @if(data_get($product, 'cover_image.url'))
                    <img src="{{ data_get($product, 'cover_image.url') }}" alt="{{ data_get($product, 'cover_image.alt_text') ?: $product['name'] }}" width="{{ data_get($product, 'cover_image.width') ?: 900 }}" height="{{ data_get($product, 'cover_image.height') ?: 1000 }}">
                @else
                    <div class="sf-product-garment" aria-label="Illustrated preview of {{ $product['name'] }}">
                        <svg viewBox="0 0 640 700" role="img" aria-label="Garment illustration with a sample artwork position">
                            <path d="M224 67c24 38 61 57 96 57s72-19 96-57l135 79-70 122-63-35v397H222V233l-63 35-70-122 135-79Z" fill="currentColor" />
                            <path d="M224 67c22 39 60 62 96 62s74-23 96-62" fill="none" stroke="#5b5553" stroke-width="9" />
                        </svg>
                        <span class="sf-artwork-mark" data-artwork-mark>YOUR<br>MARK</span>
                    </div>
                @endif
                <p class="sf-preview-caption">Product preview <span aria-hidden="true">·</span> artwork position updates with your choices</p>
            </div>
            @if(count($product['media'] ?? []) > 1)
                <div class="sf-product-thumbnails" aria-label="Product images">
                    @foreach($product['media'] as $media)
                        <button type="button" data-product-thumbnail data-src="{{ $media['url'] }}" data-alt="{{ $media['alt_text'] ?: $product['name'] }}" @if($loop->first) aria-pressed="true" @else aria-pressed="false" @endif>
                            <img src="{{ $media['url'] }}" alt="" width="96" height="106" loading="lazy">
                            <span class="sf-sr-only">Show image {{ $loop->iteration }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="sf-product-builder">
            <p class="sf-eyebrow">Made for your idea</p>
            <h1>{{ $product['name'] }}</h1>
            <p class="sf-product-lede">{{ $product['short_description'] ?: 'Choose your options, set the artwork position, and approve a proof before production.' }}</p>
            <div class="sf-product-price">
                <strong data-customizer-price>{{ data_get($initialSku, 'display_price', $product['display_price']) }}</strong>
                @if($product['display_compare_at_price'])<del>{{ $product['display_compare_at_price'] }}</del>@endif
                <span>per piece</span>
            </div>
            <ul class="sf-proof-points" aria-label="Order assurances">
                <li><x-storefront.icon name="check" /> Free digital proof</li>
                <li><x-storefront.icon name="check" /> No production before approval</li>
                <li><x-storefront.icon name="check" /> Secure checkout</li>
            </ul>

            @if($errors->any())
                <div class="sf-form-errors" role="alert" tabindex="-1" data-error-summary>
                    <strong>Please check your choices.</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="post" action="{{ route('storefront.products.cart.store', ['product' => $product['slug']]) }}" class="sf-customizer-form" data-customizer-form>
                @csrf
                <input type="hidden" name="sku_code" value="{{ old('sku_code', data_get($initialSku, 'sku_code')) }}" data-sku-code>

                @foreach($customization['option_groups'] ?? [] as $group)
                    <fieldset class="sf-option-group" data-option-group="{{ $group['code'] }}">
                        <legend><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span> Choose {{ Str::lower($group['name']) }}</legend>
                        <div class="sf-choice-grid sf-choice-grid-{{ $group['display_type'] }}">
                            @foreach($group['values'] as $value)
                                @if($value['is_active'])
                                    <label>
                                        <input type="radio" name="selected_options[{{ $group['code'] }}]" value="{{ $value['code'] }}" @checked(data_get($selectedOptions, $group['code']) === $value['code']) @if($group['is_required']) required @endif>
                                        <span>{{ $value['label'] }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                @if($requiresPrint)
                    <fieldset class="sf-option-group">
                        <legend><span>{{ str_pad((string) (count($customization['option_groups'] ?? []) + 1), 2, '0', STR_PAD_LEFT) }}</span> Place your artwork</legend>
                        <div class="sf-position-grid">
                            @foreach($customization['print_positions'] as $position)
                                <label>
                                    <input type="radio" name="print_position" value="{{ $position['code'] }}" @checked($initialPosition === $position['code']) required>
                                    <span><b aria-hidden="true"></b>{{ $position['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        <label class="sf-field-label" for="print-method">Print method</label>
                        <select id="print-method" name="print_method" required data-print-method>
                            @foreach($customization['print_methods'] as $method)
                                <option value="{{ $method['code'] }}" @selected($initialMethod === $method['code'])>{{ $method['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="sf-field-help" data-method-help>We’ll only show methods compatible with your selected placement.</p>
                    </fieldset>
                @endif

                <fieldset class="sf-option-group">
                    <legend><span>{{ str_pad((string) (count($customization['option_groups'] ?? []) + ($requiresPrint ? 2 : 1)), 2, '0', STR_PAD_LEFT) }}</span> Set quantity</legend>
                    <div class="sf-quantity-row">
                        <label for="quantity">Pieces</label>
                        <input id="quantity" type="number" name="quantity" min="{{ max(1, $product['min_order_quantity']) }}" @if($product['max_order_quantity']) max="{{ $product['max_order_quantity'] }}" @endif step="1" value="{{ old('quantity', max(1, $product['min_order_quantity'])) }}" required data-quantity-input>
                        <div class="sf-quantity-presets" aria-label="Quick quantity choices">
                            @foreach([1, 5, 10, 20] as $quantity)
                                @if($quantity >= max(1, $product['min_order_quantity']) && (!$product['max_order_quantity'] || $quantity <= $product['max_order_quantity']))
                                    <button type="button" data-quantity="{{ $quantity }}">{{ $quantity }}</button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @if($product['bulk_threshold_quantity'])<p class="sf-field-help">Orders from {{ $product['bulk_threshold_quantity'] }} pieces are reviewed for bulk pricing.</p>@endif
                </fieldset>

                @if($requiresPrint)
                    <div class="sf-artwork-path">
                        <div><p class="sf-eyebrow">Artwork</p><h2>Already have a file?</h2><p>Use the full studio to upload artwork and fine-tune placement. Or add instructions below and send the artwork after ordering.</p></div>
                        <a class="sf-text-link" href="{{ route('storefront.mockup', ['product' => $product['slug']]) }}">Open artwork studio <x-storefront.icon name="arrow" /></a>
                    </div>
                @endif

                <label class="sf-note-field" for="customer-note">
                    <span>Order notes <small>(optional)</small></span>
                    <textarea id="customer-note" name="customer_note" rows="3" maxlength="2000" placeholder="Colours, deadline, artwork notes, or anything we should know">{{ old('customer_note') }}</textarea>
                </label>

                <div class="sf-customizer-submit">
                    <p role="status" aria-live="polite" data-customizer-status>{{ $canPurchase ? 'Your current combination is ready to add.' : 'This product currently requires an enquiry.' }}</p>
                    <button class="sf-button sf-add-button" type="submit" @disabled(!$canPurchase) data-add-button>
                        <span data-add-label>{{ $canPurchase ? 'Add to bag' : 'Enquire for availability' }}</span>
                        <x-storefront.icon name="arrow" />
                    </button>
                </div>
            </form>

            @if($product['description'])
                <details class="sf-product-description-panel"><summary>Product details</summary><div>{!! nl2br(e($product['description'])) !!}</div></details>
            @endif
        </div>
    </section>

    <script type="application/json" id="product-customizer-data">{!! json_encode([
        'skus' => $customization['skus'] ?? [],
        'compatibility' => data_get($customization, 'validation.print_method_compatibility', []),
        'requiresPrint' => $requiresPrint,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</x-layouts.storefront>
