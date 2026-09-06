<x-layouts.storefront
    title="Mockup generator"
    description="Create a protected, watermarked T-shirt preview before adding your customized item to your bag."
    :site="$site"
    :navigation-categories="$navigationCategories"
    :cart-count="$cartCount"
    robots="noindex, nofollow"
    page="mockup"
>
    <section class="sf-studio" data-mockup-studio data-api-base="{{ url('/api') }}">
        <header class="sf-studio-intro"><div><p class="sf-eyebrow">Protected preview studio</p><h1>Place your artwork.<br><em>Keep the source private.</em></h1></div><p>Upload a transparent PNG, position it on a T-shirt, then generate a watermarked preview. Your clean artwork stays private for our production team.</p></header>
        @if(count($products) === 0)
            <div class="sf-flow-shell"><x-storefront.state-panel title="No customizable products available" message="Please check again when a custom-ready product has been published." action-label="Browse collections" :action-url="route('storefront.categories.index')" /></div>
        @else
            <div class="sf-studio-grid">
                <section class="sf-preview-panel" aria-labelledby="preview-heading">
                    <div class="sf-panel-heading"><div><p class="sf-eyebrow">Live placement</p><h2 id="preview-heading">Your protected preview</h2></div><span data-preview-badge>Editing</span></div>
                    <div class="sf-canvas-frame"><canvas data-mockup-canvas width="900" height="900" aria-label="T-shirt artwork placement preview. Use the position controls to make precise changes."></canvas><img data-generated-preview alt="Generated watermarked T-shirt mockup" hidden></div>
                    <div class="sf-canvas-caption"><span>Watermark shown in every preview</span><span>Production checks final placement</span></div>
                </section>
                <form class="sf-control-panel" data-studio-form>
                    <div class="sf-control-heading"><p class="sf-eyebrow">Mockup settings</p><h2>Build your preview</h2><p>Changes appear instantly. Generate only when the placement looks right.</p></div>
                    <div class="sf-studio-field"><label for="mockup-product"><span>01</span>Product</label><select id="mockup-product" name="product" data-product-select>@foreach($products as $product)<option value="{{ $product['slug'] }}" @selected(request('product') === $product['slug'])>{{ $product['name'] }}</option>@endforeach</select></div>
                    <fieldset class="sf-studio-field" data-color-fieldset><legend><span>02</span>T-shirt colour</legend><div class="sf-studio-choices" data-color-options></div></fieldset>
                    <div class="sf-studio-field"><label for="mockup-size"><span>03</span>Size</label><select id="mockup-size" name="size" data-size-select required></select></div>
                    <div class="sf-studio-field"><label for="mockup-artwork"><span>04</span>Artwork</label><label class="sf-upload-zone" for="mockup-artwork"><b aria-hidden="true">↑</b><span><strong data-file-name>Choose a transparent PNG</strong><small>Maximum 5 MB · at least 400 × 400 px</small></span></label><input class="sf-sr-only" id="mockup-artwork" name="artwork" type="file" accept="image/png" data-file-input required></div>
                    <fieldset class="sf-studio-field"><legend><span>05</span>Print position</legend><div class="sf-position-options" data-position-options></div></fieldset>
                    <div class="sf-studio-field"><div class="sf-range-heading"><label for="mockup-scale">Artwork size</label><output for="mockup-scale" data-scale-output>100%</output></div><input id="mockup-scale" type="range" min="60" max="120" value="100" step="2" data-scale-input><div class="sf-position-row"><strong>Position</strong><div class="sf-nudge-controls" aria-label="Move artwork"><button type="button" data-nudge="up" aria-label="Move artwork up">↑</button><button type="button" data-nudge="left" aria-label="Move artwork left">←</button><button type="button" data-nudge="down" aria-label="Move artwork down">↓</button><button type="button" data-nudge="right" aria-label="Move artwork right">→</button></div><button class="sf-link-button" type="button" data-reset>Reset</button><button class="sf-link-button" type="button" data-remove>Remove</button></div></div>
                    <div class="sf-notice sf-notice-warning hidden" data-form-error role="alert"></div>
                    <p class="sf-live-status" data-live-status role="status" aria-live="polite">Choose your options and upload artwork.</p>
                    <button class="sf-button sf-generate-button" type="submit" data-generate-button><span data-generate-label>Generate protected preview</span><x-storefront.icon name="arrow" /></button>
                    <div class="sf-generated-actions" data-generated-actions hidden><p><strong>Protected preview ready.</strong> The watermarked PNG and your original artwork will be saved with the order.</p><button class="sf-button sf-button-outline" type="button" data-add-cart>Add customized item to bag</button></div>
                    <p class="sf-privacy-note">Clean artwork is never shown publicly and remains available to the dashboard team.</p>
                </form>
            </div>
        @endif
        <script id="mockup-products-data" type="application/json">{!! json_encode($products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    </section>
</x-layouts.storefront>
