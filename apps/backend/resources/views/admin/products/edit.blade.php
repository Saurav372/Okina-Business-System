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
                            <x-tabs.trigger value="variants">Variants</x-tabs.trigger>
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
