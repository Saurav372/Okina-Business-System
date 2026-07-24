<x-layouts.admin title="Edit Product: {{ $product->name }}">
    <x-slot:header>
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold border border-neutral-300 rounded-xl text-neutral-700 bg-white hover:bg-neutral-50 transition-colors focus-visible:outline-none">
            Back to Products
        </a>
    </x-slot:header>

    @if(session('success'))
        <div class="mb-4">
            <x-alert type="success" dismissible="true">
                {{ session('success') }}
            </x-alert>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4">
            <x-alert type="danger" dismissible="true">
                Please correct the errors in the form.
            </x-alert>
        </div>
    @endif

    {{-- Main wrapper scoping both main form actions and Alpine states for variant/SKU CRUD --}}
    <div x-data="{ 
        activeVariant: { id: '', name: '', code: '', display_type: 'select', values_csv: '', is_required: true, sort_order: 10 },
        activeSku: { id: '', sku_code: '', barcode: '', price_minor: 0, compare_at_price_minor: '', status: 'active', direct_checkout_enabled: true, quote_required: false, track_stock: true, stock_quantity: 0, low_stock_threshold: '', allow_backorder: false, weight_grams: '', length_mm: '', width_mm: '', height_mm: '', sort_order: 0 }
    }">
        <form method="POST" action="{{ route('admin.products.update', $product) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Main Panel (Colspan 2) -->
                <div class="lg:col-span-2">
                    <x-tabs defaultTab="{{ request('tab', 'general') }}">
                        <x-tabs.list class="mb-6">
                            <x-tabs.trigger value="general">General</x-tabs.trigger>
                            <x-tabs.trigger value="variants">Variants &amp; SKUs</x-tabs.trigger>
                            <x-tabs.trigger value="media">Media</x-tabs.trigger>
                            <x-tabs.trigger value="seo">SEO &amp; Social</x-tabs.trigger>
                        </x-tabs.list>

                        <!-- General & Pricing Tab Content -->
                        <x-tabs.content value="general" class="space-y-6">
                            <!-- General Information Card -->
                            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                                <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">General Information</h3>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <x-form.input 
                                        id="name" 
                                        name="name" 
                                        label="Product Name" 
                                        value="{{ old('name', $product->name) }}" 
                                        required="true"
                                        error="{{ $errors->first('name') }}"
                                    />

                                    <x-form.input 
                                        id="slug" 
                                        name="slug" 
                                        label="Product Slug" 
                                        value="{{ old('slug', $product->slug) }}" 
                                        required="true"
                                        error="{{ $errors->first('slug') }}"
                                        hint="Normalized to lowercase, alphanumeric, and hyphens automatically."
                                    />
                                </div>

                                <div class="space-y-4">
                                    <x-form.wrapper id="short_description" label="Short Description" :error="$errors->first('short_description')">
                                        <textarea 
                                            id="short_description" 
                                            name="short_description" 
                                            rows="2" 
                                            class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                        >{{ old('short_description', $product->short_description) }}</textarea>
                                    </x-form.wrapper>

                                    <x-form.wrapper id="description" label="Description" :error="$errors->first('description')">
                                        <textarea 
                                            id="description" 
                                            name="description" 
                                            rows="5" 
                                            class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                        >{{ old('description', $product->description) }}</textarea>
                                    </x-form.wrapper>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                                    @php
                                        $typeOptions = [
                                            ['value' => \App\Models\Product::TYPE_SIMPLE, 'label' => 'Simple'],
                                            ['value' => \App\Models\Product::TYPE_VARIABLE, 'label' => 'Variable'],
                                            ['value' => \App\Models\Product::TYPE_BUNDLE, 'label' => 'Bundle']
                                        ];
                                        $customOptions = [
                                            ['value' => \App\Models\Product::CUSTOMIZATION_NONE, 'label' => 'None'],
                                            ['value' => \App\Models\Product::CUSTOMIZATION_OPTIONAL, 'label' => 'Optional'],
                                            ['value' => \App\Models\Product::CUSTOMIZATION_REQUIRED, 'label' => 'Required']
                                        ];
                                        $fulfillmentOptions = [
                                            ['value' => \App\Models\Product::FULFILLMENT_STOCKED, 'label' => 'Stocked'],
                                            ['value' => \App\Models\Product::FULFILLMENT_MADE_TO_ORDER, 'label' => 'Made to Order']
                                        ];
                                    @endphp

                                    <x-form.select 
                                        id="product_type" 
                                        name="product_type" 
                                        label="Product Type" 
                                        value="{{ old('product_type', $product->product_type) }}" 
                                        :options="$typeOptions"
                                        error="{{ $errors->first('product_type') }}"
                                    />

                                    <x-form.select 
                                        id="customization_mode" 
                                        name="customization_mode" 
                                        label="Customization" 
                                        value="{{ old('customization_mode', $product->customization_mode) }}" 
                                        :options="$customOptions"
                                        error="{{ $errors->first('customization_mode') }}"
                                    />

                                    <x-form.select 
                                        id="fulfillment_type" 
                                        name="fulfillment_type" 
                                        label="Fulfillment" 
                                        value="{{ old('fulfillment_type', $product->fulfillment_type) }}" 
                                        :options="$fulfillmentOptions"
                                        error="{{ $errors->first('fulfillment_type') }}"
                                    />
                                </div>
                            </div>

                            <!-- Pricing & Ordering Settings -->
                            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                                <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">Pricing & Ordering</h3>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <x-form.input 
                                        id="base_price_minor" 
                                        name="base_price_minor" 
                                        type="number"
                                        label="Base Price (INR minor units)" 
                                        value="{{ old('base_price_minor', $product->base_price_minor) }}" 
                                        required="true"
                                        error="{{ $errors->first('base_price_minor') }}"
                                        hint="Stored as integer minor units (e.g. 1000 = 10.00 INR). No decimal parsing occurs."
                                    />

                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Currency</label>
                                        <input 
                                            type="text" 
                                            value="INR" 
                                            disabled
                                            class="block w-full rounded-xl border border-neutral-300 px-4 py-2 text-xs text-neutral-500 bg-neutral-50 cursor-not-allowed"
                                        />
                                        <input type="hidden" name="currency" value="INR" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                                    <x-form.input 
                                        id="min_order_quantity" 
                                        name="min_order_quantity" 
                                        type="number"
                                        label="Min Order Qty" 
                                        value="{{ old('min_order_quantity', $product->min_order_quantity) }}" 
                                        required="true"
                                        error="{{ $errors->first('min_order_quantity') }}"
                                    />

                                    <x-form.input 
                                        id="max_order_quantity" 
                                        name="max_order_quantity" 
                                        type="number"
                                        label="Max Order Qty" 
                                        value="{{ old('max_order_quantity', $product->max_order_quantity) }}" 
                                        error="{{ $errors->first('max_order_quantity') }}"
                                    />

                                    <x-form.input 
                                        id="bulk_threshold_quantity" 
                                        name="bulk_threshold_quantity" 
                                        type="number"
                                        label="Bulk Threshold Qty" 
                                        value="{{ old('bulk_threshold_quantity', $product->bulk_threshold_quantity) }}" 
                                        error="{{ $errors->first('bulk_threshold_quantity') }}"
                                    />
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                    <x-form.wrapper id="direct_checkout_enabled" label="Direct Checkout" :error="$errors->first('direct_checkout_enabled')">
                                        <div class="flex items-center gap-2 mt-1">
                                            <input 
                                                type="checkbox" 
                                                id="direct_checkout_enabled" 
                                                name="direct_checkout_enabled" 
                                                value="1" 
                                                @if(old('direct_checkout_enabled', $product->direct_checkout_enabled)) checked @endif
                                                class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer"
                                            />
                                            <label for="direct_checkout_enabled" class="text-xs text-neutral-600 select-none">Enable direct checkout (skip quote request)</label>
                                        </div>
                                    </x-form.wrapper>

                                    <x-form.wrapper id="quote_enabled" label="Quote Request" :error="$errors->first('quote_enabled')">
                                        <div class="flex items-center gap-2 mt-1">
                                            <input 
                                                type="checkbox" 
                                                id="quote_enabled" 
                                                name="quote_enabled" 
                                                value="1" 
                                                @if(old('quote_enabled', $product->quote_enabled)) checked @endif
                                                class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer"
                                            />
                                            <label for="quote_enabled" class="text-xs text-neutral-600 select-none">Enable quotes for this product</label>
                                        </div>
                                    </x-form.wrapper>
                                </div>
                            </div>

                            <!-- SEO Information -->
                            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                                <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">SEO Information</h3>

                                <x-form.input 
                                    id="seo_title" 
                                    name="seo_title" 
                                    label="SEO Title" 
                                    value="{{ old('seo_title', $product->seo_title) }}" 
                                    error="{{ $errors->first('seo_title') }}"
                                />

                                <x-form.wrapper id="seo_description" label="SEO Description" :error="$errors->first('seo_description')">
                                    <textarea 
                                        id="seo_description" 
                                        name="seo_description" 
                                        rows="3" 
                                        class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                    >{{ old('seo_description', $product->seo_description) }}</textarea>
                                </x-form.wrapper>
                            </div>
                        </x-tabs.content>

                        <!-- Variants Tab Content -->
                        <x-tabs.content value="variants" class="space-y-6">
                            <!-- Category Assignment -->
                            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                                <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">Category Assignment</h3>
                                <div class="max-w-md">
                                    @php
                                        $categoryOptions = $categories->map(fn($c) => ['value' => $c->id, 'label' => $c->name])->all();
                                    @endphp
                                    <x-form.select 
                                        id="primary_category_id" 
                                        name="primary_category_id" 
                                        label="Primary Category" 
                                        placeholder="Select Category"
                                        value="{{ old('primary_category_id', $product->primary_category_id) }}" 
                                        :options="$categoryOptions"
                                        error="{{ $errors->first('primary_category_id') }}"
                                        hint="Changing category will take effect when clicking Save Changes below."
                                    />
                                </div>
                            </div>

                            <!-- Configurable Variant Options -->
                            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                                <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                                    <h3 class="font-bold text-neutral-800 text-sm">Variant Options</h3>
                                    <button 
                                        type="button" 
                                        @click="$dispatch('open-overlay', 'add-variant-modal')"
                                        class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-bold bg-neutral-800 text-white rounded-xl hover:bg-neutral-900 transition-colors focus-visible:outline-none cursor-pointer"
                                    >
                                        Add Option
                                    </button>
                                </div>

                                @if($product->variants->isEmpty())
                                    <x-empty-state 
                                        title="No Variant Options" 
                                        description="Configurable option dimensions (e.g. Size, Color) define variations for storefront checkouts."
                                        size="sm"
                                    />
                                @else
                                    <div class="divide-y divide-neutral-100">
                                        @foreach($product->variants->sortBy('sort_order') as $v)
                                            <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-2">
                                                        <h4 class="font-bold text-neutral-800 text-xs">{{ $v->name }}</h4>
                                                        <span class="font-mono text-[9px] px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-600 uppercase">{{ $v->code }}</span>
                                                        @if($v->is_required)
                                                            <span class="text-[9px] font-semibold text-rose-600 bg-rose-50 border border-rose-100 px-1.5 py-0.5 rounded">Required</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-[10px] text-neutral-400">Display: <span class="font-semibold text-neutral-600">{{ ucfirst($v->display_type) }}</span> | Sort Order: <span class="font-mono text-neutral-600">{{ $v->sort_order }}</span></div>
                                                    
                                                    <!-- Badges showing variant options values -->
                                                    <div class="flex flex-wrap gap-1.5 pt-2">
                                                        @foreach($v->values as $val)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-neutral-50 border border-neutral-200 text-neutral-600">
                                                                {{ data_get($val, 'label', '') }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <!-- Variant options triggers -->
                                                <div class="flex items-center gap-2 shrink-0">
                                                    <button 
                                                        type="button"
                                                        @click="
                                                            activeVariant = { 
                                                                id: '{{ $v->id }}', 
                                                                name: '{{ $v->name }}', 
                                                                code: '{{ $v->code }}', 
                                                                display_type: '{{ $v->display_type }}', 
                                                                values_csv: '{{ implode(', ', array_column($v->values, 'label')) }}', 
                                                                is_required: {{ $v->is_required ? 'true' : 'false' }}, 
                                                                sort_order: {{ $v->sort_order }} 
                                                            }; 
                                                            $dispatch('open-overlay', 'edit-variant-modal')
                                                        "
                                                        class="px-2.5 py-1 border border-neutral-200 rounded-lg text-xs font-semibold text-neutral-600 hover:bg-neutral-50 transition-colors"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button 
                                                        type="button" 
                                                        @click="
                                                            if (confirm('Are you sure you want to delete this option dimension? This will also affect generated SKUs.')) {
                                                                $refs['deleteForm' + {{ $v->id }}].submit();
                                                            }
                                                        "
                                                        class="px-2.5 py-1 border border-rose-100 rounded-lg text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- ────────────────────────────────────────────
                                 Product SKUs
                            ──────────────────────────────────────────────── --}}
                            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                                <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                                    <h3 class="font-bold text-neutral-800 text-sm">SKU Matrix</h3>
                                    <button
                                        type="submit"
                                        form="generate-skus-form"
                                        class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] transition-colors focus-visible:outline-none cursor-pointer"
                                    >
                                        Generate SKUs
                                    </button>
                                </div>

                                @if($product->skus->isEmpty())
                                    <x-empty-state
                                        title="No SKUs Generated"
                                        description="Click &ldquo;Generate SKUs&rdquo; to auto-create combinations from your configured variant options."
                                        size="sm"
                                    />
                                @else
                                    <div class="overflow-x-auto -mx-1">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="border-b border-neutral-100">
                                                    <th class="text-left font-semibold text-neutral-500 py-2 px-2">Combination</th>
                                                    <th class="text-left font-semibold text-neutral-500 py-2 px-2">SKU Code</th>
                                                    <th class="text-right font-semibold text-neutral-500 py-2 px-2">Price</th>
                                                    <th class="text-center font-semibold text-neutral-500 py-2 px-2">Stock</th>
                                                    <th class="text-center font-semibold text-neutral-500 py-2 px-2">Status</th>
                                                    <th class="text-right font-semibold text-neutral-500 py-2 px-2">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-neutral-50">
                                                @foreach($product->skus as $sku)
                                                    @php
                                                        $skuStatusColors = [
                                                            'active'       => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                            'out_of_stock' => 'bg-amber-50 text-amber-700 border-amber-100',
                                                            'inactive'     => 'bg-neutral-100 text-neutral-500 border-neutral-200',
                                                        ];
                                                        $skuStatusColor = $skuStatusColors[$sku->status] ?? 'bg-neutral-100 text-neutral-500';
                                                        $priceFormatted = $sku->price_minor !== null
                                                            ? '₹' . number_format($sku->price_minor / 100, 2)
                                                            : '—';
                                                    @endphp
                                                    <tr class="hover:bg-neutral-50 transition-colors">
                                                        <td class="py-2.5 px-2">
                                                            @if($sku->name_suffix)
                                                                <span class="font-semibold text-neutral-800">{{ $sku->name_suffix }}</span>
                                                            @else
                                                                <span class="text-neutral-400 italic">Default</span>
                                                            @endif
                                                        </td>
                                                        <td class="py-2.5 px-2">
                                                            <span class="font-mono text-[10px] bg-neutral-100 px-1.5 py-0.5 rounded text-neutral-600">{{ $sku->sku_code }}</span>
                                                        </td>
                                                        <td class="py-2.5 px-2 text-right font-semibold text-neutral-700">{{ $priceFormatted }}</td>
                                                        <td class="py-2.5 px-2 text-center">
                                                            @if($sku->track_stock)
                                                                <span class="font-mono text-neutral-600">{{ $sku->stock_quantity }}</span>
                                                            @else
                                                                <span class="text-neutral-400 italic">Untracked</span>
                                                            @endif
                                                        </td>
                                                        <td class="py-2.5 px-2 text-center">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $skuStatusColor }}">
                                                                {{ ucwords(str_replace('_', ' ', $sku->status)) }}
                                                            </span>
                                                        </td>
                                                        <td class="py-2.5 px-2 text-right">
                                                            <div class="flex items-center justify-end gap-2">
                                                                <button
                                                                    type="button"
                                                                    @click="
                                                                        activeSku = {
                                                                            id: '{{ $sku->id }}',
                                                                            sku_code: '{{ $sku->sku_code }}',
                                                                            barcode: '{{ $sku->barcode ?? '' }}',
                                                                            price_minor: {{ $sku->price_minor ?? 0 }},
                                                                            compare_at_price_minor: '{{ $sku->compare_at_price_minor ?? '' }}',
                                                                            status: '{{ $sku->status }}',
                                                                            direct_checkout_enabled: {{ $sku->direct_checkout_enabled ? 'true' : 'false' }},
                                                                            quote_required: {{ $sku->quote_required ? 'true' : 'false' }},
                                                                            track_stock: {{ $sku->track_stock ? 'true' : 'false' }},
                                                                            stock_quantity: {{ $sku->stock_quantity }},
                                                                            low_stock_threshold: '{{ $sku->low_stock_threshold ?? '' }}',
                                                                            allow_backorder: {{ $sku->allow_backorder ? 'true' : 'false' }},
                                                                            weight_grams: '{{ $sku->weight_grams ?? '' }}',
                                                                            length_mm: '{{ $sku->length_mm ?? '' }}',
                                                                            width_mm: '{{ $sku->width_mm ?? '' }}',
                                                                            height_mm: '{{ $sku->height_mm ?? '' }}',
                                                                            sort_order: {{ $sku->sort_order }}
                                                                        };
                                                                        $dispatch('open-overlay', 'edit-sku-modal')
                                                                    "
                                                                    class="px-2.5 py-1 border border-neutral-200 rounded-lg text-xs font-semibold text-neutral-600 hover:bg-neutral-50 transition-colors cursor-pointer"
                                                                >
                                                                    Edit
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    @click="
                                                                        if (confirm('Delete this SKU? Inventory history will be retained.')) {
                                                                            $refs['deleteSkuForm{{ $sku->id }}'].submit();
                                                                        }
                                                                    "
                                                                    class="px-2.5 py-1 border border-rose-100 rounded-lg text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                                >
                                                                    Delete
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </x-tabs.content>

                        {{-- ─────────────── Media Tab Content ─────────────── --}}
                        <x-tabs.content value="media" class="space-y-6">

                            {{-- Upload errors (redirected back to ?tab=media) --}}
                            @if($errors->hasBag('default') && request('tab') === 'media')
                                <div class="mb-2">
                                    <x-alert type="danger" dismissible="true">
                                        Please correct the upload errors below.
                                    </x-alert>
                                </div>
                            @endif

                            {{-- Upload Card --}}
                            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                                <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">Upload Images</h3>

                                <div id="product-media-upload-form-container">
                                    <div class="space-y-4">
                                        {{-- Dropzone --}}
                                        <label
                                            for="product_images"
                                            id="media-dropzone"
                                            class="flex flex-col items-center justify-center gap-3 w-full min-h-[140px] rounded-2xl border-2 border-dashed border-neutral-300 bg-neutral-50 hover:bg-neutral-100 hover:border-[color:var(--color-cta)] cursor-pointer transition-colors group"
                                        >
                                            <svg class="w-8 h-8 text-neutral-400 group-hover:text-[color:var(--color-cta)] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                            </svg>
                                            <div class="text-center">
                                                <span class="block text-sm font-semibold text-neutral-700">Click to select images</span>
                                                <span class="block text-xs text-neutral-400 mt-0.5">JPG, PNG, GIF, WEBP &mdash; up to 10&thinsp;MB each, max 10 images</span>
                                            </div>
                                            <input
                                                id="product_images"
                                                name="images[]"
                                                type="file"
                                                accept="image/jpeg,image/png,image/gif,image/webp"
                                                multiple
                                                class="sr-only"
                                                form="product-media-upload-form"
                                            />
                                        </label>

                                        @error('images')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('images.*')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror

                                        {{-- Alt text (applies to entire batch) --}}
                                        <x-form.input
                                            id="media_alt_text"
                                            name="alt_text"
                                            label="Alt Text"
                                            placeholder="Describe the image(s)…"
                                            value="{{ old('alt_text') }}"
                                            hint="Applied to all images in this upload batch. Per-image editing is planned for a future release."
                                            error="{{ $errors->first('alt_text') }}"
                                            form="product-media-upload-form"
                                        />

                                        <div class="flex justify-end">
                                            <button
                                                type="submit"
                                                form="product-media-upload-form"
                                                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold bg-neutral-800 hover:bg-neutral-900 text-white rounded-xl transition-colors cursor-pointer"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                                </svg>
                                                Upload Images
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            {{-- Gallery Card --}}
                            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                                <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                                    <h3 class="font-bold text-neutral-800 text-sm">Product Gallery</h3>
                                    @if($product->media->isNotEmpty())
                                        <span class="text-xs text-neutral-400">{{ $product->media->count() }} {{ Str::plural('image', $product->media->count()) }} &mdash; drag to reorder</span>
                                    @endif
                                </div>

                                @if($product->media->isEmpty())
                                    <x-empty-state
                                        title="No images yet"
                                        description="Upload images above to build your product gallery."
                                        size="sm"
                                    />
                                @else
                                    <div
                                        id="product-media-gallery"
                                        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4"
                                    >
                                        @foreach($product->media as $media)
                                            @php
                                                $previewUrl = URL::temporarySignedRoute(
                                                    'files.preview',
                                                    now()->addMinutes(60),
                                                    ['file' => $media->file->public_id]
                                                );
                                            @endphp
                                            <div
                                                data-id="{{ $media->id }}"
                                                class="relative group rounded-xl overflow-hidden border border-neutral-200 bg-neutral-50 aspect-square"
                                            >
                                                {{-- Thumbnail --}}
                                                <img
                                                    src="{{ $previewUrl }}"
                                                    alt="{{ $media->alt_text ?? $media->file->original_filename }}"
                                                    title="{{ $media->alt_text ?? '' }}"
                                                    class="w-full h-full object-cover transition-opacity"
                                                    loading="lazy"
                                                />

                                                {{-- Drag handle overlay --}}
                                                <div class="drag-handle absolute inset-0 cursor-grab active:cursor-grabbing opacity-0 group-hover:opacity-100 transition-opacity flex items-start justify-start p-2">
                                                    <div class="bg-white/80 backdrop-blur-sm rounded-lg p-1.5 shadow-sm">
                                                        <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                                        </svg>
                                                    </div>
                                                </div>

                                                {{-- Cover badge --}}
                                                @if($media->isCover())
                                                    <div class="absolute top-2 right-2">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold bg-amber-400 text-amber-900 rounded-full shadow-sm">
                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                            Cover
                                                        </span>
                                                    </div>
                                                @endif

                                                {{-- Action bar (visible on hover) --}}
                                                <div class="absolute bottom-0 inset-x-0 flex items-center justify-between gap-1 p-2 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                                    @unless($media->isCover())
                                                        <div class="flex-1">
                                                            <button
                                                                type="submit"
                                                                form="set-cover-form-{{ $media->id }}"
                                                                title="Set as cover image"
                                                                class="w-full flex items-center justify-center gap-1 px-2 py-1 text-[10px] font-semibold bg-white/90 hover:bg-white rounded-lg transition-colors cursor-pointer"
                                                            >
                                                                <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                                Cover
                                                            </button>
                                                        </div>
                                                    @endunless

                                                    {{-- Delete --}}
                                                    <div class="flex-shrink-0">
                                                        <button
                                                            type="submit"
                                                            form="delete-media-form-{{ $media->id }}"
                                                            onclick="return confirm('Delete this image? This cannot be undone.')"
                                                            title="Delete image"
                                                            class="flex items-center justify-center w-7 h-7 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors cursor-pointer"
                                                        >
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- SortableJS --}}
                                    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
                                    <script>
                                        (function () {
                                            const gridEl = document.getElementById('product-media-gallery');
                                            if (!gridEl) return;

                                            Sortable.create(gridEl, {
                                                animation: 150,
                                                handle: '.drag-handle',
                                                ghostClass: 'opacity-40',
                                                onEnd: function () {
                                                    const ids = Array.from(
                                                        gridEl.querySelectorAll('[data-id]')
                                                    ).map(el => parseInt(el.dataset.id, 10));

                                                    fetch('{{ route('admin.products.media.reorder', $product) }}', {
                                                        method: 'POST',
                                                        headers: {
                                                            'Content-Type': 'application/json',
                                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                                        },
                                                        body: JSON.stringify({ ids }),
                                                    });
                                                },
                                            });
                                        })();
                                    </script>
                                @endif

                                {{-- Drag-and-drop & selection feedback script --}}
                                <script>
                                    (function () {
                                        const input = document.getElementById('product_images');
                                        const dropzone = document.getElementById('media-dropzone');
                                        if (!input || !dropzone) return;

                                        const textSpan = dropzone.querySelector('span.text-neutral-700');
                                        const hintSpan = dropzone.querySelector('span.text-neutral-400');
                                        const originalText = textSpan ? textSpan.textContent : 'Click to select images';

                                        function updateFeedback(files) {
                                            if (!textSpan) return;
                                            if (!files || files.length === 0) {
                                                textSpan.textContent = originalText;
                                                textSpan.style.color = '';
                                                return;
                                            }
                                            textSpan.style.color = 'var(--color-cta, #e11d48)';
                                            if (files.length === 1) {
                                                textSpan.textContent = `Selected: ${files[0].name}`;
                                            } else {
                                                textSpan.textContent = `Selected: ${files.length} images`;
                                            }
                                        }

                                        // Input change event
                                        input.addEventListener('change', function () {
                                            updateFeedback(this.files);
                                        });

                                        // Drag over/enter
                                        ['dragenter', 'dragover'].forEach(eventName => {
                                            dropzone.addEventListener(eventName, e => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                dropzone.classList.add('bg-neutral-100');
                                                dropzone.style.borderColor = 'var(--color-cta, #e11d48)';
                                            }, false);
                                        });

                                        // Drag leave/drop
                                        ['dragleave', 'drop'].forEach(eventName => {
                                            dropzone.addEventListener(eventName, e => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                dropzone.classList.remove('bg-neutral-100');
                                                dropzone.style.borderColor = '';
                                            }, false);
                                        });

                                        // Drop event
                                        dropzone.addEventListener('drop', e => {
                                            const dt = e.dataTransfer;
                                            const files = dt.files;
                                            
                                            if (files && files.length > 0) {
                                                input.files = files;
                                                updateFeedback(files);
                                            }
                                        }, false);
                                    })();
                                </script>
                            </div>

                        </x-tabs.content>

                        <!-- SEO & Social Tab Content -->
                        <x-tabs.content value="seo" class="space-y-6">
                            <form method="POST" action="{{ route('admin.products.seo.update', $product) }}" class="space-y-6" x-data="{
                                metaTitle: {{ json_encode(old('meta_title', $product->seo?->meta_title ?? '')) }},
                                metaDescription: {{ json_encode(old('meta_description', $product->seo?->meta_description ?? '')) }},
                                slug: {{ json_encode(old('slug', $product->slug)) }},
                                focusKeyword: {{ json_encode(old('focus_keyword', $product->seo?->focus_keyword ?? '')) }},
                                canonicalUrl: {{ json_encode(old('canonical_url', $product->seo?->canonical_url ?? '')) }},
                                robotsIndex: {{ ($product->seo?->robots_index ?? true) ? 'true' : 'false' }},
                                robotsFollow: {{ ($product->seo?->robots_follow ?? true) ? 'true' : 'false' }},
                                ogTitle: {{ json_encode(old('og_title', $product->seo?->og_title ?? '')) }},
                                ogDescription: {{ json_encode(old('og_description', $product->seo?->og_description ?? '')) }},
                                ogImageId: {{ json_encode(old('og_image_id', $product->seo?->og_image_id ?? '')) }},
                                twitterTitle: {{ json_encode(old('twitter_title', $product->seo?->twitter_title ?? '')) }},
                                twitterDescription: {{ json_encode(old('twitter_description', $product->seo?->twitter_description ?? '')) }},
                                twitterImageId: {{ json_encode(old('twitter_image_id', $product->seo?->twitter_image_id ?? '')) }},
                                copiedUrl: false,
                                get fallbackTitle() {
                                    return {{ json_encode($product->name) }};
                                },
                                get fallbackDescription() {
                                    return {{ json_encode($product->short_description ?? Str::limit(strip_tags($product->description ?? ''), 160)) }};
                                },
                                get effectiveTitle() {
                                    return this.metaTitle.trim() ? this.metaTitle : this.fallbackTitle;
                                },
                                get effectiveDescription() {
                                    return this.metaDescription.trim() ? this.metaDescription : (this.fallbackDescription || 'No meta description provided for this product.');
                                },
                                get baseUrl() {
                                    return {{ json_encode(config('app.url')) }};
                                },
                                get publicUrl() {
                                    return this.canonicalUrl.trim() ? this.canonicalUrl : (this.baseUrl + '/products/' + (this.slug || 'product-slug'));
                                },
                                get titleStatus() {
                                    const len = this.metaTitle.length;
                                    if (len === 0) return { badge: 'Default Fallback', color: 'bg-neutral-100 text-neutral-600 border-neutral-200' };
                                    if (len < 20) return { badge: 'Too Short', color: 'bg-amber-50 text-amber-700 border-amber-200' };
                                    if (len <= 39) return { badge: 'Good', color: 'bg-blue-50 text-blue-700 border-blue-200' };
                                    if (len <= 60) return { badge: 'Excellent', color: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
                                    return { badge: 'Too Long', color: 'bg-rose-50 text-rose-700 border-rose-200' };
                                },
                                get descStatus() {
                                    const len = this.metaDescription.length;
                                    if (len === 0) return { badge: 'Default Fallback', color: 'bg-neutral-100 text-neutral-600 border-neutral-200' };
                                    if (len < 80) return { badge: 'Too Short', color: 'bg-amber-50 text-amber-700 border-amber-200' };
                                    if (len <= 119) return { badge: 'Good', color: 'bg-blue-50 text-blue-700 border-blue-200' };
                                    if (len <= 160) return { badge: 'Excellent', color: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
                                    return { badge: 'Too Long', color: 'bg-rose-50 text-rose-700 border-rose-200' };
                                }
                            }">
                                @csrf
                                @method('PUT')

                                <!-- Section 0: 1-Click Copy Public URL Bar -->
                                <div class="bg-neutral-900 text-white border border-neutral-800 rounded-2xl p-5 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                    <div class="space-y-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Current Public Storefront URL</span>
                                        <div class="text-xs font-mono font-semibold text-emerald-400 break-all" x-text="publicUrl"></div>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="navigator.clipboard.writeText(publicUrl); copiedUrl = true; setTimeout(() => copiedUrl = false, 2000)"
                                        class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-neutral-800 hover:bg-neutral-700 text-white border border-neutral-700 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                                    >
                                        <template x-if="!copiedUrl">
                                            <span class="inline-flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                Copy URL
                                            </span>
                                        </template>
                                        <template x-if="copiedUrl">
                                            <span class="inline-flex items-center gap-1.5 text-emerald-400">
                                                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Copied!
                                            </span>
                                        </template>
                                    </button>
                                </div>

                                <!-- Section 6: Live Google SERP Snippet Preview (Yoast / RankMath Style) -->
                                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-3">
                                    <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                                        <h3 class="font-bold text-neutral-800 text-sm flex items-center gap-2">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                            Live Search Engine Snippet Preview
                                        </h3>
                                        <span class="text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-md bg-neutral-100 text-neutral-600 border border-neutral-200">Google SERP</span>
                                    </div>

                                    <!-- Google Search Snippet Card -->
                                    <div class="p-4 bg-neutral-50 border border-neutral-200 rounded-xl space-y-1 font-sans text-left">
                                        <!-- Header / Breadcrumb -->
                                        <div class="flex items-center gap-2 text-xs text-neutral-600">
                                            <div class="w-4 h-4 rounded-full bg-neutral-200 border border-neutral-300 flex items-center justify-center text-[9px] font-bold text-neutral-700">O</div>
                                            <span class="font-semibold text-neutral-800">{{ config('app.name', 'Okina Craft') }}</span>
                                            <span class="text-neutral-400">•</span>
                                            <span class="text-[11px] text-neutral-500 font-mono" x-text="publicUrl"></span>
                                        </div>

                                        <!-- Title (Google Blue #1a0dab) -->
                                        <div class="text-base font-medium text-[#1a0dab] hover:underline cursor-pointer leading-snug line-clamp-1" x-text="effectiveTitle"></div>

                                        <!-- Description Snippet (#4d5156) -->
                                        <div class="text-xs text-[#4d5156] leading-relaxed line-clamp-2" x-text="effectiveDescription"></div>
                                    </div>
                                </div>

                                <!-- Section 1 & 5: Search Engine Metadata & Slug -->
                                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-5">
                                    <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">Search Engine Setup</h3>

                                    <!-- Meta Title Input + Character Counter -->
                                    <div class="space-y-1.5">
                                        <div class="flex items-center justify-between">
                                            <label for="meta_title" class="text-xs font-semibold text-neutral-700">Meta Title</label>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md border" :class="titleStatus.color" x-text="titleStatus.badge"></span>
                                                <span class="text-[11px] font-mono text-neutral-500" x-text="metaTitle.length + ' / 60 chars • ' + (60 - metaTitle.length) + ' left'"></span>
                                            </div>
                                        </div>
                                        <input 
                                            type="text" 
                                            id="meta_title" 
                                            name="meta_title" 
                                            x-model="metaTitle"
                                            placeholder="{{ $product->name }}"
                                            class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                        />
                                        <p class="text-[10px] text-neutral-400">Leave empty to automatically use the product name.</p>
                                    </div>

                                    <!-- Meta Description Input + Character Counter -->
                                    <div class="space-y-1.5">
                                        <div class="flex items-center justify-between">
                                            <label for="meta_description" class="text-xs font-semibold text-neutral-700">Meta Description</label>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md border" :class="descStatus.color" x-text="descStatus.badge"></span>
                                                <span class="text-[11px] font-mono text-neutral-500" x-text="metaDescription.length + ' / 160 chars • ' + (160 - metaDescription.length) + ' left'"></span>
                                            </div>
                                        </div>
                                        <textarea 
                                            id="meta_description" 
                                            name="meta_description" 
                                            rows="3" 
                                            x-model="metaDescription"
                                            placeholder="{{ $product->short_description ?? 'Summary snippet of the product for search engine results.' }}"
                                            class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                        ></textarea>
                                        <p class="text-[10px] text-neutral-400">Recommended 120-160 characters. Summarize key selling points and custom options.</p>
                                    </div>

                                    <!-- Focus Keyword & Slug Row -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <x-form.wrapper id="focus_keyword" label="Focus Keyword">
                                            <input 
                                                type="text" 
                                                id="focus_keyword" 
                                                name="focus_keyword" 
                                                x-model="focusKeyword"
                                                placeholder="e.g. custom polo t-shirt"
                                                class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                            />
                                            <p class="text-[10px] text-neutral-400 mt-1">Used for internal editor guidance only, never rendered on storefront.</p>
                                        </x-form.wrapper>

                                        <x-form.wrapper id="seo_slug" label="URL Slug" required="true">
                                            <input 
                                                type="text" 
                                                id="seo_slug" 
                                                name="slug" 
                                                x-model="slug"
                                                required
                                                class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                            />
                                            <p class="text-[10px] text-emerald-600 font-semibold mt-1 inline-flex items-center gap-1">
                                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Normalized to lowercase, hyphenated URL slug automatically.
                                            </p>
                                        </x-form.wrapper>
                                    </div>

                                    <!-- Canonical URL Override -->
                                    <x-form.wrapper id="canonical_url" label="Canonical URL Override">
                                        <input 
                                            type="url" 
                                            id="canonical_url" 
                                            name="canonical_url" 
                                            x-model="canonicalUrl"
                                            placeholder="https://okinacraft.com/products/custom-polo"
                                            class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                        />
                                        <p class="text-[10px] text-neutral-400 mt-1">Leave empty to use default route canonical URL (<span class="font-mono" x-text="baseUrl + '/products/' + (slug || 'product-slug')"></span>).</p>
                                    </x-form.wrapper>

                                    <!-- Robots Directives (2 Independent Switches) -->
                                    <div class="pt-2 border-t border-neutral-100 space-y-3">
                                        <span class="text-xs font-semibold text-neutral-700 block">Robots Directives</span>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <label class="flex items-center gap-3 p-3 border border-neutral-200 rounded-xl hover:bg-neutral-50 cursor-pointer transition-colors">
                                                <input 
                                                    type="checkbox" 
                                                    name="robots_index" 
                                                    value="1" 
                                                    x-model="robotsIndex"
                                                    class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)]"
                                                />
                                                <div class="text-xs">
                                                    <span class="font-bold text-neutral-800 block">Allow Indexing (index)</span>
                                                    <span class="text-[10px] text-neutral-500">Permit search engines to index this product page.</span>
                                                </div>
                                            </label>

                                            <label class="flex items-center gap-3 p-3 border border-neutral-200 rounded-xl hover:bg-neutral-50 cursor-pointer transition-colors">
                                                <input 
                                                    type="checkbox" 
                                                    name="robots_follow" 
                                                    value="1" 
                                                    x-model="robotsFollow"
                                                    class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)]"
                                                />
                                                <div class="text-xs">
                                                    <span class="font-bold text-neutral-800 block">Allow Following Links (follow)</span>
                                                    <span class="text-[10px] text-neutral-500">Permit search engines to crawl links on this page.</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 2: Open Graph (Facebook / LinkedIn) -->
                                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                                    <div class="flex items-center gap-2 border-b border-neutral-100 pb-3">
                                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        <h3 class="font-bold text-neutral-800 text-sm">Open Graph Settings (Facebook &amp; LinkedIn)</h3>
                                    </div>

                                    <div class="space-y-4">
                                        <x-form.input 
                                            id="og_title" 
                                            name="og_title" 
                                            label="OG Title" 
                                            x-model="ogTitle"
                                            placeholder="Defaults to Meta Title"
                                        />

                                        <x-form.wrapper id="og_description" label="OG Description">
                                            <textarea 
                                                id="og_description" 
                                                name="og_description" 
                                                rows="2" 
                                                x-model="ogDescription"
                                                placeholder="Defaults to Meta Description"
                                                class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                            ></textarea>
                                        </x-form.wrapper>

                                        <x-form.wrapper id="og_image_id" label="OG Image Selection">
                                            <select 
                                                id="og_image_id" 
                                                name="og_image_id" 
                                                x-model="ogImageId"
                                                class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] bg-white"
                                            >
                                                <option value="">-- Fallback to Cover / Primary Product Image --</option>
                                                @foreach($product->media as $mediaItem)
                                                    @if($mediaItem->file)
                                                        <option value="{{ $mediaItem->file->id }}">
                                                            Image: {{ $mediaItem->file->original_filename }} ({{ $mediaItem->role }})
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </x-form.wrapper>
                                    </div>
                                </div>

                                <!-- Section 3: Twitter Card -->
                                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                                    <div class="flex items-center gap-2 border-b border-neutral-100 pb-3">
                                        <svg class="w-4 h-4 text-sky-400" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.936 9.936 0 0024 4.59z"/></svg>
                                        <h3 class="font-bold text-neutral-800 text-sm">Twitter Card Settings</h3>
                                    </div>

                                    <div class="space-y-4">
                                        <x-form.input 
                                            id="twitter_title" 
                                            name="twitter_title" 
                                            label="Twitter Title" 
                                            x-model="twitterTitle"
                                            placeholder="Defaults to OG / Meta Title"
                                        />

                                        <x-form.wrapper id="twitter_description" label="Twitter Description">
                                            <textarea 
                                                id="twitter_description" 
                                                name="twitter_description" 
                                                rows="2" 
                                                x-model="twitterDescription"
                                                placeholder="Defaults to OG / Meta Description"
                                                class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                            ></textarea>
                                        </x-form.wrapper>

                                        <x-form.wrapper id="twitter_image_id" label="Twitter Image Selection">
                                            <select 
                                                id="twitter_image_id" 
                                                name="twitter_image_id" 
                                                x-model="twitterImageId"
                                                class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] bg-white"
                                            >
                                                <option value="">-- Fallback to OG Image / Product Image --</option>
                                                @foreach($product->media as $mediaItem)
                                                    @if($mediaItem->file)
                                                        <option value="{{ $mediaItem->file->id }}">
                                                            Image: {{ $mediaItem->file->original_filename }} ({{ $mediaItem->role }})
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </x-form.wrapper>
                                    </div>
                                </div>

                                <!-- Section 4: Structured Data (JSON-LD Read-only Pretty Preview) -->
                                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-3">
                                    <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                                        <h3 class="font-bold text-neutral-800 text-sm flex items-center gap-2">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                            Structured Data (Schema.org Product JSON-LD)
                                        </h3>
                                        <span class="text-[10px] font-mono text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200 font-bold">Read-only Output</span>
                                    </div>
                                    <p class="text-[11px] text-neutral-500">Automatically generated for search engines and rich snippet features using your product attributes and SEO values.</p>

                                    <div class="relative bg-neutral-900 border border-neutral-800 rounded-xl p-4 overflow-x-auto">
                                        <pre class="font-mono text-xs text-emerald-400 leading-relaxed whitespace-pre">{{ $product->seoPresenter()->jsonLdFormatted() }}</pre>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex justify-end gap-3 pt-4">
                                    <button 
                                        type="submit" 
                                        class="px-6 py-2.5 text-xs font-bold bg-neutral-800 hover:bg-neutral-900 text-white rounded-xl shadow-xs transition-colors cursor-pointer inline-flex items-center gap-2"
                                    >
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Save SEO &amp; Social Settings
                                    </button>
                                </div>
                            </form>
                        </x-tabs.content>

                    </x-tabs>
                </div>

                <!-- Right Sidebar Panel -->
                <div class="space-y-6">
                    <!-- Status & Visibility Settings -->
                    <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                        <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">Status & Visibility</h3>

                        @php
                            $statusOptions = [
                                ['value' => \App\Models\Product::STATUS_DRAFT, 'label' => 'Draft'],
                                ['value' => \App\Models\Product::STATUS_ACTIVE, 'label' => 'Active'],
                                ['value' => \App\Models\Product::STATUS_OUT_OF_STOCK, 'label' => 'Out of Stock'],
                                ['value' => \App\Models\Product::STATUS_BULK_ONLY, 'label' => 'Bulk Only'],
                                ['value' => \App\Models\Product::STATUS_DISCONTINUED, 'label' => 'Discontinued']
                            ];
                            $visibilityOptions = [
                                ['value' => \App\Models\Product::VISIBILITY_PUBLIC, 'label' => 'Public'],
                                ['value' => \App\Models\Product::VISIBILITY_PRIVATE, 'label' => 'Private']
                            ];
                        @endphp

                        <x-form.select 
                            id="status" 
                            name="status" 
                            label="Product Status" 
                            value="{{ old('status', $product->status) }}" 
                            :options="$statusOptions"
                            error="{{ $errors->first('status') }}"
                        />

                        <!-- Storefront Status Badge Warning -->
                        @if($product->status === \App\Models\Product::STATUS_DRAFT)
                            <div class="mt-2 text-xs text-neutral-600 bg-neutral-50 border border-neutral-200 rounded-lg p-2.5 flex items-center gap-1.5 font-semibold">
                                <x-icons.lucide name="lucide-eye-off" class="w-4 h-4 text-neutral-400 shrink-0" />
                                <span>Not visible on storefront</span>
                            </div>
                        @endif

                        <x-form.select 
                            id="visibility" 
                            name="visibility" 
                            label="Visibility" 
                            value="{{ old('visibility', $product->visibility) }}" 
                            :options="$visibilityOptions"
                            error="{{ $errors->first('visibility') }}"
                        />

                        <!-- Storefront Visibility Banner -->
                        @if($product->visibility === \App\Models\Product::VISIBILITY_PRIVATE)
                            <div class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg p-2.5 flex items-center gap-1.5 font-semibold">
                                <x-icons.lucide name="lucide-eye-off" class="w-4 h-4 text-amber-500 shrink-0" />
                                <span>Hidden from customers</span>
                            </div>
                        @endif

                        <x-form.input 
                            id="sort_order" 
                            name="sort_order" 
                            type="number"
                            label="Sort Order" 
                            value="{{ old('sort_order', $product->sort_order) }}" 
                            required="true"
                            error="{{ $errors->first('sort_order') }}"
                        />

                        <x-form.input 
                            id="published_at" 
                            name="published_at" 
                            type="date"
                            label="Published Date" 
                            value="{{ old('published_at', $product->published_at?->format('Y-m-d')) }}" 
                            error="{{ $errors->first('published_at') }}"
                        />
                    </div>

                    <!-- Read-Only Metadata Card -->
                    <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
                        <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3 mb-3">Metadata</h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-neutral-50">
                                <span class="text-neutral-400">Product ID</span>
                                <span class="font-mono font-bold text-neutral-700">{{ $product->id }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-neutral-50">
                                <span class="text-neutral-400">Created At</span>
                                <span class="font-mono text-neutral-600">{{ $product->created_at ? $product->created_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-neutral-400">Updated At</span>
                                <span class="font-mono text-neutral-600">{{ $product->updated_at ? $product->updated_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Card -->
                    <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-3">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold bg-[color:var(--color-brand-600)] hover:bg-[color:var(--color-brand-700)] text-white rounded-xl transition-colors focus-visible:outline-none cursor-pointer">
                            Save Changes
                        </button>
                        <a href="{{ route('admin.products.index') }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-semibold border border-neutral-300 hover:bg-neutral-50 text-neutral-700 rounded-xl transition-colors focus-visible:outline-none text-center">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <!-- Hidden delete forms for variant option dimensions -->
        @foreach($product->variants as $v)
            <form
                x-ref="deleteForm{{ $v->id }}"
                action="{{ route('admin.products.variants.destroy', [$product, $v]) }}"
                method="POST"
                class="hidden"
            >
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        <!-- Hidden delete forms for SKU records -->
        @foreach($product->skus as $sku)
            <form
                x-ref="deleteSkuForm{{ $sku->id }}"
                action="{{ route('admin.products.skus.destroy', [$product, $sku]) }}"
                method="POST"
                class="hidden"
            >
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        <!-- Form for SKU matrix generation -->
        <form
            id="generate-skus-form"
            action="{{ route('admin.products.skus.generate', $product) }}"
            method="POST"
            class="hidden"
        >
            @csrf
        </form>

        <!-- Form for Product Media Upload (placed outside main product form) -->
        <form
            id="product-media-upload-form"
            action="{{ route('admin.products.media.store', $product) }}"
            method="POST"
            enctype="multipart/form-data"
            class="hidden"
        >
            @csrf
        </form>

        <!-- Forms for Product Media Actions (placed outside main product form) -->
        @foreach($product->media as $media)
            <form
                id="set-cover-form-{{ $media->id }}"
                action="{{ route('admin.products.media.cover', [$product, $media]) }}"
                method="POST"
                class="hidden"
            >
                @csrf
            </form>
            <form
                id="delete-media-form-{{ $media->id }}"
                action="{{ route('admin.products.media.destroy', [$product, $media]) }}"
                method="POST"
                class="hidden"
            >
                @csrf
                @method('DELETE')
            </form>
        @endforeach


    <!-- Modals for adding/updating options (placed outside main product form) -->
    <!-- Add Variant Option Modal -->
    <x-modal id="add-variant-modal" title="Add Variant Option" size="md">
        <form method="POST" action="{{ route('admin.products.variants.store', $product) }}" class="space-y-4">
            @csrf
            <x-form.input 
                id="variant_name" 
                name="name" 
                label="Option Name" 
                placeholder="e.g. Size, Color, Capacity" 
                required="true"
            />
            <x-form.input 
                id="variant_code" 
                name="code" 
                label="Option Code" 
                placeholder="e.g. size, color, capacity" 
                required="true"
                hint="Used internally. Normalized automatically to slug format."
            />
            <x-form.select 
                id="variant_display_type" 
                name="display_type" 
                label="Display Type" 
                value="select"
                :options="[
                    ['value' => 'select', 'label' => 'Dropdown Select'],
                    ['value' => 'swatch', 'label' => 'Color Swatch'],
                    ['value' => 'button', 'label' => 'Label Buttons'],
                    ['value' => 'radio', 'label' => 'Radio Selection']
                ]"
            />
            <x-form.wrapper id="variant_values_csv" label="Option Values" required="true">
                <textarea 
                    id="variant_values_csv" 
                    name="values_csv" 
                    rows="3" 
                    placeholder="e.g. S, M, L, XL" 
                    class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                    required
                ></textarea>
                <p class="text-[10px] text-neutral-400 mt-1">Separate values with commas. Case-insensitive duplicates will be removed, preserving original ordering.</p>
            </x-form.wrapper>

            <div class="flex items-center gap-2 pt-1">
                <input 
                    type="checkbox" 
                    id="variant_is_required" 
                    name="is_required" 
                    value="1" 
                    checked
                    class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer"
                />
                <label for="variant_is_required" class="text-xs text-neutral-600 select-none font-semibold">Customers must choose an option before checkout</label>
            </div>

            <x-form.input 
                id="variant_sort_order" 
                name="sort_order" 
                type="number" 
                label="Sort Order" 
                value="10" 
                required="true"
            />

            <div class="flex justify-end gap-3 pt-4 border-t border-neutral-100">
                <button 
                    type="button" 
                    @click="$dispatch('close-overlay', 'add-variant-modal')"
                    class="px-4 py-2 text-xs font-semibold border border-neutral-300 hover:bg-neutral-50 rounded-xl transition-colors cursor-pointer"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-4 py-2 text-xs font-bold bg-neutral-800 hover:bg-neutral-900 text-white rounded-xl transition-colors cursor-pointer"
                >
                    Add Option
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Edit Variant Option Modal -->
    <x-modal id="edit-variant-modal" title="Edit Variant Option" size="md">
        <form 
            method="POST" 
            :action="'/admin/products/' + {{ $product->id }} + '/variants/' + activeVariant.id" 
            class="space-y-4"
        >
            @csrf
            @method('PUT')
            <x-form.input 
                id="edit_variant_name" 
                name="name" 
                label="Option Name" 
                x-model="activeVariant.name"
                required="true"
            />
            <x-form.input 
                id="edit_variant_code" 
                name="code" 
                label="Option Code" 
                x-model="activeVariant.code"
                required="true"
                hint="Used internally. Normalized automatically to slug format."
            />
            <x-form.select 
                id="edit_variant_display_type" 
                name="display_type" 
                label="Display Type" 
                x-model="activeVariant.display_type"
                :options="[
                    ['value' => 'select', 'label' => 'Dropdown Select'],
                    ['value' => 'swatch', 'label' => 'Color Swatch'],
                    ['value' => 'button', 'label' => 'Label Buttons'],
                    ['value' => 'radio', 'label' => 'Radio Selection']
                ]"
            />
            <x-form.wrapper id="edit_variant_values_csv" label="Option Values" required="true">
                <textarea 
                    id="edit_variant_values_csv" 
                    name="values_csv" 
                    rows="3" 
                    x-model="activeVariant.values_csv"
                    class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                    required
                ></textarea>
                <p class="text-[10px] text-neutral-400 mt-1">Separate values with commas. Case-insensitive duplicates will be removed, preserving original ordering.</p>
            </x-form.wrapper>

            <div class="flex items-center gap-2 pt-1">
                <input 
                    type="checkbox" 
                    id="edit_variant_is_required" 
                    name="is_required" 
                    value="1" 
                    x-model="activeVariant.is_required"
                    class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer"
                />
                <label for="edit_variant_is_required" class="text-xs text-neutral-600 select-none font-semibold">Customers must choose an option before checkout</label>
            </div>

            <x-form.input 
                id="edit_variant_sort_order" 
                name="sort_order" 
                type="number" 
                label="Sort Order" 
                x-model="activeVariant.sort_order"
                required="true"
            />

            <div class="flex justify-end gap-3 pt-4 border-t border-neutral-100">
                <button 
                    type="button" 
                    @click="$dispatch('close-overlay', 'edit-variant-modal')"
                    class="px-4 py-2 text-xs font-semibold border border-neutral-300 hover:bg-neutral-50 rounded-xl transition-colors cursor-pointer"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-4 py-2 text-xs font-bold bg-neutral-800 hover:bg-neutral-900 text-white rounded-xl transition-colors cursor-pointer"
                >
                    Save Changes
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Edit SKU Modal -->
    <x-modal id="edit-sku-modal" title="Edit SKU Details" size="lg">
        <form 
            method="POST" 
            :action="'/admin/products/' + {{ $product->id }} + '/skus/' + activeSku.id" 
            class="space-y-4"
        >
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-form.input 
                    id="edit_sku_code" 
                    name="sku_code" 
                    label="SKU Code" 
                    x-model="activeSku.sku_code"
                    required="true"
                />
                <x-form.input 
                    id="edit_sku_barcode" 
                    name="barcode" 
                    label="Barcode (UPC/EAN)" 
                    x-model="activeSku.barcode"
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-form.input 
                    id="edit_sku_price_minor" 
                    name="price_minor" 
                    type="number"
                    label="Price (in Paise)" 
                    x-model="activeSku.price_minor"
                    required="true"
                    hint="Enter amount in minor units (e.g., 149900 for ₹1,499.00)."
                />
                <x-form.input 
                    id="edit_sku_compare_at_price_minor" 
                    name="compare_at_price_minor" 
                    type="number"
                    label="Compare At Price (in Paise)" 
                    x-model="activeSku.compare_at_price_minor"
                    hint="Original/MSRP price for strike-through display."
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-form.select 
                    id="edit_sku_status" 
                    name="status" 
                    label="Status" 
                    x-model="activeSku.status"
                    :options="[
                        ['value' => 'active', 'label' => 'Active'],
                        ['value' => 'out_of_stock', 'label' => 'Out of Stock'],
                        ['value' => 'inactive', 'label' => 'Inactive']
                    ]"
                />
                <x-form.input 
                    id="edit_sku_sort_order" 
                    name="sort_order" 
                    type="number"
                    label="Sort Order" 
                    x-model="activeSku.sort_order"
                    required="true"
                />
            </div>

            <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-100 space-y-4">
                <h4 class="font-bold text-neutral-800 text-[10px] uppercase tracking-wider">Inventory & Checkout</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="flex items-center gap-2">
                        <input 
                            type="checkbox" 
                            id="edit_sku_direct_checkout_enabled" 
                            name="direct_checkout_enabled" 
                            value="1" 
                            x-model="activeSku.direct_checkout_enabled"
                            class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer"
                        />
                        <label for="edit_sku_direct_checkout_enabled" class="text-xs text-neutral-600 font-semibold select-none">Direct Checkout</label>
                    </div>

                    <div class="flex items-center gap-2">
                        <input 
                            type="checkbox" 
                            id="edit_sku_quote_required" 
                            name="quote_required" 
                            value="1" 
                            x-model="activeSku.quote_required"
                            class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer"
                        />
                        <label for="edit_sku_quote_required" class="text-xs text-neutral-600 font-semibold select-none">Requires Quote</label>
                    </div>

                    <div class="flex items-center gap-2">
                        <input 
                            type="checkbox" 
                            id="edit_sku_track_stock" 
                            name="track_stock" 
                            value="1" 
                            x-model="activeSku.track_stock"
                            class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer"
                        />
                        <label for="edit_sku_track_stock" class="text-xs text-neutral-600 font-semibold select-none">Track Stock</label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 border-t border-neutral-100" x-show="activeSku.track_stock">
                    <x-form.input 
                        id="edit_sku_stock_quantity" 
                        name="stock_quantity" 
                        type="number"
                        label="Stock Quantity" 
                        x-model="activeSku.stock_quantity"
                    />
                    <x-form.input 
                        id="edit_sku_low_stock_threshold" 
                        name="low_stock_threshold" 
                        type="number"
                        label="Low Stock Threshold" 
                        x-model="activeSku.low_stock_threshold"
                    />
                    <div class="flex items-center gap-2 pt-5">
                        <input 
                            type="checkbox" 
                            id="edit_sku_allow_backorder" 
                            name="allow_backorder" 
                            value="1" 
                            x-model="activeSku.allow_backorder"
                            class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer"
                        />
                        <label for="edit_sku_allow_backorder" class="text-xs text-neutral-600 font-semibold select-none">Allow Backorder</label>
                    </div>
                </div>
            </div>

            <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-100 space-y-4">
                <h4 class="font-bold text-neutral-800 text-[10px] uppercase tracking-wider">Dimensions & Weight</h4>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <x-form.input 
                        id="edit_sku_weight_grams" 
                        name="weight_grams" 
                        type="number"
                        label="Weight (g)" 
                        x-model="activeSku.weight_grams"
                    />
                    <x-form.input 
                        id="edit_sku_length_mm" 
                        name="length_mm" 
                        type="number"
                        label="Length (mm)" 
                        x-model="activeSku.length_mm"
                    />
                    <x-form.input 
                        id="edit_sku_width_mm" 
                        name="width_mm" 
                        type="number"
                        label="Width (mm)" 
                        x-model="activeSku.width_mm"
                    />
                    <x-form.input 
                        id="edit_sku_height_mm" 
                        name="height_mm" 
                        type="number"
                        label="Height (mm)" 
                        x-model="activeSku.height_mm"
                    />
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-neutral-100">
                <button 
                    type="button" 
                    @click="$dispatch('close-overlay', 'edit-sku-modal')"
                    class="px-4 py-2 text-xs font-semibold border border-neutral-300 hover:bg-neutral-50 rounded-xl transition-colors cursor-pointer"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-4 py-2 text-xs font-bold bg-neutral-800 hover:bg-neutral-900 text-white rounded-xl transition-colors cursor-pointer"
                >
                    Save Changes
                </button>
            </div>
        </form>
    </x-modal>
</div>
</x-layouts.admin>
